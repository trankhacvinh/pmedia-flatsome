<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Block_Library
{
    public static function save(array $data)
    {
        $title = sanitize_text_field($data['title'] ?? 'Untitled Block');
        $post_id = wp_insert_post([
            'post_type' => 'pmedia_ai_block',
            'post_title' => $title ?: 'Untitled Block',
            'post_status' => 'publish',
        ], true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        self::update_meta($post_id, $data);
        return self::get($post_id);
    }

    public static function update(int $post_id, array $data)
    {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'pmedia_ai_block') {
            return new WP_Error('not_found', 'Không tìm thấy block.', ['status' => 404]);
        }

        if (isset($data['title'])) {
            wp_update_post([
                'ID' => $post_id,
                'post_title' => sanitize_text_field($data['title']) ?: get_the_title($post_id),
            ]);
        }

        self::update_meta($post_id, $data);
        return self::get($post_id);
    }

    public static function duplicate(int $post_id)
    {
        $block = self::get($post_id);
        if (is_wp_error($block)) {
            return $block;
        }

        $block['title'] = ($block['title'] ?: 'Untitled Block') . ' - Copy';
        $block['source'] = 'duplicate';
        return self::save($block);
    }

    public static function export(int $post_id)
    {
        $block = self::get($post_id);
        if (is_wp_error($block)) {
            return $block;
        }

        $block['exported_by'] = 'Pmedia Flatsome AI Toolkit';
        $block['export_version'] = PMFAI_VERSION;
        $block['exported_at'] = current_time('mysql');
        return $block;
    }

    public static function import(array $data)
    {
        if (!empty($data['block']) && is_array($data['block'])) {
            $data = $data['block'];
        }

        if (empty($data['html'])) {
            return new WP_Error('invalid_block', 'Block import thiếu HTML.', ['status' => 400]);
        }

        $data['source'] = 'json-import';
        return self::save($data);
    }

    public static function list(array $args = []): array
    {
        $args = wp_parse_args($args, [
            'posts_per_page' => 30,
            'paged' => 1,
            's' => '',
            'type' => '',
            'industry' => '',
        ]);

        $query_args = [
            'post_type' => 'pmedia_ai_block',
            'post_status' => 'publish',
            'posts_per_page' => absint($args['posts_per_page']),
            'paged' => max(1, absint($args['paged'])),
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if (!empty($args['s'])) {
            $query_args['s'] = sanitize_text_field($args['s']);
        }

        $meta_query = [];
        if (!empty($args['type'])) {
            $meta_query[] = [
                'key' => '_pmfai_type',
                'value' => sanitize_key($args['type']),
            ];
        }
        if (!empty($args['industry'])) {
            $meta_query[] = [
                'key' => '_pmfai_industry',
                'value' => sanitize_text_field($args['industry']),
                'compare' => 'LIKE',
            ];
        }
        if ($meta_query) {
            $query_args['meta_query'] = $meta_query;
        }

        $q = new WP_Query($query_args);
        $items = [];
        foreach ($q->posts as $post) {
            $items[] = self::format_post($post);
        }

        return [
            'items' => $items,
            'total' => (int)$q->found_posts,
            'pages' => (int)$q->max_num_pages,
        ];
    }

    public static function get(int $post_id)
    {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'pmedia_ai_block') {
            return new WP_Error('not_found', 'Không tìm thấy block.', ['status' => 404]);
        }
        return self::format_post($post, true);
    }

    public static function delete(int $post_id)
    {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'pmedia_ai_block') {
            return new WP_Error('not_found', 'Không tìm thấy block.', ['status' => 404]);
        }
        wp_delete_post($post_id, true);
        return ['deleted' => true, 'id' => $post_id];
    }

    private static function update_meta(int $post_id, array $data): void
    {
        $fields = [
            '_pmfai_type' => sanitize_key($data['type'] ?? 'custom'),
            '_pmfai_style' => sanitize_text_field($data['style'] ?? ''),
            '_pmfai_industry' => sanitize_text_field($data['industry'] ?? ''),
            '_pmfai_tags' => sanitize_text_field($data['tags'] ?? ''),
            '_pmfai_description' => sanitize_textarea_field($data['description'] ?? ''),
            '_pmfai_html' => (string)($data['html'] ?? ''),
            '_pmfai_css' => (string)($data['css'] ?? ''),
            '_pmfai_js' => (string)($data['js'] ?? ''),
            '_pmfai_notes' => wp_json_encode($data['notes'] ?? []),
            '_pmfai_scores' => wp_json_encode($data['scores'] ?? []),
            '_pmfai_source' => sanitize_text_field($data['source'] ?? 'chatgpt'),
        ];

        foreach ($fields as $key => $value) {
            if (array_key_exists(str_replace('_pmfai_', '', $key), $data) || $key === '_pmfai_source') {
                update_post_meta($post_id, $key, $value);
            }
        }
    }

    private static function format_post(WP_Post $post, bool $full = false): array
    {
        $scores = json_decode((string)get_post_meta($post->ID, '_pmfai_scores', true), true);
        $notes = json_decode((string)get_post_meta($post->ID, '_pmfai_notes', true), true);

        $item = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'type' => (string)get_post_meta($post->ID, '_pmfai_type', true),
            'style' => (string)get_post_meta($post->ID, '_pmfai_style', true),
            'industry' => (string)get_post_meta($post->ID, '_pmfai_industry', true),
            'tags' => (string)get_post_meta($post->ID, '_pmfai_tags', true),
            'description' => (string)get_post_meta($post->ID, '_pmfai_description', true),
            'scores' => is_array($scores) ? $scores : [],
            'notes' => is_array($notes) ? $notes : [],
            'source' => (string)get_post_meta($post->ID, '_pmfai_source', true),
            'created_at' => get_date_from_gmt($post->post_date_gmt, 'Y-m-d H:i:s'),
            'updated_at' => get_date_from_gmt($post->post_modified_gmt, 'Y-m-d H:i:s'),
        ];

        if ($full) {
            $item['html'] = (string)get_post_meta($post->ID, '_pmfai_html', true);
            $item['css'] = (string)get_post_meta($post->ID, '_pmfai_css', true);
            $item['js'] = (string)get_post_meta($post->ID, '_pmfai_js', true);
        }

        return $item;
    }
}
