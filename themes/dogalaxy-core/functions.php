<?php
/**
 * DoGalaxy Core.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus([
        'primary' => __('Primary', 'dogalaxy'),
    ]);
});

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'dogalaxy-fonts',
        'https://fonts.bunny.net/css?family=figtree:400,500,600,700|fraunces:500,560,700',
        [],
        null
    );
    wp_enqueue_style(
        'dogalaxy-core',
        get_template_directory_uri() . '/style.css',
        ['dogalaxy-fonts'],
        wp_get_theme('dogalaxy-core')->get('Version')
    );
});

/**
 * Shared enquiry CPT + shortcode [do_enquiry].
 */
add_action('init', function (): void {
    register_post_type('do_enquiry', [
        'labels' => [
            'name' => 'Enquiries',
            'singular_name' => 'Enquiry',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-email-alt',
        'supports' => ['title', 'editor'],
        'capability_type' => 'post',
    ]);
});

add_shortcode('do_enquiry', function (): string {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_enquiry_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['do_enquiry_nonce'])), 'do_enquiry')) {
        $name = sanitize_text_field(wp_unslash($_POST['do_name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['do_email'] ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['do_phone'] ?? ''));
        $msg = sanitize_textarea_field(wp_unslash($_POST['do_message'] ?? ''));
        if ($name && $email && $msg) {
            wp_insert_post([
                'post_type' => 'do_enquiry',
                'post_status' => 'private',
                'post_title' => $name . ' · ' . $email,
                'post_content' => "Phone: {$phone}\n\n{$msg}",
            ]);
            return '<p class="dg-notice">Received. We will respond shortly.</p>';
        }
        return '<p class="dg-notice">Please add your name, email and message.</p>' . dogalaxy_enquiry_form();
    }
    return dogalaxy_enquiry_form();
});

function dogalaxy_enquiry_form(): string
{
    ob_start();
    ?>
    <form class="dg-form" method="post">
      <?php wp_nonce_field('do_enquiry', 'do_enquiry_nonce'); ?>
      <label>Name<input type="text" name="do_name" required></label>
      <label>Email<input type="email" name="do_email" required></label>
      <label>Phone<input type="tel" name="do_phone"></label>
      <label>How can we help?<textarea name="do_message" rows="4" required></textarea></label>
      <button type="submit">Send enquiry</button>
    </form>
    <?php
    return (string) ob_get_clean();
}
