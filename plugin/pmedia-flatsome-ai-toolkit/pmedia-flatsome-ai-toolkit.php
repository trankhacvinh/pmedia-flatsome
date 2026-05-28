<?php
/**
 * Plugin Name: Pmedia Flatsome AI Toolkit
 * Plugin URI: https://pmedia.vn
 * Description: Design System, ChatGPT Bridge, Import/Validate block, Page Builder AI và CSS Toolkit cho Flatsome.
 * Version: 2.5.2
 * Author: Pmedia
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: pmedia-flatsome-ai-toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PMFAI_VERSION', '2.5.2');
define('PMFAI_FILE', __FILE__);
define('PMFAI_DIR', plugin_dir_path(__FILE__));
define('PMFAI_URL', plugin_dir_url(__FILE__));
define('PMFAI_OPTION_KEY', 'pmedia_flatsome_ai_toolkit_options');

require_once PMFAI_DIR . 'includes/functions-compat.php';
require_once PMFAI_DIR . 'includes/class-settings.php';
require_once PMFAI_DIR . 'includes/class-post-types.php';
require_once PMFAI_DIR . 'includes/class-prompt-builder.php';
require_once PMFAI_DIR . 'includes/class-usage-logger.php';
require_once PMFAI_DIR . 'includes/class-usage-logs-v2.php';
require_once PMFAI_DIR . 'includes/providers/class-ai-provider-interface.php';
require_once PMFAI_DIR . 'includes/providers/class-openai-provider.php';
require_once PMFAI_DIR . 'includes/providers/class-anthropic-provider.php';
require_once PMFAI_DIR . 'includes/class-ai-provider-manager.php';
require_once PMFAI_DIR . 'includes/class-ai-provider-tester.php';
require_once PMFAI_DIR . 'includes/class-section-patterns.php';
require_once PMFAI_DIR . 'includes/class-section-pattern-templates.php';
require_once PMFAI_DIR . 'includes/class-visual-quality-validator.php';
require_once PMFAI_DIR . 'includes/class-design-system-ai.php';
require_once PMFAI_DIR . 'includes/class-page-builder-ai.php';
require_once PMFAI_DIR . 'includes/class-page-section-regenerator.php';
require_once PMFAI_DIR . 'includes/class-page-insert-box.php';
require_once PMFAI_DIR . 'includes/class-ai-service.php';
require_once PMFAI_DIR . 'includes/class-code-validator.php';
require_once PMFAI_DIR . 'includes/class-code-auto-fixer.php';
require_once PMFAI_DIR . 'includes/class-block-library.php';
require_once PMFAI_DIR . 'includes/class-assets.php';
require_once PMFAI_DIR . 'includes/class-rest-api.php';
require_once PMFAI_DIR . 'includes/class-admin-pages.php';
require_once PMFAI_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, ['PMFAI_Plugin', 'activate']);
add_action('plugins_loaded', ['PMFAI_Plugin', 'init']);
