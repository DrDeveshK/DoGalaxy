<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'dovyapaar',
        get_stylesheet_uri(),
        ['dogalaxy-core'],
        wp_get_theme()->get('Version')
    );
});

add_action('init', function (): void {
    register_post_type('supplier', [
        'labels' => [
            'name' => 'Suppliers',
            'singular_name' => 'Supplier',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'suppliers'],
        'show_in_rest' => true,
    ]);
    register_post_type('trade_lead', [
        'labels' => [
            'name' => 'Trade Leads',
            'singular_name' => 'Lead',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'leads'],
        'show_in_rest' => true,
    ]);
});
