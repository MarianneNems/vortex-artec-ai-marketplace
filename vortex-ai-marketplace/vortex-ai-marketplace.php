<?php
/**
 * Plugin Name: VORTEX AI Marketplace
 * Plugin URI: https://vortexartec.com
 * Description: Complete AI-powered marketplace with WooCommerce integration, TOLA Masterwork automation, artist journey management, blockchain royalty distribution, and gamification systems with HURAII AI integration.
 * Version: 3.0.0
 * Author: Mariana Villard - VORTEX ARTEC
 * Author URI: https://vortexartec.com
 * License: Proprietary License
 * License URI: LICENSE
 * Text Domain: vortex-ai-marketplace
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('VORTEX_AI_MARKETPLACE_VERSION', '3.0.0');
define('VORTEX_AI_MARKETPLACE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_AI_MARKETPLACE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_AI_MARKETPLACE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function vortex_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo __('VORTEX AI Marketplace requires WooCommerce to be installed and active.', 'vortex-ai-marketplace');
            echo '</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Plugin activation hook.
 */
function activate_vortex_ai_marketplace() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-activator.php';
    Vortex_Activator::activate();
}

/**
 * Plugin deactivation hook.
 */
function deactivate_vortex_ai_marketplace() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-deactivator.php';
    Vortex_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_vortex_ai_marketplace');
register_deactivation_hook(__FILE__, 'deactivate_vortex_ai_marketplace');

/**
 * Include the main plugin class.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-ai-marketplace.php';

/**
 * Include shortcode classes.
 */
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-vortex-artist-business-quiz.php';

/**
 * Include API handlers.
 */
require_once plugin_dir_path(__FILE__) . 'includes/api/class-vortex-quiz-optimizer-handler.php';

/**
 * Begin execution of the plugin.
 */
function run_vortex_ai_marketplace() {
    if (!vortex_check_woocommerce()) {
        return;
    }
    
    $plugin = new Vortex_AI_Marketplace();
    $plugin->run();
}

// Initialize the plugin after WooCommerce is loaded
add_action('woocommerce_loaded', 'run_vortex_ai_marketplace');

/**
 * Enqueue scripts and styles.
 */
function vortex_enqueue_scripts() {
    // Main script
    wp_enqueue_script(
        'vortex-tola',
        plugin_dir_url(__FILE__) . 'public/js/vortex-tola.js',
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
        'woocommerceEnabled' => class_exists('WooCommerce'),
    ));

    // Quiz optimizer script (conditionally loaded by shortcode)
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vortex_artist_business_quiz')) {
        wp_enqueue_script('vortex-quiz-optimizer', plugin_dir_url(__FILE__) . 'public/js/quiz-optimizer.js', ['jquery'], null, true);
        wp_localize_script('vortex-quiz-optimizer', 'VortexQuizOptimizer', ['nonce' => wp_create_nonce('vortex_quiz')]);
    }

    // Main stylesheet
    wp_enqueue_style(
        'vortex-marketplace',
        plugin_dir_url(__FILE__) . 'public/css/vortex-marketplace.css',
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
        plugin_dir_url(__FILE__) . 'admin/js/vortex-admin.js',
        array('jquery'),
        VORTEX_AI_MARKETPLACE_VERSION,
        true
    );

    wp_enqueue_style(
        'vortex-admin',
        plugin_dir_url(__FILE__) . 'admin/css/vortex-admin.css',
        array(),
        VORTEX_AI_MARKETPLACE_VERSION
    );
}
add_action('admin_enqueue_scripts', 'vortex_admin_enqueue_scripts');

/**
 * Add custom user roles for artist plans
 */
function vortex_add_custom_roles() {
    add_role('artist_starter', 'Artist Starter', array(
        'read' => true,
        'upload_files' => true,
        'vortex_generate_art' => true,
    ));
    
    add_role('artist_pro', 'Artist Pro', array(
        'read' => true,
        'upload_files' => true,
        'vortex_generate_art' => true,
        'vortex_advanced_tools' => true,
    ));
    
    add_role('artist_studio', 'Artist Studio', array(
        'read' => true,
        'upload_files' => true,
        'vortex_generate_art' => true,
        'vortex_advanced_tools' => true,
        'vortex_studio_features' => true,
    ));
}
add_action('init', 'vortex_add_custom_roles');