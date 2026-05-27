<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_REST_API
{
    public static function register(): void
    {
        register_rest_route('pmedia-ai/v1', '/bridge-prompt', ['methods' => 'POST', 'callback' => [__CLASS__, 'bridge_prompt'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/generate-block', ['methods' => 'POST', 'callback' => [__CLASS__, 'generate_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/design-system/bridge-prompt', ['methods' => 'POST', 'callback' => [__CLASS__, 'design_system_bridge_prompt'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/design-system/generate', ['methods' => 'POST', 'callback' => [__CLASS__, 'design_system_generate'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/design-system/import', ['methods' => 'POST', 'callback' => [__CLASS__, 'design_system_import'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/design-system/apply', ['methods' => 'POST', 'callback' => [__CLASS__, 'design_system_apply'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/page-builder/bridge-prompt', ['methods' => 'POST', 'callback' => [__CLASS__, 'page_builder_bridge_prompt'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/page-builder/generate', ['methods' => 'POST', 'callback' => [__CLASS__, 'page_builder_generate'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/page-builder/import', ['methods' => 'POST', 'callback' => [__CLASS__, 'page_builder_import'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/page-builder/create-draft', ['methods' => 'POST', 'callback' => [__CLASS__, 'page_builder_create_draft'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/parse-chatgpt-block', ['methods' => 'POST', 'callback' => [__CLASS__, 'parse_chatgpt_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/validate-code', ['methods' => 'POST', 'callback' => [__CLASS__, 'validate_code'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/auto-fix-code', ['methods' => 'POST', 'callback' => [__CLASS__, 'auto_fix_code'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/usage-logs', ['methods' => 'GET', 'callback' => [__CLASS__, 'usage_logs'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks', ['methods' => 'GET', 'callback' => [__CLASS__, 'list_blocks'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks', ['methods' => 'POST', 'callback' => [__CLASS__, 'save_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks/import', ['methods' => 'POST', 'callback' => [__CLASS__, 'import_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks/(?P<id>\d+)', ['methods' => 'GET', 'callback' => [__CLASS__, 'get_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks/(?P<id>\d+)', ['methods' => 'PUT', 'callback' => [__CLASS__, 'update_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks/(?P<id>\d+)', ['methods' => 'DELETE', 'callback' => [__CLASS__, 'delete_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks/(?P<id>\d+)/duplicate', ['methods' => 'POST', 'callback' => [__CLASS__, 'duplicate_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/blocks/(?P<id>\d+)/export', ['methods' => 'GET', 'callback' => [__CLASS__, 'export_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
    }

    public static function can_manage(): bool { return current_user_can('manage_options'); }

    public static function bridge_prompt(WP_REST_Request $request)
    {
        $params = $request->get_json_params() ?: [];
        return rest_ensure_response(['prompt' => PMFAI_Prompt_Builder::build(sanitize_key($params['type'] ?? 'generate-from-description'), $params)]);
    }

    public static function generate_block(WP_REST_Request $request)
    {
        $generated = PMFAI_AI_Service::generate_block($request->get_json_params() ?: []);
        return is_wp_error($generated) ? $generated : rest_ensure_response($generated);
    }

    public static function design_system_bridge_prompt(WP_REST_Request $request)
    {
        return rest_ensure_response(['prompt' => PMFAI_Design_System_AI::bridge_prompt($request->get_json_params() ?: [])]);
    }

    public static function design_system_generate(WP_REST_Request $request)
    {
        $result = PMFAI_Design_System_AI::generate($request->get_json_params() ?: []);
        return is_wp_error($result) ? $result : rest_ensure_response($result);
    }

    public static function design_system_import(WP_REST_Request $request)
    {
        $result = PMFAI_Design_System_AI::parse_json($request->get_param('raw') ?: '');
        return is_wp_error($result) ? $result : rest_ensure_response($result);
    }

    public static function design_system_apply(WP_REST_Request $request)
    {
        $result = PMFAI_Design_System_AI::apply($request->get_json_params() ?: []);
        return is_wp_error($result) ? $result : rest_ensure_response($result);
    }

    public static function page_builder_bridge_prompt(WP_REST_Request $request)
    {
        return rest_ensure_response(['prompt' => PMFAI_Page_Builder_AI::bridge_prompt($request->get_json_params() ?: [])]);
    }

    public static function page_builder_generate(WP_REST_Request $request)
    {
        $result = PMFAI_Page_Builder_AI::generate($request->get_json_params() ?: []);
        return is_wp_error($result) ? $result : rest_ensure_response($result);
    }

    public static function page_builder_import(WP_REST_Request $request)
    {
        $result = PMFAI_Page_Builder_AI::parse_json($request->get_param('raw') ?: '');
        return is_wp_error($result) ? $result : rest_ensure_response($result);
    }

    public static function page_builder_create_draft(WP_REST_Request $request)
    {
        $result = PMFAI_Page_Builder_AI::create_draft($request->get_json_params() ?: []);
        return is_wp_error($result) ? $result : rest_ensure_response($result);
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

    public static function usage_logs(WP_REST_Request $request)
    {
        return rest_ensure_response(PMFAI_Usage_Logger::list([
            'posts_per_page' => $request->get_param('per_page') ?: 50,
            'paged' => $request->get_param('page') ?: 1,
            'status' => $request->get_param('status') ?: '',
            'mode' => $request->get_param('mode') ?: '',
        ]));
    }

    public static function list_blocks(WP_REST_Request $request)
    {
        return rest_ensure_response(PMFAI_Block_Library::list([
            'posts_per_page' => $request->get_param('per_page') ?: 30,
            'paged' => $request->get_param('page') ?: 1,
            's' => $request->get_param('s') ?: '',
            'type' => $request->get_param('type') ?: '',
            'industry' => $request->get_param('industry') ?: '',
        ]));
    }

    public static function save_block(WP_REST_Request $request)
    {
        $saved = PMFAI_Block_Library::save($request->get_json_params() ?: []);
        return is_wp_error($saved) ? $saved : rest_ensure_response($saved);
    }

    public static function import_block(WP_REST_Request $request)
    {
        $imported = PMFAI_Block_Library::import($request->get_json_params() ?: []);
        return is_wp_error($imported) ? $imported : rest_ensure_response($imported);
    }

    public static function get_block(WP_REST_Request $request)
    {
        $block = PMFAI_Block_Library::get(absint($request['id']));
        return is_wp_error($block) ? $block : rest_ensure_response($block);
    }

    public static function update_block(WP_REST_Request $request)
    {
        $updated = PMFAI_Block_Library::update(absint($request['id']), $request->get_json_params() ?: []);
        return is_wp_error($updated) ? $updated : rest_ensure_response($updated);
    }

    public static function duplicate_block(WP_REST_Request $request)
    {
        $duplicated = PMFAI_Block_Library::duplicate(absint($request['id']));
        return is_wp_error($duplicated) ? $duplicated : rest_ensure_response($duplicated);
    }

    public static function export_block(WP_REST_Request $request)
    {
        $export = PMFAI_Block_Library::export(absint($request['id']));
        return is_wp_error($export) ? $export : rest_ensure_response($export);
    }

    public static function delete_block(WP_REST_Request $request)
    {
        $deleted = PMFAI_Block_Library::delete(absint($request['id']));
        return is_wp_error($deleted) ? $deleted : rest_ensure_response($deleted);
    }
}
