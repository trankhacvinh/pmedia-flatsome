<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Plugin
{
    public static function init(): void
    {
        add_action('init', ['PMFAI_Post_Types', 'register']);
        add_action('admin_menu', ['PMFAI_Admin_Pages', 'register_menu']);
        add_action('admin_init', ['PMFAI_Settings', 'register']);
        add_action('admin_enqueue_scripts', ['PMFAI_Assets', 'admin']);
        add_action('wp_enqueue_scripts', ['PMFAI_Assets', 'frontend']);
        add_action('rest_api_init', ['PMFAI_REST_API', 'register']);
    }

    public static function activate(): void
    {
        update_option(PMFAI_OPTION_KEY, wp_parse_args(get_option(PMFAI_OPTION_KEY, []), PMFAI_Settings::default_options()));
        PMFAI_Post_Types::register();
        flush_rewrite_rules();
    }
}
