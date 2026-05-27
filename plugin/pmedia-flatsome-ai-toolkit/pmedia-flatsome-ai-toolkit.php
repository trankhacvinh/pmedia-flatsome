<?php
/**
 * Plugin Name: Pmedia Flatsome AI Toolkit
 * Plugin URI: https://pmedia.vn
 * Description: Design System, ChatGPT Bridge, Import/Validate block và CSS Toolkit cho Flatsome.
 * Version: 1.1.0
 * Author: Pmedia
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: pmedia-flatsome-ai-toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PMFAI_VERSION', '1.1.0');
define('PMFAI_OPTION_KEY', 'pmedia_flatsome_ai_toolkit_options');

final class PMFAI_Plugin
{
    public static function init(): void
    {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('admin_menu', [__CLASS__, 'register_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'frontend_assets']);
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
    }

    public static function activate(): void
    {
        update_option(PMFAI_OPTION_KEY, wp_parse_args(get_option(PMFAI_OPTION_KEY, []), self::default_options()));
        self::register_post_type();
        flush_rewrite_rules();
    }

    public static function default_options(): array
    {
        return [
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
            'api_key' => '',
            'api_model' => 'gpt-4.1-mini',
            'temperature' => '0.4',
            'primary_color' => '#e31e24',
            'secondary_color' => '#111827',
            'accent_color' => '#f59e0b',
            'text_color' => '#1f2937',
            'muted_color' => '#6b7280',
            'border_color' => '#e5e7eb',
            'bg_soft_color' => '#f9fafb',
            'radius_sm' => '8px',
            'radius_md' => '16px',
            'radius_lg' => '24px',
            'section_padding_desktop' => '72px',
            'section_padding_mobile' => '42px',
            'enable_frontend_css' => '1',
            'extra_rules' => 'Ưu tiên dùng class Flatsome: row, col, col-inner, button primary, is-large. Không dùng Bootstrap/Tailwind.',
        ];
    }

    public static function get_options(): array
    {
        return wp_parse_args(get_option(PMFAI_OPTION_KEY, []), self::default_options());
    }

    public static function sanitize_options($input): array
    {
        $defaults = self::default_options();
        $current = self::get_options();
        $output = [];

        foreach ($defaults as $key => $default) {
            $value = $input[$key] ?? ($current[$key] ?? $default);

            if (strpos($key, 'color') !== false) {
                $output[$key] = sanitize_hex_color($value) ?: $default;
            } elseif ($key === 'extra_rules') {
                $output[$key] = sanitize_textarea_field($value);
            } elseif ($key === 'enable_frontend_css') {
                $output[$key] = !empty($value) ? '1' : '0';
            } else {
                $output[$key] = sanitize_text_field($value);
            }
        }

        return $output;
    }

    public static function register_settings(): void
    {
        register_setting('pmfai_settings_group', PMFAI_OPTION_KEY, [
            'sanitize_callback' => [__CLASS__, 'sanitize_options'],
        ]);
    }

    public static function register_post_type(): void
    {
        register_post_type('pmedia_ai_block', [
            'labels' => [
                'name' => 'Pmedia AI Blocks',
                'singular_name' => 'Pmedia AI Block',
            ],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title'],
        ]);
    }

    public static function register_admin_menu(): void
    {
        add_menu_page('Pmedia AI Builder', 'Pmedia AI Builder', 'manage_options', 'pmedia-ai-builder', [__CLASS__, 'page_dashboard'], 'dashicons-art', 58);
        add_submenu_page('pmedia-ai-builder', 'Dashboard', 'Dashboard', 'manage_options', 'pmedia-ai-builder', [__CLASS__, 'page_dashboard']);
        add_submenu_page('pmedia-ai-builder', 'Design System', 'Design System', 'manage_options', 'pmfai-design-system', [__CLASS__, 'page_design_system']);
        add_submenu_page('pmedia-ai-builder', 'ChatGPT Bridge', 'ChatGPT Bridge', 'manage_options', 'pmfai-chatgpt-bridge', [__CLASS__, 'page_chatgpt_bridge']);
        add_submenu_page('pmedia-ai-builder', 'Import From ChatGPT', 'Import From ChatGPT', 'manage_options', 'pmfai-import-chatgpt', [__CLASS__, 'page_import_chatgpt']);
        add_submenu_page('pmedia-ai-builder', 'Clean / Validate Code', 'Clean / Validate Code', 'manage_options', 'pmfai-clean-code', [__CLASS__, 'page_validate_code']);
        add_submenu_page('pmedia-ai-builder', 'Prompt Library', 'Prompt Library', 'manage_options', 'pmfai-prompt-library', [__CLASS__, 'page_prompt_library']);
        add_submenu_page('pmedia-ai-builder', 'Settings', 'Settings', 'manage_options', 'pmfai-settings', [__CLASS__, 'page_settings']);
    }

    public static function admin_assets($hook): void
    {
        if (strpos((string)$hook, 'pmfai') === false && strpos((string)$hook, 'pmedia-ai-builder') === false) {
            return;
        }

        wp_register_style('pmfai-admin', false, [], PMFAI_VERSION);
        wp_enqueue_style('pmfai-admin');
        wp_add_inline_style('pmfai-admin', self::admin_css());

        wp_register_script('pmfai-admin', '', [], PMFAI_VERSION, true);
        wp_enqueue_script('pmfai-admin');
        wp_add_inline_script('pmfai-admin', self::admin_js());
        wp_localize_script('pmfai-admin', 'PMFAI', [
            'restUrl' => esc_url_raw(rest_url('pmedia-ai/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    public static function frontend_assets(): void
    {
        $options = self::get_options();
        if ($options['enable_frontend_css'] !== '1') {
            return;
        }

        wp_register_style('pmfai-frontend', false, [], PMFAI_VERSION);
        wp_enqueue_style('pmfai-frontend');
        wp_add_inline_style('pmfai-frontend', self::tokens_css() . self::frontend_css());
    }

    public static function page_header(string $title, string $description = ''): void
    {
        echo '<div class="pmfai-header"><div><h1>' . esc_html($title) . '</h1>';
        if ($description) {
            echo '<p>' . esc_html($description) . '</p>';
        }
        echo '</div><span class="pmfai-badge">v' . esc_html(PMFAI_VERSION) . '</span></div>';
    }

    public static function page_dashboard(): void
    {
        echo '<div class="wrap pmfai-wrap">';
        self::page_header('Pmedia Flatsome AI Toolkit', 'Design System + ChatGPT Bridge + CSS Validator cho Flatsome.');
        echo '<div class="pmfai-panel"><h2>3 workflow chính</h2><div class="pmfai-grid-3">';
        echo '<div class="pmfai-card"><h3>Auto Mode</h3><p>Plugin gọi AI API trực tiếp. Phù hợp block đơn giản.</p></div>';
        echo '<div class="pmfai-card"><h3>Bridge Mode</h3><p>Plugin tạo prompt, ChatGPT xử lý ảnh/mô tả, paste kết quả về plugin.</p></div>';
        echo '<div class="pmfai-card"><h3>Manual Mode</h3><p>ChatGPT sinh code, plugin kiểm tra CSS Safety và Flatsome Compatibility.</p></div>';
        echo '</div></div>';
        echo '<div class="pmfai-panel"><h2>Quy trình với ảnh mẫu</h2><ol><li>Vào ChatGPT Bridge.</li><li>Copy prompt sang ChatGPT và đính kèm ảnh.</li><li>Yêu cầu output dạng <code>pmedia-flatsome-block</code>.</li><li>Paste vào Import From ChatGPT để parse/validate.</li><li>Copy HTML vào Flatsome HTML Block.</li></ol></div>';
        echo '</div>';
    }

    public static function page_design_system(): void
    {
        $options = self::get_options();
        echo '<div class="wrap pmfai-wrap">';
        self::page_header('Design System', 'Chuẩn hóa màu, radius, spacing và CSS component layer.');
        echo '<form method="post" action="options.php"><div class="pmfai-panel"><div class="pmfai-grid-2">';
        settings_fields('pmfai_settings_group');
        foreach ([
            'primary_color' => 'Màu chính', 'secondary_color' => 'Màu phụ', 'accent_color' => 'Màu nhấn',
            'text_color' => 'Màu chữ', 'muted_color' => 'Màu chữ phụ', 'border_color' => 'Màu viền', 'bg_soft_color' => 'Màu nền nhẹ',
            'radius_sm' => 'Radius nhỏ', 'radius_md' => 'Radius vừa', 'radius_lg' => 'Radius lớn',
            'section_padding_desktop' => 'Section padding desktop', 'section_padding_mobile' => 'Section padding mobile',
        ] as $key => $label) {
            self::field($key, $label);
        }
        echo '</div><label class="pmfai-check"><input type="checkbox" name="' . esc_attr(PMFAI_OPTION_KEY . '[enable_frontend_css]') . '" value="1" ' . checked($options['enable_frontend_css'], '1', false) . '> Bật CSS Toolkit ở frontend</label><p>';
        submit_button('Lưu Design System', 'primary', 'submit', false);
        echo '</p></div></form></div>';
    }

    public static function page_settings(): void
    {
        $options = self::get_options();
        echo '<div class="wrap pmfai-wrap">';
        self::page_header('Settings', 'API chỉ dùng cho Auto Mode. Bridge/Manual Mode không gọi API plugin.');
        echo '<form method="post" action="options.php"><div class="pmfai-panel"><div class="pmfai-grid-2">';
        settings_fields('pmfai_settings_group');
        foreach (['api_endpoint' => 'API endpoint', 'api_key' => 'API key', 'api_model' => 'Model', 'temperature' => 'Temperature'] as $key => $label) {
            self::field($key, $label, $key === 'api_key' ? 'password' : 'text');
        }
        echo '</div><label class="pmfai-field"><span>Rule bổ sung</span><textarea name="' . esc_attr(PMFAI_OPTION_KEY . '[extra_rules]') . '" rows="5">' . esc_textarea($options['extra_rules']) . '</textarea></label><p>';
        submit_button('Lưu Settings', 'primary', 'submit', false);
        echo '</p></div></form></div>';
    }

    public static function page_chatgpt_bridge(): void
    {
        echo '<div class="wrap pmfai-wrap">';
        self::page_header('ChatGPT Bridge', 'Tạo prompt chuẩn để dùng ChatGPT web, giảm chi phí API plugin.');
        echo '<div class="pmfai-panel"><div class="pmfai-grid-2"><label class="pmfai-field"><span>Loại yêu cầu</span><select id="pmfai-bridge-type">';
        foreach (self::prompt_types() as $key => $label) {
            echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }
        echo '</select></label><label class="pmfai-field"><span>Ngành nghề</span><input id="pmfai-bridge-industry" value="doanh nghiệp dịch vụ"></label><label class="pmfai-field"><span>Phong cách</span><input id="pmfai-bridge-style" value="hiện đại, chuyên nghiệp"></label><label class="pmfai-field"><span>Mục tiêu</span><input id="pmfai-bridge-goal" value="Tạo HTML Block copy vào Flatsome"></label></div><label class="pmfai-field"><span>Nội dung / mô tả</span><textarea id="pmfai-bridge-content" rows="5"></textarea></label><p><button class="button button-primary" id="pmfai-build-prompt">Tạo prompt</button> <button class="button pmfai-copy" data-target="pmfai-bridge-output">Copy prompt</button></p><textarea id="pmfai-bridge-output" rows="18" readonly></textarea></div></div>';
    }

    public static function page_import_chatgpt(): void
    {
        echo '<div class="wrap pmfai-wrap">';
        self::page_header('Import From ChatGPT', 'Paste block pmedia-flatsome-block để parse, validate và copy code.');
        echo '<div class="pmfai-panel"><label class="pmfai-field"><span>Kết quả từ ChatGPT</span><textarea id="pmfai-import-raw" rows="14"></textarea></label><p><button class="button button-primary" id="pmfai-parse-chatgpt">Parse + Validate</button></p><div id="pmfai-import-result"></div></div></div>';
    }

    public static function page_validate_code(): void
    {
        echo '<div class="wrap pmfai-wrap">';
        self::page_header('Clean / Validate Code', 'Kiểm tra CSS global, selector nguy hiểm và độ tương thích Flatsome.');
        echo '<div class="pmfai-panel"><div class="pmfai-grid-2"><label class="pmfai-field"><span>HTML</span><textarea id="pmfai-clean-html" rows="14"></textarea></label><label class="pmfai-field"><span>CSS</span><textarea id="pmfai-clean-css" rows="14"></textarea></label></div><p><button class="button button-primary" id="pmfai-validate-code">Validate</button></p><div id="pmfai-clean-result"></div></div></div>';
    }

    public static function page_prompt_library(): void
    {
        echo '<div class="wrap pmfai-wrap">';
        self::page_header('Prompt Library', 'Prompt mẫu để copy sang ChatGPT.');
        foreach (self::prompt_types() as $key => $label) {
            $id = 'pmfai-prompt-' . esc_attr($key);
            echo '<div class="pmfai-panel"><h2>' . esc_html($label) . '</h2><textarea id="' . $id . '" rows="12" readonly>' . esc_textarea(self::build_prompt($key)) . '</textarea><p><button class="button pmfai-copy" data-target="' . $id . '">Copy Prompt</button></p></div>';
        }
        echo '</div>';
    }

    private static function field(string $key, string $label, string $type = 'text'): void
    {
        $options = self::get_options();
        echo '<label class="pmfai-field"><span>' . esc_html($label) . '</span><input type="' . esc_attr($type) . '" name="' . esc_attr(PMFAI_OPTION_KEY . '[' . $key . ']') . '" value="' . esc_attr($options[$key] ?? '') . '"></label>';
    }

    public static function register_rest_routes(): void
    {
        register_rest_route('pmedia-ai/v1', '/bridge-prompt', ['methods' => 'POST', 'callback' => [__CLASS__, 'api_bridge_prompt'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/parse-chatgpt-block', ['methods' => 'POST', 'callback' => [__CLASS__, 'api_parse_chatgpt_block'], 'permission_callback' => [__CLASS__, 'can_manage']]);
        register_rest_route('pmedia-ai/v1', '/validate-code', ['methods' => 'POST', 'callback' => [__CLASS__, 'api_validate_code'], 'permission_callback' => [__CLASS__, 'can_manage']]);
    }

    public static function can_manage(): bool
    {
        return current_user_can('manage_options');
    }

    public static function api_bridge_prompt(WP_REST_Request $request)
    {
        $params = $request->get_json_params() ?: [];
        return rest_ensure_response(['prompt' => self::build_prompt(sanitize_key($params['type'] ?? 'generate-from-description'), $params)]);
    }

    public static function api_parse_chatgpt_block(WP_REST_Request $request)
    {
        $parsed = self::parse_block($request->get_param('raw') ?: '');
        return is_wp_error($parsed) ? $parsed : rest_ensure_response($parsed);
    }

    public static function api_validate_code(WP_REST_Request $request)
    {
        return rest_ensure_response(self::analyze_code($request->get_param('html') ?: '', $request->get_param('css') ?: ''));
    }

    public static function prompt_types(): array
    {
        return [
            'analyze-image' => 'Phân tích ảnh giao diện',
            'generate-from-description' => 'Sinh HTML Block từ mô tả',
            'refactor-code' => 'Refactor HTML/CSS cũ',
            'convert-to-flatsome' => 'Chuyển layout sang Flatsome-compatible',
            'review-css' => 'Review CSS nguy hiểm',
            'ux-builder-guide' => 'Tạo hướng dẫn UX Builder',
        ];
    }

    public static function build_prompt(string $type, array $args = []): string
    {
        $args = wp_parse_args($args, [
            'site' => 'Website WordPress dùng theme Flatsome',
            'industry' => 'doanh nghiệp dịch vụ',
            'style' => 'hiện đại, chuyên nghiệp, dễ bán hàng',
            'goal' => 'Tạo giao diện section có thể copy vào Flatsome HTML Block.',
            'content' => '',
        ]);

        $tasks = [
            'analyze-image' => 'Tôi sẽ đính kèm ảnh giao diện mẫu. Hãy phân tích ảnh rồi dựng lại HTML Block tương thích Flatsome.',
            'generate-from-description' => 'Tạo HTML Block dựa trên mô tả sau.',
            'refactor-code' => 'Refactor đoạn HTML/CSS sau về chuẩn toolkit, xóa CSS global nguy hiểm và giảm CSS custom.',
            'convert-to-flatsome' => 'Chuyển layout/mô tả/code sau sang cấu trúc tương thích Flatsome HTML Block.',
            'review-css' => 'Review code sau, chỉ ra lỗi CSS safety, sau đó trả về phiên bản đã sửa.',
            'ux-builder-guide' => 'Tạo hướng dẫn dựng bằng UX Builder và HTML Block mẫu tối thiểu.',
        ];

        return "Bạn là chuyên gia chuyển đổi UI sang HTML Block tương thích WordPress Flatsome.\n\n"
            . "Bối cảnh: {$args['site']}\nNgành nghề: {$args['industry']}\nPhong cách: {$args['style']}\nMục tiêu: {$args['goal']}\n\n"
            . "Class ưu tiên: pm-section, pm-section-soft, pm-section-title, pm-eyebrow, pm-lead, pm-card, pm-feature-list, pm-cta-box, pm-pricing-card, pm-faq, pm-step, pm-hero-split, pm-service-grid, pmedia-ai-block, row, col, col-inner, button primary is-large.\n\n"
            . "Quy tắc bắt buộc:\n- Không dùng Tailwind/Bootstrap.\n- Không viết CSS global: body, html, *, a, img, h1-h6, .row, .col, .button, .container, .section.\n- Ưu tiên dùng class Flatsome và pm-* đã có.\n- Nếu cần CSS custom, scope bằng .pmedia-ai-block.\n- Không dùng class chung như .card, .title, .box, .item, .wrapper.\n- HTML không chứa html/head/body tag.\n- Output có wrapper .pmedia-ai-block.\n- Trả về đúng một code block markdown language pmedia-flatsome-block, bên trong là JSON hợp lệ gồm title,type,style,description,html,css,js,notes.\n\n"
            . "Nhiệm vụ: " . ($tasks[$type] ?? $tasks['generate-from-description']) . "\n\n"
            . "Nội dung/mô tả/code cần xử lý:\n" . ($args['content'] ?: '[Dán nội dung hoặc đính kèm ảnh ở ChatGPT]') . "\n\n"
            . "Ví dụ output:\n```pmedia-flatsome-block\n{\"title\":\"Tên block\",\"type\":\"hero\",\"style\":\"business\",\"description\":\"Mô tả ngắn\",\"html\":\"<section class=\\\"pm-section pmedia-ai-block\\\">...</section>\",\"css\":\"\",\"js\":\"\",\"notes\":[\"Ghi chú nếu có\"]}\n```";
    }

    public static function parse_block(string $raw)
    {
        $raw = wp_unslash($raw);
        if (preg_match('/```pmedia-flatsome-block\s*(.*?)```/is', $raw, $matches)) {
            $json = trim($matches[1]);
        } elseif (preg_match('/```json\s*(.*?)```/is', $raw, $matches)) {
            $json = trim($matches[1]);
        } else {
            $start = strpos($raw, '{');
            $end = strrpos($raw, '}');
            $json = ($start !== false && $end !== false && $end > $start) ? substr($raw, $start, $end - $start + 1) : '';
        }

        if (!$json) {
            return new WP_Error('parse_failed', 'Không tìm thấy JSON pmedia-flatsome-block hợp lệ.', ['status' => 400]);
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return new WP_Error('json_invalid', 'JSON không hợp lệ: ' . json_last_error_msg(), ['status' => 400]);
        }

        $html = (string)($data['html'] ?? '');
        $css = (string)($data['css'] ?? '');
        $analysis = self::analyze_code($html, $css);

        return [
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
    }

    public static function analyze_code(string $html, string $css): array
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

    public static function tokens_css(): string
    {
        $o = self::get_options();
        return ":root{--pm-color-primary:{$o['primary_color']};--pm-color-secondary:{$o['secondary_color']};--pm-color-accent:{$o['accent_color']};--pm-color-text:{$o['text_color']};--pm-color-muted:{$o['muted_color']};--pm-color-border:{$o['border_color']};--pm-color-bg-soft:{$o['bg_soft_color']};--pm-radius-sm:{$o['radius_sm']};--pm-radius-md:{$o['radius_md']};--pm-radius-lg:{$o['radius_lg']};--pm-section-padding:{$o['section_padding_desktop']};--pm-section-padding-mobile:{$o['section_padding_mobile']};--pm-shadow-sm:0 4px 16px rgba(15,23,42,.08);--pm-shadow-md:0 14px 36px rgba(15,23,42,.12)}";
    }

    public static function frontend_css(): string
    {
        return '.pm-section{padding-top:var(--pm-section-padding);padding-bottom:var(--pm-section-padding)}.pm-section-soft{background:var(--pm-color-bg-soft)}.pm-section-title{text-align:center;max-width:780px;margin:0 auto 36px}.pm-eyebrow{display:inline-block;color:var(--pm-color-primary);font-weight:800;text-transform:uppercase;font-size:13px;letter-spacing:.08em;margin-bottom:10px}.pm-lead{font-size:18px;line-height:1.7;color:var(--pm-color-muted)}.pm-card,.pm-cta-box,.pm-pricing-card,.pm-step{background:#fff;border:1px solid var(--pm-color-border);border-radius:var(--pm-radius-md);box-shadow:var(--pm-shadow-sm);padding:24px}.pm-card:hover,.pm-pricing-card:hover{box-shadow:var(--pm-shadow-md);transform:translateY(-2px)}.pm-hero-split,.pm-service-grid,.pm-pricing,.pm-process,.pm-stats{display:grid;gap:24px}.pm-hero-split{grid-template-columns:1.05fr .95fr;align-items:center}.pm-service-grid{grid-template-columns:repeat(3,1fr)}.pm-pricing,.pm-process,.pm-stats{grid-template-columns:repeat(3,1fr)}.pm-feature-list{margin:0;padding:0;list-style:none}.pm-feature-list li{margin:0 0 10px;padding-left:24px;position:relative}.pm-feature-list li:before{content:"✓";position:absolute;left:0;color:var(--pm-color-primary);font-weight:900}.pm-step-number{font-size:34px;font-weight:900;color:var(--pm-color-primary);line-height:1}.button.primary{background-color:var(--pm-color-primary);border-color:var(--pm-color-primary)}@media(max-width:849px){.pm-section{padding-top:var(--pm-section-padding-mobile);padding-bottom:var(--pm-section-padding-mobile)}.pm-hero-split,.pm-service-grid,.pm-pricing,.pm-process,.pm-stats{grid-template-columns:1fr}}';
    }

    public static function admin_css(): string
    {
        return '.pmfai-wrap{max-width:1180px}.pmfai-header{display:flex;justify-content:space-between;align-items:center;margin:24px 0}.pmfai-header h1{margin:0;font-size:28px}.pmfai-header p{margin:6px 0 0;color:#64748b}.pmfai-badge{background:#111827;color:#fff;border-radius:999px;padding:8px 12px}.pmfai-panel{background:#fff;border:1px solid #dcdcde;border-radius:14px;padding:22px;margin:18px 0;box-shadow:0 8px 24px rgba(15,23,42,.05)}.pmfai-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}.pmfai-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.pmfai-card{border:1px solid #e5e7eb;border-radius:12px;padding:18px;background:#f9fafb}.pmfai-field{display:block;margin-bottom:14px}.pmfai-field span{display:block;font-weight:700;margin-bottom:6px}.pmfai-field input,.pmfai-field textarea,.pmfai-field select{width:100%;max-width:100%;border-radius:8px}.pmfai-check{display:block;margin:18px 0}.pmfai-result pre{white-space:pre-wrap;background:#0f172a;color:#e5e7eb;border-radius:10px;padding:14px;overflow:auto}.pmfai-score{display:inline-block;margin-right:10px;padding:6px 10px;border-radius:999px;background:#eef2ff;font-weight:700}@media(max-width:900px){.pmfai-grid-2,.pmfai-grid-3{grid-template-columns:1fr}}';
    }

    public static function admin_js(): string
    {
        return "document.addEventListener('click',async function(e){const t=e.target;if(t.classList.contains('pmfai-copy')){e.preventDefault();const el=document.getElementById(t.dataset.target);if(el){navigator.clipboard.writeText(el.value||el.textContent||'');t.textContent='Copied';setTimeout(()=>t.textContent='Copy prompt',1200)}}if(t.id==='pmfai-build-prompt'){e.preventDefault();const body={type:document.getElementById('pmfai-bridge-type').value,industry:document.getElementById('pmfai-bridge-industry').value,style:document.getElementById('pmfai-bridge-style').value,goal:document.getElementById('pmfai-bridge-goal').value,content:document.getElementById('pmfai-bridge-content').value};const r=await fetch(PMFAI.restUrl+'/bridge-prompt',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':PMFAI.nonce},body:JSON.stringify(body)});const j=await r.json();document.getElementById('pmfai-bridge-output').value=j.prompt||''}if(t.id==='pmfai-parse-chatgpt'){e.preventDefault();const r=await fetch(PMFAI.restUrl+'/parse-chatgpt-block',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':PMFAI.nonce},body:JSON.stringify({raw:document.getElementById('pmfai-import-raw').value})});const j=await r.json();document.getElementById('pmfai-import-result').innerHTML=renderResult(j)}if(t.id==='pmfai-validate-code'){e.preventDefault();const r=await fetch(PMFAI.restUrl+'/validate-code',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':PMFAI.nonce},body:JSON.stringify({html:document.getElementById('pmfai-clean-html').value,css:document.getElementById('pmfai-clean-css').value})});const j=await r.json();document.getElementById('pmfai-clean-result').innerHTML=renderResult(j)}});function esc(s){return String(s||'').replace(/[&<>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]))}function renderResult(j){if(j.code&&j.message)return '<div class=\"notice notice-error\"><p>'+esc(j.message)+'</p></div>';let h='<div class=\"pmfai-result\">';if(j.scores)h+='<p><span class=\"pmfai-score\">CSS Safety: '+j.scores.css_safety+'/100</span><span class=\"pmfai-score\">Flatsome: '+j.scores.flatsome_compatibility+'/100</span></p>';if(j.warnings&&j.warnings.length)h+='<h3>Cảnh báo</h3><ul>'+j.warnings.map(x=>'<li>'+esc(x)+'</li>').join('')+'</ul>';if(j.suggestions&&j.suggestions.length)h+='<h3>Gợi ý</h3><ul>'+j.suggestions.map(x=>'<li>'+esc(x)+'</li>').join('')+'</ul>';if(j.html)h+='<h3>HTML</h3><pre>'+esc(j.html)+'</pre>';if(j.css)h+='<h3>CSS</h3><pre>'+esc(j.css)+'</pre>';return h+'</div>'}";
    }
}

register_activation_hook(__FILE__, ['PMFAI_Plugin', 'activate']);
add_action('plugins_loaded', ['PMFAI_Plugin', 'init']);
