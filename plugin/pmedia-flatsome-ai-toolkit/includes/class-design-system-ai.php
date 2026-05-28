<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Design_System_AI
{
    public static function schema(): array
    {
        return [
            'version' => '1.0',
            'design_system' => [
                'name' => '',
                'description' => '',
                'primary_color' => '',
                'secondary_color' => '',
                'accent_color' => '',
                'text_color' => '',
                'muted_color' => '',
                'border_color' => '',
                'bg_soft_color' => '',
                'radius_sm' => '',
                'radius_md' => '',
                'radius_lg' => '',
                'section_padding_desktop' => '',
                'section_padding_mobile' => '',
                'style_keywords' => [],
                'ai_rules' => [],
            ],
        ];
    }

    public static function bridge_prompt(array $params = []): string
    {
        $brief = trim((string)($params['brief'] ?? ''));
        $schema = wp_json_encode(self::schema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return "Bạn là chuyên gia UI/UX cho website WordPress Flatsome.\n\n"
            . "Hãy phân tích brief/ảnh mẫu tôi gửi và tạo Design System ban đầu cho website.\n\n"
            . "Yêu cầu bắt buộc:\n"
            . "- Phù hợp với WordPress Flatsome và HTML Block.\n"
            . "- Màu phải dùng mã HEX hợp lệ.\n"
            . "- Spacing/radius phải có đơn vị px.\n"
            . "- Không dùng Bootstrap/Tailwind.\n"
            . "- Trả về duy nhất JSON hợp lệ, không markdown, không giải thích ngoài JSON.\n"
            . "- Giữ đúng key trong schema.\n\n"
            . "Brief:\n" . ($brief ?: '[Dán brief hoặc mô tả website tại đây]') . "\n\n"
            . "Schema bắt buộc:\n" . $schema;
    }

    public static function generate(array $params)
    {
        $started = microtime(true);
        $options = PMFAI_Settings::get_options();
        $provider = PMFAI_AI_Provider_Manager::provider_id($options);
        $model = self::model($options, $provider);
        $temperature = is_numeric($options['balanced_temperature']) ? (float)$options['balanced_temperature'] : 0.4;
        $prompt = self::bridge_prompt($params);

        $response = PMFAI_AI_Provider_Manager::complete([
            'options' => $options,
            'provider' => $provider,
            'model' => $model,
            'temperature' => $temperature,
            'timeout' => 90,
            'messages' => [
                ['role' => 'system', 'content' => 'You create strict JSON design systems for WordPress Flatsome. Return valid JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $duration_ms = (int)round((microtime(true) - $started) * 1000);
        if (is_wp_error($response)) {
            PMFAI_Usage_Logger::log([
                'action' => 'design-system-generate',
                'status' => 'error',
                'mode' => 'balanced',
                'model' => $model,
                'type' => 'design-system',
                'duration_ms' => $duration_ms,
                'error_message' => $response->get_error_message(),
            ]);
            return $response;
        }

        $content = trim((string)($response['content'] ?? ''));
        $usage = is_array($response['usage'] ?? null) ? $response['usage'] : [];
        $code = (int)($response['http_code'] ?? 200);
        $parsed = self::parse_json($content, ['raw' => $content, 'prompt' => $prompt, 'usage' => $usage, 'model' => $model, 'provider' => $provider, 'cost_mode' => 'balanced']);

        PMFAI_Usage_Logger::log([
            'action' => 'design-system-generate',
            'status' => is_wp_error($parsed) ? 'error' : 'success',
            'mode' => 'balanced',
            'model' => $model,
            'type' => 'design-system',
            'duration_ms' => $duration_ms,
            'http_code' => $code,
            'usage' => $usage,
            'error_message' => is_wp_error($parsed) ? $parsed->get_error_message() : '',
        ]);

        return $parsed;
    }

    private static function model(array $options, string $provider): string
    {
        $override = trim((string)($options['balanced_model'] ?? ''));
        if ($override !== '') { return $override; }
        return PMFAI_AI_Provider_Manager::model($provider, $options);
    }

    public static function parse_json(string $raw, array $extra = [])
    {
        $raw = trim(wp_unslash($raw));
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return new WP_Error('invalid_json', 'JSON không hợp lệ.', ['status' => 400]);
        }

        $validated = self::validate($data);
        if (is_wp_error($validated)) { return $validated; }
        return array_merge($validated, $extra);
    }

    public static function validate(array $data)
    {
        $ds = $data['design_system'] ?? $data;
        if (!is_array($ds)) {
            return new WP_Error('invalid_design_system', 'Không tìm thấy object design_system.', ['status' => 400]);
        }

        $defaults = PMFAI_Settings::default_options();
        $keys = ['primary_color','secondary_color','accent_color','text_color','muted_color','border_color','bg_soft_color','radius_sm','radius_md','radius_lg','section_padding_desktop','section_padding_mobile'];
        $out = [
            'version' => sanitize_text_field($data['version'] ?? '1.0'),
            'design_system' => [
                'name' => sanitize_text_field($ds['name'] ?? ''),
                'description' => sanitize_textarea_field($ds['description'] ?? ''),
                'style_keywords' => array_values(array_filter(array_map('sanitize_text_field', (array)($ds['style_keywords'] ?? [])))),
                'ai_rules' => array_values(array_filter(array_map('sanitize_text_field', (array)($ds['ai_rules'] ?? [])))),
            ],
            'warnings' => [],
        ];

        foreach ($keys as $key) {
            $value = trim((string)($ds[$key] ?? ''));
            if (strpos($key, 'color') !== false) {
                $hex = sanitize_hex_color($value);
                if (!$hex) {
                    $hex = $defaults[$key];
                    $out['warnings'][] = "Field $key không hợp lệ, dùng fallback {$defaults[$key]}.";
                }
                $out['design_system'][$key] = $hex;
            } else {
                if (!preg_match('/^\d+(\.\d+)?(px|rem|em|%)$/', $value)) {
                    $value = $defaults[$key];
                    $out['warnings'][] = "Field $key không hợp lệ, dùng fallback {$defaults[$key]}.";
                }
                $out['design_system'][$key] = $value;
            }
        }

        return $out;
    }

    public static function apply(array $data): array
    {
        $validated = self::validate($data);
        if (is_wp_error($validated)) { return $validated; }
        $current = PMFAI_Settings::get_options();
        foreach ($validated['design_system'] as $key => $value) {
            if (array_key_exists($key, $current)) {
                $current[$key] = $value;
            }
        }
        if (!empty($validated['design_system']['ai_rules'])) {
            $current['extra_rules'] = implode("\n", $validated['design_system']['ai_rules']);
        }
        update_option(PMFAI_OPTION_KEY, PMFAI_Settings::sanitize_options($current));
        return ['applied' => true, 'design_system' => $validated['design_system'], 'tokensCss' => PMFAI_Assets::tokens_css()];
    }
}
