<?php
/**
 * PHPUnit bootstrap file for VORTEX AI MARKETPLACE tests
 */

// Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize WP_Mock
\WP_Mock::bootstrap();

// Define WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// Mock WordPress plugin functions
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

echo "VORTEX AI MARKETPLACE Test Suite Bootstrap Loaded\n";
