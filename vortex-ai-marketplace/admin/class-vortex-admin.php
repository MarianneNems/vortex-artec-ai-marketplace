<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 */
class Vortex_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Add admin menu pages.
     *
     * @since    2.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __('VORTEX AI Marketplace', 'vortex-ai-marketplace'),
            __('VORTEX AI', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-marketplace',
            array($this, 'admin_dashboard'),
            'dashicons-art',
            30
        );

        add_submenu_page(
            'vortex-ai-marketplace',
            __('Dashboard', 'vortex-ai-marketplace'),
            __('Dashboard', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-marketplace',
            array($this, 'admin_dashboard')
        );

        add_submenu_page(
            'vortex-ai-marketplace',
            __('Artist Journey', 'vortex-ai-marketplace'),
            __('Artist Journey', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-artist-journey',
            array($this, 'artist_journey_page')
        );

        add_submenu_page(
            'vortex-ai-marketplace',
            __('AI Agents', 'vortex-ai-marketplace'),
            __('AI Agents', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-agents',
            array($this, 'ai_agents_page')
        );

        add_submenu_page(
            'vortex-ai-marketplace',
            __('Blockchain Settings', 'vortex-ai-marketplace'),
            __('Blockchain', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-blockchain',
            array($this, 'blockchain_settings_page')
        );

        add_submenu_page(
            'vortex-ai-marketplace',
            __('Settings', 'vortex-ai-marketplace'),
            __('Settings', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Initialize admin settings.
     *
     * @since    2.0.0
     */
    public function admin_init() {
        register_setting('vortex_settings', 'vortex_api_settings');
        register_setting('vortex_settings', 'vortex_blockchain_settings');
        register_setting('vortex_settings', 'vortex_agent_settings');
    }

    /**
     * Admin dashboard page.
     *
     * @since    2.0.0
     */
    public function admin_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e('VORTEX AI Marketplace Dashboard', 'vortex-ai-marketplace'); ?></h1>
            
            <div class="vortex-admin-dashboard">
                <div class="vortex-dashboard-cards">
                    <div class="vortex-card">
                        <h3><?php _e('Total Users', 'vortex-ai-marketplace'); ?></h3>
                        <div class="vortex-stat-number"><?php echo $this->get_total_users(); ?></div>
                        <p><?php _e('Registered artists and collectors', 'vortex-ai-marketplace'); ?></p>
                    </div>

                    <div class="vortex-card">
                        <h3><?php _e('Active Subscriptions', 'vortex-ai-marketplace'); ?></h3>
                        <div class="vortex-stat-number"><?php echo $this->get_active_subscriptions(); ?></div>
                        <p><?php _e('Pro and Studio subscribers', 'vortex-ai-marketplace'); ?></p>
                    </div>

                    <div class="vortex-card">
                        <h3><?php _e('AI Generations Today', 'vortex-ai-marketplace'); ?></h3>
                        <div class="vortex-stat-number"><?php echo $this->get_daily_generations(); ?></div>
                        <p><?php _e('HURAII artwork generations', 'vortex-ai-marketplace'); ?></p>
                    </div>

                    <div class="vortex-card">
                        <h3><?php _e('NFTs Minted', 'vortex-ai-marketplace'); ?></h3>
                        <div class="vortex-stat-number"><?php echo $this->get_total_nfts(); ?></div>
                        <p><?php _e('Total NFTs created on TOLA', 'vortex-ai-marketplace'); ?></p>
                    </div>
                </div>

                <div class="vortex-dashboard-row">
                    <div class="vortex-dashboard-col-8">
                        <div class="vortex-card">
                            <h3><?php _e('AI Agent Status', 'vortex-ai-marketplace'); ?></h3>
                            <div class="vortex-agent-status">
                                <?php $this->display_agent_status(); ?>
                            </div>
                        </div>
                    </div>

                    <div class="vortex-dashboard-col-4">
                        <div class="vortex-card">
                            <h3><?php _e('Quick Actions', 'vortex-ai-marketplace'); ?></h3>
                            <div class="vortex-quick-actions">
                                <a href="<?php echo admin_url('admin.php?page=vortex-artist-journey'); ?>" class="button button-primary">
                                    <?php _e('Manage Artist Journey', 'vortex-ai-marketplace'); ?>
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=vortex-ai-agents'); ?>" class="button">
                                    <?php _e('Configure AI Agents', 'vortex-ai-marketplace'); ?>
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=vortex-blockchain'); ?>" class="button">
                                    <?php _e('Blockchain Settings', 'vortex-ai-marketplace'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .vortex-admin-dashboard {
            margin-top: 20px;
        }
        .vortex-dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .vortex-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e1e5e9;
        }
        .vortex-stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .vortex-dashboard-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        .vortex-agent-status {
            display: grid;
            gap: 10px;
        }
        .vortex-agent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .vortex-status-active {
            color: #28a745;
            font-weight: bold;
        }
        .vortex-status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
        .vortex-quick-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        @media (max-width: 768px) {
            .vortex-dashboard-row {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }

    /**
     * Artist Journey management page.
     *
     * @since    2.0.0
     */
    public function artist_journey_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Artist Journey Management', 'vortex-ai-marketplace'); ?></h1>
            
            <div class="vortex-admin-content">
                <div class="vortex-card">
                    <h3><?php _e('Journey Statistics', 'vortex-ai-marketplace'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Milestone', 'vortex-ai-marketplace'); ?></th>
                                <th><?php _e('Completion Rate', 'vortex-ai-marketplace'); ?></th>
                                <th><?php _e('Total Users', 'vortex-ai-marketplace'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $this->display_milestone_stats(); ?>
                        </tbody>
                    </table>
                </div>

                <div class="vortex-card">
                    <h3><?php _e('Subscription Plan Analytics', 'vortex-ai-marketplace'); ?></h3>
                    <div class="vortex-plan-analytics">
                        <?php $this->display_plan_analytics(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AI Agents configuration page.
     *
     * @since    2.0.0
     */
    public function ai_agents_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Agents Configuration', 'vortex-ai-marketplace'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('vortex_settings'); ?>
                
                <div class="vortex-admin-content">
                    <div class="vortex-card">
                        <h3><?php _e('HURAII (GPU Generation)', 'vortex-ai-marketplace'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('RunPod API Key', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="password" name="vortex_agent_settings[huraii_api_key]" 
                                           value="<?php echo esc_attr($this->get_setting('huraii_api_key')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('GPU Instance Type', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <select name="vortex_agent_settings[huraii_gpu_type]">
                                        <option value="rtx_a6000">RTX A6000 (16GB VRAM)</option>
                                        <option value="rtx_4090">RTX 4090 (24GB VRAM)</option>
                                        <option value="rtx_a100">RTX A100 (40GB VRAM)</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="vortex-card">
                        <h3><?php _e('CPU Agents (CLOE, HORACE, THORIUS, ARCHER)', 'vortex-ai-marketplace'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('CPU Cores Allocation', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="number" name="vortex_agent_settings[cpu_cores]" 
                                           value="<?php echo esc_attr($this->get_setting('cpu_cores', '16')); ?>" 
                                           min="4" max="32" />
                                    <p class="description"><?php _e('Total CPU cores to allocate for all CPU agents', 'vortex-ai-marketplace'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Memory Allocation (GB)', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="number" name="vortex_agent_settings[memory_gb]" 
                                           value="<?php echo esc_attr($this->get_setting('memory_gb', '32')); ?>" 
                                           min="8" max="128" />
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Blockchain settings page.
     *
     * @since    2.0.0
     */
    public function blockchain_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Blockchain & TOLA Settings', 'vortex-ai-marketplace'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('vortex_settings'); ?>
                
                <div class="vortex-admin-content">
                    <div class="vortex-card">
                        <h3><?php _e('Solana Network Configuration', 'vortex-ai-marketplace'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Network', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <select name="vortex_blockchain_settings[solana_network]">
                                        <option value="mainnet-beta">Mainnet Beta</option>
                                        <option value="devnet">Devnet (Testing)</option>
                                        <option value="testnet">Testnet</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('RPC Endpoint', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="url" name="vortex_blockchain_settings[rpc_endpoint]" 
                                           value="<?php echo esc_attr($this->get_blockchain_setting('rpc_endpoint')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="vortex-card">
                        <h3><?php _e('TOLA Token Settings', 'vortex-ai-marketplace'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Token Address', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="text" name="vortex_blockchain_settings[tola_token_address]" 
                                           value="<?php echo esc_attr($this->get_blockchain_setting('tola_token_address')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Exchange Rate (USD to TOLA)', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="number" name="vortex_blockchain_settings[usd_to_tola_rate]" 
                                           value="<?php echo esc_attr($this->get_blockchain_setting('usd_to_tola_rate', '1.0')); ?>" 
                                           step="0.01" min="0" />
                                    <p class="description"><?php _e('Currently set to 1:1 ratio', 'vortex-ai-marketplace'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * General settings page.
     *
     * @since    2.0.0
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('VORTEX AI Marketplace Settings', 'vortex-ai-marketplace'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('vortex_settings'); ?>
                
                <div class="vortex-admin-content">
                    <div class="vortex-card">
                        <h3><?php _e('General Settings', 'vortex-ai-marketplace'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Debug Mode', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="checkbox" name="vortex_api_settings[debug_mode]" 
                                           value="1" <?php checked($this->get_api_setting('debug_mode'), '1'); ?> />
                                    <p class="description"><?php _e('Enable verbose logging for debugging', 'vortex-ai-marketplace'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('API Rate Limiting', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="checkbox" name="vortex_api_settings[rate_limiting]" 
                                           value="1" <?php checked($this->get_api_setting('rate_limiting'), '1'); ?> />
                                    <p class="description"><?php _e('Enable API rate limiting for security', 'vortex-ai-marketplace'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get total users count.
     *
     * @since    2.0.0
     * @return   int
     */
    private function get_total_users() {
        return count_users()['total_users'];
    }

    /**
     * Get active subscriptions count.
     *
     * @since    2.0.0
     * @return   int
     */
    private function get_active_subscriptions() {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->usermeta} 
            WHERE meta_key = 'vortex_subscription_plan' 
            AND meta_value IN ('pro', 'studio')
        "));
        return intval($count);
    }

    /**
     * Get daily generations count.
     *
     * @since    2.0.0
     * @return   int
     */
    private function get_daily_generations() {
        // Mock data for demonstration
        return 127;
    }

    /**
     * Get total NFTs count.
     *
     * @since    2.0.0
     * @return   int
     */
    private function get_total_nfts() {
        // Mock data for demonstration
        return 1543;
    }

    /**
     * Display AI agent status.
     *
     * @since    2.0.0
     */
    private function display_agent_status() {
        $agents = array(
            'HURAII' => true,
            'CLOE' => true,
            'HORACE' => true,
            'THORIUS' => true,
            'ARCHER' => true,
        );

        foreach ($agents as $agent => $status) {
            echo '<div class="vortex-agent-item">';
            echo '<span>' . esc_html($agent) . '</span>';
            echo '<span class="' . ($status ? 'vortex-status-active' : 'vortex-status-inactive') . '">';
            echo $status ? __('Active', 'vortex-ai-marketplace') : __('Inactive', 'vortex-ai-marketplace');
            echo '</span>';
            echo '</div>';
        }
    }

    /**
     * Display milestone statistics.
     *
     * @since    2.0.0
     */
    private function display_milestone_stats() {
        $milestones = array(
            'Role Quiz Completion' => array('rate' => 85, 'users' => 340),
            'Wallet Connection' => array('rate' => 72, 'users' => 288),
            'First Artwork Upload' => array('rate' => 65, 'users' => 260),
            'First Sale' => array('rate' => 23, 'users' => 92),
        );

        foreach ($milestones as $milestone => $data) {
            echo '<tr>';
            echo '<td>' . esc_html($milestone) . '</td>';
            echo '<td>' . esc_html($data['rate']) . '%</td>';
            echo '<td>' . esc_html($data['users']) . '</td>';
            echo '</tr>';
        }
    }

    /**
     * Display plan analytics.
     *
     * @since    2.0.0
     */
    private function display_plan_analytics() {
        $plans = array(
            'Starter' => array('count' => 150, 'revenue' => 2997.50),
            'Pro' => array('count' => 85, 'revenue' => 3399.15),
            'Studio' => array('count' => 12, 'revenue' => 1199.88),
        );

        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';
        foreach ($plans as $plan => $data) {
            echo '<div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">';
            echo '<h4>' . esc_html($plan) . '</h4>';
            echo '<div style="font-size: 1.5em; font-weight: bold; color: #667eea;">' . esc_html($data['count']) . '</div>';
            echo '<div style="color: #666;">subscribers</div>';
            echo '<div style="font-size: 1.2em; font-weight: bold; color: #28a745; margin-top: 10px;">$' . esc_html(number_format($data['revenue'], 2)) . '</div>';
            echo '<div style="color: #666;">monthly revenue</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Get agent setting.
     *
     * @since    2.0.0
     * @param    string $key Setting key.
     * @param    string $default Default value.
     * @return   string
     */
    private function get_setting($key, $default = '') {
        $settings = get_option('vortex_agent_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Get blockchain setting.
     *
     * @since    2.0.0
     * @param    string $key Setting key.
     * @param    string $default Default value.
     * @return   string
     */
    private function get_blockchain_setting($key, $default = '') {
        $settings = get_option('vortex_blockchain_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Get API setting.
     *
     * @since    2.0.0
     * @param    string $key Setting key.
     * @param    string $default Default value.
     * @return   string
     */
    private function get_api_setting($key, $default = '') {
        $settings = get_option('vortex_api_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
} 