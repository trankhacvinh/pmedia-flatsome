<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_REST_API
{
    public static function register(): void
    {
        register_rest_route('pmedia-ai/v1', '/bridge-prompt', ['methods' => 'POST', 'callback' => [__CLASS__, 'bridge_prompt'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/parse-chatgpt-block', ['methods' => 'POST', 'callback' => [__CLASS__, 'parse_chatgpt_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/validate-code', ['methods' => 'POST', 'callback' => [__CLASS__, 'validate_code'], 'permission_callback' => [__CLASS__, 'can_manage']]);
    }

    public static function can_manage(): bool
    {
        return current_user_can('manage_options');
    }

    public static function bridge_prompt(WP_REST_Request $request)
    {
        $params = $request->get_json_params() ?: [];
        return rest_ensure_response(['prompt' => PMFAI_Prompt_Builder::build(sanitize_key($params['type'] ?? 'generate-from-description'), $params)]);
    }

    public static function parse_chatgpt_block(WP_REST_Request $request)
    {
        $parsed = PMFAI_Code_Validator::parse_block($request->get_param('raw') ?: '');
        return is_wp_error($parsed) ? $parsed : rest_ensure_response($parsed);
    }

    public static function validate_code(WP_REST_Request $request)
    {
        return rest_ensure_response(PMFAI_Code_Validator::analyze($request->get_param('html') ?: '', $request->get_param('css') ?: ''));
    }
}
