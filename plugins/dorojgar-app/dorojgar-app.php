<?php
/**
 * Plugin Name: Do Rojgar App
 * Description: End-to-end Do Rojgar product.
 * Version: 1.0.0
 * Author: Dr. Devesh Kumar Sharma
 */
if (!defined('ABSPATH')) { exit; }
define('DOROJGAR_DIR', plugin_dir_path(__FILE__));
define('DOROJGAR_URL', plugin_dir_url(__FILE__));
require_once DOROJGAR_DIR . 'inc/app.php';
register_activation_hook(__FILE__, 'dorojgar_activate');
