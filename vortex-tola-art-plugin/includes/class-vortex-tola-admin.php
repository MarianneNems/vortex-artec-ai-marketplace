<?php
/**
 * TOLA-ART Admin Class
 * 
 * @package VortexTOLAArt
 * @version 2.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_TOLA_Admin {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        add_menu_page(
            __('TOLA-ART Dashboard', 'vortex-tola-art'),
            __('TOLA-ART', 'vortex-tola-art'),
            'manage_options',
            'vortex-tola-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-art',
            30
        );
        
        add_submenu_page(
            'vortex-tola-dashboard',
            __('Settings', 'vortex-tola-art'),
            __('Settings', 'vortex-tola-art'),
            'manage_options',
            'vortex-tola-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include VORTEX_TOLA_ART_PLUGIN_PATH . 'admin/partials/tola-art-admin-page.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        echo '<div class="wrap"><h1>TOLA-ART Settings</h1><p>Settings interface coming soon...</p></div>';
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'vortex-tola') !== false) {
            wp_enqueue_script('jquery');
        }
    }
} 