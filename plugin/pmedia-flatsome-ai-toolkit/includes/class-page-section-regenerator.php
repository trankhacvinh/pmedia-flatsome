<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Page_Section_Regenerator
{
    public static function register_routes(): void
    {
        register_rest_route('pmedia-ai/v1', '/page-builder/regenerate-section', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'route_regenerate'],
            'permission_callback' => static function () { return current_user_can('manage_options'); },
        ]);
    }

    public static function route_regenerate(WP_REST_Request $request)
    {
        $result = self::regenerate($request->get_json_params() ?: []);
        return is_wp_error($result) ? $result : rest_ensure_response($result);
    }

    public static function regenerate(array $params)
    {
        $started = microtime(true);
        $options = PMFAI_Settings::get_options();
        $provider = PMFAI_AI_Provider_Manager::provider_id($options);
        $page = is_array($params['page'] ?? null) ? $params['page'] : [];
        $section = is_array($params['section'] ?? null) ? $params['section'] : [];
        $mode = sanitize_key($params['costMode'] ?? 'balanced');
        $output_mode = self::output_mode((string)($params['outputMode'] ?? ($page['output_mode'] ?? 'html-block')));
        $mode_config = self::mode_config($mode, $options, $provider);

        if (!$page || !$section) {
            return new WP_Error('invalid_input', 'Thiếu dữ liệu page hoặc section.', ['status' => 400]);
        }

        $prompt = self::prompt($page, $section, $params, $output_mode);
        $response = PMFAI_AI_Provider_Manager::complete([
            'options' => $options,
            'provider' => $provider,
            'model' => $mode_config['model'],
            'temperature' => $mode_config['temperature'],
            'timeout' => $mode_config['timeout'],
            'messages' => [
                ['role' => 'system', 'content' => 'You generate exactly one strict valid JSON section object for WordPress Flatsome. Return JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $duration_ms = (int)round((microtime(true) - $started) * 1000);
        if (is_wp_error($response)) {
            PMFAI_Usage_Logger::log([
                'action' => 'page-builder-regenerate-section',
                'status' => 'error',
                'mode' => $mode,
                'model' => $mode_config['model'],
                'type' => $output_mode,
                'duration_ms' => $duration_ms,
                'error_message' => $response->get_error_message(),
            ]);
            return $response;
        }

        $content = trim((string)($response['content'] ?? ''));
        $usage = is_array($response['usage'] ?? null) ? $response['usage'] : [];
        $code = (int)($response['http_code'] ?? 200);
        $section_data = self::parse_section_json($content);
        if (is_wp_error($section_data)) {
            PMFAI_Usage_Logger::log([
                'action' => 'page-builder-regenerate-section',
                'status' => 'error',
                'mode' => $mode,
                'model' => $mode_config['model'],
                'type' => $output_mode,
                'duration_ms' => $duration_ms,
                'http_code' => $code,
                'usage' => $usage,
                'error_message' => $section_data->get_error_message(),
            ]);
            return $section_data;
        }

        $validated = PMFAI_Page_Builder_AI::validate([
            'page' => [
                'title' => sanitize_text_field($page['title'] ?? 'Trang mới'),
                'slug' => sanitize_title($page['slug'] ?? 'trang-moi'),
                'description' => sanitize_textarea_field($page['description'] ?? ''),
                'output_mode' => $output_mode,
                'style_preset' => sanitize_key($page['style_preset'] ?? 'corporate-blue'),
                'sections' => [$section_data],
            ],
        ]);
        if (is_wp_error($validated)) {
            return $validated;
        }

        $new_section = $validated['page']['sections'][0] ?? null;
        if (!$new_section) {
            return new WP_Error('invalid_section', 'Không tạo được section hợp lệ.', ['status' => 400]);
        }
        $visual_quality = PMFAI_Visual_Quality_Validator::analyze_page(['sections' => [$new_section]]);

        PMFAI_Usage_Logger::log([
            'action' => 'page-builder-regenerate-section',
            'status' => 'success',
            'mode' => $mode,
            'model' => $mode_config['model'],
            'type' => $output_mode,
            'duration_ms' => $duration_ms,
            'http_code' => $code,
            'usage' => $usage,
            'error_message' => '',
        ]);

        return [
            'section' => $new_section,
            'visual_quality' => $visual_quality,
            'usage' => $usage,
            'model' => $mode_config['model'],
            'provider' => $provider,
            'cost_mode' => $mode,
            'output_mode' => $output_mode,
        ];
    }

    private static function prompt(array $page, array $section, array $params, string $output_mode): string
    {
        $brief = trim((string)($params['brief'] ?? ''));
        $recommended = implode(', ', PMFAI_Section_Patterns::recommend_for_text(
            ($section['title'] ?? '') . ' ' . ($section['goal'] ?? '') . ' ' . $brief
        ));
        $page_summary = [
            'title' => $page['title'] ?? '',
            'description' => $page['description'] ?? '',
            'style_preset' => $page['style_preset'] ?? 'corporate-blue',
            'output_mode' => $output_mode,
        ];
        $output_rule = $output_mode === 'flatsome-native'
            ? 'Output mode là Flatsome Native Shortcode: field shortcode phải có nội dung, ưu tiên [section], [row], [col], [ux_text], [button], [title], [gap].'
            : 'Output mode là Flatsome Section + HTML Block: field html phải có nội dung, dùng class pm-* và pmedia-ai-block.';

        return "Bạn là senior UI engineer cho WordPress Flatsome.\n\n"
            . "Hãy sinh lại DUY NHẤT 1 section, không sinh cả page.\n"
            . "{$output_rule}\n\n"
            . "Bối cảnh page hiện tại:\n" . wp_json_encode($page_summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Section hiện tại cần cải thiện:\n" . wp_json_encode($section, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Yêu cầu bổ sung của người dùng:\n" . ($brief ?: '[Không có yêu cầu bổ sung]') . "\n\n"
            . "Pattern Library:\n" . PMFAI_Section_Patterns::prompt_catalog() . "\n\n"
            . "Pattern gợi ý: {$recommended}\n\n"
            . "Yêu cầu chất lượng:\n"
            . "- Giữ nội dung cốt lõi nhưng làm layout đẹp hơn.\n"
            . "- Được phép đổi pattern nếu pattern hiện tại không phù hợp.\n"
            . "- Không để card quá hẹp, không để chữ rơi dọc.\n"
            . "- Copy ngắn, chuyên nghiệp, tự nhiên.\n"
            . "- Tận dụng pm-* component class và Design System token.\n"
            . "- Không dùng Bootstrap/Tailwind, không external assets, không script inline.\n\n"
            . "Trả về duy nhất JSON object section hợp lệ, không markdown, không giải thích.\n"
            . "Schema:\n{\n  \"id\": \"section-id\",\n  \"type\": \"services\",\n  \"pattern\": \"service-grid-clean\",\n  \"title\": \"\",\n  \"goal\": \"\",\n  \"html\": \"\",\n  \"css\": \"\",\n  \"shortcode\": \"\"\n}";
    }

    private static function parse_section_json(string $raw)
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $unslashed = wp_unslash($raw);
            if ($unslashed !== $raw) {
                $data = json_decode($unslashed, true);
            }
        }
        if (!is_array($data)) {
            return new WP_Error('invalid_json', 'Section JSON không hợp lệ: ' . json_last_error_msg(), ['status' => 400]);
        }
        if (isset($data['section']) && is_array($data['section'])) {
            $data = $data['section'];
        }
        return $data;
    }

    private static function output_mode(string $mode): string
    {
        return $mode === 'flatsome-native' ? 'flatsome-native' : 'html-block';
    }

    private static function mode_config(string $mode, array $options, string $provider = 'openai'): array
    {
        if ($provider === 'anthropic') {
            $base_model = $options['anthropic_model'] ?: 'claude-3-5-sonnet-latest';
        } elseif ($provider === 'openai_compatible') {
            $base_model = $options['compatible_model'] ?: ($options['api_model'] ?: 'gpt-4.1-mini');
        } else {
            $base_model = $options['api_model'] ?: 'gpt-4.1-mini';
        }
        $base_temperature = is_numeric($options['temperature']) ? (float)$options['temperature'] : 0.4;
        $configs = [
            'fast' => ['model' => trim((string)($options['fast_model'] ?? '')) ?: $base_model, 'temperature' => is_numeric($options['fast_temperature'] ?? null) ? (float)$options['fast_temperature'] : 0.2, 'timeout' => 90],
            'balanced' => ['model' => trim((string)($options['balanced_model'] ?? '')) ?: $base_model, 'temperature' => is_numeric($options['balanced_temperature'] ?? null) ? (float)$options['balanced_temperature'] : $base_temperature, 'timeout' => 120],
            'high' => ['model' => trim((string)($options['high_model'] ?? '')) ?: $base_model, 'temperature' => is_numeric($options['high_temperature'] ?? null) ? (float)$options['high_temperature'] : 0.65, 'timeout' => 180],
        ];
        return $configs[$mode] ?? $configs['balanced'];
    }
}
