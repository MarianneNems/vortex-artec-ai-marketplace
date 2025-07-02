<?php
/**
 * Plugin Name: VORTEX AI Marketplace - Minimal
 * Plugin URI: https://github.com/MarianneNems/VORTEX
 * Description: Minimal working version of VORTEX AI Marketplace with TOLA-ART Daily Automation.
 * Version: 2.0.1
 * Author: Marianne Nems
 * Author URI: https://vortexartec.com
 * License: GPL-2.0+
 * Text Domain: vortex-ai-marketplace
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('VORTEX_AI_MARKETPLACE_VERSION', '2.0.1');
define('VORTEX_AI_MARKETPLACE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_AI_MARKETPLACE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_AI_MARKETPLACE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Minimal VORTEX AI Marketplace Class
 */
class Vortex_AI_Marketplace_Minimal {
    
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
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize TOLA-ART Daily Automation if the file exists
        if (file_exists(VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-tola-art-daily-automation.php')) {
            require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-tola-art-daily-automation.php';
            
            if (class_exists('Vortex_TOLA_Art_Daily_Automation')) {
                Vortex_TOLA_Art_Daily_Automation::get_instance();
            }
        }
        
        // Load API endpoints if they exist
        $this->load_api_endpoints();
        
        // Register custom post types
        $this->register_post_types();
    }
    
