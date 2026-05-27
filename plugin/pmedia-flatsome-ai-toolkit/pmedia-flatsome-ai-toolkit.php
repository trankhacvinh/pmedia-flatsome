<?php
/**
 * Plugin Name: Pmedia Flatsome AI Toolkit
 * Plugin URI: https://pmedia.vn
 * Description: Design System, ChatGPT Bridge, Import/Validate block và CSS Toolkit cho Flatsome.
 * Version: 1.7.0
 * Author: Pmedia
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: pmedia-flatsome-ai-toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PMFAI_VERSION', '1.7.0');
define('PMFAI_FILE', __FILE__);
define('PMFAI_DIR', plugin_dir_path(__FILE__));
define('PMFAI_URL', plugin_dir_url(__FILE__));
define('PMFAI_OPTION_KEY', 'pmedia_flatsome_ai_toolkit_options');

require_once PMFAI_DIR . 'includes/class-settings.php';
require_once PMFAI_DIR . 'includes/class-post-types.php';
require_once PMFAI_DIR . 'includes/class-prompt-builder.php';
require_once PMFAI_DIR . 'includes/class-code-validator.php';
require_once PMFAI_DIR . 'includes/class-code-auto-fixer.php';
require_once PMFAI_DIR . 'includes/class-block-library.php';
require_once PMFAI_DIR . 'includes/class-assets.php';
require_once PMFAI_DIR . 'includes/class-rest-api.php';
require_once PMFAI_DIR . 'includes/class-admin-pages.php';
require_once PMFAI_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, ['PMFAI_Plugin', 'activate']);
add_action('plugins_loaded', ['PMFAI_Plugin', 'init']);
