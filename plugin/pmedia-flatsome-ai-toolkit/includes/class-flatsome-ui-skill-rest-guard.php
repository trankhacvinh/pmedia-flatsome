<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Flatsome_UI_Skill_REST_Guard
{
    public static function register(): void
    {
        add_filter('rest_request_after_callbacks', [__CLASS__, 'guard_response'], 20, 3);
    }

    public static function guard_response($response, array $handler, WP_REST_Request $request)
    {
        if (is_wp_error($response) || !($response instanceof WP_REST_Response)) { return $response; }
        if (strpos((string)$request->get_route(), '/pmedia-ai/v1/') !== 0) { return $response; }
        $data = $response->get_data();
        if (!is_array($data)) { return $response; }
        $response->set_data(self::guard_array($data));
        return $response;
    }

    private static function guard_array(array $data): array
    {
        if (isset($data['page']) && is_array($data['page'])) {
            $data['page'] = self::guard_page($data['page']);
            $data['ui_skill_quality'] = self::page_quality($data['page']);
        }
        if (isset($data['section']) && is_array($data['section'])) {
            $data['section'] = self::guard_section($data['section'], self::mode($data));
        }
        if (isset($data['html']) || isset($data['css'])) {
            $data = PMFAI_Flatsome_UI_Skill::repair_block($data);
            $data['quality'] = self::block_quality((string)($data['html'] ?? ''), (string)($data['css'] ?? ''), (string)($data['type'] ?? 'block'));
        }
        if (isset($data['shortcode']) && is_string($data['shortcode'])) {
            $data['shortcode'] = PMFAI_Flatsome_UI_Skill::repair_shortcode($data['shortcode'], self::mode($data));
            $data['ui_skill_shortcode_quality'] = self::block_quality($data['shortcode'], '', 'shortcode');
        }
        $data['ui_skill_guarded'] = true;
        return $data;
    }

    private static function guard_page(array $page): array
    {
        $mode = self::mode($page);
        if (!empty($page['sections']) && is_array($page['sections'])) {
            foreach ($page['sections'] as $i => $section) {
                if (is_array($section)) { $page['sections'][$i] = self::guard_section($section, $mode); }
            }
        }
        return $page;
    }

    private static function guard_section(array $section, string $mode): array
    {
        if (!empty($section['html']) || !empty($section['css'])) {
            $block = PMFAI_Flatsome_UI_Skill::repair_block(['html'=>(string)($section['html'] ?? ''),'css'=>(string)($section['css'] ?? ''),'warnings'=>[]]);
            $section['html'] = $block['html'];
            $section['css'] = $block['css'];
            $section['ui_skill_quality'] = self::block_quality((string)$section['html'], (string)$section['css'], (string)($section['type'] ?? 'section'));
        }
        if (!empty($section['shortcode'])) {
            $section['shortcode'] = PMFAI_Flatsome_UI_Skill::repair_shortcode((string)$section['shortcode'], $mode);
            $section['ui_skill_quality'] = self::block_quality((string)$section['shortcode'], '', (string)($section['type'] ?? 'section'));
        }
        return $section;
    }

    private static function page_quality(array $page): array
    {
        return class_exists('PMFAI_UI_Design_Quality') ? PMFAI_UI_Design_Quality::page_quality($page) : PMFAI_Flatsome_UI_Skill::page_quality($page);
    }

    private static function block_quality(string $html, string $css = '', string $type = 'block'): array
    {
        return class_exists('PMFAI_UI_Design_Quality') ? PMFAI_UI_Design_Quality::score_block($html, $css, $type) : PMFAI_Flatsome_UI_Skill::score_block($html, $css);
    }

    private static function mode(array $data): string
    {
        $mode = sanitize_key((string)($data['output_mode'] ?? $data['outputMode'] ?? ''));
        if ($mode === 'flatsome-native') { return 'flatsome-native'; }
        $json = wp_json_encode($data);
        return is_string($json) && stripos($json, 'pm-native-section') !== false ? 'flatsome-native' : 'html-block';
    }
}
