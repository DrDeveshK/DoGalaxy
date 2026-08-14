<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'dobajar',
        get_stylesheet_uri(),
        ['dogalaxy-core'],
        wp_get_theme()->get('Version')
    );
});

add_action('init', function (): void {
    register_post_type('listing', [
        'labels' => [
            'name' => 'Listings',
            'singular_name' => 'Listing',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-cart',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'listings'],
        'show_in_rest' => true,
    ]);
});
