<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Settings
{
    public static function default_options(): array
    {
        return [
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
            'api_key' => '',
            'api_model' => 'gpt-4.1-mini',
            'temperature' => '0.4',
            'primary_color' => '#e31e24',
            'secondary_color' => '#111827',
            'accent_color' => '#f59e0b',
            'text_color' => '#1f2937',
            'muted_color' => '#6b7280',
            'border_color' => '#e5e7eb',
            'bg_soft_color' => '#f9fafb',
            'radius_sm' => '8px',
            'radius_md' => '16px',
            'radius_lg' => '24px',
            'section_padding_desktop' => '72px',
            'section_padding_mobile' => '42px',
            'enable_frontend_css' => '1',
            'extra_rules' => 'Ưu tiên dùng class Flatsome: row, col, col-inner, button primary, is-large. Không dùng Bootstrap/Tailwind.',
        ];
    }

    public static function get_options(): array
    {
        return wp_parse_args(get_option(PMFAI_OPTION_KEY, []), self::default_options());
    }

    public static function register(): void
    {
        register_setting('pmfai_settings_group', PMFAI_OPTION_KEY, [
            'sanitize_callback' => [__CLASS__, 'sanitize_options'],
        ]);
    }

    public static function sanitize_options($input): array
    {
        $defaults = self::default_options();
        $current = self::get_options();
        $output = [];

        foreach ($defaults as $key => $default) {
            $value = $input[$key] ?? ($current[$key] ?? $default);
            if (strpos($key, 'color') !== false) {
                $output[$key] = sanitize_hex_color($value) ?: $default;
            } elseif ($key === 'extra_rules') {
                $output[$key] = sanitize_textarea_field($value);
            } elseif ($key === 'enable_frontend_css') {
                $output[$key] = !empty($value) ? '1' : '0';
            } else {
                $output[$key] = sanitize_text_field($value);
            }
        }

        return $output;
    }
}
