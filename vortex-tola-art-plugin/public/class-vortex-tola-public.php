<?php
/**
 * TOLA-ART Public Class
 * 
 * @package VortexTOLAArt
 * @version 2.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_TOLA_Public {
    
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_shortcode('tola_art_gallery', array($this, 'gallery_shortcode'));
        add_shortcode('tola_art_daily', array($this, 'daily_art_shortcode'));
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_public_scripts() {
        wp_enqueue_style(
            'vortex-tola-public',
            VORTEX_TOLA_ART_PLUGIN_URL . 'public/css/tola-art-public.css',
            array(),
            VORTEX_TOLA_ART_VERSION
        );
    }
    
    /**
     * Gallery shortcode
     */
    public function gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'columns' => 3
        ), $atts);
        
        return '<div class="tola-art-gallery">TOLA-ART Gallery coming soon...</div>';
    }
    
    /**
     * Daily art shortcode
     */
    public function daily_art_shortcode($atts) {
        $atts = shortcode_atts(array(
            'date' => date('Y-m-d')
        ), $atts);
        
        return '<div class="tola-art-daily">Today\'s TOLA-ART coming soon...</div>';
    }
} 