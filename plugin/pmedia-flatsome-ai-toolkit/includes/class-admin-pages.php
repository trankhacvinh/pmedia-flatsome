<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Admin_Pages
{
    public static function register_menu(): void
    {
        add_menu_page('Pmedia AI Builder', 'Pmedia AI Builder', 'manage_options', 'pmedia-ai-builder', [__CLASS__, 'dashboard'], 'dashicons-art', 58);
        add_submenu_page('pmedia-ai-builder', 'Dashboard', 'Dashboard', 'manage_options', 'pmedia-ai-builder', [__CLASS__, 'dashboard']);
        add_submenu_page('pmedia-ai-builder', 'Design System', 'Design System', 'manage_options', 'pmfai-design-system', [__CLASS__, 'design_system']);
        add_submenu_page('pmedia-ai-builder', 'Design System AI', 'Design System AI', 'manage_options', 'pmfai-design-system-ai', [__CLASS__, 'design_system_ai']);
        add_submenu_page('pmedia-ai-builder', 'Generate Block', 'Generate Block', 'manage_options', 'pmfai-generate-block', [__CLASS__, 'generate_block']);
        add_submenu_page('pmedia-ai-builder', 'Page Builder AI', 'Page Builder AI', 'manage_options', 'pmfai-page-builder-ai', [__CLASS__, 'page_builder_ai']);
        add_submenu_page('pmedia-ai-builder', 'ChatGPT Bridge', 'ChatGPT Bridge', 'manage_options', 'pmfai-chatgpt-bridge', [__CLASS__, 'chatgpt_bridge']);
        add_submenu_page('pmedia-ai-builder', 'Import From ChatGPT', 'Import From ChatGPT', 'manage_options', 'pmfai-import-chatgpt', [__CLASS__, 'import_chatgpt']);
        add_submenu_page('pmedia-ai-builder', 'Clean / Validate Code', 'Clean / Validate Code', 'manage_options', 'pmfai-clean-code', [__CLASS__, 'validate_code']);
        add_submenu_page('pmedia-ai-builder', 'Block Library', 'Block Library', 'manage_options', 'pmfai-block-library', [__CLASS__, 'block_library']);
        add_submenu_page('pmedia-ai-builder', 'Usage Logs', 'Usage Logs', 'manage_options', 'pmfai-usage-logs', [__CLASS__, 'usage_logs']);
        add_submenu_page('pmedia-ai-builder', 'Prompt Library', 'Prompt Library', 'manage_options', 'pmfai-prompt-library', [__CLASS__, 'prompt_library']);
        add_submenu_page('pmedia-ai-builder', 'Settings', 'Settings', 'manage_options', 'pmfai-settings', [__CLASS__, 'settings']);
    }

    private static function header(string $title, string $description = ''): void
    {
        echo '<div class="pmfai-header"><div><h1>' . esc_html($title) . '</h1>';
        if ($description) { echo '<p>' . esc_html($description) . '</p>'; }
        echo '</div><span class="pmfai-badge">v' . esc_html(PMFAI_VERSION) . '</span></div>';
    }

    public static function dashboard(): void
    {
        echo '<div class="wrap pmfai-wrap">';
        self::header('Pmedia Flatsome AI Toolkit', 'Design System + Block Builder + Page Builder AI cho Flatsome.');
        echo '<div class="pmfai-panel"><h2>Workflow chính</h2><div class="pmfai-grid-3">';
        echo '<div class="pmfai-card"><h3>Design System AI</h3><p>Sinh hoặc import JSON để chuẩn hóa màu, radius, spacing.</p></div>';
        echo '<div class="pmfai-card"><h3>Generate Block</h3><p>Sinh từng section, validate, preview, lưu vào Library.</p></div>';
        echo '<div class="pmfai-card"><h3>Page Builder AI</h3><p>Sinh cả trang hoặc từng phần, ghép thành Flatsome shortcode.</p></div>';
        echo '</div></div></div>';
    }

    public static function design_system(): void
    {
        $options = PMFAI_Settings::get_options();
        echo '<div class="wrap pmfai-wrap">'; self::header('Design System', 'Chuẩn hóa màu, radius, spacing và CSS component layer.');
        echo '<form method="post" action="options.php"><div class="pmfai-panel"><div class="pmfai-grid-2">'; settings_fields('pmfai_settings_group');
        foreach (['primary_color'=>'Màu chính','secondary_color'=>'Màu phụ','accent_color'=>'Màu nhấn','text_color'=>'Màu chữ','muted_color'=>'Màu chữ phụ','border_color'=>'Màu viền','bg_soft_color'=>'Màu nền nhẹ','radius_sm'=>'Radius nhỏ','radius_md'=>'Radius vừa','radius_lg'=>'Radius lớn','section_padding_desktop'=>'Section padding desktop','section_padding_mobile'=>'Section padding mobile'] as $key => $label) { self::field($key, $label); }
        echo '</div><label class="pmfai-check"><input type="checkbox" name="' . esc_attr(PMFAI_OPTION_KEY . '[enable_frontend_css]') . '" value="1" ' . checked($options['enable_frontend_css'], '1', false) . '> Bật CSS Toolkit ở frontend</label><p>'; submit_button('Lưu Design System', 'primary', 'submit', false); echo '</p></div></form></div>';
    }

    public static function design_system_ai(): void
    {
        echo '<div class="wrap pmfai-wrap">'; self::header('Design System AI', 'Sinh Design System bằng AI hoặc import JSON từ ChatGPT.');
        echo '<div class="pmfai-panel"><h2>1. Brief</h2><label class="pmfai-field"><span>Mô tả website / thương hiệu / ảnh mẫu</span><textarea id="pmfai-ds-brief" rows="7" placeholder="Ví dụ: Website nha khoa cao cấp, sạch, đáng tin, màu xanh trắng, khách hàng 25-45 tuổi..."></textarea></label><p><button class="button button-primary" id="pmfai-ds-generate">Generate bằng AI</button> <button class="button" id="pmfai-ds-bridge">Tạo prompt ChatGPT</button></p><textarea id="pmfai-ds-prompt" rows="10" readonly placeholder="Prompt bridge sẽ hiện ở đây..."></textarea></div>';
        echo '<div class="pmfai-panel"><h2>2. Import JSON</h2><label class="pmfai-field"><span>Dán JSON Design System</span><textarea id="pmfai-ds-json" rows="12"></textarea></label><p><button class="button" id="pmfai-ds-import">Validate JSON</button> <button class="button button-primary" id="pmfai-ds-apply">Apply vào Settings</button></p><div id="pmfai-ds-result"></div></div></div>';
    }

    public static function generate_block(): void
    {
        echo '<div class="wrap pmfai-wrap">'; self::header('Generate Block', 'Auto Mode: plugin gọi AI API trực tiếp, sau đó parse, validate, preview và lưu Library.');
        echo '<div class="pmfai-panel"><div class="pmfai-grid-2"><label class="pmfai-field"><span>Loại yêu cầu</span><select id="pmfai-generate-type">'; foreach (PMFAI_Prompt_Builder::types() as $key => $label) { echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>'; }
        echo '</select></label><label class="pmfai-field"><span>Cost / Quality Mode</span><select id="pmfai-generate-cost-mode"><option value="fast">Fast / Cheap</option><option value="balanced" selected>Balanced</option><option value="high">High Quality</option></select></label><label class="pmfai-field"><span>Ngành nghề</span><input id="pmfai-generate-industry" value="doanh nghiệp dịch vụ"></label><label class="pmfai-field"><span>Phong cách</span><input id="pmfai-generate-style" value="hiện đại, chuyên nghiệp"></label><label class="pmfai-field"><span>Mục tiêu</span><input id="pmfai-generate-goal" value="Tạo HTML Block copy vào Flatsome"></label></div><div class="pmfai-mode-help"><strong>Gợi ý:</strong> Fast dùng cho section đơn giản; Balanced dùng hằng ngày; High Quality dùng cho hero/landing page quan trọng.</div><label class="pmfai-field"><span>Nội dung / mô tả block</span><textarea id="pmfai-generate-content" rows="7"></textarea></label><p><button class="button button-primary" id="pmfai-generate-block">Generate Block</button></p><div id="pmfai-generate-result"></div></div></div>';
    }

    public static function page_builder_ai(): void
    {
        echo '<div class="wrap pmfai-wrap">'; self::header('Page Builder AI', 'Sinh từng phần hoặc cả trang, ghép thành shortcode Flatsome.');
        echo '<div class="pmfai-panel"><div class="pmfai-grid-2"><label class="pmfai-field"><span>Build mode</span><select id="pmfai-page-build-mode"><option value="full-page">Sinh cả trang</option><option value="single-section">Sinh từng section</option></select></label><label class="pmfai-field"><span>Output mode</span><select id="pmfai-page-output-mode"><option value="html-block" selected>Flatsome Section + HTML Block</option><option value="flatsome-native">Flatsome Native Shortcode nhiều hơn</option></select></label><label class="pmfai-field"><span>Cost / Quality Mode</span><select id="pmfai-page-cost-mode"><option value="fast">Fast / Cheap</option><option value="balanced" selected>Balanced</option><option value="high">High Quality</option></select></label></div><div class="pmfai-mode-help"><strong>Output mode:</strong> HTML Block an toàn hơn; Native Shortcode dễ chỉnh trong UX Builder hơn nhưng cần kiểm tra kỹ shortcode Flatsome.</div><label class="pmfai-field"><span>Brief trang</span><textarea id="pmfai-page-brief" rows="8" placeholder="Ví dụ: Tạo trang chủ cho công ty thiết kế website, gồm hero, dịch vụ, quy trình, dự án, FAQ, CTA..."></textarea></label><p><button class="button button-primary" id="pmfai-page-generate">Generate Page</button> <button class="button" id="pmfai-page-bridge">Tạo prompt ChatGPT</button></p><textarea id="pmfai-page-prompt" rows="10" readonly></textarea></div>';
        echo '<div class="pmfai-panel"><h2>Import / Result JSON</h2><textarea id="pmfai-page-json" rows="12"></textarea><p><button class="button" id="pmfai-page-import">Validate/Ghép shortcode</button> <button class="button button-primary" id="pmfai-page-create-draft">Create WordPress Page Draft</button></p><div id="pmfai-page-result"></div></div></div>';
    }

    public static function settings(): void
    {
        $options = PMFAI_Settings::get_options(); echo '<div class="wrap pmfai-wrap">'; self::header('Settings', 'API chỉ dùng cho Auto Mode. Bridge/Manual Mode không gọi API plugin.'); echo '<form method="post" action="options.php"><div class="pmfai-panel"><h2>API mặc định</h2><div class="pmfai-grid-2">'; settings_fields('pmfai_settings_group'); foreach (['api_endpoint'=>'API endpoint','api_key'=>'API key','api_model'=>'Model mặc định','temperature'=>'Temperature mặc định'] as $key => $label) { self::field($key, $label, $key === 'api_key' ? 'password' : 'text'); } echo '</div><h2>Model theo Cost / Quality Mode</h2><p class="description">Nếu để trống model của từng mode, plugin sẽ dùng Model mặc định ở trên.</p><div class="pmfai-grid-2">'; foreach (['fast_model'=>'Fast / Cheap model','fast_temperature'=>'Fast / Cheap temperature','balanced_model'=>'Balanced model','balanced_temperature'=>'Balanced temperature','high_model'=>'High Quality model','high_temperature'=>'High Quality temperature'] as $key => $label) { self::field($key, $label); } echo '</div><label class="pmfai-field"><span>Rule bổ sung</span><textarea name="' . esc_attr(PMFAI_OPTION_KEY . '[extra_rules]') . '" rows="5">' . esc_textarea($options['extra_rules']) . '</textarea></label><p>'; submit_button('Lưu Settings', 'primary', 'submit', false); echo '</p></div></form></div>';
    }

    public static function chatgpt_bridge(): void { echo '<div class="wrap pmfai-wrap">'; self::header('ChatGPT Bridge', 'Tạo prompt chuẩn để dùng ChatGPT web, giảm chi phí API plugin.'); echo '<div class="pmfai-panel"><div class="pmfai-grid-2"><label class="pmfai-field"><span>Loại yêu cầu</span><select id="pmfai-bridge-type">'; foreach (PMFAI_Prompt_Builder::types() as $key => $label) { echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>'; } echo '</select></label><label class="pmfai-field"><span>Ngành nghề</span><input id="pmfai-bridge-industry" value="doanh nghiệp dịch vụ"></label><label class="pmfai-field"><span>Phong cách</span><input id="pmfai-bridge-style" value="hiện đại, chuyên nghiệp"></label><label class="pmfai-field"><span>Mục tiêu</span><input id="pmfai-bridge-goal" value="Tạo HTML Block copy vào Flatsome"></label></div><label class="pmfai-field"><span>Nội dung / mô tả</span><textarea id="pmfai-bridge-content" rows="5"></textarea></label><p><button class="button button-primary" id="pmfai-build-prompt">Tạo prompt</button> <button class="button pmfai-copy" data-target="pmfai-bridge-output">Copy prompt</button></p><textarea id="pmfai-bridge-output" rows="18" readonly></textarea></div></div>'; }
    public static function import_chatgpt(): void { echo '<div class="wrap pmfai-wrap">'; self::header('Import From ChatGPT', 'Paste block pmedia-flatsome-block để parse, validate, lưu Library và copy code.'); echo '<div class="pmfai-panel"><label class="pmfai-field"><span>Kết quả từ ChatGPT</span><textarea id="pmfai-import-raw" rows="14"></textarea></label><p><button class="button button-primary" id="pmfai-parse-chatgpt">Parse + Validate</button></p><div id="pmfai-import-result"></div></div></div>'; }
    public static function validate_code(): void { echo '<div class="wrap pmfai-wrap">'; self::header('Clean / Validate Code', 'Kiểm tra và tự sửa CSS global, selector nguy hiểm, độ tương thích Flatsome.'); echo '<div class="pmfai-panel"><div class="pmfai-grid-2"><label class="pmfai-field"><span>HTML</span><textarea id="pmfai-clean-html" rows="14"></textarea></label><label class="pmfai-field"><span>CSS</span><textarea id="pmfai-clean-css" rows="14"></textarea></label></div><p><button class="button" id="pmfai-validate-code">Validate</button> <button class="button button-primary" id="pmfai-auto-fix-code">Auto Fix</button></p><div id="pmfai-clean-result"></div></div></div>'; }
    public static function block_library(): void { echo '<div class="wrap pmfai-wrap">'; self::header('Block Library', 'Kho block đã import/generate để tái sử dụng cho Flatsome.'); echo '<div class="pmfai-panel"><div class="pmfai-toolbar"><input id="pmfai-library-search" placeholder="Tìm theo tên block..."> <input id="pmfai-library-industry" placeholder="Ngành nghề..."> <select id="pmfai-library-type"><option value="">Tất cả loại</option><option value="hero">Hero</option><option value="service">Service</option><option value="pricing">Pricing</option><option value="faq">FAQ</option><option value="cta">CTA</option><option value="custom">Custom</option></select> <button class="button button-primary" id="pmfai-load-library">Tải danh sách</button></div><div class="pmfai-import-json"><h3>Import block JSON</h3><textarea id="pmfai-import-json" rows="6" placeholder="Dán JSON block đã export tại đây..."></textarea><p><button class="button" id="pmfai-import-json-button">Import JSON</button></p></div><div id="pmfai-library-result"></div></div></div>'; }
    public static function usage_logs(): void { echo '<div class="wrap pmfai-wrap">'; self::header('Usage Logs', 'Theo dõi lượt gọi AI, mode, model, trạng thái, token và thời gian xử lý.'); echo '<div class="pmfai-panel"><div class="pmfai-toolbar"><select id="pmfai-usage-status"><option value="">Tất cả trạng thái</option><option value="success">Success</option><option value="error">Error</option></select> <select id="pmfai-usage-mode"><option value="">Tất cả mode</option><option value="fast">Fast / Cheap</option><option value="balanced">Balanced</option><option value="high">High Quality</option></select> <button class="button button-primary" id="pmfai-load-usage">Tải logs</button></div><div id="pmfai-usage-result"></div></div></div>'; }
    public static function prompt_library(): void { echo '<div class="wrap pmfai-wrap">'; self::header('Prompt Library', 'Prompt mẫu để copy sang ChatGPT.'); foreach (PMFAI_Prompt_Builder::types() as $key => $label) { $id = 'pmfai-prompt-' . esc_attr($key); echo '<div class="pmfai-panel"><h2>' . esc_html($label) . '</h2><textarea id="' . $id . '" rows="12" readonly>' . esc_textarea(PMFAI_Prompt_Builder::build($key)) . '</textarea><p><button class="button pmfai-copy" data-target="' . $id . '">Copy Prompt</button></p></div>'; } echo '</div>'; }

    private static function field(string $key, string $label, string $type = 'text'): void
    {
        $options = PMFAI_Settings::get_options();
        echo '<label class="pmfai-field"><span>' . esc_html($label) . '</span><input type="' . esc_attr($type) . '" name="' . esc_attr(PMFAI_OPTION_KEY . '[' . $key . ']') . '" value="' . esc_attr($options[$key] ?? '') . '"></label>';
    }
}
