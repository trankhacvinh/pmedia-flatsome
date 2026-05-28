<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Usage_Logs_V2
{
    public static function register_routes(): void
    {
        register_rest_route('pmedia-ai/v1', '/usage-logs-v2', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'route_list'],
            'permission_callback' => static function () { return current_user_can('manage_options'); },
        ]);
    }

    public static function route_list(WP_REST_Request $request)
    {
        return rest_ensure_response(PMFAI_Usage_Logger::list([
            'posts_per_page' => $request->get_param('per_page') ?: 100,
            'paged' => $request->get_param('page') ?: 1,
            'status' => $request->get_param('status') ?: '',
            'mode' => $request->get_param('mode') ?: '',
            'provider' => $request->get_param('provider') ?: '',
        ]));
    }
}
