<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'doudyog',
        get_stylesheet_uri(),
        ['dogalaxy-core'],
        wp_get_theme()->get('Version')
    );
});

add_action('init', function (): void {
    register_post_type('business', [
        'labels' => [
            'name' => 'Businesses',
            'singular_name' => 'Business',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-store',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'businesses'],
        'show_in_rest' => true,
    ]);
});
