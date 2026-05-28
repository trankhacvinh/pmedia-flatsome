<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Health_Check
{
    public static function collect(): array
    {
        $options = PMFAI_Settings::get_options();
        $provider = PMFAI_AI_Provider_Manager::provider_id($options);
        $theme = wp_get_theme();
        $parent = $theme->parent();
        $theme_name = $theme->get('Name');
        $parent_name = $parent ? $parent->get('Name') : '';
        $is_flatsome = stripos($theme_name, 'flatsome') !== false || stripos($parent_name, 'flatsome') !== false;

        $checks = [];
        $checks[] = self::check('WordPress', get_bloginfo('version'), true, 'Phiên bản WordPress hiện tại.');
        $checks[] = self::check('PHP', PHP_VERSION, version_compare(PHP_VERSION, '7.4', '>='), 'Plugin yêu cầu PHP >= 7.4.');
        $checks[] = self::check('Theme Flatsome', $theme_name . ($parent_name ? ' / Parent: ' . $parent_name : ''), $is_flatsome, 'Nên dùng theme Flatsome hoặc child theme của Flatsome.');
        $checks[] = self::check('Frontend CSS Toolkit', $options['enable_frontend_css'] === '1' ? 'Enabled' : 'Disabled', $options['enable_frontend_css'] === '1', 'Nên bật để các class pm-* hiển thị đúng ở frontend.');
        $checks[] = self::check('REST API', esc_url_raw(rest_url('pmedia-ai/v1')), true, 'REST namespace của plugin.');
        $checks[] = self::provider_check($provider, $options);
        $checks[] = self::check('Provider model', PMFAI_AI_Provider_Manager::model($provider, $options), true, 'Model mặc định theo provider đang chọn.');
        $checks[] = self::check('Usage logs post type', post_type_exists('pmedia_ai_usage') ? 'Registered' : 'Missing', post_type_exists('pmedia_ai_usage'), 'Dùng để ghi log lượt gọi AI.');
        $checks[] = self::check('PHP compat helpers', function_exists('pmfai_str_starts_with') ? 'Loaded' : 'Missing', function_exists('pmfai_str_starts_with'), 'Hỗ trợ PHP 7.4 cho str_starts_with/str_ends_with.');
        $checks[] = self::check('Anthropic max tokens', (string)($options['anthropic_max_tokens'] ?? '4096'), absint($options['anthropic_max_tokens'] ?? 0) >= 512, 'Áp dụng khi chọn Claude.');

        $status = 'ok';
        foreach ($checks as $check) {
            if ($check['status'] === 'error') { $status = 'error'; break; }
            if ($check['status'] === 'warning') { $status = 'warning'; }
        }

        return [
            'status' => $status,
            'provider' => $provider,
            'provider_label' => PMFAI_AI_Provider_Manager::provider_label($provider),
            'checks' => $checks,
        ];
    }

    private static function provider_check(string $provider, array $options): array
    {
        $key = '';
        $endpoint = '';
        if ($provider === 'anthropic') {
            $key = trim((string)($options['anthropic_api_key'] ?? ''));
            $endpoint = trim((string)($options['anthropic_endpoint'] ?? ''));
        } elseif ($provider === 'openai_compatible') {
            $key = trim((string)($options['compatible_api_key'] ?? ''));
            $endpoint = trim((string)($options['compatible_endpoint'] ?? ''));
        } else {
            $key = trim((string)($options['api_key'] ?? ''));
            $endpoint = trim((string)($options['api_endpoint'] ?? ''));
        }

        $ok = $key !== '' && $endpoint !== '';
        return self::check('AI Provider', PMFAI_AI_Provider_Manager::provider_label($provider) . ' / ' . ($endpoint ?: 'missing endpoint'), $ok, $ok ? 'Provider đã có endpoint và API key.' : 'Provider đang chọn thiếu endpoint hoặc API key.');
    }

    private static function check(string $name, string $value, bool $ok, string $note = '', string $warning_if = ''): array
    {
        return [
            'name' => $name,
            'value' => $value,
            'status' => $ok ? 'ok' : ($warning_if ? 'warning' : 'error'),
            'note' => $note,
        ];
    }

    public static function render(): void
    {
        $data = self::collect();
        echo '<div class="wrap pmfai-wrap">';
        echo '<div class="pmfai-header"><div><h1>Pmedia AI Health Check</h1><p>Kiểm tra nhanh môi trường, provider, REST route và các thành phần quan trọng.</p></div><span class="pmfai-badge">v' . esc_html(PMFAI_VERSION) . '</span></div>';
        echo '<div class="pmfai-panel"><h2>Trạng thái tổng quan: ' . esc_html(strtoupper($data['status'])) . '</h2>';
        echo '<p><span class="pmfai-score">Provider: ' . esc_html($data['provider_label']) . '</span></p>';
        echo '<table class="widefat striped"><thead><tr><th>Check</th><th>Value</th><th>Status</th><th>Ghi chú</th></tr></thead><tbody>';
        foreach ($data['checks'] as $check) {
            $color = $check['status'] === 'ok' ? '#166534' : ($check['status'] === 'warning' ? '#92400e' : '#991b1b');
            echo '<tr><td><strong>' . esc_html($check['name']) . '</strong></td><td>' . esc_html($check['value']) . '</td><td><strong style="color:' . esc_attr($color) . '">' . esc_html(strtoupper($check['status'])) . '</strong></td><td>' . esc_html($check['note']) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '<p style="margin-top:16px"><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=pmfai-settings')) . '">Mở Settings</a> <a class="button" href="' . esc_url(rest_url('pmedia-ai/v1')) . '" target="_blank">Mở REST Namespace</a></p>';
        echo '</div></div>';
    }
}
