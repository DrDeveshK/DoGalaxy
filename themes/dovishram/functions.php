<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('dovishram', get_stylesheet_uri(), ['dogalaxy-core'], wp_get_theme()->get('Version'));
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
    register_taxonomy('city', ['stay'], [
        'label' => 'Cities',
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'city'],
    ]);
});

add_action('after_switch_theme', function (): void {
    if (get_option('dovishram_seeded')) {
        return;
    }
    $samples = [
        ['Ghat Homestay', 'Ganga-facing rooms for families.', 'Varanasi'],
        ['Aranya Retreat', 'Weekend rest in a quiet grove.', 'Jim Corbett'],
        ['Kashi Guest House', 'Simple rooms near the old city.', 'Varanasi'],
        ['Lakeview Rooms', 'Short stay for travelling professionals.', 'Udaipur']
    ];
    foreach ($samples as [$title, $excerpt, $city]) {
        $id = wp_insert_post([
            'post_type' => 'stay',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_excerpt' => $excerpt,
            'post_content' => $excerpt,
        ]);
        if (!is_wp_error($id)) {
            wp_set_object_terms($id, $city, 'city');
        }
    }
    update_option('dovishram_seeded', 1);
});

