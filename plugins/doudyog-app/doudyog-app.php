<?php
/**
 * Plugin Name: Do Udyog App
 * Description: End-to-end MSME product — register, directory, dashboard, compliance, enquiries.
 * Version: 1.0.0
 * Author: Dr. Devesh Kumar Sharma
 * Text Domain: doudyog
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DOUDYOG_DIR', plugin_dir_path(__FILE__));
define('DOUDYOG_URL', plugin_dir_url(__FILE__));
define('DOUDYOG_VER', '1.0.0');

require_once DOUDYOG_DIR . 'inc/setup.php';
require_once DOUDYOG_DIR . 'inc/auth.php';
require_once DOUDYOG_DIR . 'inc/business.php';
require_once DOUDYOG_DIR . 'inc/enquiry.php';
require_once DOUDYOG_DIR . 'inc/routes.php';

register_activation_hook(__FILE__, 'doudyog_activate');
register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});
