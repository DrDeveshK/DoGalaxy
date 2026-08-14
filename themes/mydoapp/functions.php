<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'mydoapp',
        get_stylesheet_uri(),
        ['dogalaxy-core'],
        wp_get_theme()->get('Version')
    );
});
