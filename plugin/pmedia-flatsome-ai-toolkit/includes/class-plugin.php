<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Plugin
{
    public static function init(): void
    {
        if (!class_exists('PMFAI_Page_Section_Regenerator')) {
            require_once PMFAI_DIR . 'includes/class-page-section-regenerator.php';
        }
        if (!class_exists('PMFAI_Flatsome_UI_Skill_REST_Guard')) {
            require_once PMFAI_DIR . 'includes/class-flatsome-ui-skill-rest-guard.php';
        }
        if (!class_exists('PMFAI_UI_Quality_Gate')) {
            require_once PMFAI_DIR . 'includes/class-ui-quality-gate.php';
        }
        if (!class_exists('PMFAI_UI_Quality_Gate_REST')) {
            require_once PMFAI_DIR . 'includes/class-ui-quality-gate-rest.php';
        }

        add_action('init', ['PMFAI_Post_Types', 'register']);
        add_action('init', ['PMFAI_Usage_Logger', 'register']);
        add_action('init', ['PMFAI_Native_Shortcode_Sanitizer', 'register']);
        add_action('init', ['PMFAI_Flatsome_UI_Skill_REST_Guard', 'register']);
        add_action('init', ['PMFAI_UI_Quality_Gate_REST', 'register']);
        add_action('admin_menu', ['PMFAI_Admin_Pages', 'register_menu']);
        add_action('admin_menu', [__CLASS__, 'register_health_check_menu'], 30);
        add_action('admin_init', ['PMFAI_Settings', 'register']);
        add_action('add_meta_boxes', ['PMFAI_Page_Insert_Box', 'register']);
        add_action('admin_enqueue_scripts', ['PMFAI_Assets', 'admin']);
        add_action('wp_enqueue_scripts', ['PMFAI_Assets', 'frontend']);
        add_action('rest_api_init', ['PMFAI_REST_API', 'register']);
        add_action('rest_api_init', ['PMFAI_Page_Section_Regenerator', 'register_routes']);
        add_action('rest_api_init', ['PMFAI_AI_Provider_Tester', 'register_routes']);
        add_action('rest_api_init', ['PMFAI_Usage_Logs_V2', 'register_routes']);
    }

    public static function register_health_check_menu(): void
    {
        add_submenu_page('pmedia-ai-builder', 'Health Check', 'Health Check', 'manage_options', 'pmfai-health-check', ['PMFAI_Health_Check', 'render']);
    }

    public static function activate(): void
    {
        update_option(PMFAI_OPTION_KEY, wp_parse_args(get_option(PMFAI_OPTION_KEY, []), PMFAI_Settings::default_options()));
        PMFAI_Post_Types::register();
        PMFAI_Usage_Logger::register();
        flush_rewrite_rules();
    }
}
