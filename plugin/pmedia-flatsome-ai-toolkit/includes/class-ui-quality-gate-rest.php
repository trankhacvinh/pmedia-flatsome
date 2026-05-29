<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_UI_Quality_Gate_REST
{
    public static function register(): void
    {
        add_filter('rest_pre_dispatch', [__CLASS__, 'pre_dispatch'], 20, 3);
    }

    public static function pre_dispatch($result, WP_REST_Server $server, WP_REST_Request $request)
    {
        if ($result !== null) { return $result; }
        if ((string)$request->get_route() !== '/pmedia-ai/v1/page-builder/create-draft') { return $result; }
        $params = $request->get_json_params() ?: [];
        $validated = PMFAI_Page_Builder_AI::validate($params);
        if (is_wp_error($validated)) { return $validated; }
        $gate = PMFAI_UI_Quality_Gate::check_page($validated['page'], 'create draft');
        return is_wp_error($gate) ? $gate : $result;
    }
}
