<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('dorishta', get_stylesheet_uri(), ['dogalaxy-core'], wp_get_theme()->get('Version'));
});

add_action('init', function (): void {
    register_post_type('success_story', [
        'labels' => [
            'name' => 'Success Stories',
            'singular_name' => 'Story',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-heart',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'stories'],
        'show_in_rest' => true,
    ]);
    register_taxonomy('city', ['success_story'], [
        'label' => 'Cities',
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'city'],
    ]);
});

add_action('after_switch_theme', function (): void {
    if (get_option('dorishta_seeded')) {
        return;
    }
    $samples = [
        ['Asha & Rohan', 'A family-led introduction that took its time.', 'Lucknow'],
        ['Meera & Kabir', 'Two professionals, one quiet yes.', 'Pune'],
        ['Nandini & Arjun', 'Community depth, modern consent.', 'Jaipur'],
        ['Zara & Vikram', 'Guided matching, not a swipe.', 'Delhi']
    ];
    foreach ($samples as [$title, $excerpt, $city]) {
        $id = wp_insert_post([
            'post_type' => 'success_story',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_excerpt' => $excerpt,
            'post_content' => $excerpt,
        ]);
        if (!is_wp_error($id)) {
            wp_set_object_terms($id, $city, 'city');
        }
    }
    update_option('dorishta_seeded', 1);
});

