<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('dorojgar', get_stylesheet_uri(), ['dogalaxy-core'], wp_get_theme()->get('Version'));
});

add_action('init', function (): void {
    register_post_type('job', [
        'labels' => [
            'name' => 'Jobs',
            'singular_name' => 'Job',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-id',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'jobs'],
        'show_in_rest' => true,
    ]);
    register_taxonomy('city', ['job'], [
        'label' => 'Cities',
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'city'],
    ]);
});

add_action('after_switch_theme', function (): void {
    if (get_option('dorojgar_seeded')) {
        return;
    }
    $samples = [
        ['Front desk associate', 'Hospitality desk role, day shift.', 'Jaipur'],
        ['Site supervisor', 'Construction supervision for a local builder.', 'Lucknow'],
        ['Accounts executive', 'SME accounts and GST support.', 'Delhi'],
        ['Delivery rider', 'Hyperlocal gig, weekly payout.', 'Kanpur']
    ];
    foreach ($samples as [$title, $excerpt, $city]) {
        $id = wp_insert_post([
            'post_type' => 'job',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_excerpt' => $excerpt,
            'post_content' => $excerpt,
        ]);
        if (!is_wp_error($id)) {
            wp_set_object_terms($id, $city, 'city');
        }
    }
    update_option('dorojgar_seeded', 1);
});

