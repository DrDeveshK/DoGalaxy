<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('dobajar', get_stylesheet_uri(), ['dogalaxy-core'], wp_get_theme()->get('Version'));
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
    register_taxonomy('city', ['listing'], [
        'label' => 'Cities',
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'city'],
    ]);
});

add_action('after_switch_theme', function (): void {
    if (get_option('dobajar_seeded')) {
        return;
    }
    $samples = [
        ['Handloom stoles', 'Weaver collective, limited runs.', 'Varanasi'],
        ['Spice tins', 'Kitchen staples from a family mill.', 'Kanpur'],
        ['Brass diyas', 'Festival sets, wholesale and retail.', 'Moradabad'],
        ['School notebooks', 'Local printer, bulk orders.', 'Lucknow']
    ];
    foreach ($samples as [$title, $excerpt, $city]) {
        $id = wp_insert_post([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_excerpt' => $excerpt,
            'post_content' => $excerpt,
        ]);
        if (!is_wp_error($id)) {
            wp_set_object_terms($id, $city, 'city');
        }
    }
    update_option('dobajar_seeded', 1);
});

