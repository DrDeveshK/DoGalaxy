<?php
/**
 * Plugin Name: Do Rishta App
 * Description: End-to-end Do Rishta product.
 * Version: 1.0.0
 * Author: Dr. Devesh Kumar Sharma
 */
if (!defined('ABSPATH')) { exit; }
define('DORISHTA_DIR', plugin_dir_path(__FILE__));
define('DORISHTA_URL', plugin_dir_url(__FILE__));
require_once DORISHTA_DIR . 'inc/app.php';
register_activation_hook(__FILE__, 'dorishta_activate');
