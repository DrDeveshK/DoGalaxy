<?php
/**
 * Plugin Name: MyDoApp
 * Description: Do Galaxy hub — journeys, member account, and routes into every Do product.
 * Version: 1.0.0
 * Author: Dr. Devesh Kumar Sharma
 */
if (!defined('ABSPATH')) {
    exit;
}
define('MYDOAPP_DIR', plugin_dir_path(__FILE__));
define('MYDOAPP_URL', plugin_dir_url(__FILE__));
require_once MYDOAPP_DIR . 'inc/app.php';
register_activation_hook(__FILE__, 'mydoapp_activate');
