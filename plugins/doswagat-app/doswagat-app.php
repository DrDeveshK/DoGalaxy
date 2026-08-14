<?php
/**
 * Plugin Name: Do Swagat App
 * Description: End-to-end Do Swagat product.
 * Version: 1.0.0
 * Author: Dr. Devesh Kumar Sharma
 */
if (!defined('ABSPATH')) { exit; }
define('DOSWAGAT_DIR', plugin_dir_path(__FILE__));
define('DOSWAGAT_URL', plugin_dir_url(__FILE__));
require_once DOSWAGAT_DIR . 'inc/app.php';
register_activation_hook(__FILE__, 'doswagat_activate');
