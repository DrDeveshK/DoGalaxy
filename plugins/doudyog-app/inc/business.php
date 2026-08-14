<?php
if (!defined('ABSPATH')) {
    exit;
}

function doudyog_compliance_score(int $id): int
{
    $keys = array_keys(doudyog_compliance_items());
    $done = 0;
    foreach ($keys as $k) {
        if (get_post_meta($id, 'comp_' . $k, true) === '1') {
            $done++;
        }
    }
    return (int) round(100 * $done / max(1, count($keys)));
}

function doudyog_query_businesses(): WP_Query
{
    $tax = [];
    $meta = [['key' => 'verify', 'value' => 'rejected', 'compare' => '!=']];
    $args = [
        'post_type' => 'business',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        's' => sanitize_text_field(wp_unslash($_GET['q'] ?? '')),
        'paged' => max(1, (int) ($_GET['pg'] ?? 1)),
    ];
    $industry = sanitize_text_field(wp_unslash($_GET['industry'] ?? ''));
    $city = sanitize_text_field(wp_unslash($_GET['city'] ?? ''));
    if ($industry) {
        $args['meta_query'][] = ['key' => 'industry', 'value' => $industry];
    }
    if ($city) {
        $args['meta_query'][] = ['key' => 'city', 'value' => $city, 'compare' => 'LIKE'];
    }
    return new WP_Query($args);
}

add_action('admin_post_doudyog_save_business', function (): void {
    if (!wp_verify_nonce($_POST['doudyog_nonce'] ?? '', 'doudyog_save_business')) {
        wp_die('Invalid request');
    }
    doudyog_require_login();
    $biz = doudyog_owner_business();
    if (!$biz) {
        wp_safe_redirect(home_url('/join/'));
        exit;
    }
    $id = (int) $biz->ID;
    wp_update_post([
        'ID' => $id,
        'post_title' => sanitize_text_field(wp_unslash($_POST['business_name'] ?? $biz->post_title)),
        'post_content' => wp_kses_post(wp_unslash($_POST['about'] ?? '')),
        'post_excerpt' => sanitize_text_field(wp_unslash($_POST['tagline'] ?? '')),
    ]);
    foreach (['industry', 'city', 'phone', 'website', 'employees', 'year_started'] as $k) {
        update_post_meta($id, $k, sanitize_text_field(wp_unslash($_POST[$k] ?? '')));
    }
    wp_safe_redirect(home_url('/dashboard/?saved=1'));
    exit;
});

add_action('admin_post_doudyog_save_compliance', function (): void {
    if (!wp_verify_nonce($_POST['doudyog_nonce'] ?? '', 'doudyog_save_compliance')) {
        wp_die('Invalid request');
    }
    doudyog_require_login();
    $biz = doudyog_owner_business();
    if (!$biz) {
        wp_safe_redirect(home_url('/join/'));
        exit;
    }
    foreach (array_keys(doudyog_compliance_items()) as $k) {
        $on = isset($_POST['comp'][$k]) ? '1' : '0';
        update_post_meta($biz->ID, 'comp_' . $k, $on);
        if (!empty($_POST['comp_val'][$k])) {
            update_post_meta($biz->ID, 'comp_val_' . $k, sanitize_text_field(wp_unslash($_POST['comp_val'][$k])));
        }
    }
    wp_safe_redirect(home_url('/compliance/?saved=1'));
    exit;
});
