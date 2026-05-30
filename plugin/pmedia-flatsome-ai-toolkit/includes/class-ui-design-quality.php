<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_UI_Design_Quality
{
    public static function score_block(string $html, string $css = '', string $type = ''): array
    {
        $base = PMFAI_Flatsome_UI_Skill::score_block($html, $css);
        $score = (int)($base['score'] ?? 0);
        $warnings = is_array($base['warnings'] ?? null) ? $base['warnings'] : [];
        $all = strtolower($html . ' ' . $css . ' ' . $type);

        if (substr_count($css, '{') !== substr_count($css, '}')) {
            $score -= 35;
            $warnings[] = 'CSS có số lượng dấu ngoặc { } không khớp, nguy cơ hỏng style.';
        }
        if (stripos($html, '<ux_text') !== false || stripos($html, '</ux_text') !== false) {
            $score -= 18;
            $warnings[] = 'HTML Block có tag <ux_text>; chỉ dùng [ux_text] trong shortcode/native mode.';
        }
        if (preg_match_all('/\sstyle=["\']/i', $html, $m) && count($m[0]) > 2) {
            $score -= min(18, count($m[0]) * 3);
            $warnings[] = 'Có quá nhiều inline style; nên chuyển sang class pm-* để giao diện ổn định hơn.';
        }
        if (strpos($all, 'hero') !== false) {
            if (stripos($html, '<h1') === false) { $score -= 10; $warnings[] = 'Hero thiếu H1 rõ ràng.'; }
            if (stripos($html, 'pm-lead') === false && stripos($html, 'lead') === false) { $score -= 8; $warnings[] = 'Hero thiếu lead/subtitle.'; }
            if (stripos($html, '[button') === false && stripos($html, 'button') === false) { $score -= 10; $warnings[] = 'Hero thiếu CTA button rõ ràng.'; }
            if (stripos($html, 'pm-badge') === false && stripos($html, 'pm-trust') === false && stripos($html, 'pm-proof') === false) { $score -= 6; $warnings[] = 'Hero thiếu trust signal/badge/proof.'; }
        }
        if (strpos($all, 'cta') !== false) {
            if (stripos($html, '[button') === false && stripos($html, 'button') === false) { $score -= 12; $warnings[] = 'CTA section thiếu nút hành động.'; }
            if (stripos($html . $css, 'background') === false && stripos($html . $css, '--pm-color-primary') === false) { $score -= 8; $warnings[] = 'CTA chưa có nền/contrast đủ mạnh.'; }
        }
        if (preg_match_all('/pm-service-card|pm-card|pm-timeline-step|pm-step/i', $html, $cards) && count($cards[0]) >= 3) {
            if (stripos($html . $css, 'shadow') === false) { $score -= 6; $warnings[] = 'Card grid thiếu depth/shadow, dễ nhìn phẳng.'; }
            if (stripos($html . $css, 'radius') === false) { $score -= 5; $warnings[] = 'Card grid thiếu radius/shape consistency.'; }
        }

        $score = max(0, min(100, $score));
        return ['score' => $score, 'warnings' => array_values(array_unique($warnings)), 'gate' => $score >= 82 ? 'pass' : ($score >= 65 ? 'warning' : 'fail'), 'base_score' => (int)($base['score'] ?? 0)];
    }

    public static function page_quality(array $page): array
    {
        $sections = is_array($page['sections'] ?? null) ? $page['sections'] : [];
        $scores = [];
        $warnings = [];
        $types = [];
        foreach ($sections as $section) {
            $id = sanitize_html_class($section['id'] ?? 'section');
            $type = sanitize_key($section['type'] ?? 'custom');
            $types[] = $type;
            $q = self::score_block((string)($section['html'] ?? $section['shortcode'] ?? ''), (string)($section['css'] ?? ''), $type);
            $scores[$id] = $q;
            foreach ($q['warnings'] as $warning) { $warnings[] = $id . ': ' . $warning; }
        }
        $avg = 0;
        if ($scores) { $avg = (int)round(array_sum(array_map(static function ($x) { return (int)$x['score']; }, $scores)) / count($scores)); }
        if (count($sections) >= 4 && count(array_unique($types)) < 4) { $avg -= 8; $warnings[] = 'Nhịp page bị đều đều, thiếu variation giữa các loại section.'; }
        $avg = max(0, min(100, $avg));
        return ['score' => $avg, 'gate' => $avg >= 82 ? 'pass' : ($avg >= 65 ? 'warning' : 'fail'), 'section_scores' => $scores, 'warnings' => array_values(array_unique($warnings))];
    }
}
