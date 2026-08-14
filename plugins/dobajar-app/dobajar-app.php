<?php
/**
 * Plugin Name: Do Bajar App
 * Description: End-to-end Do Bajar product.
 * Version: 1.0.0
 * Author: Dr. Devesh Kumar Sharma
 */
if (!defined('ABSPATH')) { exit; }
define('DOBAJAR_DIR', plugin_dir_path(__FILE__));
define('DOBAJAR_URL', plugin_dir_url(__FILE__));
require_once DOBAJAR_DIR . 'inc/app.php';
register_activation_hook(__FILE__, 'dobajar_activate');
