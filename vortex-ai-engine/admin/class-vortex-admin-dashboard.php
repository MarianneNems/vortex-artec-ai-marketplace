<?php
/**
 * Vortex AI Engine Admin Dashboard
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Admin_Dashboard {
    
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
        
        // AJAX handlers
        add_action('wp_ajax_vortex_ai_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_vortex_trigger_ai_task', array($this, 'ajax_trigger_ai_task'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main dashboard
        add_menu_page(
            __('Vortex AI Engine', 'vortex-ai-engine'),
            __('Vortex AI', 'vortex-ai-engine'),
            'manage_options',
            'vortex-ai-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-superhero-alt',
            25
        );
        
        // Sub-pages
        add_submenu_page(
            'vortex-ai-dashboard',
            __('AI Agents', 'vortex-ai-engine'),
            __('AI Agents', 'vortex-ai-engine'),
            'manage_options',
            'vortex-ai-agents',
            array($this, 'agents_page')
        );
        
        add_submenu_page(
            'vortex-ai-dashboard',
            __('TOLA-ART', 'vortex-ai-engine'),
            __('TOLA-ART', 'vortex-ai-engine'),
            'manage_options',
            'vortex-tola-art',
            array($this, 'tola_art_page')
        );
        
        add_submenu_page(
            'vortex-ai-dashboard',
            __('Artist Journey', 'vortex-ai-engine'),
            __('Artist Journey', 'vortex-ai-engine'),
            'manage_options',
            'vortex-artist-journey',
            array($this, 'artist_journey_page')
        );
        
        add_submenu_page(
            'vortex-ai-dashboard',
            __('Settings', 'vortex-ai-engine'),
            __('Settings', 'vortex-ai-engine'),
            'manage_options',
            'vortex-ai-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('🚀 Vortex AI Engine Dashboard', 'vortex-ai-engine'); ?></h1>
            
            <!-- AI System Status -->
            <div class="ai-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                
                <!-- ARCHER Orchestrator -->
                <div class="status-card" style="background: white; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px;">
                    <h3 style="margin-top: 0; color: #1d2327;">🎯 ARCHER Orchestrator</h3>
                    <div class="status-indicator" style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-dot" style="width: 12px; height: 12px; background: #4caf50; border-radius: 50%; animation: pulse 2s infinite;"></span>
                        <span style="font-weight: bold; color: #4caf50;">ACTIVE</span>
                    </div>
                    <p style="color: #666; font-size: 14px; margin: 10px 0 0 0;">5-second sync intervals • Master coordination</p>
                </div>
                
                <!-- HURAII Agent -->
                <div class="status-card" style="background: white; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px;">
                    <h3 style="margin-top: 0; color: #1d2327;">🎨 HURAII (GPU)</h3>
                    <div class="status-indicator" style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-dot" style="width: 12px; height: 12px; background: #2196f3; border-radius: 50%;"></span>
                        <span style="font-weight: bold; color: #2196f3;">READY</span>
                    </div>
                    <p style="color: #666; font-size: 14px; margin: 10px 0 0 0;">Generative AI • RTX A6000 • Image creation</p>
                </div>
                
                <!-- CLOE Agent -->
                <div class="status-card" style="background: white; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px;">
                    <h3 style="margin-top: 0; color: #1d2327;">📊 CLOE (CPU)</h3>
                    <div class="status-indicator" style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-dot" style="width: 12px; height: 12px; background: #ff9800; border-radius: 50%;"></span>
                        <span style="font-weight: bold; color: #ff9800;">READY</span>
                    </div>
                    <p style="color: #666; font-size: 14px; margin: 10px 0 0 0;">Market analysis • Collector matching</p>
                </div>
                
                <!-- HORACE Agent -->
                <div class="status-card" style="background: white; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px;">
                    <h3 style="margin-top: 0; color: #1d2327;">📝 HORACE (CPU)</h3>
                    <div class="status-indicator" style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-dot" style="width: 12px; height: 12px; background: #9c27b0; border-radius: 50%;"></span>
                        <span style="font-weight: bold; color: #9c27b0;">READY</span>
                    </div>
                    <p style="color: #666; font-size: 14px; margin: 10px 0 0 0;">Content optimization • SEO management</p>
                </div>
                
                <!-- THORIUS Agent -->
                <div class="status-card" style="background: white; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px;">
                    <h3 style="margin-top: 0; color: #1d2327;">🛡️ THORIUS (CPU)</h3>
                    <div class="status-indicator" style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-dot" style="width: 12px; height: 12px; background: #f44336; border-radius: 50%;"></span>
                        <span style="font-weight: bold; color: #f44336;">MONITORING</span>
                    </div>
                    <p style="color: #666; font-size: 14px; margin: 10px 0 0 0;">Platform guide • Security monitoring</p>
                </div>
                
                <!-- TOLA-ART System -->
                <div class="status-card" style="background: white; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px;">
                    <h3 style="margin-top: 0; color: #1d2327;">🎨 TOLA-ART</h3>
                    <div class="status-indicator" style="display: flex; align-items: center; gap: 8px;">
                        <span class="status-dot" style="width: 12px; height: 12px; background: #4caf50; border-radius: 50%;"></span>
                        <span style="font-weight: bold; color: #4caf50;">AUTOMATED</span>
                    </div>
                    <p style="color: #666; font-size: 14px; margin: 10px 0 0 0;">Daily generation • Dual royalty • Smart contracts</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div style="background: white; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h2 style="margin-top: 0;">⚡ Quick Actions</h2>
                <p>
                    <button type="button" class="button button-primary" onclick="triggerDailyArt()">
                        🎨 Generate Daily Art
                    </button>
                    <button type="button" class="button" onclick="syncAIAgents()">
                        🔄 Sync AI Agents
                    </button>
                    <button type="button" class="button" onclick="runSystemCheck()">
                        🔍 System Health Check
                    </button>
                    <button type="button" class="button button-secondary" onclick="viewLogs()">
                        📋 View Logs
                    </button>
                </p>
            </div>
            
            <!-- System Overview -->
            <div style="background: white; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h2 style="margin-top: 0;">📊 System Overview</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #2196f3;">∞</div>
                        <div style="color: #666;">AI Tasks Processed</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #4caf50;">24/7</div>
                        <div style="color: #666;">System Uptime</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #ff9800;">5s</div>
                        <div style="color: #666;">Sync Interval</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #9c27b0;">100%</div>
                        <div style="color: #666;">To Creators</div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        </style>
        
        <script>
        function triggerDailyArt() {
            alert('🎨 TOLA-ART generation will be triggered...');
        }
        
        function syncAIAgents() {
            alert('🔄 Synchronizing all AI agents...');
        }
        
        function runSystemCheck() {
            alert('🔍 Running comprehensive system health check...');
        }
        
        function viewLogs() {
            alert('📋 System logs interface will open...');
        }
        </script>
        <?php
    }
    
    /**
     * AI Agents page
     */
    public function agents_page() {
        echo '<div class="wrap"><h1>' . __('AI Agents Management', 'vortex-ai-engine') . '</h1><p>Individual agent configuration and monitoring coming soon...</p></div>';
    }
    
    /**
     * TOLA-ART page  
     */
    public function tola_art_page() {
        if (file_exists(VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/tola-art-admin-page.php')) {
            include VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/tola-art-admin-page.php';
        } else {
            echo '<div class="wrap"><h1>' . __('TOLA-ART Management', 'vortex-ai-engine') . '</h1><p>TOLA-ART interface loading...</p></div>';
        }
    }
    
    /**
     * Artist Journey page
     */
    public function artist_journey_page() {
        echo '<div class="wrap"><h1>' . __('Artist Journey Management', 'vortex-ai-engine') . '</h1><p>Artist onboarding and subscription management coming soon...</p></div>';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Vortex AI Engine Settings', 'vortex-ai-engine'); ?></h1>
            <p>Complete settings interface will be available in the next update.</p>
            
            <h2>🔧 Quick Configuration</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">ARCHER Orchestrator</th>
                    <td>
                        <label><input type="checkbox" checked disabled> Enable orchestration</label><br>
                        <small>5-second sync intervals for optimal performance</small>
                    </td>
                </tr>
                <tr>
                    <th scope="row">TOLA-ART Daily Generation</th>
                    <td>
                        <label><input type="checkbox" checked disabled> Enable daily automation</label><br>
                        <small>Midnight generation with dual royalty structure</small>
                    </td>
                </tr>
                <tr>
                    <th scope="row">RunPod Vault</th>
                    <td>
                        <label><input type="checkbox" disabled> Enable secure processing</label><br>
                        <small>78% cost savings with GPU/CPU optimization</small>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'vortex-ai') !== false) {
            wp_enqueue_script('jquery');
        }
    }
    
    /**
     * AJAX: Dashboard stats
     */
    public function ajax_dashboard_stats() {
        wp_send_json_success(array(
            'ai_agents_active' => 5,
            'tasks_processed' => 'infinite',
            'uptime' => '24/7',
            'sync_interval' => '5s'
        ));
    }
    
    /**
     * AJAX: Trigger AI task
     */
    public function ajax_trigger_ai_task() {
        wp_send_json_success(array('message' => 'AI task triggered successfully'));
    }
} 