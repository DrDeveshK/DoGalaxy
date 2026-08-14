<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'style', 'script']);
});

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'doudyog-fonts',
        'https://fonts.bunny.net/css?family=figtree:400,500,600,700|fraunces:500,560,700',
        [],
        null
    );
    wp_enqueue_style('doudyog', get_stylesheet_uri(), ['doudyog-fonts'], wp_get_theme()->get('Version'));
});

add_action('admin_notices', function (): void {
    if (!function_exists('doudyog_activate')) {
        echo '<div class="notice notice-error"><p>Activate the <strong>Do Udyog App</strong> plugin. The theme is only the shell.</p></div>';
    }
});
