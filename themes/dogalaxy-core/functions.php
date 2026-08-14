<?php
/**
 * DoGalaxy Core.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('responsive-embeds');
    register_nav_menus([
        'primary' => __('Primary', 'dogalaxy'),
    ]);
});

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'dogalaxy-core',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme('dogalaxy-core')->get('Version')
    );
});
