<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_post_nopriv_doudyog_enquiry', 'doudyog_handle_enquiry');
add_action('admin_post_doudyog_enquiry', 'doudyog_handle_enquiry');

function doudyog_handle_enquiry(): void
{
    if (!wp_verify_nonce($_POST['doudyog_nonce'] ?? '', 'doudyog_enquiry')) {
        wp_die('Invalid request');
    }
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $intent = sanitize_text_field(wp_unslash($_POST['intent'] ?? 'general'));
    $msg = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    $biz_id = (int) ($_POST['business_id'] ?? 0);
    if (!$name || !is_email($email) || !$msg) {
        wp_safe_redirect(wp_get_referer() ?: home_url('/contact/?err=1'));
        exit;
    }
    $title = $name . ' · ' . $intent;
    $eid = wp_insert_post([
        'post_type' => 'do_enquiry',
        'post_status' => 'private',
        'post_title' => $title,
        'post_content' => "Phone: {$phone}\nIntent: {$intent}\n\n{$msg}",
    ]);
    if (!is_wp_error($eid) && $biz_id) {
        update_post_meta($eid, 'business_id', $biz_id);
        $owner = (int) get_post_field('post_author', $biz_id);
        if ($owner) {
            $u = get_userdata($owner);
            if ($u) {
                wp_mail($u->user_email, 'New Do Udyog enquiry', "{$name} ({$email})\n{$phone}\n\n{$msg}");
            }
        }
    }
    wp_mail(get_option('admin_email'), 'Do Udyog enquiry: ' . $title, "{$name} <{$email}>\n{$phone}\n\n{$msg}");
    $back = wp_get_referer() ?: home_url('/contact/');
    wp_safe_redirect(add_query_arg('sent', '1', $back));
    exit;
}

function doudyog_enquiries_for(int $biz_id): array
{
    $q = new WP_Query([
        'post_type' => 'do_enquiry',
        'post_status' => 'private',
        'posts_per_page' => 20,
        'meta_key' => 'business_id',
        'meta_value' => $biz_id,
    ]);
    return $q->posts;
}
