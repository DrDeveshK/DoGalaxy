<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'dovishram',
        get_stylesheet_uri(),
        ['dogalaxy-core'],
        wp_get_theme()->get('Version')
    );
});

add_action('init', function (): void {
    register_post_type('stay', [
        'labels' => [
            'name' => 'Stays',
            'singular_name' => 'Stay',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-admin-home',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'stay'],
        'show_in_rest' => true,
    ]);
});
