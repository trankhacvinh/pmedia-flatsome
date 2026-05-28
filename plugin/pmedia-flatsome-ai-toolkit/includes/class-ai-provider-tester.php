<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_AI_Provider_Tester
{
    public static function register_routes(): void
    {
        register_rest_route('pmedia-ai/v1', '/provider/test', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'route_test'],
            'permission_callback' => static function () { return current_user_can('manage_options'); },
        ]);
    }

    public static function route_test(WP_REST_Request $request)
    {
        $started = microtime(true);
        $options = PMFAI_Settings::get_options();
        $provider = PMFAI_AI_Provider_Manager::provider_id($options);
        $model = PMFAI_AI_Provider_Manager::model($provider, $options);

        $response = PMFAI_AI_Provider_Manager::complete([
            'options' => $options,
            'provider' => $provider,
            'model' => $model,
            'temperature' => 0,
            'timeout' => 45,
            'max_tokens' => 128,
            'messages' => [
                ['role' => 'system', 'content' => 'Return only valid JSON.'],
                ['role' => 'user', 'content' => 'Return exactly this JSON object: {"ok":true,"message":"connected"}'],
            ],
        ]);

        $duration_ms = (int)round((microtime(true) - $started) * 1000);
        if (is_wp_error($response)) {
            return new WP_Error('provider_test_failed', $response->get_error_message(), ['status' => 500]);
        }

        return rest_ensure_response([
            'ok' => true,
            'provider' => $provider,
            'provider_label' => PMFAI_AI_Provider_Manager::provider_label($provider),
            'model' => $model,
            'duration_ms' => $duration_ms,
            'content' => trim((string)($response['content'] ?? '')),
            'usage' => is_array($response['usage'] ?? null) ? $response['usage'] : [],
        ]);
    }
}
