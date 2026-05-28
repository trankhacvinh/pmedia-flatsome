<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Settings
{
    public static function default_options(): array
    {
        return [
            'ai_provider' => 'openai',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
            'api_key' => '',
            'api_model' => 'gpt-4.1-mini',
            'compatible_endpoint' => '',
            'compatible_api_key' => '',
            'compatible_model' => '',
            'anthropic_endpoint' => 'https://api.anthropic.com/v1/messages',
            'anthropic_api_key' => '',
            'anthropic_model' => 'claude-3-5-sonnet-latest',
            'temperature' => '0.4',
            'fast_model' => '',
            'fast_temperature' => '0.2',
            'balanced_model' => '',
            'balanced_temperature' => '0.4',
            'high_model' => '',
            'high_temperature' => '0.65',
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
            if ($key === 'ai_provider') {
                $provider = sanitize_key($value);
                $output[$key] = in_array($provider, ['openai', 'openai_compatible', 'anthropic'], true) ? $provider : 'openai';
            } elseif (strpos($key, 'color') !== false) {
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
