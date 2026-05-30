<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Beauty_Preset_REST
{
    public static function register(): void
    {
        register_rest_route('pmedia-ai/v1', '/beauty-preset/apply', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'apply'],
            'permission_callback' => [__CLASS__, 'can_manage'],
        ]);
        register_rest_route('pmedia-ai/v1', '/beauty-preset/list', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list'],
            'permission_callback' => [__CLASS__, 'can_manage'],
        ]);
    }

    public static function register_assets(): void
    {
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
    }

    public static function admin_assets($hook): void
    {
        $is_pmfai_screen = strpos((string)$hook, 'pmfai') !== false || strpos((string)$hook, 'pmedia-ai-builder') !== false;
        if (!$is_pmfai_screen) { return; }
        wp_enqueue_script('pmfai-beauty-preset-ui', PMFAI_URL . 'assets/js/beauty-preset-ui.js', ['pmfai-admin'], PMFAI_VERSION, true);
    }

    public static function can_manage(): bool
    {
        return current_user_can('manage_options');
    }

    public static function apply(WP_REST_Request $request)
    {
        $params = $request->get_json_params() ?: [];
        $preset = sanitize_key((string)($params['preset'] ?? ''));
        if (!$preset) {
            return new WP_Error('missing_preset', 'Thiếu Beauty Preset.', ['status' => 400]);
        }
        $all = PMFAI_Beauty_Presets::all();
        if (!isset($all[$preset])) {
            return new WP_Error('invalid_preset', 'Beauty Preset không hợp lệ.', ['status' => 400]);
        }
        return rest_ensure_response(PMFAI_Beauty_Presets::apply_tokens($preset));
    }

    public static function list(WP_REST_Request $request)
    {
        $items = [];
        foreach (PMFAI_Beauty_Presets::all() as $id => $preset) {
            $items[$id] = [
                'id' => $id,
                'name' => $preset['name'] ?? $id,
                'best_for' => $preset['best_for'] ?? '',
                'visual_rules' => $preset['visual_rules'] ?? '',
                'avoid' => $preset['avoid'] ?? '',
                'tokens' => $preset['tokens'] ?? [],
            ];
        }
        return rest_ensure_response(['items' => $items, 'current' => PMFAI_Settings::get_options()['beauty_preset'] ?? 'corporate_blue']);
    }
}

add_action('plugins_loaded', ['PMFAI_Beauty_Preset_REST', 'register_assets'], 20);
