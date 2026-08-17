<?php
/**
 * Plugin Name: Restore original DoUdyog
 * Description: Original navy/orange theme only. Stops the cream overlay plugin.
 */
add_action('plugins_loaded', static function (): void {
    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (function_exists('is_plugin_active') && is_plugin_active('doudyog-app/doudyog-app.php')) {
        deactivate_plugins('doudyog-app/doudyog-app.php');
    }
}, 0);
