<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_AI_Service
{
    public static function generate_block(array $params)
    {
        $options = PMFAI_Settings::get_options();
        $api_key = trim((string)($options['api_key'] ?? ''));
        if ($api_key === '') {
            return new WP_Error('missing_api_key', 'Chưa cấu hình API key trong Settings.', ['status' => 400]);
        }

        $type = sanitize_key($params['type'] ?? 'generate-from-description');
        $prompt = PMFAI_Prompt_Builder::build($type, [
            'industry' => sanitize_text_field($params['industry'] ?? 'doanh nghiệp dịch vụ'),
            'style' => sanitize_text_field($params['style'] ?? 'hiện đại, chuyên nghiệp'),
            'goal' => sanitize_text_field($params['goal'] ?? 'Tạo HTML Block copy vào Flatsome'),
            'content' => sanitize_textarea_field($params['content'] ?? ''),
        ]);

        $payload = [
            'model' => $options['api_model'] ?: 'gpt-4.1-mini',
            'temperature' => is_numeric($options['temperature']) ? (float)$options['temperature'] : 0.4,
            'messages' => [
                ['role' => 'system', 'content' => 'You generate clean WordPress Flatsome-compatible HTML blocks. Return only the requested pmedia-flatsome-block JSON markdown block.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $response = wp_remote_post($options['api_endpoint'], [
            'timeout' => 90,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if ($code < 200 || $code >= 300) {
            $message = $json['error']['message'] ?? ('AI API error HTTP ' . $code);
            return new WP_Error('api_error', $message, ['status' => 500]);
        }

        $content = $json['choices'][0]['message']['content'] ?? '';
        if (!$content) {
            return new WP_Error('empty_response', 'AI trả về rỗng hoặc không đúng định dạng.', ['status' => 500]);
        }

        $parsed = PMFAI_Code_Validator::parse_block($content);
        if (is_wp_error($parsed)) {
            return [
                'raw' => $content,
                'parse_error' => $parsed->get_error_message(),
                'prompt' => $prompt,
            ];
        }

        $parsed['raw'] = $content;
        $parsed['prompt'] = $prompt;
        $parsed['source'] = 'auto-mode';
        return $parsed;
    }
}
