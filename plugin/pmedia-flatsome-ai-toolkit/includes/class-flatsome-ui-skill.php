<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Flatsome_UI_Skill
{
    public static function prompt_rules(string $output_mode = 'html-block', string $task = 'block'): string
    {
        $output_mode = $output_mode === 'flatsome-native' ? 'flatsome-native' : 'html-block';
        $mode_rule = $output_mode === 'flatsome-native'
            ? "OUTPUT MODE: Flatsome Native Safe. Chỉ dùng shortcode thật của Flatsome: [section], [row], [col], [ux_text], [button], [gap], [title], [accordion], [accordion-item]. Không nested [row]/[col]. Không dùng shortcode tự chế [pm-*]. HTML class pm-* chỉ được đặt bên trong [ux_text]."
            : "OUTPUT MODE: Safe HTML Block. Plugin sẽ bọc bằng Flatsome [section]/[row]/[col] nếu cần. HTML phải có .pmedia-ai-block và dùng component class pm-*. CSS custom phải tối thiểu.";

        return "PMEDIA FLATSOME UI SKILL - STRICT RULES\n"
            . "{$mode_rule}\n"
            . "Professional layout rules:\n"
            . "- Không freestyle layout. Chọn pattern từ Pattern Library trước khi sinh UI.\n"
            . "- Page chuyên nghiệp cần nhịp rõ: Hero → Trust/Stats → Services/Features → Process → Proof/Testimonial/FAQ → CTA.\n"
            . "- Mỗi section phải có visual hierarchy: eyebrow/kicker, title, lead, content group, CTA/next action nếu phù hợp.\n"
            . "- Card không được quá hẹp; text trong card phải ngắn, không để chữ rơi dọc.\n"
            . "- Không dùng lorem ipsum, không dùng ảnh example.com, không gọi external script.\n"
            . "- Không dùng Bootstrap/Tailwind. Không dùng class chung như .card, .box, .item, .title, .wrapper.\n"
            . "- Không CSS global: body, html, *, a, img, h1-h6, .row, .col, .button, .section, .container.\n"
            . "- Dùng Design System token: var(--pm-color-primary), var(--pm-color-secondary), var(--pm-color-accent), var(--pm-color-text), var(--pm-color-muted), var(--pm-color-border), var(--pm-color-bg-soft), var(--pm-radius-md), var(--pm-radius-lg), var(--pm-shadow-sm), var(--pm-shadow-md).\n"
            . "- Không tự khai báo token cục bộ kiểu --pm-navy, --pm-blue, --pm-text, --pm-muted, --pm-border, --pm-soft.\n"
            . "- Nếu pm-* component class đã đủ dùng, css phải để chuỗi rỗng.\n"
            . "- Trả về đúng schema được yêu cầu, JSON hợp lệ, không markdown nếu schema yêu cầu JSON thuần.\n"
            . "Self-check trước khi trả kết quả nhưng không in self-check ra: shortcode lạ? nested row/col? CSS global? ảnh lỗi? thiếu pattern? text quá dài?\n"
            . "Task scope: {$task}.\n";
    }

    public static function pattern_catalog(): string
    {
        return PMFAI_Section_Patterns::prompt_catalog();
    }

    public static function enhance_prompt(string $prompt, string $output_mode = 'html-block', string $task = 'block'): string
    {
        return self::prompt_rules($output_mode, $task) . "\nPattern Library:\n" . self::pattern_catalog() . "\n\n" . $prompt;
    }

    public static function repair_block(array $block): array
    {
        $html = (string)($block['html'] ?? '');
        $css = (string)($block['css'] ?? '');
        $warnings = is_array($block['warnings'] ?? null) ? $block['warnings'] : [];

        $original_html = $html;
        $original_css = $css;

        $html = self::strip_bad_images($html);
        $html = self::ensure_block_wrapper($html);
        $html = self::repair_pm_pseudo_shortcodes($html);
        $css = self::repair_css($css);

        if ($html !== $original_html) { $warnings[] = 'UI Skill: Đã repair HTML wrapper/pseudo-shortcode/ảnh lỗi.'; }
        if ($css !== $original_css) { $warnings[] = 'UI Skill: Đã repair CSS global/token lặp.'; }

        $block['html'] = $html;
        $block['css'] = $css;
        $block['warnings'] = array_values(array_unique($warnings));
        $block['quality'] = self::score_block($html, $css);
        return $block;
    }

    public static function repair_shortcode(string $shortcode, string $output_mode = 'html-block'): string
    {
        $shortcode = trim($shortcode);
        if ($shortcode === '') { return ''; }
        if ($output_mode === 'flatsome-native' && class_exists('PMFAI_Native_Shortcode_Sanitizer')) {
            return PMFAI_Native_Shortcode_Sanitizer::sanitize_content($shortcode);
        }
        $shortcode = self::repair_pm_pseudo_shortcodes($shortcode);
        $shortcode = self::strip_bad_images($shortcode);
        return $shortcode;
    }

    public static function score_block(string $html, string $css = ''): array
    {
        $score = 100;
        $warnings = [];
        if (stripos($html, 'pmedia-ai-block') === false && stripos($html, '[section') === false) { $score -= 12; $warnings[] = 'Thiếu wrapper .pmedia-ai-block hoặc [section].'; }
        if (!preg_match('/\bpm-[a-z0-9-]+/i', $html . $css)) { $score -= 14; $warnings[] = 'Thiếu component class pm-*.'; }
        if (preg_match('/\[(\/)?pm-[a-z0-9-]+/i', $html)) { $score -= 25; $warnings[] = 'Có shortcode tự chế pm-*.'; }
        if (preg_match('/example\.com|placehold\.co|placeholder\.com/i', $html)) { $score -= 12; $warnings[] = 'Có ảnh placeholder/external không phù hợp.'; }
        if (preg_match('/lorem ipsum/i', $html)) { $score -= 20; $warnings[] = 'Có lorem ipsum.'; }
        if (preg_match('/(^|\}|,)\s*(body|html|\*|\.row|\.col|\.button|\.section|\.container)\s*(\{|,|:)/m', $css)) { $score -= 18; $warnings[] = 'CSS có selector global/nguy hiểm.'; }
        if (preg_match_all('/#[0-9a-f]{3,8}\b/i', $css, $hex) && count($hex[0]) > 3) { $score -= 8; $warnings[] = 'CSS lặp nhiều mã màu hex.'; }
        if (strlen(wp_strip_all_tags($html)) < 80) { $score -= 8; $warnings[] = 'Nội dung quá mỏng.'; }
        return ['score' => max(0, min(100, $score)), 'warnings' => $warnings, 'gate' => $score >= 80 ? 'pass' : ($score >= 60 ? 'warning' : 'fail')];
    }

    public static function page_quality(array $page): array
    {
        $sections = is_array($page['sections'] ?? null) ? $page['sections'] : [];
        $scores = [];
        $warnings = [];
        foreach ($sections as $section) {
            $id = sanitize_html_class($section['id'] ?? 'section');
            $q = self::score_block((string)($section['html'] ?? $section['shortcode'] ?? ''), (string)($section['css'] ?? ''));
            $scores[$id] = $q;
            foreach ($q['warnings'] as $warning) { $warnings[] = $id . ': ' . $warning; }
        }
        $avg = 0;
        if ($scores) { $avg = (int)round(array_sum(array_map(static function ($x) { return (int)$x['score']; }, $scores)) / count($scores)); }
        return ['score' => $avg, 'gate' => $avg >= 80 ? 'pass' : ($avg >= 60 ? 'warning' : 'fail'), 'section_scores' => $scores, 'warnings' => $warnings];
    }

    private static function ensure_block_wrapper(string $html): string
    {
        $html = trim($html);
        if ($html === '') { return $html; }
        if (stripos($html, 'pmedia-ai-block') !== false) { return $html; }
        return '<section class="pm-section pmedia-ai-block pm-skill-block">' . "\n" . $html . "\n" . '</section>';
    }

    private static function strip_bad_images(string $html): string
    {
        return preg_replace('/<img\b([^>]*?)src=["\']https?:\/\/(?:example\.com|placehold\.co|placeholder\.com)[^"\']*["\']([^>]*)>/i', '<div class="pm-media-box pm-placeholder-visual" aria-hidden="true"></div>', $html);
    }

    private static function repair_pm_pseudo_shortcodes(string $html): string
    {
        $html = preg_replace_callback('/\[\/(pm-[a-z0-9-]+)\]/i', static function ($m) { return '</div>'; }, $html);
        $html = preg_replace_callback('/\[(pm-[a-z0-9-]+)([^\]]*)\]/i', static function ($m) {
            $name = sanitize_html_class(strtolower($m[1]));
            return '<div class="' . esc_attr($name) . '">';
        }, $html);
        return $html;
    }

    private static function repair_css(string $css): string
    {
        $css = trim($css);
        if ($css === '') { return ''; }
        $css = preg_replace('/(^|\}|,)\s*(body|html|\*|a|img|h1|h2|h3|h4|h5|h6|\.row|\.col|\.button|\.section|\.container)\s*\{[^}]*\}/mi', '', $css);
        $map = ['--pm-navy'=>'var(--pm-color-secondary)','--pm-blue'=>'var(--pm-color-primary)','--pm-text'=>'var(--pm-color-text)','--pm-muted'=>'var(--pm-color-muted)','--pm-border'=>'var(--pm-color-border)','--pm-soft'=>'var(--pm-color-bg-soft)'];
        foreach ($map as $var => $token) {
            $css = preg_replace('/' . preg_quote($var, '/') . '\s*:\s*[^;{}]+;?/i', '', $css);
            $css = str_ireplace('var(' . $var . ')', $token, $css);
        }
        $css = preg_replace('/\s+/', ' ', $css);
        return trim($css);
    }
}
