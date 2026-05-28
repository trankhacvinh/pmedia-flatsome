<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Page_Builder_AI
{
    public static function schema(): array
    {
        return [
            'version' => '1.0',
            'page' => [
                'title' => '',
                'slug' => '',
                'description' => '',
                'output_mode' => 'html-block',
                'style_preset' => 'corporate-blue',
                'sections' => [[
                    'id' => 'home-hero',
                    'type' => 'hero',
                    'pattern' => 'hero-split-premium',
                    'title' => '',
                    'goal' => '',
                    'html' => '',
                    'css' => '',
                    'shortcode' => '',
                ]],
            ],
        ];
    }

    public static function bridge_prompt(array $params = []): string
    {
        $brief = trim((string)($params['brief'] ?? ''));
        $mode = sanitize_key($params['buildMode'] ?? 'full-page');
        $output_mode = self::output_mode($params['outputMode'] ?? 'html-block');
        $schema = wp_json_encode(self::schema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $recommended = implode(', ', PMFAI_Section_Patterns::recommend_for_text($brief));
        $output_rule = $output_mode === 'flatsome-native'
            ? "Output mode: Flatsome Native Shortcode. Mỗi section nên ưu tiên field shortcode với shortcode Flatsome thuần như [section], [row], [col], [ux_text], [button], [gap], [title], [accordion], [accordion-item]. Chỉ dùng html/css khi shortcode thuần không đủ. Không dùng shortcode lạ không chắc Flatsome hỗ trợ."
            : "Output mode: Flatsome Section + HTML Block. Mỗi section dùng html/css; plugin sẽ tự bọc bằng [section] + [row] + [col]. Đây là mode an toàn nhất.";

        return "Bạn là senior UI engineer cho WordPress Flatsome.\n\n"
            . "Hãy tạo page plan và các section theo brief, nhưng KHÔNG được freestyle layout. Phải chọn pattern từ Pattern Library trước rồi mới sinh section.\n"
            . "{$output_rule}\n\n"
            . "Pattern Library được phép dùng:\n" . PMFAI_Section_Patterns::prompt_catalog() . "\n\n"
            . "Pattern được plugin gợi ý từ brief: {$recommended}\n\n"
            . "Yêu cầu bắt buộc về layout quality:\n"
            . "- Mỗi section BẮT BUỘC có field pattern đúng với Pattern Library.\n"
            . "- Process/quy trình từ 4 bước trở lên phải dùng process-horizontal-timeline hoặc process-dark-icon-strip, không dùng card hẹp.\n"
            . "- Nếu item có mô tả dài, giảm số cột hoặc rút ngắn copy. Không để chữ rơi dọc trong card.\n"
            . "- Mỗi card/service/process item nên có icon, number, badge hoặc visual rhythm.\n"
            . "- Copy trong card phải ngắn, tự nhiên, chuyên nghiệp, không văn AI.\n"
            . "- Ưu tiên section có nhịp thị giác: hero mạnh, service rõ, process/timeline, stats/proof, CTA.\n\n"
            . "Yêu cầu JSON:\n"
            . "- Trả về duy nhất JSON hợp lệ, không markdown, không giải thích.\n"
            . "- HTML, CSS và shortcode phải nằm trong JSON string hợp lệ.\n"
            . "- Escape dấu nháy kép bên trong HTML/CSS/shortcode đúng một lần: dùng \\\" thay cho dấu nháy kép thô.\n"
            . "- Không double-escape HTML/CSS. Không trả về chuỗi có \\\\n hoặc \\\\\\\" còn hiện ra thành chữ trong preview.\n"
            . "- Nếu build mode là single-section thì chỉ tạo 1 section.\n"
            . "- Nếu build mode là full-page thì tạo đủ các section cần thiết cho một trang hoàn chỉnh.\n\n"
            . "Quy tắc cho HTML Block mode:\n"
            . "- HTML phải dùng class pm-* và pmedia-ai-block, không Bootstrap/Tailwind.\n"
            . "- CSS phải scoped trong .pmedia-ai-block hoặc class section riêng.\n"
            . "- Không dùng script inline, không gọi external assets.\n\n"
            . "Quy tắc giảm CSS lặp lại:\n"
            . "- Design System token đã có sẵn, PHẢI dùng token chung thay vì tự khai báo biến màu trong từng section.\n"
            . "- Không khai báo các biến kiểu --pm-navy, --pm-blue, --pm-text, --pm-muted, --pm-border, --pm-soft trong từng block.\n"
            . "- Dùng token có sẵn: var(--pm-color-primary), var(--pm-color-secondary), var(--pm-color-accent), var(--pm-color-text), var(--pm-color-muted), var(--pm-color-border), var(--pm-color-bg-soft), var(--pm-radius-md), var(--pm-radius-lg), var(--pm-shadow-sm), var(--pm-shadow-md).\n"
            . "- Nếu class pm-* hoặc shortcode Flatsome đã đủ dùng thì css để chuỗi rỗng.\n\n"
            . "Build mode: {$mode}\nOutput mode: {$output_mode}\nBrief:\n" . ($brief ?: '[Dán brief trang hoặc website tại đây]') . "\n\n"
            . "Schema bắt buộc:\n" . $schema;
    }

    public static function generate(array $params)
    {
        $started = microtime(true);
        $options = PMFAI_Settings::get_options();
        $provider = PMFAI_AI_Provider_Manager::provider_id($options);
        $mode = sanitize_key($params['costMode'] ?? 'balanced');
        $mode_config = self::mode_config($mode, $options, $provider);
        $build_mode = sanitize_key($params['buildMode'] ?? 'full-page');
        $output_mode = self::output_mode($params['outputMode'] ?? 'html-block');
        $prompt = self::bridge_prompt($params);

        $response = PMFAI_AI_Provider_Manager::complete([
            'options' => $options,
            'provider' => $provider,
            'model' => $mode_config['model'],
            'temperature' => $mode_config['temperature'],
            'timeout' => $mode_config['timeout'],
            'messages' => [
                ['role' => 'system', 'content' => 'You generate strict valid JSON page structures for WordPress Flatsome. Always choose section patterns from the provided Pattern Library. Return JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $duration_ms = (int)round((microtime(true) - $started) * 1000);
        if (is_wp_error($response)) {
            PMFAI_Usage_Logger::log(['action'=>'page-builder-generate','status'=>'error','mode'=>$mode,'model'=>$mode_config['model'],'type'=>$build_mode . '/' . $output_mode,'duration_ms'=>$duration_ms,'error_message'=>$response->get_error_message()]);
            return $response;
        }

        $content = trim((string)($response['content'] ?? ''));
        $usage = is_array($response['usage'] ?? null) ? $response['usage'] : [];
        $code = (int)($response['http_code'] ?? 200);
        $parsed = self::parse_json($content, [
            'raw' => $content,
            'prompt' => $prompt,
            'usage' => $usage,
            'cost_mode' => $mode,
            'model' => $mode_config['model'],
            'provider' => $provider,
            'output_mode' => $output_mode,
        ]);

        PMFAI_Usage_Logger::log([
            'action' => 'page-builder-generate',
            'status' => is_wp_error($parsed) ? 'error' : 'success',
            'mode' => $mode,
            'model' => $mode_config['model'],
            'type' => $build_mode . '/' . $output_mode,
            'duration_ms' => $duration_ms,
            'http_code' => $code,
            'usage' => $usage,
            'error_message' => is_wp_error($parsed) ? $parsed->get_error_message() : '',
        ]);
        return $parsed;
    }

    public static function parse_json(string $raw, array $extra = [])
    {
        $raw = self::strip_code_fence((string)$raw);
        $data = json_decode($raw, true);
        if (!is_array($data)) { $unslashed = self::strip_code_fence(wp_unslash($raw)); if ($unslashed !== $raw) { $data = json_decode($unslashed, true); } }
        if (!is_array($data)) { $repaired = self::repair_unescaped_html_css_strings($raw); if ($repaired !== $raw) { $data = json_decode($repaired, true); } }
        if (!is_array($data)) { return new WP_Error('invalid_json', 'JSON page không hợp lệ: ' . json_last_error_msg() . '. Gợi ý: HTML/CSS trong JSON phải escape dấu nháy kép.', ['status' => 400]); }
        if (!empty($extra['output_mode'])) { $data['output_mode'] = self::output_mode($extra['output_mode']); if (isset($data['page']) && is_array($data['page'])) { $data['page']['output_mode'] = $data['output_mode']; } }
        $validated = self::validate($data);
        if (is_wp_error($validated)) { return $validated; }
        $validated['shortcode'] = self::to_shortcode($validated['page']);
        $validated['visual_quality'] = PMFAI_Visual_Quality_Validator::analyze_page($validated['page']);
        if (!empty($validated['visual_quality']['warnings'])) { $validated['warnings'] = array_merge($validated['warnings'], $validated['visual_quality']['warnings']); }
        if (!empty($validated['visual_quality']['suggestions'])) { $validated['suggestions'] = $validated['visual_quality']['suggestions']; }
        return array_merge($validated, $extra);
    }

    private static function strip_code_fence(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);
        return trim($raw);
    }

    private static function repair_unescaped_html_css_strings(string $raw): string
    {
        $raw = self::strip_code_fence($raw);
        foreach (['html','css','shortcode'] as $field) {
            $raw = preg_replace_callback('/"' . $field . '"\s*:\s*"([\s\S]*?)"\s*(?=,\s*"|\}\s*(?:,|\]))/', function ($m) use ($field) {
                return '"' . $field . '":' . wp_json_encode($m[1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }, $raw);
        }
        return $raw;
    }

    private static function normalize_code_string(string $value): array
    {
        $original = $value;
        $value = trim($value);
        $value = str_replace(["\\r\\n", "\\n", "\\t"], ["\n", "\n", "\t"], $value);
        $value = str_replace(['\\"', "\\'", '\\/'], ['"', "'", '/'], $value);
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) { $maybe = json_decode($value, true); if (is_string($maybe)) { $value = $maybe; } }
        return ['value' => $value, 'changed' => $value !== $original];
    }

    public static function validate(array $data)
    {
        $page = $data['page'] ?? $data;
        if (!is_array($page)) { return new WP_Error('invalid_page', 'Không tìm thấy object page.', ['status' => 400]); }
        $sections = $page['sections'] ?? [];
        if (!is_array($sections) || empty($sections)) { return new WP_Error('missing_sections', 'Page phải có ít nhất một section.', ['status' => 400]); }
        $output_mode = self::output_mode($page['output_mode'] ?? $data['output_mode'] ?? 'html-block');
        $patterns = PMFAI_Section_Patterns::all();
        $out = ['version'=>sanitize_text_field($data['version'] ?? '1.0'),'page'=>['title'=>sanitize_text_field($page['title'] ?? 'Trang mới'),'slug'=>sanitize_title($page['slug'] ?? $page['title'] ?? 'trang-moi'),'description'=>sanitize_textarea_field($page['description'] ?? ''),'output_mode'=>$output_mode,'style_preset'=>sanitize_key($page['style_preset'] ?? 'corporate-blue'),'sections'=>[]],'warnings'=>[]];
        foreach ($sections as $index => $section) {
            $id = sanitize_html_class($section['id'] ?? ('section-' . ($index + 1)));
            $type = sanitize_key($section['type'] ?? 'custom');
            $pattern = sanitize_key($section['pattern'] ?? '');
            if (!$pattern || !isset($patterns[$pattern])) { $recommended = PMFAI_Section_Patterns::recommend_for_text(($section['title'] ?? '') . ' ' . ($section['goal'] ?? '') . ' ' . ($section['html'] ?? '') . ' ' . ($section['shortcode'] ?? '')); $pattern = $recommended[0] ?? 'service-grid-clean'; $out['warnings'][] = "Section {$id}: Thiếu/không đúng pattern, đã gợi ý dùng {$pattern}."; }
            $htmlN = self::normalize_code_string((string)($section['html'] ?? ''));
            $cssN = self::normalize_code_string((string)($section['css'] ?? ''));
            $shortcodeN = self::normalize_code_string((string)($section['shortcode'] ?? ''));
            $html = $htmlN['value']; $css = $cssN['value']; $shortcode = $shortcodeN['value'];
            if ($htmlN['changed'] || $cssN['changed'] || $shortcodeN['changed']) { $out['warnings'][] = "Section {$id}: Đã tự sửa chuỗi bị double-escaped."; }
            if ($output_mode !== 'flatsome-native' && trim($html) === '') { $out['warnings'][] = "Section {$id} thiếu HTML và đã bị bỏ qua."; continue; }
            if ($output_mode !== 'flatsome-native' && stripos($html, 'pmedia-ai-block') === false) { $html = '<div class="pmedia-ai-block pm-page-block pm-page-block-' . esc_attr($id) . '">' . "\n" . trim($html) . "\n" . '</div>'; }
            $normalized = self::normalize_section_css($css); $css = $normalized['css']; foreach ($normalized['warnings'] as $warning) { $out['warnings'][] = "Section {$id}: " . $warning; }
            $out['page']['sections'][] = ['id'=>$id,'type'=>$type,'pattern'=>$pattern,'title'=>sanitize_text_field($section['title'] ?? ''),'goal'=>sanitize_textarea_field($section['goal'] ?? ''),'html'=>$html,'css'=>$css,'shortcode'=>$shortcode,'scores'=>PMFAI_Code_Validator::analyze($html, $css)['scores'] ?? []];
        }
        if (empty($out['page']['sections'])) { return new WP_Error('no_valid_sections', 'Không có section hợp lệ.', ['status' => 400]); }
        return $out;
    }

    private static function normalize_section_css(string $css): array
    {
        $warnings = [];
        $css = trim($css);
        if ($css === '') { return ['css'=>'','warnings'=>[]]; }
        $original = $css;
        $options = PMFAI_Settings::get_options();
        $customVarMap = ['--pm-navy'=>'var(--pm-color-secondary)','--pm-blue'=>'var(--pm-color-primary)','--pm-accent'=>'var(--pm-color-accent)','--pm-text'=>'var(--pm-color-text)','--pm-muted'=>'var(--pm-color-muted)','--pm-border'=>'var(--pm-color-border)','--pm-soft'=>'var(--pm-color-bg-soft)'];
        foreach ($customVarMap as $varName => $token) { $css = preg_replace('/' . preg_quote($varName, '/') . '\s*:\s*[^;{}]+;?/i', '', $css); $css = str_ireplace('var(' . $varName . ')', $token, $css); }
        $colorMap = ['#062B63'=>'var(--pm-color-secondary)','#0B4EA2'=>'var(--pm-color-primary)','#2F80ED'=>'var(--pm-color-accent)','#102033'=>'var(--pm-color-text)','#64748B'=>'var(--pm-color-muted)','#DDE6F2'=>'var(--pm-color-border)','#F5F8FC'=>'var(--pm-color-bg-soft)','#FFFFFF'=>'#fff'];
        $settingsColorMap = [$options['primary_color'] ?? ''=>'var(--pm-color-primary)',$options['secondary_color'] ?? ''=>'var(--pm-color-secondary)',$options['accent_color'] ?? ''=>'var(--pm-color-accent)',$options['text_color'] ?? ''=>'var(--pm-color-text)',$options['muted_color'] ?? ''=>'var(--pm-color-muted)',$options['border_color'] ?? ''=>'var(--pm-color-border)',$options['bg_soft_color'] ?? ''=>'var(--pm-color-bg-soft)'];
        foreach (array_merge($colorMap, $settingsColorMap) as $hex => $token) { if ($hex && $token) { $css = str_ireplace($hex, $token, $css); } }
        $css = preg_replace('/;{2,}/', ';', $css);
        $css = preg_replace('/\{\s*;/', '{', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,>])\s*/', '$1', $css);
        $css = trim($css);
        if ($css !== $original) { $warnings[] = 'Đã normalize CSS: xóa token cục bộ và thay màu lặp bằng Design System variables.'; }
        return ['css'=>$css,'warnings'=>$warnings];
    }

    public static function to_shortcode(array $page): string
    {
        $output_mode = self::output_mode($page['output_mode'] ?? 'html-block');
        $content = '';
        foreach ($page['sections'] as $section) {
            if ($output_mode === 'flatsome-native' && !empty($section['shortcode'])) { $content .= trim((string)$section['shortcode']) . "\n\n"; continue; }
            if ($output_mode === 'flatsome-native') { $content .= self::fallback_native_shortcode($section) . "\n\n"; continue; }
            $class = 'pm-section pm-page-section pm-section-' . sanitize_html_class($section['id']) . ' pm-section-type-' . sanitize_html_class($section['type']) . ' pm-pattern-' . sanitize_html_class($section['pattern'] ?? 'custom');
            $html = trim((string)$section['html']); $css = trim((string)$section['css']); $style = $css ? "\n<style>\n" . $css . "\n</style>\n" : '';
            $content .= '[section class="' . esc_attr($class) . '"]' . "\n" . '  [row]' . "\n" . '    [col span__sm="12"]' . "\n" . $style . $html . "\n" . '    [/col]' . "\n" . '  [/row]' . "\n" . '[/section]' . "\n\n";
        }
        return trim($content);
    }

    private static function fallback_native_shortcode(array $section): string
    {
        $title = esc_html($section['title'] ?? ''); $goal = esc_html($section['goal'] ?? ''); $text = wp_strip_all_tags((string)($section['html'] ?? '')); $body = $text ?: $goal;
        return '[section class="pm-native-section pm-section-' . esc_attr($section['id'] ?? 'section') . ' pm-pattern-' . esc_attr($section['pattern'] ?? 'custom') . '"]' . "\n" . '  [row]' . "\n" . '    [col span__sm="12"]' . "\n" . '      [ux_text]' . "\n" . '        <h2>' . $title . '</h2>' . "\n" . '        <p>' . esc_html($body) . '</p>' . "\n" . '      [/ux_text]' . "\n" . '    [/col]' . "\n" . '  [/row]' . "\n" . '[/section]';
    }

    public static function create_draft(array $data)
    {
        $validated = self::validate($data); if (is_wp_error($validated)) { return $validated; }
        $shortcode = self::to_shortcode($validated['page']);
        $post_id = wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>$validated['page']['title'],'post_name'=>$validated['page']['slug'],'post_content'=>$shortcode], true);
        if (is_wp_error($post_id)) { return $post_id; }
        return ['created'=>true,'post_id'=>$post_id,'edit_url'=>get_edit_post_link($post_id, 'raw'),'shortcode'=>$shortcode];
    }

    private static function output_mode(string $mode): string { return $mode === 'flatsome-native' ? 'flatsome-native' : 'html-block'; }

    private static function mode_config(string $mode, array $options, string $provider = 'openai'): array
    {
        if ($provider === 'anthropic') {
            $base_model = $options['anthropic_model'] ?: 'claude-3-5-sonnet-latest';
        } elseif ($provider === 'openai_compatible') {
            $base_model = $options['compatible_model'] ?: ($options['api_model'] ?: 'gpt-4.1-mini');
        } else {
            $base_model = $options['api_model'] ?: 'gpt-4.1-mini';
        }
        $base_temperature = is_numeric($options['temperature']) ? (float)$options['temperature'] : 0.4;
        $configs = ['fast'=>['model'=>trim((string)($options['fast_model'] ?? '')) ?: $base_model,'temperature'=>is_numeric($options['fast_temperature'] ?? null) ? (float)$options['fast_temperature'] : 0.2,'timeout'=>90],'balanced'=>['model'=>trim((string)($options['balanced_model'] ?? '')) ?: $base_model,'temperature'=>is_numeric($options['balanced_temperature'] ?? null) ? (float)$options['balanced_temperature'] : $base_temperature,'timeout'=>120],'high'=>['model'=>trim((string)($options['high_model'] ?? '')) ?: $base_model,'temperature'=>is_numeric($options['high_temperature'] ?? null) ? (float)$options['high_temperature'] : 0.65,'timeout'=>180]];
        return $configs[$mode] ?? $configs['balanced'];
    }
}
