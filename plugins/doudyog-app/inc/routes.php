<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('template_include', function (string $template): string {
    if (is_front_page()) {
        return DOUDYOG_DIR . 'views/home.php';
    }
    if (is_singular('business')) {
        return DOUDYOG_DIR . 'views/single-business.php';
    }
    if (!is_page()) {
        return $template;
    }
    $slug = get_post_field('post_name', get_queried_object_id());
    $map = [
        'join' => 'join.php',
        'login' => 'login.php',
        'dashboard' => 'dashboard.php',
        'businesses' => 'directory.php',
        'compliance' => 'compliance.php',
        'services' => 'services.php',
        'growth' => 'growth.php',
        'contact' => 'contact.php',
    ];
    if (isset($map[$slug])) {
        if (in_array($slug, ['dashboard', 'compliance'], true)) {
            doudyog_require_login();
        }
        return DOUDYOG_DIR . 'views/' . $map[$slug];
    }
    return $template;
});

function doudyog_view(string $notice = ''): void
{
    if ($notice) {
        echo '<p class="ud-notice">' . esc_html($notice) . '</p>';
    }
}
