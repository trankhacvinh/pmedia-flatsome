<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Post_Types
{
    public static function register(): void
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
}
