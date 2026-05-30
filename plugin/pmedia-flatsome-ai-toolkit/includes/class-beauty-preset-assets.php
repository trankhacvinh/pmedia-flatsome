<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Beauty_Preset_Assets
{
    public static function register(): void
    {
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin']);
    }

    public static function admin($hook): void
    {
        $is_pmfai_screen = strpos((string)$hook, 'pmfai') !== false || strpos((string)$hook, 'pmedia-ai-builder') !== false;
        if (!$is_pmfai_screen) { return; }
        wp_enqueue_script('pmfai-beauty-preset-ui', PMFAI_URL . 'assets/js/beauty-preset-ui.js', ['pmfai-admin'], PMFAI_VERSION, true);
    }
}

add_action('plugins_loaded', ['PMFAI_Beauty_Preset_Assets', 'register'], 20);
