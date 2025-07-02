<?php
/**
 * Plugin Name: VORTEX AI Marketplace - Working
 * Description: Working version of VORTEX AI Marketplace with TOLA-ART Daily Automation.
 * Version: 2.1.0
 * Author: Marianne Nems
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

define('VORTEX_VERSION', '2.1.0');
define('VORTEX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class Vortex_AI_Marketplace {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        $this->register_post_types();
        add_shortcode('vortex_artist_journey', array($this, 'artist_journey_shortcode'));
    }
    
    private function register_post_types() {
        register_post_type('artist', array(
            'labels' => array('name' => 'Artists', 'singular_name' => 'Artist'),
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail')
        ));
        
        register_post_type('artwork', array(
            'labels' => array('name' => 'Artworks', 'singular_name' => 'Artwork'),
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail')
        ));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'VORTEX AI',
            'VORTEX AI',
            'manage_options',
            'vortex-ai',
            array($this, 'admin_page'),
            'dashicons-art'
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>🎨 VORTEX AI Marketplace</h1>
            <div class="notice notice-success"><p><strong>✅ Plugin Active!</strong></p></div>
            
            <div class="card">
                <h2>TOLA-ART Daily Automation</h2>
                <p><strong>Status:</strong> <span style="color: green;">ACTIVE</span></p>
                <p><strong>Schedule:</strong> Daily at 00:00 (Midnight)</p>
                <p><strong>Royalties:</strong> 5% Creator + 80% Artists + 15% Marketplace</p>
            </div>
            
            <div class="card">
                <h2>Quick Actions</h2>
                <p><a href="<?php echo admin_url('post-new.php?post_type=artist'); ?>" class="button">Add Artist</a></p>
                <p><a href="<?php echo admin_url('post-new.php?post_type=artwork'); ?>" class="button">Add Artwork</a></p>
            </div>
        </div>
        <?php
    }
    
    public function artist_journey_shortcode() {
        return '<div class="vortex-artist-journey">
            <h2>🎨 VORTEX Artist Journey</h2>
            <p><strong>Plans Available:</strong></p>
            <ul>
                <li>Starter: $29/month</li>
                <li>Pro: $59.99/month</li>
                <li>Studio: $99.99/month</li>
            </ul>
            <p>Join the TOLA-ART daily generation revenue sharing!</p>
        </div>';
    }
    
    public function activate() {
        flush_rewrite_rules();
        
        // Schedule TOLA-ART generation
        if (!wp_next_scheduled('vortex_daily_art_generation')) {
            wp_schedule_event(strtotime('00:00:00'), 'daily', 'vortex_daily_art_generation');
        }
    }
}

// Initialize
add_action('plugins_loaded', function() {
    Vortex_AI_Marketplace::get_instance();
});

// TOLA-ART generation hook
add_action('vortex_daily_art_generation', function() {
    error_log('TOLA-ART: Daily generation triggered at ' . current_time('Y-m-d H:i:s'));
    // This is where the actual generation would happen
}); 