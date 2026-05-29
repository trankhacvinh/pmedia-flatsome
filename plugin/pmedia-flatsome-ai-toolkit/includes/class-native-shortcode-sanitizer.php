<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Native_Shortcode_Sanitizer
{
    public static function register(): void
    {
        add_filter('wp_insert_post_data', [__CLASS__, 'filter_insert_post_data'], 20, 2);
    }

    public static function filter_insert_post_data(array $data, array $postarr): array
    {
        if (($data['post_type'] ?? '') !== 'page') {
            return $data;
        }
        $content = (string)($data['post_content'] ?? '');
        if (!self::looks_like_pmfai_native_content($content)) {
            return $data;
        }
        $data['post_content'] = self::sanitize_content($content);
        return $data;
    }

    private static function looks_like_pmfai_native_content(string $content): bool
    {
        return stripos($content, 'pm-pattern-') !== false
            || stripos($content, 'pm-native-section') !== false
            || stripos($content, '[pm-') !== false
            || stripos($content, 'pm-service-card') !== false
            || stripos($content, 'pm-process-timeline') !== false
            || stripos($content, 'pm-hero-visual') !== false;
    }

    public static function sanitize_content(string $content): string
    {
        $content = trim($content);
        $content = preg_replace_callback('/\[section([^\]]*)\]([\s\S]*?)\[\/section\]/i', function ($m) {
            $attrs = (string)$m[1];
            $inner = (string)$m[2];
            return self::sanitize_section($attrs, $inner);
        }, $content);
        return trim($content);
    }

    private static function sanitize_section(string $attrs, string $inner): string
    {
        $class = self::attr_value($attrs, 'class');
        $class = trim($class . ' pm-native-safe');
        $html = self::shortcode_inner_to_html($inner);

        return '[section class="' . esc_attr($class ?: 'pm-native-safe') . '"]' . "\n"
            . '[row]' . "\n"
            . '[col span__sm="12"]' . "\n"
            . '[ux_text]' . "\n"
            . $html . "\n"
            . '[/ux_text]' . "\n"
            . '[/col]' . "\n"
            . '[/row]' . "\n"
            . '[/section]';
    }

    private static function shortcode_inner_to_html(string $value): string
    {
        $value = self::convert_pm_pseudo_shortcodes($value);
        $value = self::convert_title_shortcodes($value);
        $value = self::convert_button_shortcodes($value);
        $value = self::convert_accordion_shortcodes($value);
        $value = self::convert_row_col_shortcodes($value);
        $value = self::strip_ux_text_wrappers($value);
        $value = self::strip_unknown_layout_shortcodes($value);
        return trim($value);
    }

    private static function convert_row_col_shortcodes(string $value): string
    {
        $value = preg_replace_callback('/\[row([^\]]*)\]/i', function ($m) {
            $class = self::attr_value((string)$m[1], 'class');
            return '<div class="pm-native-row ' . esc_attr($class) . '">';
        }, $value);
        $value = preg_replace('/\[\/row\]/i', '</div>', $value);

        $value = preg_replace_callback('/\[col([^\]]*)\]/i', function ($m) {
            $attrs = (string)$m[1];
            $span = self::attr_value($attrs, 'span');
            $class = self::attr_value($attrs, 'class');
            $classes = trim('pm-native-col ' . ($span ? 'pm-native-col-' . sanitize_html_class($span) : '') . ' ' . $class);
            return '<div class="' . esc_attr($classes) . '">';
        }, $value);
        $value = preg_replace('/\[\/col\]/i', '</div>', $value);
        return $value;
    }

    private static function strip_ux_text_wrappers(string $value): string
    {
        $value = preg_replace('/\[ux_text[^\]]*\]/i', '', $value);
        $value = preg_replace('/\[\/ux_text\]/i', '', $value);
        return $value;
    }

    private static function convert_title_shortcodes(string $value): string
    {
        $value = preg_replace_callback('/\[title([^\]]*)\]([\s\S]*?)\[\/title\]/i', function ($m) {
            $tag = self::attr_value((string)$m[1], 'tag') ?: self::attr_value((string)$m[1], 'tag_name') ?: 'h2';
            $tag = in_array(strtolower($tag), ['h1','h2','h3','h4','h5','h6'], true) ? strtolower($tag) : 'h2';
            return '<' . $tag . '>' . esc_html(trim(wp_strip_all_tags((string)$m[2]))) . '</' . $tag . '>';
        }, $value);
        $value = preg_replace_callback('/\[title([^\]]*)\]/i', function ($m) {
            $text = self::attr_value((string)$m[1], 'text');
            $tag = self::attr_value((string)$m[1], 'tag') ?: self::attr_value((string)$m[1], 'tag_name') ?: 'h2';
            $tag = in_array(strtolower($tag), ['h1','h2','h3','h4','h5','h6'], true) ? strtolower($tag) : 'h2';
            return $text ? '<' . $tag . '>' . esc_html($text) . '</' . $tag . '>' : '';
        }, $value);
        return $value;
    }

    private static function convert_button_shortcodes(string $value): string
    {
        $value = preg_replace_callback('/\[button([^\]]*)\]([\s\S]*?)\[\/button\]/i', function ($m) {
            return self::button_html((string)$m[1], trim(wp_strip_all_tags((string)$m[2])));
        }, $value);
        $value = preg_replace_callback('/\[button([^\]]*)\]/i', function ($m) {
            return self::button_html((string)$m[1], '');
        }, $value);
        return $value;
    }

    private static function button_html(string $attrs, string $fallback_text = ''): string
    {
        $text = self::attr_value($attrs, 'text') ?: $fallback_text ?: 'Xem thêm';
        $href = self::attr_value($attrs, 'link') ?: self::attr_value($attrs, 'url') ?: '#';
        $color = self::attr_value($attrs, 'color') ?: self::attr_value($attrs, 'style');
        $class = 'button';
        if ($color) { $class .= ' ' . sanitize_html_class($color); }
        return '<a class="' . esc_attr($class) . '" href="' . esc_url($href) . '">' . esc_html($text) . '</a>';
    }

    private static function convert_accordion_shortcodes(string $value): string
    {
        $value = preg_replace('/\[accordion[^\]]*\]/i', '<div class="pm-faq">', $value);
        $value = preg_replace('/\[\/accordion\]/i', '</div>', $value);
        $value = preg_replace_callback('/\[accordion-item([^\]]*)\]([\s\S]*?)\[\/accordion-item\]/i', function ($m) {
            $title = self::attr_value((string)$m[1], 'title') ?: 'Câu hỏi';
            $body = trim((string)$m[2]);
            return '<details class="pm-faq-item"><summary class="pm-faq-question">' . esc_html($title) . '</summary><div class="pm-faq-answer">' . wp_kses_post($body) . '</div></details>';
        }, $value);
        return $value;
    }

    private static function convert_pm_pseudo_shortcodes(string $value): string
    {
        $closeMap = [
            'pm-service-card' => '</div>', 'pm-card-icon' => '</div>', 'pm-card-title' => '</h3>', 'pm-card-text' => '</p>',
            'pm-timeline-step' => '</div>', 'pm-step-dot' => '</div>', 'pm-faq-item' => '</div>', 'pm-faq-question' => '</h3>', 'pm-faq-answer' => '</p>',
        ];
        $openMap = [
            'pm-service-card' => '<div class="pm-service-card">', 'pm-card-title' => '<h3 class="pm-card-title">', 'pm-card-text' => '<p class="pm-card-text">',
            'pm-timeline-step' => '<div class="pm-timeline-step">', 'pm-step-dot' => '<div class="pm-step-dot">', 'pm-faq-item' => '<div class="pm-faq-item">',
            'pm-faq-question' => '<h3 class="pm-faq-question">', 'pm-faq-answer' => '<p class="pm-faq-answer">',
        ];
        $value = preg_replace_callback('/\[\/(pm-[a-z0-9-]+)\]/i', function ($m) use ($closeMap) {
            $name = strtolower($m[1]);
            return $closeMap[$name] ?? '</div>';
        }, $value);
        $value = preg_replace_callback('/\[(pm-[a-z0-9-]+)([^\]]*)\]/i', function ($m) use ($openMap) {
            $name = strtolower($m[1]);
            $attrs = (string)($m[2] ?? '');
            if ($name === 'pm-card-icon') {
                $icon = self::attr_value($attrs, 'icon');
                return '<div class="pm-card-icon" aria-hidden="true">' . esc_html($icon ?: '•');
            }
            return $openMap[$name] ?? '<div class="' . esc_attr($name) . '">';
        }, $value);
        return $value;
    }

    private static function strip_unknown_layout_shortcodes(string $value): string
    {
        $value = preg_replace('/\[\/(row|col|ux_text|section|title|button)\]/i', '', $value);
        $value = preg_replace('/\[(row|col|ux_text|section|title|button)([^\]]*)\]/i', '', $value);
        return $value;
    }

    private static function attr_value(string $attrs, string $name): string
    {
        if (preg_match('/\b' . preg_quote($name, '/') . '="([^"]*)"/i', $attrs, $m)) { return (string)$m[1]; }
        if (preg_match('/\b' . preg_quote($name, '/') . "='([^']*)'/i", $attrs, $m)) { return (string)$m[1]; }
        return '';
    }
}
