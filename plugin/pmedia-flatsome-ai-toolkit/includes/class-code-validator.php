<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Code_Validator
{
    public static function parse_block(string $raw)
    {
        $raw = (string)$raw;
        $json = self::extract_json($raw);

        if (!$json) {
            return new WP_Error('parse_failed', 'Không tìm thấy JSON pmedia-flatsome-block hợp lệ.', ['status' => 400]);
        }

        $data = json_decode($json, true);

        // Backward-compatible fallback for form-encoded/manual pasted content.
        // Important: do not wp_unslash before the first json_decode because it corrupts valid JSON strings containing escaped HTML quotes like class="...".
        if (!is_array($data)) {
            $unslashed_json = self::extract_json(wp_unslash($raw));
            if ($unslashed_json && $unslashed_json !== $json) {
                $data = json_decode($unslashed_json, true);
            }
        }

        if (!is_array($data)) {
            return new WP_Error('json_invalid', 'JSON không hợp lệ: ' . json_last_error_msg(), ['status' => 400]);
        }

        $html = (string)($data['html'] ?? '');
        $css = (string)($data['css'] ?? '');
        $analysis = self::analyze($html, $css);

        $block = [
            'title' => sanitize_text_field($data['title'] ?? 'Imported ChatGPT Block'),
            'type' => sanitize_key($data['type'] ?? 'custom'),
            'style' => sanitize_text_field($data['style'] ?? 'business'),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'html' => $html,
            'css' => $css,
            'js' => (string)($data['js'] ?? ''),
            'notes' => $data['notes'] ?? [],
            'warnings' => $analysis['warnings'],
            'suggestions' => $analysis['suggestions'],
            'scores' => $analysis['scores'],
        ];

        $block = PMFAI_Flatsome_UI_Skill::repair_block($block);
        $analysis = self::analyze((string)$block['html'], (string)$block['css']);
        $block['warnings'] = array_values(array_unique(array_merge((array)($block['warnings'] ?? []), $analysis['warnings'])));
        $block['suggestions'] = array_values(array_unique(array_merge((array)($block['suggestions'] ?? []), $analysis['suggestions'])));
        $block['scores'] = $analysis['scores'];
        $block['quality'] = PMFAI_Flatsome_UI_Skill::score_block((string)$block['html'], (string)$block['css']);

        return $block;
    }

    private static function extract_json(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/```pmedia-flatsome-block\s*(.*?)```/is', $raw, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/```json\s*(.*?)```/is', $raw, $matches)) {
            return trim($matches[1]);
        }
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        return ($start !== false && $end !== false && $end > $start) ? trim(substr($raw, $start, $end - $start + 1)) : '';
    }

    public static function analyze(string $html, string $css): array
    {
        $warnings = [];
        $suggestions = [];
        $cssPenalty = 0;
        $flatsomePenalty = 0;
        $forbidden = ['body', 'html', '*', 'a', 'img', 'h1', 'h2', 'h3', '.container', '.row', '.col', '.button', '.section', '.card', '.title', '.box'];

        foreach ($forbidden as $selector) {
            if (preg_match('/(^|\}|,)\s*' . preg_quote($selector, '/') . '\s*(\{|,|:|\.)/m', $css)) {
                $warnings[] = 'CSS selector nguy hiểm hoặc quá chung: ' . $selector;
                $cssPenalty += 8;
                $flatsomePenalty += 6;
            }
        }

        if (preg_match_all('/!important/i', $css, $matches) && count($matches[0]) > 0) {
            $warnings[] = 'Có ' . count($matches[0]) . ' lần dùng !important.';
            $cssPenalty += min(20, count($matches[0]) * 3);
        }

        if (preg_match('/z-index\s*:\s*(9999|99999|999999)/i', $css)) {
            $warnings[] = 'Có z-index quá cao.';
            $cssPenalty += 8;
        }

        if (preg_match('/(^|\s)(body|html)\b/i', $html)) {
            $warnings[] = 'HTML có body/html tag.';
            $flatsomePenalty += 15;
        }

        if (!preg_match('/pmedia-ai-block/i', $html . $css)) {
            $warnings[] = 'Thiếu wrapper .pmedia-ai-block.';
            $cssPenalty += 10;
            $flatsomePenalty += 8;
            $suggestions[] = 'Bọc HTML trong <section class="pm-section pmedia-ai-block">.';
        }

        if (!preg_match('/\bpm-[a-z0-9-]+/i', $html . $css)) {
            $warnings[] = 'Chưa thấy class chuẩn pm-*.';
            $flatsomePenalty += 10;
            $suggestions[] = 'Dùng pm-section, pm-card, pm-lead, pm-eyebrow hoặc pm-cta-box.';
        }

        if (preg_match('/\[(\/)?pm-[a-z0-9-]+/i', $html)) {
            $warnings[] = 'Có shortcode tự chế pm-*. Skill sẽ cố chuyển thành HTML.';
            $flatsomePenalty += 20;
        }

        if (preg_match_all('/#[0-9a-f]{3,8}\b/i', $css, $hex) && count($hex[0]) > 0) {
            $warnings[] = 'Có ' . count($hex[0]) . ' màu hex trong CSS. Nên dùng CSS variables.';
            $flatsomePenalty += min(12, count($hex[0]) * 2);
        }

        if (!$warnings) {
            $suggestions[] = 'Code tương đối sạch. Vẫn nên test responsive trực tiếp trong Flatsome.';
        }

        return [
            'warnings' => $warnings,
            'suggestions' => $suggestions,
            'scores' => [
                'css_safety' => max(0, 100 - $cssPenalty),
                'flatsome_compatibility' => max(0, 100 - $flatsomePenalty),
            ],
        ];
    }
}
