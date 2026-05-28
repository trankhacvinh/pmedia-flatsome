<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Page_Insert_Box
{
    public static function register(): void
    {
        add_meta_box(
            'pmfai-page-insert-box',
            'Pmedia AI Insert',
            [__CLASS__, 'render'],
            'page',
            'side',
            'high'
        );
    }

    public static function render(WP_Post $post): void
    {
        if (!current_user_can('edit_post', $post->ID)) {
            return;
        }

        $content = (string)$post->post_content;
        $section_count = preg_match_all('/\[section\b/i', $content);
        $content_len = function_exists('mb_strlen') ? mb_strlen($content) : strlen($content);
        $excerpt = wp_trim_words(wp_strip_all_tags(strip_shortcodes($content)), 40, '...');

        echo '<div class="pmfai-insert-box" data-post-id="' . esc_attr($post->ID) . '">';
        echo '<p class="pmfai-insert-summary"><strong>Trang hiện tại</strong><br>Sections: ' . esc_html((string)$section_count) . ' · Length: ' . esc_html((string)$content_len) . ' chars</p>';
        if ($excerpt) {
            echo '<p class="pmfai-insert-excerpt">' . esc_html($excerpt) . '</p>';
        }
        echo '<label><span>Output mode</span><select id="pmfai-insert-output-mode"><option value="html-block">Section + HTML Block</option><option value="flatsome-native">Flatsome Native Shortcode</option></select></label>';
        echo '<label><span>Cost / Quality</span><select id="pmfai-insert-cost-mode"><option value="fast">Fast / Cheap</option><option value="balanced" selected>Balanced</option><option value="high">High Quality</option></select></label>';
        echo '<label><span>Thao tác</span><select id="pmfai-insert-action"><option value="append">Append vào cuối page</option><option value="prepend">Chèn lên đầu page</option><option value="replace">Replace toàn bộ page</option></select></label>';
        echo '<label><span>Yêu cầu AI</span><textarea id="pmfai-insert-brief" rows="7" placeholder="Ví dụ: Thêm section bảng giá 3 gói dịch vụ, phong cách chuyên nghiệp, có CTA tư vấn..."></textarea></label>';
        echo '<p><button type="button" class="button button-primary" id="pmfai-insert-generate">Generate & Insert</button></p>';
        echo '<p><button type="button" class="button" id="pmfai-insert-prompt">Tạo prompt ChatGPT</button></p>';
        echo '<textarea id="pmfai-insert-prompt-output" rows="8" readonly placeholder="Prompt sẽ hiện ở đây..."></textarea>';
        echo '<div id="pmfai-insert-result"></div>';
        echo '</div>';
    }

    public static function build_context(int $post_id): array
    {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'page') {
            return [];
        }

        $content = (string)$post->post_content;
        preg_match_all('/\[section\b[\s\S]*?\[\/section\]/i', $content, $matches);
        $sections = array_slice($matches[0] ?? [], 0, 12);

        return [
            'post_id' => $post_id,
            'title' => get_the_title($post_id),
            'slug' => $post->post_name,
            'content' => $content,
            'content_excerpt' => wp_trim_words(wp_strip_all_tags(strip_shortcodes($content)), 120, '...'),
            'section_count' => count($matches[0] ?? []),
            'sections_preview' => array_map(static function ($section) {
                return wp_trim_words(wp_strip_all_tags(strip_shortcodes($section)), 50, '...');
            }, $sections),
        ];
    }

    public static function apply_content(int $post_id, string $shortcode, string $action = 'append')
    {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'page') {
            return new WP_Error('invalid_page', 'Không tìm thấy page hợp lệ.', ['status' => 404]);
        }
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error('forbidden', 'Bạn không có quyền sửa page này.', ['status' => 403]);
        }

        $current = (string)$post->post_content;
        $shortcode = trim($shortcode);
        if ($shortcode === '') {
            return new WP_Error('empty_shortcode', 'Shortcode rỗng, không thể cập nhật page.', ['status' => 400]);
        }

        switch ($action) {
            case 'replace':
                $new_content = $shortcode;
                break;
            case 'prepend':
                $new_content = $shortcode . "\n\n" . $current;
                break;
            case 'append':
            default:
                $new_content = rtrim($current) . "\n\n" . $shortcode;
                break;
        }

        $updated = wp_update_post([
            'ID' => $post_id,
            'post_content' => $new_content,
        ], true);

        if (is_wp_error($updated)) {
            return $updated;
        }

        return [
            'updated' => true,
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'view_url' => get_permalink($post_id),
            'action' => $action,
            'shortcode' => $shortcode,
            'content_length' => strlen($new_content),
        ];
    }
}
