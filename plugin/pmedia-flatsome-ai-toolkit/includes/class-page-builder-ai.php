<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Page_Builder_AI
{
    public static function schema(): array
    {
        return [
            'version' => '1.0',
            'page' => [
                'title' => '',
                'slug' => '',
                'description' => '',
                'sections' => [
                    [
                        'id' => 'home-hero',
                        'type' => 'hero',
                        'title' => '',
                        'goal' => '',
                        'html' => '',
                        'css' => '',
                    ],
                ],
            ],
        ];
    }

    public static function bridge_prompt(array $params = []): string
    {
        $brief = trim((string)($params['brief'] ?? ''));
        $mode = sanitize_key($params['buildMode'] ?? 'full-page');
        $schema = wp_json_encode(self::schema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return "Bạn là senior UI engineer cho WordPress Flatsome.\n\n"
            . "Hãy tạo page plan và các section HTML/CSS cho website theo brief.\n"
            . "Output sẽ được plugin ghép thành Flatsome shortcode dạng [section] + [row] + [col] + HTML block.\n\n"
            . "Yêu cầu bắt buộc:\n"
            . "- Trả về duy nhất JSON hợp lệ, không markdown, không giải thích.\n"
            . "- Mỗi section có html và css riêng.\n"
            . "- HTML phải dùng class pm-* và pmedia-ai-block, không Bootstrap/Tailwind.\n"
            . "- CSS phải scoped trong .pmedia-ai-block hoặc class section riêng.\n"
            . "- Không dùng script inline, không gọi external assets.\n"
            . "- Nội dung tiếng Việt tự nhiên, chuyên nghiệp, không văn AI.\n"
            . "- Nếu build mode là single-section thì chỉ tạo 1 section.\n"
            . "- Nếu build mode là full-page thì tạo đủ các section cần thiết cho một trang hoàn chỉnh.\n\n"
            . "Build mode: {$mode}\n"
            . "Brief:\n" . ($brief ?: '[Dán brief trang hoặc website tại đây]') . "\n\n"
            . "Schema bắt buộc:\n" . $schema;
    }

    public static function generate(array $params)
    {
        $options = PMFAI_Settings::get_options();
        $api_key = trim((string)($options['api_key'] ?? ''));
        if ($api_key === '') {
            return new WP_Error('missing_api_key', 'Chưa cấu hình API key trong Settings.', ['status' => 400]);
        }

        $mode = sanitize_key($params['costMode'] ?? 'balanced');
        $mode_config = self::mode_config($mode, $options);
        $prompt = self::bridge_prompt($params);
        $payload = [
            'model' => $mode_config['model'],
            'temperature' => $mode_config['temperature'],
            'messages' => [
                ['role' => 'system', 'content' => 'You generate strict JSON page structures for WordPress Flatsome. Return valid JSON only.'],
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

        if (is_wp_error($response)) { return $response; }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('api_error', $json['error']['message'] ?? ('AI API error HTTP ' . $code), ['status' => 500]);
        }

        $content = trim((string)($json['choices'][0]['message']['content'] ?? ''));
        $parsed = self::parse_json($content, ['raw' => $content, 'prompt' => $prompt, 'usage' => $json['usage'] ?? [], 'cost_mode' => $mode, 'model' => $mode_config['model']]);
        if (is_wp_error($parsed)) { return $parsed; }
        return $parsed;
    }

    public static function parse_json(string $raw, array $extra = [])
    {
        $raw = trim(wp_unslash($raw));
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return new WP_Error('invalid_json', 'JSON page không hợp lệ.', ['status' => 400]);
        }
        $validated = self::validate($data);
        if (is_wp_error($validated)) { return $validated; }
        $validated['shortcode'] = self::to_shortcode($validated['page']);
        return array_merge($validated, $extra);
    }

    public static function validate(array $data)
    {
        $page = $data['page'] ?? $data;
        if (!is_array($page)) {
            return new WP_Error('invalid_page', 'Không tìm thấy object page.', ['status' => 400]);
        }
        $sections = $page['sections'] ?? [];
        if (!is_array($sections) || empty($sections)) {
            return new WP_Error('missing_sections', 'Page phải có ít nhất một section.', ['status' => 400]);
        }

        $out = [
            'version' => sanitize_text_field($data['version'] ?? '1.0'),
            'page' => [
                'title' => sanitize_text_field($page['title'] ?? 'Trang mới'),
                'slug' => sanitize_title($page['slug'] ?? $page['title'] ?? 'trang-moi'),
                'description' => sanitize_textarea_field($page['description'] ?? ''),
                'sections' => [],
            ],
            'warnings' => [],
        ];

        foreach ($sections as $index => $section) {
            $id = sanitize_html_class($section['id'] ?? ('section-' . ($index + 1)));
            $type = sanitize_key($section['type'] ?? 'custom');
            $html = (string)($section['html'] ?? '');
            $css = (string)($section['css'] ?? '');
            if (trim($html) === '') {
                $out['warnings'][] = "Section {$id} thiếu HTML và đã bị bỏ qua.";
                continue;
            }
            if (stripos($html, 'pmedia-ai-block') === false) {
                $html = '<div class="pmedia-ai-block pm-page-block pm-page-block-' . esc_attr($id) . '">' . "\n" . trim($html) . "\n" . '</div>';
            }
            $out['page']['sections'][] = [
                'id' => $id,
                'type' => $type,
                'title' => sanitize_text_field($section['title'] ?? ''),
                'goal' => sanitize_textarea_field($section['goal'] ?? ''),
                'html' => $html,
                'css' => $css,
                'scores' => PMFAI_Code_Validator::analyze($html, $css)['scores'] ?? [],
            ];
        }

        if (empty($out['page']['sections'])) {
            return new WP_Error('no_valid_sections', 'Không có section hợp lệ.', ['status' => 400]);
        }
        return $out;
    }

    public static function to_shortcode(array $page): string
    {
        $content = '';
        foreach ($page['sections'] as $section) {
            $class = 'pm-section pm-page-section pm-section-' . sanitize_html_class($section['id']) . ' pm-section-type-' . sanitize_html_class($section['type']);
            $html = trim((string)$section['html']);
            $css = trim((string)$section['css']);
            $style = $css ? "\n<style>\n" . $css . "\n</style>\n" : '';
            $content .= '[section class="' . esc_attr($class) . '"]' . "\n";
            $content .= '  [row]' . "\n";
            $content .= '    [col span__sm="12"]' . "\n";
            $content .= $style . $html . "\n";
            $content .= '    [/col]' . "\n";
            $content .= '  [/row]' . "\n";
            $content .= '[/section]' . "\n\n";
        }
        return trim($content);
    }

    public static function create_draft(array $data)
    {
        $validated = self::validate($data);
        if (is_wp_error($validated)) { return $validated; }
        $shortcode = self::to_shortcode($validated['page']);
        $post_id = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_title' => $validated['page']['title'],
            'post_name' => $validated['page']['slug'],
            'post_content' => $shortcode,
        ], true);
        if (is_wp_error($post_id)) { return $post_id; }
        return ['created' => true, 'post_id' => $post_id, 'edit_url' => get_edit_post_link($post_id, 'raw'), 'shortcode' => $shortcode];
    }

    private static function mode_config(string $mode, array $options): array
    {
        $base_model = $options['api_model'] ?: 'gpt-4.1-mini';
        $base_temperature = is_numeric($options['temperature']) ? (float)$options['temperature'] : 0.4;
        $configs = [
            'fast' => ['model' => trim((string)($options['fast_model'] ?? '')) ?: $base_model, 'temperature' => is_numeric($options['fast_temperature'] ?? null) ? (float)$options['fast_temperature'] : 0.2, 'timeout' => 90],
            'balanced' => ['model' => trim((string)($options['balanced_model'] ?? '')) ?: $base_model, 'temperature' => is_numeric($options['balanced_temperature'] ?? null) ? (float)$options['balanced_temperature'] : $base_temperature, 'timeout' => 120],
            'high' => ['model' => trim((string)($options['high_model'] ?? '')) ?: $base_model, 'temperature' => is_numeric($options['high_temperature'] ?? null) ? (float)$options['high_temperature'] : 0.65, 'timeout' => 180],
        ];
        return $configs[$mode] ?? $configs['balanced'];
    }
}
