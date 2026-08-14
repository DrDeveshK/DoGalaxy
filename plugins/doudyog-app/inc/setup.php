<?php
if (!defined('ABSPATH')) {
    exit;
}

function doudyog_industries(): array
{
    return ['Manufacturing', 'Retail', 'Services', 'Construction', 'Hospitality', 'Trading', 'Agriculture', 'IT'];
}

function doudyog_compliance_items(): array
{
    return [
        'udyam' => 'Udyam / MSME registration number',
        'gstin' => 'GSTIN',
        'pan' => 'Business PAN',
        'bank' => 'Bank account for settlements',
        'address' => 'Registered address proof',
        'licence' => 'Trade / shop licence (if required)',
        'invoice' => 'Invoice / billing process',
        'contact' => 'Public phone and email verified',
    ];
}

function doudyog_activate(): void
{
    add_role('udyog_owner', 'Do Udyog Owner', [
        'read' => true,
        'upload_files' => true,
    ]);

    $pages = [
        'join' => 'Join',
        'login' => 'Login',
        'dashboard' => 'Dashboard',
        'businesses' => 'Businesses',
        'compliance' => 'Compliance',
        'services' => 'Services',
        'growth' => 'Growth',
        'contact' => 'Contact',
    ];
    foreach ($pages as $slug => $title) {
        if (!get_page_by_path($slug)) {
            wp_insert_post([
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => $slug,
                'post_content' => '',
            ]);
        }
    }
    doudyog_register_types();
    flush_rewrite_rules();
}

add_action('init', 'doudyog_register_types');

function doudyog_register_types(): void
{
    register_post_type('business', [
        'labels' => [
            'name' => 'Businesses',
            'singular_name' => 'Business',
        ],
        'public' => true,
        'has_archive' => false,
        'rewrite' => ['slug' => 'business'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-store',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'capability_type' => 'post',
    ]);
    register_post_type('do_enquiry', [
        'labels' => ['name' => 'Enquiries', 'singular_name' => 'Enquiry'],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-email-alt',
        'supports' => ['title', 'editor'],
    ]);
}

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('doudyog-app', DOUDYOG_URL . 'assets/app.css', [], DOUDYOG_VER);
});

add_action('add_meta_boxes', function (): void {
    add_meta_box('doudyog_verify', 'Do Udyog verification', function (WP_Post $post): void {
        $v = get_post_meta($post->ID, 'verify', true) ?: 'pending';
        wp_nonce_field('doudyog_verify', 'doudyog_verify_nonce');
        echo '<select name="doudyog_verify">';
        foreach (['pending', 'verified', 'rejected'] as $s) {
            echo '<option ' . selected($v, $s, false) . '>' . esc_html($s) . '</option>';
        }
        echo '</select><p>Verified businesses are published to the directory.</p>';
    }, 'business', 'side');
});

add_action('save_post_business', function (int $id): void {
    if (!isset($_POST['doudyog_verify_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['doudyog_verify_nonce'])), 'doudyog_verify')) {
        return;
    }
    $v = sanitize_text_field(wp_unslash($_POST['doudyog_verify'] ?? 'pending'));
    update_post_meta($id, 'verify', $v);
    if ($v === 'verified') {
        wp_update_post(['ID' => $id, 'post_status' => 'publish']);
    }
});
