<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Usage_Logger
{
    public static function register(): void
    {
        register_post_type('pmedia_ai_usage', [
            'labels' => [
                'name' => 'Pmedia AI Usage Logs',
                'singular_name' => 'Pmedia AI Usage Log',
            ],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title'],
        ]);
    }

    public static function log(array $data): int
    {
        $status = sanitize_key($data['status'] ?? 'unknown');
        $action = sanitize_key($data['action'] ?? 'generate-block');
        $title = strtoupper($status) . ' - ' . $action . ' - ' . current_time('Y-m-d H:i:s');
        $options = PMFAI_Settings::get_options();
        $provider = sanitize_key($data['provider'] ?? PMFAI_AI_Provider_Manager::provider_id($options));

        $post_id = wp_insert_post([
            'post_type' => 'pmedia_ai_usage',
            'post_title' => $title,
            'post_status' => 'publish',
        ], true);

        if (is_wp_error($post_id)) {
            return 0;
        }

        $user = wp_get_current_user();
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];

        $fields = [
            '_pmfai_action' => $action,
            '_pmfai_status' => $status,
            '_pmfai_provider' => $provider,
            '_pmfai_mode' => sanitize_key($data['mode'] ?? ''),
            '_pmfai_model' => sanitize_text_field($data['model'] ?? ''),
            '_pmfai_type' => sanitize_key($data['type'] ?? ''),
            '_pmfai_industry' => sanitize_text_field($data['industry'] ?? ''),
            '_pmfai_duration_ms' => absint($data['duration_ms'] ?? 0),
            '_pmfai_prompt_tokens' => absint($usage['prompt_tokens'] ?? 0),
            '_pmfai_completion_tokens' => absint($usage['completion_tokens'] ?? 0),
            '_pmfai_total_tokens' => absint($usage['total_tokens'] ?? 0),
            '_pmfai_error_message' => sanitize_textarea_field($data['error_message'] ?? ''),
            '_pmfai_http_code' => absint($data['http_code'] ?? 0),
            '_pmfai_user_id' => absint($user->ID ?? 0),
            '_pmfai_user_login' => sanitize_text_field($user->user_login ?? ''),
        ];

        foreach ($fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        return $post_id;
    }

    public static function list(array $args = []): array
    {
        $args = wp_parse_args($args, [
            'posts_per_page' => 50,
            'paged' => 1,
            'status' => '',
            'mode' => '',
            'provider' => '',
        ]);

        $query_args = [
            'post_type' => 'pmedia_ai_usage',
            'post_status' => 'publish',
            'posts_per_page' => absint($args['posts_per_page']),
            'paged' => max(1, absint($args['paged'])),
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $meta_query = [];
        if (!empty($args['status'])) {
            $meta_query[] = ['key' => '_pmfai_status', 'value' => sanitize_key($args['status'])];
        }
        if (!empty($args['mode'])) {
            $meta_query[] = ['key' => '_pmfai_mode', 'value' => sanitize_key($args['mode'])];
        }
        if (!empty($args['provider'])) {
            $meta_query[] = ['key' => '_pmfai_provider', 'value' => sanitize_key($args['provider'])];
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
            'summary' => self::summary(),
        ];
    }

    public static function summary(): array
    {
        $q = new WP_Query([
            'post_type' => 'pmedia_ai_usage',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $summary = [
            'recent_count' => (int)$q->found_posts,
            'success' => 0,
            'error' => 0,
            'total_tokens' => 0,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'providers' => [],
        ];

        foreach ($q->posts as $post) {
            $status = get_post_meta($post->ID, '_pmfai_status', true);
            if ($status === 'success') { $summary['success']++; }
            if ($status === 'error') { $summary['error']++; }
            $summary['total_tokens'] += absint(get_post_meta($post->ID, '_pmfai_total_tokens', true));
            $summary['prompt_tokens'] += absint(get_post_meta($post->ID, '_pmfai_prompt_tokens', true));
            $summary['completion_tokens'] += absint(get_post_meta($post->ID, '_pmfai_completion_tokens', true));
            $provider = (string)get_post_meta($post->ID, '_pmfai_provider', true);
            if ($provider !== '') {
                $summary['providers'][$provider] = ($summary['providers'][$provider] ?? 0) + 1;
            }
        }

        return $summary;
    }

    private static function format_post(WP_Post $post): array
    {
        return [
            'id' => $post->ID,
            'created_at' => get_date_from_gmt($post->post_date_gmt, 'Y-m-d H:i:s'),
            'action' => (string)get_post_meta($post->ID, '_pmfai_action', true),
            'status' => (string)get_post_meta($post->ID, '_pmfai_status', true),
            'provider' => (string)get_post_meta($post->ID, '_pmfai_provider', true),
            'mode' => (string)get_post_meta($post->ID, '_pmfai_mode', true),
            'model' => (string)get_post_meta($post->ID, '_pmfai_model', true),
            'type' => (string)get_post_meta($post->ID, '_pmfai_type', true),
            'industry' => (string)get_post_meta($post->ID, '_pmfai_industry', true),
            'duration_ms' => absint(get_post_meta($post->ID, '_pmfai_duration_ms', true)),
            'prompt_tokens' => absint(get_post_meta($post->ID, '_pmfai_prompt_tokens', true)),
            'completion_tokens' => absint(get_post_meta($post->ID, '_pmfai_completion_tokens', true)),
            'total_tokens' => absint(get_post_meta($post->ID, '_pmfai_total_tokens', true)),
            'error_message' => (string)get_post_meta($post->ID, '_pmfai_error_message', true),
            'http_code' => absint(get_post_meta($post->ID, '_pmfai_http_code', true)),
            'user_login' => (string)get_post_meta($post->ID, '_pmfai_user_login', true),
        ];
    }
}