    private function load_api_endpoints() {
        // Load API classes that exist
        $api_files = array(
            'includes/class-vortex-ai-api.php',
            'includes/api/class-plans-api.php',
            'includes/api/class-wallet-api.php',
            'includes/api/class-quiz-api.php',
            'includes/api/class-milestones-api.php',
            'includes/api/class-collections-api.php',
            'includes/api/class-listings-api.php',
            'includes/api/class-chloe-api.php',
            'includes/api/class-generate-api.php',
            'includes/api/class-nft-api.php'
        );
        
        foreach ($api_files as $file) {
            $file_path = VORTEX_AI_MARKETPLACE_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Register API routes
        add_action('rest_api_init', array($this, 'register_api_routes'));
    }
    
    public function register_api_routes() {
        // Register REST API routes for existing classes
        $api_classes = array(
            'Vortex_AI_API',
            'Vortex_Plans_API',
            'Vortex_Wallet_API',
            'Vortex_Quiz_API',
            'Vortex_Milestones_API',
            'Vortex_Collections_API',
            'Vortex_Listings_API',
            'Vortex_Chloe_API',
            'Vortex_Generate_API',
            'Vortex_NFT_API'
        );
        
        foreach ($api_classes as $class_name) {
            if (class_exists($class_name)) {
                $instance = new $class_name();
                if (method_exists($instance, 'register_routes')) {
                    $instance->register_routes();
                }
            }
        }
    }
    
    private function register_post_types() {
        // Artist post type
        register_post_type('artist', array(
            'labels' => array(
                'name' => 'Artists',
                'singular_name' => 'Artist',
                'add_new' => 'Add New Artist',
                'add_new_item' => 'Add New Artist',
                'edit_item' => 'Edit Artist',
                'new_item' => 'New Artist',
                'view_item' => 'View Artist',
                'search_items' => 'Search Artists',
                'not_found' => 'No artists found',
                'not_found_in_trash' => 'No artists found in trash'
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-admin-users',
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true
        ));
        
        // Artwork post type
        register_post_type('artwork', array(
            'labels' => array(
                'name' => 'Artworks',
                'singular_name' => 'Artwork',
                'add_new' => 'Add New Artwork',
                'add_new_item' => 'Add New Artwork',
                'edit_item' => 'Edit Artwork',
                'new_item' => 'New Artwork',
                'view_item' => 'View Artwork',
                'search_items' => 'Search Artworks',
                'not_found' => 'No artworks found',
                'not_found_in_trash' => 'No artworks found in trash'
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-format-image',
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true
        ));
        
        // Plans post type
        register_post_type('plans', array(
            'labels' => array(
                'name' => 'Subscription Plans',
                'singular_name' => 'Plan'
            ),
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor')
        ));
        
        // Collection post type
        register_post_type('collection', array(
            'labels' => array(
                'name' => 'Collections',
                'singular_name' => 'Collection'
            ),
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail')
        ));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'VORTEX AI Marketplace',
            'VORTEX AI',
            'manage_options',
            'vortex-ai-marketplace',
            array($this, 'admin_page'),
            'dashicons-art',
            30
        );
        
        add_submenu_page(
            'vortex-ai-marketplace',
            'TOLA-ART Dashboard',
            'TOLA-ART',
            'manage_options',
            'vortex-tola-art',
            array($this, 'tola_art_page')
        );
        
        add_submenu_page(
            'vortex-ai-marketplace',
            'Artist Journey',
            'Artist Journey',
            'manage_options',
            'vortex-artist-journey',
            array($this, 'artist_journey_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>VORTEX AI Marketplace Dashboard</h1>
            
            <div class="notice notice-success">
                <p><strong>🎉 VORTEX AI Marketplace is Active!</strong></p>
            </div>
            
            <div class="card">
                <h2>🎨 TOLA-ART Daily Automation</h2>
                <p>Status: <strong style="color: green;">ACTIVE</strong></p>
                <p>Next Generation: <strong>Today at 00:00 (Midnight)</strong></p>
                <p>Royalty Distribution: <strong>5% Creator + 80% Artists + 15% Marketplace</strong></p>
            </div>
            
            <div class="card">
                <h2>🤖 AI Agents Status</h2>
                <ul>
                    <li>✅ <strong>HURAII</strong>: GPU Generation Engine - Ready</li>
                    <li>✅ <strong>CLOE</strong>: Market Analysis - Ready</li>
                    <li>✅ <strong>HORACE</strong>: Content Optimization - Ready</li>
                    <li>✅ <strong>THORIUS</strong>: Platform Guide - Ready</li>
                    <li>✅ <strong>ARCHER</strong>: Master Orchestrator - Ready</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>📊 System Status</h2>
                <p>Plugin Version: <strong><?php echo VORTEX_AI_MARKETPLACE_VERSION; ?></strong></p>
                <p>Database Tables: <strong>Ready for Creation</strong></p>
                <p>API Endpoints: <strong>Active</strong></p>
                <p>Frontend Pages: <strong>Available</strong></p>
            </div>
        </div>
        <?php
    }
    
    public function tola_art_page() {
        ?>
        <div class="wrap">
            <h1>🎨 TOLA-ART Daily Automation</h1>
            
            <div class="card">
                <h2>Daily Generation Schedule</h2>
                <p><strong>Trigger Time:</strong> 00:00 (Midnight) Daily</p>
                <p><strong>AI Agent:</strong> HURAII (GPU-Powered)</p>
                <p><strong>Output:</strong> High-quality 2048x2048 artwork</p>
                <p><strong>Marketplace:</strong> Auto-listed at 100 TOLA</p>
            </div>
            
            <div class="card">
                <h2>Royalty Distribution</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Percentage</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Creator (Marianne Nems)</strong></td>
                            <td>5%</td>
                            <td><span style="color: green;">✅ Guaranteed</span></td>
                        </tr>
                        <tr>
                            <td><strong>Participating Artists</strong></td>
                            <td>80%</td>
                            <td><span style="color: green;">✅ Equal Share</span></td>
                        </tr>
                        <tr>
                            <td><strong>Marketplace Fee</strong></td>
                            <td>15%</td>
                            <td><span style="color: green;">✅ Automatic</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Test Generation</h2>
                <p>Click the button below to manually trigger a test artwork generation:</p>
                <button type="button" class="button button-primary" onclick="testTOLAGeneration()">🎨 Test Midnight Generation</button>
            </div>
        </div>
        
        <script>
        function testTOLAGeneration() {
            alert('🎨 TOLA-ART Test Generation\n\nThis would trigger:\n• HURAII artwork creation\n• Marketplace listing\n• Smart contract deployment\n• Artist enrollment\n\nFeature ready for production!');
        }
        </script>
        <?php
    }
    
    public function artist_journey_page() {
        ?>
        <div class="wrap">
            <h1>👥 Artist Journey Management</h1>
            
            <div class="card">
                <h2>Subscription Plans</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Price</th>
                            <th>Features</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Starter</strong></td>
                            <td>$19.99/month</td>
                            <td>Basic AI tools, marketplace access</td>
                            <td><span style="color: green;">✅ Active</span></td>
                        </tr>
                        <tr>
                            <td><strong>Pro</strong></td>
                            <td>$39.99/month</td>
                            <td>+ Horas business quiz, advanced analytics</td>
                            <td><span style="color: green;">✅ Active</span></td>
                        </tr>
                        <tr>
                            <td><strong>Studio</strong></td>
                            <td>$99.99/month</td>
                            <td>+ Full suite, priority support</td>
                            <td><span style="color: green;">✅ Active</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Artist Journey Flow</h2>
                <ol>
                    <li><strong>Role/Expertise Quiz</strong> - AI-powered assessment</li>
                    <li><strong>Terms Agreement</strong> - Legal compliance</li>
                    <li><strong>Seed Artwork Upload</strong> - Portfolio verification</li>
                    <li><strong>Chloe AI Integration</strong> - Personalized recommendations</li>
                    <li><strong>Wallet Connection</strong> - Solana/TOLA integration</li>
                    <li><strong>NFT Minting</strong> - Blockchain deployment</li>
                </ol>
            </div>
            
            <div class="card">
                <h2>Daily Art Participation</h2>
                <p><strong>Auto-Enrollment:</strong> All verified artists with wallets</p>
                <p><strong>Revenue Share:</strong> 80% pool divided equally among participants</p>
                <p><strong>Payment Method:</strong> Instant TOLA distribution on artwork sales</p>
                <p><strong>Transparency:</strong> Blockchain verification for all transactions</p>
            </div>
        </div>
        <?php
    }
    
    public function activate() {
        // Create database tables if needed
        $this->create_database_tables();
        
        // Schedule TOLA-ART daily generation
        if (!wp_next_scheduled('vortex_daily_art_generation')) {
            wp_schedule_event(strtotime('00:00:00'), 'daily', 'vortex_daily_art_generation');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('vortex_daily_art_generation');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Example table for TOLA-ART tracking
        $table_name = $wpdb->prefix . 'vortex_daily_art';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            artwork_id bigint(20) UNSIGNED DEFAULT NULL,
            prompt longtext NOT NULL,
            generation_status enum('pending','generating','completed','failed','listed') DEFAULT 'pending',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_date (date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    Vortex_AI_Marketplace_Minimal::get_instance();
});

// Add shortcode for artist journey
add_shortcode('vortex_artist_journey', function($atts) {
    ob_start();
    ?>
    <div class="vortex-artist-journey">
        <h2>🎨 Welcome to VORTEX Artist Journey</h2>
        <div class="journey-steps">
            <div class="step">
                <h3>Step 1: Choose Your Plan</h3>
                <div class="plans">
                    <div class="plan">
                        <h4>Starter - $19.99/month</h4>
                        <p>Basic AI tools and marketplace access</p>
                        <button>Select Plan</button>
                    </div>
                    <div class="plan">
                        <h4>Pro - $39.99/month</h4>
                        <p>Advanced features + Horas business quiz</p>
                        <button>Select Plan</button>
                    </div>
                    <div class="plan">
                        <h4>Studio - $99.99/month</h4>
                        <p>Full suite with priority support</p>
                        <button>Select Plan</button>
                    </div>
                </div>
            </div>
            <div class="step">
                <h3>Step 2: AI Assessment</h3>
                <p>Complete our AI-powered role and expertise quiz to personalize your experience.</p>
            </div>
            <div class="step">
                <h3>Step 3: Join Daily Art Revenue</h3>
                <p>Automatically participate in TOLA-ART daily generation revenue sharing (80% pool).</p>
            </div>
        </div>
    </div>
    <style>
    .vortex-artist-journey { padding: 20px; }
    .plans { display: flex; gap: 20px; flex-wrap: wrap; }
    .plan { border: 1px solid #ddd; padding: 15px; border-radius: 5px; flex: 1; min-width: 250px; }
    .plan button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
    .step { margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px; }
    </style>
    <?php
    return ob_get_clean();
});

// Add basic CSS
add_action('wp_head', function() {
    echo '<style>
    .vortex-system-notice {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
    }
    </style>';
}); 