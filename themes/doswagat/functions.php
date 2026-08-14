<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('doswagat', get_stylesheet_uri(), ['dogalaxy-core'], wp_get_theme()->get('Version'));
});

add_action('init', function (): void {
    register_post_type('venue', [
        'labels' => [
            'name' => 'Venues',
            'singular_name' => 'Venue',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-location',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'venues'],
        'show_in_rest' => true,
    ]);
    register_post_type('event_service', [
        'labels' => [
            'name' => 'Services',
            'singular_name' => 'Service',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-awards',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'services'],
        'show_in_rest' => true,
    ]);
    register_post_type('event_package', [
        'labels' => [
            'name' => 'Packages',
            'singular_name' => 'Package',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-tickets-alt',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'packages'],
        'show_in_rest' => true,
    ]);
    register_taxonomy('city', ['venue', 'event_service', 'event_package'], [
        'label' => 'Cities',
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'city'],
    ]);
});

add_action('after_switch_theme', function (): void {
    if (get_option('doswagat_seeded')) {
        return;
    }
    $samples = [
        ['Riverfront Lawn', 'Outdoor wedding lawn for 400 guests.', 'Varanasi'],
        ['Mandap Hall', 'Indoor banquet with in-house catering.', 'Lucknow'],
        ['Courtyard House', 'Intimate family functions.', 'Jaipur'],
        ['Conference Pavilion', 'Corporate days and offsites.', 'Delhi']
    ];
    foreach ($samples as [$title, $excerpt, $city]) {
        $id = wp_insert_post([
            'post_type' => 'venue',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_excerpt' => $excerpt,
            'post_content' => $excerpt,
        ]);
        if (!is_wp_error($id)) {
            wp_set_object_terms($id, $city, 'city');
        }
    }
    update_option('doswagat_seeded', 1);
});

