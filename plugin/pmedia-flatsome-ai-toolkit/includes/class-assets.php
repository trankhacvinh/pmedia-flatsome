<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Assets
{
    public static function admin($hook): void
    {
        $is_pmfai_screen = strpos((string)$hook, 'pmfai') !== false || strpos((string)$hook, 'pmedia-ai-builder') !== false;
        $is_page_edit = in_array((string)$hook, ['post.php', 'post-new.php'], true) && self::is_page_edit_screen();

        if (!$is_pmfai_screen && !$is_page_edit) {
            return;
        }

        wp_enqueue_style('pmfai-admin', PMFAI_URL . 'assets/css/admin.css', [], PMFAI_VERSION);
        wp_enqueue_script('pmfai-admin', PMFAI_URL . 'assets/js/admin.js', [], PMFAI_VERSION, true);
        wp_localize_script('pmfai-admin', 'PMFAI', [
            'restUrl' => esc_url_raw(rest_url('pmedia-ai/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'tokens' => self::tokens_array(),
            'tokensCss' => self::tokens_css(),
        ]);

        if ($is_pmfai_screen) {
            wp_enqueue_script('pmfai-page-builder-output-mode', PMFAI_URL . 'assets/js/page-builder-output-mode.js', ['pmfai-admin'], PMFAI_VERSION, true);
        }

        if ($is_page_edit) {
            wp_enqueue_script('pmfai-page-insert-box', PMFAI_URL . 'assets/js/page-insert-box.js', ['pmfai-admin'], PMFAI_VERSION, true);
        }
    }

    private static function is_page_edit_screen(): bool
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->post_type === 'page') {
            return true;
        }
        return isset($_GET['post_type']) && sanitize_key((string)$_GET['post_type']) === 'page';
    }

    public static function frontend(): void
    {
        $options = PMFAI_Settings::get_options();
        if ($options['enable_frontend_css'] !== '1') {
            return;
        }

        wp_enqueue_style('pmfai-frontend', PMFAI_URL . 'assets/css/frontend.css', [], PMFAI_VERSION);
        wp_add_inline_style('pmfai-frontend', self::tokens_css());
    }

    public static function tokens_array(): array
    {
        $o = PMFAI_Settings::get_options();
        return [
            'primaryColor' => $o['primary_color'],
            'secondaryColor' => $o['secondary_color'],
            'accentColor' => $o['accent_color'],
            'textColor' => $o['text_color'],
            'mutedColor' => $o['muted_color'],
            'borderColor' => $o['border_color'],
            'bgSoftColor' => $o['bg_soft_color'],
            'radiusSm' => $o['radius_sm'],
            'radiusMd' => $o['radius_md'],
            'radiusLg' => $o['radius_lg'],
            'sectionPaddingDesktop' => $o['section_padding_desktop'],
            'sectionPaddingMobile' => $o['section_padding_mobile'],
        ];
    }

    public static function tokens_css(): string
    {
        $o = PMFAI_Settings::get_options();
        return ":root{--pm-color-primary:{$o['primary_color']};--pm-color-secondary:{$o['secondary_color']};--pm-color-accent:{$o['accent_color']};--pm-color-text:{$o['text_color']};--pm-color-muted:{$o['muted_color']};--pm-color-border:{$o['border_color']};--pm-color-bg-soft:{$o['bg_soft_color']};--pm-radius-sm:{$o['radius_sm']};--pm-radius-md:{$o['radius_md']};--pm-radius-lg:{$o['radius_lg']};--pm-section-padding:{$o['section_padding_desktop']};--pm-section-padding-mobile:{$o['section_padding_mobile']};--pm-shadow-sm:0 4px 16px rgba(15,23,42,.08);--pm-shadow-md:0 14px 36px rgba(15,23,42,.12)}";
    }
}
