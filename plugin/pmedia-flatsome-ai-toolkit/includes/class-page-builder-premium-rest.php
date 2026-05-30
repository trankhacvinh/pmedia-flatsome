<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Page_Builder_Premium_REST
{
    public static function register(): void
    {
        add_filter('rest_pre_dispatch', [__CLASS__, 'pre_dispatch'], 10, 3);
    }

    public static function pre_dispatch($result, WP_REST_Server $server, WP_REST_Request $request)
    {
        if ($result !== null) { return $result; }
        $route = (string)$request->get_route();
        if ($route === '/pmedia-ai/v1/page-builder/bridge-prompt') {
            $params = $request->get_json_params() ?: [];
            return rest_ensure_response(['prompt' => PMFAI_Page_Builder_Prompt::build($params, PMFAI_Page_Builder_AI::schema())]);
        }
        if ($route === '/pmedia-ai/v1/page-builder/generate') {
            $params = $request->get_json_params() ?: [];
            return rest_ensure_response(self::generate($params));
        }
        if ($route === '/pmedia-ai/v1/page-insert/bridge-prompt') {
            return self::page_insert_bridge_prompt($request);
        }
        if ($route === '/pmedia-ai/v1/page-insert/generate') {
            return self::page_insert_generate($request);
        }
        return $result;
    }

    private static function page_insert_bridge_prompt(WP_REST_Request $request)
    {
        $params = $request->get_json_params() ?: [];
        $post_id = absint($params['postId'] ?? 0);
        $context = PMFAI_Page_Insert_Box::build_context($post_id);
        $brief = trim((string)($params['brief'] ?? ''));
        $params['buildMode'] = 'single-section';
        $params['brief'] = "Trang hiện tại: " . ($context['title'] ?? '') . "\nSection count: " . ($context['section_count'] ?? 0) . "\nTóm tắt nội dung hiện tại: " . ($context['content_excerpt'] ?? '') . "\n\nYêu cầu thêm/sửa section:\n" . $brief;
        return rest_ensure_response(['prompt' => PMFAI_Page_Builder_Prompt::build($params, PMFAI_Page_Builder_AI::schema()), 'context' => $context]);
    }

    private static function page_insert_generate(WP_REST_Request $request)
    {
        $params = $request->get_json_params() ?: [];
        $post_id = absint($params['postId'] ?? 0);
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error('forbidden', 'Bạn không có quyền sửa page này.', ['status' => 403]);
        }
        $context = PMFAI_Page_Insert_Box::build_context($post_id);
        $brief = trim((string)($params['brief'] ?? ''));
        $params['buildMode'] = 'single-section';
        $params['brief'] = "Bạn đang hỗ trợ bổ sung section cho một page WordPress Flatsome hiện có.\n\nTrang hiện tại: " . ($context['title'] ?? '') . "\nSlug: " . ($context['slug'] ?? '') . "\nSố section hiện tại: " . ($context['section_count'] ?? 0) . "\nTóm tắt nội dung hiện tại: " . ($context['content_excerpt'] ?? '') . "\n\nYêu cầu section mới:\n" . $brief;
        $generated = self::generate($params);
        if (is_wp_error($generated)) { return $generated; }
        $shortcode = (string)($generated['shortcode'] ?? '');
        if (!empty($params['autoApply'])) {
            $applied = PMFAI_Page_Insert_Box::apply_content($post_id, $shortcode, sanitize_key($params['action'] ?? 'append'));
            if (is_wp_error($applied)) { return $applied; }
            $generated['applied'] = $applied;
        }
        return rest_ensure_response($generated);
    }

    private static function generate(array $params)
    {
        $started = microtime(true);
        $options = PMFAI_Settings::get_options();
        $provider = PMFAI_AI_Provider_Manager::provider_id($options);
        $mode = sanitize_key($params['costMode'] ?? 'balanced');
        $mode_config = self::mode_config($mode, $options, $provider);
        $build_mode = sanitize_key($params['buildMode'] ?? 'full-page');
        $output_mode = self::output_mode((string)($params['outputMode'] ?? 'html-block'));
        $prompt = PMFAI_Page_Builder_Prompt::build($params, PMFAI_Page_Builder_AI::schema());

        $response = PMFAI_AI_Provider_Manager::complete([
            'options' => $options,
            'provider' => $provider,
            'model' => $mode_config['model'],
            'temperature' => $mode_config['temperature'],
            'timeout' => $mode_config['timeout'],
            'messages' => [
                ['role' => 'system', 'content' => 'You generate strict valid JSON page structures for WordPress Flatsome. Use approved premium pattern templates. Use only real Flatsome shortcodes. Never invent custom pm-* shortcodes. Return JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $duration_ms = (int)round((microtime(true) - $started) * 1000);
        if (is_wp_error($response)) {
            PMFAI_Usage_Logger::log(['action'=>'page-builder-generate-premium','status'=>'error','mode'=>$mode,'model'=>$mode_config['model'],'type'=>$build_mode . '/' . $output_mode,'duration_ms'=>$duration_ms,'error_message'=>$response->get_error_message()]);
            return $response;
        }

        $actual_provider = sanitize_key($response['provider'] ?? $provider);
        $content = trim((string)($response['content'] ?? ''));
        $usage = is_array($response['usage'] ?? null) ? $response['usage'] : [];
        $parsed = PMFAI_Page_Builder_AI::parse_json($content, [
            'raw' => $content,
            'prompt' => $prompt,
            'usage' => $usage,
            'cost_mode' => $mode,
            'model' => $mode_config['model'],
            'provider' => $actual_provider,
            'output_mode' => $output_mode,
        ]);

        PMFAI_Usage_Logger::log([
            'action' => 'page-builder-generate-premium',
            'status' => is_wp_error($parsed) ? 'error' : 'success',
            'provider' => $actual_provider,
            'mode' => $mode,
            'model' => $mode_config['model'],
            'type' => $build_mode . '/' . $output_mode,
            'duration_ms' => $duration_ms,
            'http_code' => (int)($response['http_code'] ?? 200),
            'usage' => $usage,
            'error_message' => is_wp_error($parsed) ? $parsed->get_error_message() : (!empty($response['fallback_used']) ? 'Fallback used' : ''),
        ]);
        return $parsed;
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
        $base_temperature = is_numeric($options['temperature'] ?? null) ? (float)$options['temperature'] : 0.4;
        $configs = [
            'fast' => ['model'=>trim((string)($options['fast_model'] ?? '')) ?: $base_model, 'temperature'=>is_numeric($options['fast_temperature'] ?? null) ? (float)$options['fast_temperature'] : 0.2, 'timeout'=>90],
            'balanced' => ['model'=>trim((string)($options['balanced_model'] ?? '')) ?: $base_model, 'temperature'=>is_numeric($options['balanced_temperature'] ?? null) ? (float)$options['balanced_temperature'] : $base_temperature, 'timeout'=>120],
            'high' => ['model'=>trim((string)($options['high_model'] ?? '')) ?: $base_model, 'temperature'=>is_numeric($options['high_temperature'] ?? null) ? (float)$options['high_temperature'] : 0.65, 'timeout'=>180],
        ];
        return $configs[$mode] ?? $configs['balanced'];
    }
}
