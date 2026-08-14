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
            'add_new_item' => 'Add business',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-store',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'businesses'],
        'show_in_rest' => true,
    ]);
    register_taxonomy('industry', 'business', [
        'label' => 'Industries',
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'industry'],
    ]);
    register_taxonomy('city', 'business', [
        'label' => 'Cities',
        'public' => true,
        'hierarchical' => false,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'city'],
    ]);
});

add_action('after_switch_theme', function (): void {
    if (get_option('doudyog_seeded')) {
        return;
    }
    $samples = [
        ['Swagat Hospitality Services', 'Hospitality operator for events and hotels.', 'Hospitality', 'Jaipur'],
        ['Nirman BuildMart', 'Construction material distributor.', 'Construction', 'Delhi'],
        ['Aarambh Retail Network', 'Multi-city retailer seeking suppliers and hiring support.', 'Retail', 'Lucknow'],
        ['Sharma Engineering Works', 'Machining, fabrication and industrial components.', 'Manufacturing', 'Pune'],
    ];
    foreach ($samples as [$title, $excerpt, $industry, $city]) {
        $id = wp_insert_post([
            'post_type' => 'business',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_excerpt' => $excerpt,
            'post_content' => $excerpt . "\n\nReplace this sample with a verified Do Udyog member.",
        ]);
        if (!is_wp_error($id)) {
            wp_set_object_terms($id, $industry, 'industry');
            wp_set_object_terms($id, $city, 'city');
        }
    }
    update_option('doudyog_seeded', 1);
});
