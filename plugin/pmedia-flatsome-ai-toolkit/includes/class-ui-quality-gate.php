<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_UI_Quality_Gate
{
    public const HARD_MIN_SCORE = 60;
    public const PASS_MIN_SCORE = 80;

    public static function check_page(array $page, string $context = 'page')
    {
        $quality = class_exists('PMFAI_UI_Design_Quality')
            ? PMFAI_UI_Design_Quality::page_quality($page)
            : PMFAI_Flatsome_UI_Skill::page_quality($page);
        return self::check_quality($quality, $context);
    }

    public static function check_shortcode(string $shortcode, string $context = 'shortcode')
    {
        $quality = class_exists('PMFAI_UI_Design_Quality')
            ? PMFAI_UI_Design_Quality::score_block($shortcode, '', 'shortcode')
            : PMFAI_Flatsome_UI_Skill::score_block($shortcode, '');
        return self::check_quality($quality, $context);
    }

    public static function check_block(string $html, string $css = '', string $context = 'block')
    {
        $quality = class_exists('PMFAI_UI_Design_Quality')
            ? PMFAI_UI_Design_Quality::score_block($html, $css, 'block')
            : PMFAI_Flatsome_UI_Skill::score_block($html, $css);
        return self::check_quality($quality, $context);
    }

    public static function check_quality(array $quality, string $context)
    {
        $score = (int)($quality['score'] ?? 0);
        $gate = (string)($quality['gate'] ?? 'fail');
        if ($gate === 'fail' || $score < self::HARD_MIN_SCORE) {
            return new WP_Error(
                'ui_quality_gate_failed',
                'UI Skill Quality Gate chặn ' . $context . ': điểm ' . $score . '/100. Cần regenerate hoặc sửa lại trước khi lưu vào website.',
                ['status' => 422, 'quality' => $quality]
            );
        }
        return ['ok' => true, 'quality' => $quality, 'warning' => self::warning($quality)];
    }

    public static function warning(array $quality): string
    {
        $score = (int)($quality['score'] ?? 0);
        $gate = (string)($quality['gate'] ?? '');
        if ($gate === 'warning' || $score < self::PASS_MIN_SCORE) {
            return 'UI Skill cảnh báo: output chỉ đạt ' . $score . '/100. Nên kiểm tra preview hoặc regenerate section yếu trước khi publish.';
        }
        return '';
    }
}
