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
            . "- HTML và CSS phải nằm trong JSON string hợp lệ.\n"
            . "- Escape dấu nháy kép bên trong HTML/CSS đúng một lần: dùng \\\" thay cho dấu nháy kép thô.\n"
            . "- Không double-escape HTML/CSS. Không trả về chuỗi có \\\\n hoặc \\\\\\\" còn hiện ra thành chữ trong preview.\n"
            . "- Mỗi section có html và css riêng.\n"
            . "- HTML phải dùng class pm-* và pmedia-ai-block, không Bootstrap/Tailwind.\n"
            . "- CSS phải scoped trong .pmedia-ai-block hoặc class section riêng.\n"
            . "- Không dùng script inline, không gọi external assets.\n"
            . "- Nội dung tiếng Việt tự nhiên, chuyên nghiệp, không văn AI.\n"
            . "- Nếu build mode là single-section thì chỉ tạo 1 section.\n"
            . "- Nếu build mode là full-page thì tạo đủ các section cần thiết cho một trang hoàn chỉnh.\n\n"
            . "Quy tắc giảm CSS lặp lại:\n"
            . "- Design System token đã có sẵn, PHẢI dùng token chung thay vì tự khai báo biến màu trong từng section.\n"
            . "- Không khai báo các biến kiểu --pm-navy, --pm-blue, --pm-text, --pm-muted, --pm-border, --pm-soft trong từng block.\n"
            . "- Dùng các token có sẵn: var(--pm-color-primary), var(--pm-color-secondary), var(--pm-color-accent), var(--pm-color-text), var(--pm-color-muted), var(--pm-color-border), var(--pm-color-bg-soft), var(--pm-radius-md), var(--pm-radius-lg), var(--pm-shadow-sm), var(--pm-shadow-md).\n"
            . "- Không lặp lại màu HEX nếu có thể dùng token chung.\n"
            . "- CSS section chỉ viết layout/hiệu ứng riêng thật sự cần thiết. Nếu class pm-* đã đủ dùng thì css để chuỗi rỗng.\n\n"
            . "Ví dụ JSON string đúng: {\"html\":\"<section class=\\\"pm-section pmedia-ai-block\\\">Nội dung</section>\",\"css\":\".pmedia-ai-block{padding:var(--pm-section-padding) 24px}\"}\n\n"
            . "Build mode: {$mode}\n"
            . "Brief:\n" . ($brief ?: '[Dán brief trang hoặc website tại đây]') . "\n\n"
            . "Schema bắt buộc:\n" . $schema;
    }

    public static function generate(array $params)
    {
        $started = microtime(true);
        $options = PMFAI_Settings::get_options();
        $api_key = trim((string)($options['api_key'] ?? ''));
        $mode = sanitize_key($params['costMode'] ?? 'balanced');
        $mode_config = self::mode_config($mode, $options);
        $build_mode = sanitize_key($params['buildMode'] ?? 'full-page');

        if ($api_key === '') {
            PMFAI_Usage_Logger::log([
                'action' => 'page-builder-generate',
                'status' => 'error',
                'mode' => $mode,
                'model' => $mode_config['model'],
                'type' => $build_mode,
                'error_message' => 'Missing API key',
            ]);
            return new WP_Error('missing_api_key', 'Chưa cấu hình API key trong Settings.', ['status' => 400]);
        }

        $prompt = self::bridge_prompt($params);
        $payload = [
            'model' => $mode_config['model'],
            'temperature' => $mode_config['temperature'],
            'messages' => [
                ['role' => 'system', 'content' => 'You generate strict valid JSON page structures for WordPress Flatsome. Return JSON only. Escape all double quotes inside HTML/CSS strings exactly once. Do not double-escape newline or quote characters. Reuse existing design tokens and avoid redeclaring per-section color variables.'],
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
                'action' => 'page-builder-generate',
                'status' => 'error',
                'mode' => $mode,
                'model' => $mode_config['model'],
                'type' => $build_mode,
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
                'action' => 'page-builder-generate',
                'status' => 'error',
                'mode' => $mode,
                'model' => $mode_config['model'],
                'type' => $build_mode,
                'duration_ms' => $duration_ms,
                'http_code' => $code,
                'usage' => $usage,
                'error_message' => $message,
            ]);
            return new WP_Error('api_error', $message, ['status' => 500]);
        }

        $content = trim((string)($json['choices'][0]['message']['content'] ?? ''));
        $parsed = self::parse_json($content, ['raw' => $content, 'prompt' => $prompt, 'usage' => $usage, 'cost_mode' => $mode, 'model' => $mode_config['model']]);

        PMFAI_Usage_Logger::log([
            'action' => 'page-builder-generate',
            'status' => is_wp_error($parsed) ? 'error' : 'success',
            'mode' => $mode,
            'model' => $mode_config['model'],
            'type' => $build_mode,
            'duration_ms' => $duration_ms,
            'http_code' => $code,
            'usage' => $usage,
            'error_message' => is_wp_error($parsed) ? $parsed->get_error_message() : '',
        ]);

        if (is_wp_error($parsed)) { return $parsed; }
        return $parsed;
    }

    public static function parse_json(string $raw, array $extra = [])
    {
        $raw = self::strip_code_fence((string)$raw);

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            $unslashed = self::strip_code_fence(wp_unslash($raw));
            if ($unslashed !== $raw) {
                $data = json_decode($unslashed, true);
            }
        }

        if (!is_array($data)) {
            $repaired = self::repair_unescaped_html_css_strings($raw);
            if ($repaired !== $raw) {
                $data = json_decode($repaired, true);
            }
        }

        if (!is_array($data)) {
            return new WP_Error('invalid_json', 'JSON page không hợp lệ: ' . json_last_error_msg() . '. Gợi ý: HTML/CSS trong JSON phải escape dấu nháy kép, ví dụ class=\\"pm-section\\".', ['status' => 400]);
        }

        $validated = self::validate($data);
        if (is_wp_error($validated)) { return $validated; }
        $validated['shortcode'] = self::to_shortcode($validated['page']);
        return array_merge($validated, $extra);
    }

    private static function strip_code_fence(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);
        return trim($raw);
    }

    private static function repair_unescaped_html_css_strings(string $raw): string
    {
        $raw = self::strip_code_fence($raw);

        $raw = preg_replace_callback('/"html"\s*:\s*"([\s\S]*?)"\s*,\s*"css"\s*:/', function ($m) {
            return '"html":' . wp_json_encode($m[1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',"css":';
        }, $raw);

        $raw = preg_replace_callback('/"css"\s*:\s*"([\s\S]*?)"\s*(?=\}\s*(?:,|\]))/', function ($m) {
            return '"css":' . wp_json_encode($m[1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $raw);

        return $raw;
    }

    private static function normalize_code_string(string $value): array
    {
        $original = $value;
        $value = trim($value);

        $value = str_replace(["\\r\\n", "\\n", "\\t"], ["\n", "\n", "\t"], $value);
        $value = str_replace(['\\"', "\\'", '\\/'], ['"', "'", '/'], $value);

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $maybe = json_decode($value, true);
            if (is_string($maybe)) {
                $value = $maybe;
            }
        }

        return ['value' => $value, 'changed' => $value !== $original];
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
            $htmlNormalized = self::normalize_code_string((string)($section['html'] ?? ''));
            $cssNormalized = self::normalize_code_string((string)($section['css'] ?? ''));
            $html = $htmlNormalized['value'];
            $css = $cssNormalized['value'];

            if ($htmlNormalized['changed'] || $cssNormalized['changed']) {
                $out['warnings'][] = "Section {$id}: Đã tự sửa chuỗi HTML/CSS bị double-escaped, ví dụ \\n hoặc \\\" còn hiện ra trong preview.";
            }

            if (trim($html) === '') {
                $out['warnings'][] = "Section {$id} thiếu HTML và đã bị bỏ qua.";
                continue;
            }
            if (stripos($html, 'pmedia-ai-block') === false) {
                $html = '<div class="pmedia-ai-block pm-page-block pm-page-block-' . esc_attr($id) . '">' . "\n" . trim($html) . "\n" . '</div>';
            }

            $normalized = self::normalize_section_css($css);
            $css = $normalized['css'];
            foreach ($normalized['warnings'] as $warning) {
                $out['warnings'][] = "Section {$id}: " . $warning;
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

    private static function normalize_section_css(string $css): array
    {
        $warnings = [];
        $css = trim($css);
        if ($css === '') {
            return ['css' => '', 'warnings' => []];
        }

        $original = $css;
        $options = PMFAI_Settings::get_options();

        $customVarMap = [
            '--pm-navy' => 'var(--pm-color-secondary)',
            '--pm-blue' => 'var(--pm-color-primary)',
            '--pm-accent' => 'var(--pm-color-accent)',
            '--pm-text' => 'var(--pm-color-text)',
            '--pm-muted' => 'var(--pm-color-muted)',
            '--pm-border' => 'var(--pm-color-border)',
            '--pm-soft' => 'var(--pm-color-bg-soft)',
        ];

        foreach ($customVarMap as $varName => $token) {
            $css = preg_replace('/' . preg_quote($varName, '/') . '\s*:\s*[^;{}]+;?/i', '', $css);
            $css = str_ireplace('var(' . $varName . ')', $token, $css);
        }

        $colorMap = [
            '#062B63' => 'var(--pm-color-secondary)',
            '#0B4EA2' => 'var(--pm-color-primary)',
            '#2F80ED' => 'var(--pm-color-accent)',
            '#102033' => 'var(--pm-color-text)',
            '#64748B' => 'var(--pm-color-muted)',
            '#DDE6F2' => 'var(--pm-color-border)',
            '#F5F8FC' => 'var(--pm-color-bg-soft)',
            '#FFFFFF' => '#fff',
        ];

        $settingsColorMap = [
            $options['primary_color'] ?? '' => 'var(--pm-color-primary)',
            $options['secondary_color'] ?? '' => 'var(--pm-color-secondary)',
            $options['accent_color'] ?? '' => 'var(--pm-color-accent)',
            $options['text_color'] ?? '' => 'var(--pm-color-text)',
            $options['muted_color'] ?? '' => 'var(--pm-color-muted)',
            $options['border_color'] ?? '' => 'var(--pm-color-border)',
            $options['bg_soft_color'] ?? '' => 'var(--pm-color-bg-soft)',
        ];

        foreach (array_merge($colorMap, $settingsColorMap) as $hex => $token) {
            if ($hex && $token) {
                $css = str_ireplace($hex, $token, $css);
            }
        }

        $css = preg_replace('/;{2,}/', ';', $css);
        $css = preg_replace('/\{\s*;/', '{', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,>])\s*/', '$1', $css);
        $css = trim($css);

        if ($css !== $original) {
            $warnings[] = 'Đã normalize CSS: xóa token cục bộ và thay màu lặp bằng Design System variables.';
        }

        return ['css' => $css, 'warnings' => $warnings];
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
