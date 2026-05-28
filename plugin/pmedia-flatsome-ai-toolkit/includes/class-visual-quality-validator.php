<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Visual_Quality_Validator
{
    public static function analyze_page(array $page): array
    {
        $warnings = [];
        $suggestions = [];
        $score = 100;

        foreach (($page['sections'] ?? []) as $section) {
            $result = self::analyze_section($section);
            foreach ($result['warnings'] as $warning) {
                $warnings[] = ($section['id'] ?? 'section') . ': ' . $warning;
            }
            foreach ($result['suggestions'] as $suggestion) {
                $suggestions[] = ($section['id'] ?? 'section') . ': ' . $suggestion;
            }
            $score -= (100 - $result['score']) * 0.35;
        }

        return [
            'score' => max(0, min(100, (int)round($score))),
            'warnings' => array_values(array_unique($warnings)),
            'suggestions' => array_values(array_unique($suggestions)),
        ];
    }

    public static function analyze_section(array $section): array
    {
        $warnings = [];
        $suggestions = [];
        $score = 100;
        $html = (string)($section['html'] ?? '');
        $css = (string)($section['css'] ?? '');
        $shortcode = (string)($section['shortcode'] ?? '');
        $text = trim(wp_strip_all_tags(strip_shortcodes($html . ' ' . $shortcode)));
        $type = sanitize_key($section['type'] ?? 'custom');
        $pattern = sanitize_key($section['pattern'] ?? '');

        $word_count = str_word_count($text);
        $card_count = preg_match_all('/pm-(?:card|service-card|work-card|feature-card|step|proof-card)|\[col\b/i', $html . $shortcode);
        $has_timeline = preg_match('/timeline|process-horizontal|process-dark|pm-process-line|pm-process|pm-step-number/i', $html . $css . $shortcode);
        $has_visual = preg_match('/pm-card-icon|pm-icon-box|ux_image|\[ux_image|<img|inline-svg|icon_box|\[icon_box|pm-work-thumb|pm-hero-visual/i', $html . $shortcode);

        if ($card_count >= 4 && $word_count > 120 && !$has_timeline) {
            $warnings[] = 'Nhiều card/cột nhưng nội dung dài, dễ làm chữ rơi dọc hoặc layout bị chật.';
            $suggestions[] = 'Rút gọn mô tả card hoặc đổi sang process-horizontal-timeline / grid 2 cột.';
            $score -= 22;
        }

        if (($type === 'process' || preg_match('/quy trình|process|workflow|bước/i', $text)) && $card_count >= 4 && !$has_timeline) {
            $warnings[] = 'Section quy trình có nhiều bước nhưng chưa dùng timeline/process pattern rõ ràng.';
            $suggestions[] = 'Dùng pattern process-horizontal-timeline hoặc process-dark-icon-strip.';
            $score -= 18;
        }

        if ($word_count > 80 && !$has_visual && !in_array($type, ['faq'], true)) {
            $warnings[] = 'Section nhiều chữ nhưng thiếu visual/icon/card rhythm.';
            $suggestions[] = 'Thêm icon, badge, metric, visual card hoặc chia nội dung thành card ngắn.';
            $score -= 12;
        }

        if (preg_match('/grid-template-columns\s*:\s*repeat\((4|5|6)/i', $css) && $word_count > 100) {
            $warnings[] = 'CSS dùng quá nhiều cột cho nội dung dài.';
            $suggestions[] = 'Giảm còn 2-3 cột hoặc rút gọn copy mỗi item.';
            $score -= 18;
        }

        if ($pattern === '') {
            $warnings[] = 'Section chưa khai báo pattern, khó kiểm soát chất lượng thiết kế.';
            $suggestions[] = 'Bổ sung field pattern theo Pattern Library.';
            $score -= 8;
        }

        return [
            'score' => max(0, min(100, $score)),
            'warnings' => $warnings,
            'suggestions' => $suggestions,
        ];
    }
}
