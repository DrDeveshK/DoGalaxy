<?php
if (!defined('ABSPATH')) { exit; }
add_action('after_setup_theme', function () { add_theme_support('title-tag'); add_theme_support('html5', ['search-form','style','script']); });
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dg-fonts', 'https://fonts.bunny.net/css?family=figtree:400,500,600,700|fraunces:500,560,700', [], null);
    wp_enqueue_style('dg-theme', get_stylesheet_uri(), ['dg-fonts'], wp_get_theme()->get('Version'));
});
