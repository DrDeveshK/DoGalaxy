<?php
/**
 * Plugin Name: Do Vishram App
 * Description: End-to-end Do Vishram product.
 * Version: 1.0.0
 * Author: Dr. Devesh Kumar Sharma
 */
if (!defined('ABSPATH')) { exit; }
define('DOVISHRAM_DIR', plugin_dir_path(__FILE__));
define('DOVISHRAM_URL', plugin_dir_url(__FILE__));
require_once DOVISHRAM_DIR . 'inc/app.php';
register_activation_hook(__FILE__, 'dovishram_activate');
