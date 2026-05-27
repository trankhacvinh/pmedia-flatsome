<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Code_Auto_Fixer
{
    public static function fix(string $html, string $css): array
    {
        $changes = [];
        $html = wp_unslash($html);
        $css = wp_unslash($css);

        $html = self::remove_document_tags($html, $changes);
        $html = self::ensure_wrapper($html, $changes);
        $html = self::replace_common_classes_in_html($html, $changes);
        $css = self::remove_global_reset_rules($css, $changes);
        $css = self::replace_common_classes_in_css($css, $changes);
        $css = self::replace_token_colors($css, $changes);
        $css = self::scope_custom_css($css, $changes);
        $css = self::reduce_important($css, $changes);

        $analysis = PMFAI_Code_Validator::analyze($html, $css);

        return [
            'html' => trim($html),
            'css' => trim($css),
            'changes' => array_values(array_unique($changes)),
            'warnings' => $analysis['warnings'],
            'suggestions' => $analysis['suggestions'],
            'scores' => $analysis['scores'],
        ];
    }

    private static function remove_document_tags(string $html, array &$changes): string
    {
        $original = $html;
        $html = preg_replace('/<!doctype[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?head[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
        $html = preg_replace('/<meta[^>]*>/i', '', $html);
        $html = preg_replace('/<title[^>]*>.*?<\/title>/is', '', $html);
        if ($html !== $original) {
            $changes[] = 'Đã xóa document tags như html/head/body/meta/title.';
        }
        return trim($html);
    }

    private static function ensure_wrapper(string $html, array &$changes): string
    {
        if (!preg_match('/pmedia-ai-block/i', $html)) {
            $html = '<section class="pm-section pmedia-ai-block">' . "\n" . trim($html) . "\n" . '</section>';
            $changes[] = 'Đã thêm wrapper <section class="pm-section pmedia-ai-block">.';
        }
        return $html;
    }

    private static function replace_common_classes_in_html(string $html, array &$changes): string
    {
        $map = self::class_map();
        foreach ($map as $from => $to) {
            $pattern = '/class=("|\')([^"\']*\b)' . preg_quote($from, '/') . '(\b[^"\']*)("|\')/i';
            $html = preg_replace_callback($pattern, function ($m) use ($from, $to, &$changes) {
                $classes = preg_replace('/\b' . preg_quote($from, '/') . '\b/i', $to, $m[2] . $from . $m[3]);
                $changes[] = "Đã đổi class .$from thành .$to trong HTML.";
                return 'class=' . $m[1] . trim($classes) . $m[4];
            }, $html);
        }
        return $html;
    }

    private static function remove_global_reset_rules(string $css, array &$changes): string
    {
        $original = $css;
        $selectors = ['\\*', 'body', 'html', 'a', 'img', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        foreach ($selectors as $selector) {
            $css = preg_replace('/(^|\})\s*' . $selector . '\s*\{[^{}]*\}/im', '$1', $css);
        }
        if ($css !== $original) {
            $changes[] = 'Đã xóa một số global reset selectors nguy hiểm.';
        }
        return trim($css);
    }

    private static function replace_common_classes_in_css(string $css, array &$changes): string
    {
        $map = self::class_map();
        foreach ($map as $from => $to) {
            $new = preg_replace('/\.' . preg_quote($from, '/') . '\b/i', '.' . $to, $css);
            if ($new !== $css) {
                $changes[] = "Đã đổi selector .$from thành .$to trong CSS.";
                $css = $new;
            }
        }
        return $css;
    }

    private static function replace_token_colors(string $css, array &$changes): string
    {
        $options = PMFAI_Settings::get_options();
        $tokenMap = [
            strtolower($options['primary_color']) => 'var(--pm-color-primary)',
            strtolower($options['secondary_color']) => 'var(--pm-color-secondary)',
            strtolower($options['accent_color']) => 'var(--pm-color-accent)',
            strtolower($options['text_color']) => 'var(--pm-color-text)',
            strtolower($options['muted_color']) => 'var(--pm-color-muted)',
            strtolower($options['border_color']) => 'var(--pm-color-border)',
            strtolower($options['bg_soft_color']) => 'var(--pm-color-bg-soft)',
        ];

        foreach ($tokenMap as $hex => $token) {
            if ($hex && strpos(strtolower($css), $hex) !== false) {
                $css = str_ireplace($hex, $token, $css);
                $changes[] = "Đã thay màu $hex bằng $token.";
            }
        }

        return $css;
    }

    private static function scope_custom_css(string $css, array &$changes): string
    {
        $css = trim($css);
        if ($css === '') {
            return $css;
        }

        $scoped = preg_replace_callback('/(^|\})([^@{}][^{}]*)\{/m', function ($m) use (&$changes) {
            $prefix = $m[1];
            $selectors = trim($m[2]);
            $parts = array_map('trim', explode(',', $selectors));
            $newParts = [];
            foreach ($parts as $selector) {
                if ($selector === '' || stripos($selector, '.pmedia-ai-block') !== false || stripos($selector, ':root') !== false) {
                    $newParts[] = $selector;
                    continue;
                }
                if (preg_match('/^(from|to|\d+%|@)/i', $selector)) {
                    $newParts[] = $selector;
                    continue;
                }
                $newParts[] = '.pmedia-ai-block ' . $selector;
            }
            if (implode(', ', $newParts) !== $selectors) {
                $changes[] = 'Đã scope một số CSS selector vào .pmedia-ai-block.';
            }
            return $prefix . implode(', ', $newParts) . '{';
        }, $css);

        return trim($scoped);
    }

    private static function reduce_important(string $css, array &$changes): string
    {
        $count = 0;
        $css = preg_replace('/\s*!important/i', '', $css, -1, $count);
        if ($count > 0) {
            $changes[] = "Đã xóa $count lần !important.";
        }
        return $css;
    }

    private static function class_map(): array
    {
        return [
            'card' => 'pm-card',
            'title' => 'pm-section-title',
            'lead' => 'pm-lead',
            'muted' => 'pm-muted',
            'badge' => 'pm-eyebrow',
            'cta' => 'pm-cta-box',
            'step' => 'pm-step',
            'pricing-card' => 'pm-pricing-card',
            'feature-list' => 'pm-feature-list',
        ];
    }
}
