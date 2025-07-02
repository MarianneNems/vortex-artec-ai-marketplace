<?php
/**
 * Plugin Name: VORTEX AI Marketplace
 * Plugin URI: https://github.com/MarianneNems/VORTEX
 * Description: Complete AI-powered marketplace with Artist Journey, subscription plans, TOLA blockchain integration, and 5 specialized AI agents (HURAII, CLOE, HORACE, THORIUS, ARCHER).
 * Version: 2.0.0
 * Author: Marianne Nems
 * Author URI: https://vortexartec.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: vortex-ai-marketplace
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('VORTEX_AI_MARKETPLACE_VERSION', '2.0.0');
define('VORTEX_AI_MARKETPLACE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_AI_MARKETPLACE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_AI_MARKETPLACE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin activation hook.
 */
function activate_vortex_ai_marketplace() {
    require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-activator.php';
    Vortex_Activator::activate();
}

/**
 * Plugin deactivation hook.
 */
function deactivate_vortex_ai_marketplace() {
    require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-deactivator.php';
    Vortex_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_vortex_ai_marketplace');
register_deactivation_hook(__FILE__, 'deactivate_vortex_ai_marketplace');

/**
 * Include the main plugin class.
 */
require VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-ai-marketplace.php';

/**
 * Begin execution of the plugin.
 */
function run_vortex_ai_marketplace() {
    $plugin = new Vortex_AI_Marketplace();
    $plugin->run();
}

// Initialize the plugin
add_action('plugins_loaded', 'run_vortex_ai_marketplace');

/**
 * Enqueue scripts and styles.
 */
function vortex_enqueue_scripts() {
    // Main script
    wp_enqueue_script(
        'vortex-tola',
        VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'public/js/vortex-tola.js',
        array('jquery', 'wp-api'),
        VORTEX_AI_MARKETPLACE_VERSION,
        true
    );

    // Localize script for AJAX
    wp_localize_script('vortex-tola', 'vortexAjax', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => rest_url('vortex/v1/'),
        'nonce' => wp_create_nonce('wp_rest'),
        'currentUserId' => get_current_user_id(),
        'isUserLoggedIn' => is_user_logged_in(),
    ));

    // Main stylesheet
    wp_enqueue_style(
        'vortex-marketplace',
        VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'public/css/vortex-marketplace.css',
        array(),
        VORTEX_AI_MARKETPLACE_VERSION
    );
}
add_action('wp_enqueue_scripts', 'vortex_enqueue_scripts');

/**
 * Admin scripts and styles.
 */
function vortex_admin_enqueue_scripts($hook) {
    // Only load on VORTEX admin pages
    if (strpos($hook, 'vortex') === false) {
        return;
    }

    wp_enqueue_script(
        'vortex-admin',
        VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'admin/js/vortex-admin.js',
        array('jquery'),
        VORTEX_AI_MARKETPLACE_VERSION,
        true
    );

    wp_enqueue_style(
        'vortex-admin',
        VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'admin/css/vortex-admin.css',
        array(),
        VORTEX_AI_MARKETPLACE_VERSION
    );
}
add_action('admin_enqueue_scripts', 'vortex_admin_enqueue_scripts');