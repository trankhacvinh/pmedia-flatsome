<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_REST_API
{
    public static function register(): void
    {
        register_rest_route('pmedia-ai/v1', '/bridge-prompt', ['methods' => 'POST', 'callback' => [__CLASS__, 'bridge_prompt'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/parse-chatgpt-block', ['methods' => 'POST', 'callback' => [__CLASS__, 'parse_chatgpt_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/validate-code', ['methods' => 'POST', 'callback' => [__CLASS__, 'validate_code'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/auto-fix-code', ['methods' => 'POST', 'callback' => [__CLASS__, 'auto_fix_code'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks', ['methods' => 'GET', 'callback' => [__CLASS__, 'list_blocks'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks', ['methods' => 'POST', 'callback' => [__CLASS__, 'save_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks/(?P<id>\d+)', ['methods' => 'GET', 'callback' => [__CLASS__, 'get_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks/(?P<id>\d+)', ['methods' => 'DELETE', 'callback' => [__CLASS__, 'delete_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
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

    public static function auto_fix_code(WP_REST_Request $request)
    {
        return rest_ensure_response(PMFAI_Code_Auto_Fixer::fix($request->get_param('html') ?: '', $request->get_param('css') ?: ''));
    }

    public static function list_blocks(WP_REST_Request $request)
    {
        return rest_ensure_response(PMFAI_Block_Library::list([
            'posts_per_page' => $request->get_param('per_page') ?: 30,
            'paged' => $request->get_param('page') ?: 1,
            's' => $request->get_param('s') ?: '',
            'type' => $request->get_param('type') ?: '',
        ]));
    }

    public static function save_block(WP_REST_Request $request)
    {
        $params = $request->get_json_params() ?: [];
        $saved = PMFAI_Block_Library::save($params);
        return is_wp_error($saved) ? $saved : rest_ensure_response($saved);
    }

    public static function get_block(WP_REST_Request $request)
    {
        $block = PMFAI_Block_Library::get(absint($request['id']));
        return is_wp_error($block) ? $block : rest_ensure_response($block);
    }

    public static function delete_block(WP_REST_Request $request)
    {
        $deleted = PMFAI_Block_Library::delete(absint($request['id']));
        return is_wp_error($deleted) ? $deleted : rest_ensure_response($deleted);
    }
}
