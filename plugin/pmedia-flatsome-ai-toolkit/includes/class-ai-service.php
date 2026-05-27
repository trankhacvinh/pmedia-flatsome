<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_AI_Service
{
    public static function generate_block(array $params)
    {
        $started = microtime(true);
        $options = PMFAI_Settings::get_options();
        $api_key = trim((string)($options['api_key'] ?? ''));
        if ($api_key === '') {
            PMFAI_Usage_Logger::log([
                'action' => 'generate-block',
                'status' => 'error',
                'mode' => sanitize_key($params['costMode'] ?? 'balanced'),
                'type' => sanitize_key($params['type'] ?? 'generate-from-description'),
                'industry' => sanitize_text_field($params['industry'] ?? ''),
                'error_message' => 'Missing API key',
            ]);
            return new WP_Error('missing_api_key', 'Chưa cấu hình API key trong Settings.', ['status' => 400]);
        }

        $type = sanitize_key($params['type'] ?? 'generate-from-description');
        $cost_mode = sanitize_key($params['costMode'] ?? 'balanced');
        $mode_config = self::mode_config($cost_mode, $options);

        $content = sanitize_textarea_field($params['content'] ?? '');
        $content .= "\n\nCost/Quality mode: " . $mode_config['label'] . "\n" . $mode_config['instruction'];

        $prompt = PMFAI_Prompt_Builder::build($type, [
            'industry' => sanitize_text_field($params['industry'] ?? 'doanh nghiệp dịch vụ'),
            'style' => sanitize_text_field($params['style'] ?? 'hiện đại, chuyên nghiệp'),
            'goal' => sanitize_text_field($params['goal'] ?? 'Tạo HTML Block copy vào Flatsome'),
            'content' => $content,
        ]);

        $payload = [
            'model' => $mode_config['model'],
            'temperature' => $mode_config['temperature'],
            'messages' => [
                ['role' => 'system', 'content' => $mode_config['system']],
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $response = wp_remote_post($options['api_endpoint'], [
            'timeout' => $mode_config['timeout'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($payload),
        ]);

        $duration_ms = (int)round((microtime(true) - $started) * 1000);

        if (is_wp_error($response)) {
            PMFAI_Usage_Logger::log([
                'action' => 'generate-block',
                'status' => 'error',
                'mode' => $cost_mode,
                'model' => $mode_config['model'],
                'type' => $type,
                'industry' => sanitize_text_field($params['industry'] ?? ''),
                'duration_ms' => $duration_ms,
                'error_message' => $response->get_error_message(),
            ]);
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
        $usage = is_array($json['usage'] ?? null) ? $json['usage'] : [];

        if ($code < 200 || $code >= 300) {
            $message = $json['error']['message'] ?? ('AI API error HTTP ' . $code);
            PMFAI_Usage_Logger::log([
                'action' => 'generate-block',
                'status' => 'error',
                'mode' => $cost_mode,
                'model' => $mode_config['model'],
                'type' => $type,
                'industry' => sanitize_text_field($params['industry'] ?? ''),
                'duration_ms' => $duration_ms,
                'http_code' => $code,
                'usage' => $usage,
                'error_message' => $message,
            ]);
            return new WP_Error('api_error', $message, ['status' => 500]);
        }

        $content = $json['choices'][0]['message']['content'] ?? '';
        if (!$content) {
            PMFAI_Usage_Logger::log([
                'action' => 'generate-block',
                'status' => 'error',
                'mode' => $cost_mode,
                'model' => $mode_config['model'],
                'type' => $type,
                'industry' => sanitize_text_field($params['industry'] ?? ''),
                'duration_ms' => $duration_ms,
                'http_code' => $code,
                'usage' => $usage,
                'error_message' => 'Empty AI response',
            ]);
            return new WP_Error('empty_response', 'AI trả về rỗng hoặc không đúng định dạng.', ['status' => 500]);
        }

        $parsed = PMFAI_Code_Validator::parse_block($content);
        if (is_wp_error($parsed)) {
            PMFAI_Usage_Logger::log([
                'action' => 'generate-block',
                'status' => 'error',
                'mode' => $cost_mode,
                'model' => $mode_config['model'],
                'type' => $type,
                'industry' => sanitize_text_field($params['industry'] ?? ''),
                'duration_ms' => $duration_ms,
                'http_code' => $code,
                'usage' => $usage,
                'error_message' => 'Parse failed: ' . $parsed->get_error_message(),
            ]);
            return [
                'raw' => $content,
                'parse_error' => $parsed->get_error_message(),
                'prompt' => $prompt,
                'cost_mode' => $cost_mode,
                'model' => $mode_config['model'],
                'usage' => $usage,
            ];
        }

        PMFAI_Usage_Logger::log([
            'action' => 'generate-block',
            'status' => 'success',
            'mode' => $cost_mode,
            'model' => $mode_config['model'],
            'type' => $type,
            'industry' => sanitize_text_field($params['industry'] ?? ''),
            'duration_ms' => $duration_ms,
            'http_code' => $code,
            'usage' => $usage,
        ]);

        $parsed['raw'] = $content;
        $parsed['prompt'] = $prompt;
        $parsed['source'] = 'auto-mode-' . $cost_mode;
        $parsed['cost_mode'] = $cost_mode;
        $parsed['model'] = $mode_config['model'];
        $parsed['usage'] = $usage;
        return $parsed;
    }

    private static function mode_config(string $mode, array $options): array
    {
        $base_model = $options['api_model'] ?: 'gpt-4.1-mini';
        $base_temperature = is_numeric($options['temperature']) ? (float)$options['temperature'] : 0.4;

        $fast_model = trim((string)($options['fast_model'] ?? '')) ?: $base_model;
        $balanced_model = trim((string)($options['balanced_model'] ?? '')) ?: $base_model;
        $high_model = trim((string)($options['high_model'] ?? '')) ?: $base_model;

        $fast_temperature = is_numeric($options['fast_temperature'] ?? null) ? (float)$options['fast_temperature'] : min($base_temperature, 0.25);
        $balanced_temperature = is_numeric($options['balanced_temperature'] ?? null) ? (float)$options['balanced_temperature'] : $base_temperature;
        $high_temperature = is_numeric($options['high_temperature'] ?? null) ? (float)$options['high_temperature'] : max($base_temperature, 0.55);

        $configs = [
            'fast' => [
                'label' => 'Fast / Cheap',
                'model' => $fast_model,
                'temperature' => $fast_temperature,
                'timeout' => 60,
                'system' => 'You generate concise, clean WordPress Flatsome-compatible HTML blocks. Return only the requested pmedia-flatsome-block JSON markdown block. Prefer existing pm-* classes and avoid custom CSS unless required.',
                'instruction' => 'Ưu tiên tốc độ và tiết kiệm token. Tạo block đơn giản, ít CSS nhất có thể, không phân tích quá dài.',
            ],
            'balanced' => [
                'label' => 'Balanced',
                'model' => $balanced_model,
                'temperature' => $balanced_temperature,
                'timeout' => 90,
                'system' => 'You generate clean WordPress Flatsome-compatible HTML blocks. Return only the requested pmedia-flatsome-block JSON markdown block.',
                'instruction' => 'Cân bằng chất lượng và chi phí. Bố cục rõ ràng, responsive ổn, CSS custom tối thiểu.',
            ],
            'high' => [
                'label' => 'High Quality',
                'model' => $high_model,
                'temperature' => $high_temperature,
                'timeout' => 120,
                'system' => 'You are a senior UI engineer specialized in WordPress Flatsome. Generate polished, production-ready, conversion-focused HTML blocks. Return only the requested pmedia-flatsome-block JSON markdown block.',
                'instruction' => 'Ưu tiên chất lượng cao: bố cục thuyết phục, copywriting tự nhiên, spacing tốt, responsive rõ ràng, vẫn giữ CSS sạch và tương thích Flatsome.',
            ],
        ];

        return $configs[$mode] ?? $configs['balanced'];
    }
}
