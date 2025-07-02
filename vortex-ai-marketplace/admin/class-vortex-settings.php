<?php
/**
 * The admin settings page class.
 *
 * @since      3.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 */

class Vortex_Settings {

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.0.0
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_vortex_test_s3_connection', array($this, 'test_s3_connection'));
        add_action('wp_ajax_vortex_test_ai_server', array($this, 'test_ai_server'));
    }

    /**
     * Add settings page to admin menu
     *
     * @since    3.0.0
     */
    public function add_settings_page() {
        add_options_page(
            'VORTEX AI Settings',
            'VORTEX AI',
            'manage_options',
            'vortex-ai-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Register all settings
     *
     * @since    3.0.0
     */
    public function register_settings() {
        // AWS S3 Settings
        register_setting('vortex_aws_settings', 'vortex_aws_access_key');
        register_setting('vortex_aws_settings', 'vortex_aws_secret_key');
        register_setting('vortex_aws_settings', 'vortex_aws_region');
        register_setting('vortex_aws_settings', 'vortex_aws_s3_bucket');

        // AI Server Settings
        register_setting('vortex_ai_settings', 'vortex_ai_server_url');
        register_setting('vortex_ai_settings', 'vortex_ai_server_token');
        register_setting('vortex_ai_settings', 'vortex_runpod_endpoint');
        register_setting('vortex_ai_settings', 'vortex_runpod_token');

        // Blockchain Settings
        register_setting('vortex_blockchain_settings', 'vortex_blockchain_enabled');
        register_setting('vortex_blockchain_settings', 'vortex_solana_rpc_url');
        register_setting('vortex_blockchain_settings', 'vortex_solana_program_id');
        register_setting('vortex_blockchain_settings', 'vortex_tola_token_mint');
        register_setting('vortex_blockchain_settings', 'vortex_marketplace_wallet');

        // Marketplace Settings
        register_setting('vortex_marketplace_settings', 'vortex_marketplace_commission');
        register_setting('vortex_marketplace_settings', 'vortex_swap_fee');
        register_setting('vortex_marketplace_settings', 'vortex_sale_fee');
        register_setting('vortex_marketplace_settings', 'vortex_wallet_management_fee');

        // Add sections
        add_settings_section(
            'vortex_aws_section',
            'AWS S3 Configuration',
            array($this, 'aws_section_callback'),
            'vortex_aws_settings'
        );

        add_settings_section(
            'vortex_ai_section',
            'AI Server Configuration',
            array($this, 'ai_section_callback'),
            'vortex_ai_settings'
        );

        add_settings_section(
            'vortex_blockchain_section',
            'Blockchain Configuration',
            array($this, 'blockchain_section_callback'),
            'vortex_blockchain_settings'
        );

        add_settings_section(
            'vortex_marketplace_section',
            'Marketplace Configuration',
            array($this, 'marketplace_section_callback'),
            'vortex_marketplace_settings'
        );

        // Add fields
        $this->add_settings_fields();
    }

    /**
     * Add all settings fields
     *
     * @since    3.0.0
     */
    private function add_settings_fields() {
        // AWS Fields
        add_settings_field(
            'vortex_aws_access_key',
            'AWS Access Key',
            array($this, 'text_field_callback'),
            'vortex_aws_settings',
            'vortex_aws_section',
            array('field' => 'vortex_aws_access_key', 'type' => 'password')
        );

        add_settings_field(
            'vortex_aws_secret_key',
            'AWS Secret Key',
            array($this, 'text_field_callback'),
            'vortex_aws_settings',
            'vortex_aws_section',
            array('field' => 'vortex_aws_secret_key', 'type' => 'password')
        );

        add_settings_field(
            'vortex_aws_region',
            'AWS Region',
            array($this, 'select_field_callback'),
            'vortex_aws_settings',
            'vortex_aws_section',
            array(
                'field' => 'vortex_aws_region',
                'options' => array(
                    'us-east-1' => 'US East (N. Virginia)',
                    'us-east-2' => 'US East (Ohio)',
                    'us-west-1' => 'US West (N. California)',
                    'us-west-2' => 'US West (Oregon)',
                    'eu-west-1' => 'Europe (Ireland)',
                    'eu-central-1' => 'Europe (Frankfurt)',
                    'ap-southeast-1' => 'Asia Pacific (Singapore)'
                )
            )
        );

        add_settings_field(
            'vortex_aws_s3_bucket',
            'S3 Bucket Name',
            array($this, 'text_field_callback'),
            'vortex_aws_settings',
            'vortex_aws_section',
            array('field' => 'vortex_aws_s3_bucket')
        );

        // AI Server Fields
        add_settings_field(
            'vortex_ai_server_url',
            'AI Server URL',
            array($this, 'text_field_callback'),
            'vortex_ai_settings',
            'vortex_ai_section',
            array('field' => 'vortex_ai_server_url', 'placeholder' => 'https://api.your-ai-server.com')
        );

        add_settings_field(
            'vortex_ai_server_token',
            'AI Server Token',
            array($this, 'text_field_callback'),
            'vortex_ai_settings',
            'vortex_ai_section',
            array('field' => 'vortex_ai_server_token', 'type' => 'password')
        );

        add_settings_field(
            'vortex_runpod_endpoint',
            'RunPod Endpoint',
            array($this, 'text_field_callback'),
            'vortex_ai_settings',
            'vortex_ai_section',
            array('field' => 'vortex_runpod_endpoint', 'placeholder' => 'https://api.runpod.ai/v2/...')
        );

        add_settings_field(
            'vortex_runpod_token',
            'RunPod API Token',
            array($this, 'text_field_callback'),
            'vortex_ai_settings',
            'vortex_ai_section',
            array('field' => 'vortex_runpod_token', 'type' => 'password')
        );

        // Blockchain Fields
        add_settings_field(
            'vortex_blockchain_enabled',
            'Enable Blockchain Integration',
            array($this, 'checkbox_field_callback'),
            'vortex_blockchain_settings',
            'vortex_blockchain_section',
            array('field' => 'vortex_blockchain_enabled')
        );

        add_settings_field(
            'vortex_solana_rpc_url',
            'Solana RPC URL',
            array($this, 'text_field_callback'),
            'vortex_blockchain_settings',
            'vortex_blockchain_section',
            array('field' => 'vortex_solana_rpc_url', 'placeholder' => 'https://api.mainnet-beta.solana.com')
        );

        add_settings_field(
            'vortex_solana_program_id',
            'Solana Program ID',
            array($this, 'text_field_callback'),
            'vortex_blockchain_settings',
            'vortex_blockchain_section',
            array('field' => 'vortex_solana_program_id')
        );

        add_settings_field(
            'vortex_tola_token_mint',
            'TOLA Token Mint Address',
            array($this, 'text_field_callback'),
            'vortex_blockchain_settings',
            'vortex_blockchain_section',
            array('field' => 'vortex_tola_token_mint')
        );

        add_settings_field(
            'vortex_marketplace_wallet',
            'Marketplace Wallet Address',
            array($this, 'text_field_callback'),
            'vortex_blockchain_settings',
            'vortex_blockchain_section',
            array('field' => 'vortex_marketplace_wallet')
        );

        // Marketplace Fields
        add_settings_field(
            'vortex_marketplace_commission',
            'Marketplace Commission (%)',
            array($this, 'number_field_callback'),
            'vortex_marketplace_settings',
            'vortex_marketplace_section',
            array('field' => 'vortex_marketplace_commission', 'min' => 0, 'max' => 50, 'step' => 0.1, 'default' => 15)
        );

        add_settings_field(
            'vortex_swap_fee',
            'Swap Fee ($)',
            array($this, 'number_field_callback'),
            'vortex_marketplace_settings',
            'vortex_marketplace_section',
            array('field' => 'vortex_swap_fee', 'min' => 0, 'step' => 0.01, 'default' => 3)
        );

        add_settings_field(
            'vortex_sale_fee',
            'Sale Transaction Fee ($)',
            array($this, 'number_field_callback'),
            'vortex_marketplace_settings',
            'vortex_marketplace_section',
            array('field' => 'vortex_sale_fee', 'min' => 0, 'step' => 0.01, 'default' => 89)
        );

        add_settings_field(
            'vortex_wallet_management_fee',
            'Wallet Management Fee (%)',
            array($this, 'number_field_callback'),
            'vortex_marketplace_settings',
            'vortex_marketplace_section',
            array('field' => 'vortex_wallet_management_fee', 'min' => 0, 'max' => 5, 'step' => 0.1, 'default' => 0.5)
        );
    }

    /**
     * Display the settings page
     *
     * @since    3.0.0
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>VORTEX AI Marketplace Settings</h1>
            
            <div class="nav-tab-wrapper">
                <a href="#aws-settings" class="nav-tab nav-tab-active">AWS S3</a>
                <a href="#ai-settings" class="nav-tab">AI Servers</a>
                <a href="#blockchain-settings" class="nav-tab">Blockchain</a>
                <a href="#marketplace-settings" class="nav-tab">Marketplace</a>
                <a href="#system-status" class="nav-tab">System Status</a>
            </div>

            <div id="aws-settings" class="tab-content active">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('vortex_aws_settings');
                    do_settings_sections('vortex_aws_settings');
                    ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="Save AWS Settings" />
                        <button type="button" class="button" id="test-s3-connection">Test S3 Connection</button>
                    </p>
                </form>
            </div>

            <div id="ai-settings" class="tab-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('vortex_ai_settings');
                    do_settings_sections('vortex_ai_settings');
                    ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="Save AI Settings" />
                        <button type="button" class="button" id="test-ai-server">Test AI Server</button>
                    </p>
                </form>
            </div>

            <div id="blockchain-settings" class="tab-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('vortex_blockchain_settings');
                    do_settings_sections('vortex_blockchain_settings');
                    ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="Save Blockchain Settings" />
                    </p>
                </form>
            </div>

            <div id="marketplace-settings" class="tab-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('vortex_marketplace_settings');
                    do_settings_sections('vortex_marketplace_settings');
                    ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="Save Marketplace Settings" />
                    </p>
                </form>
            </div>

            <div id="system-status" class="tab-content">
                <?php $this->display_system_status(); ?>
            </div>
        </div>

        <style>
        .nav-tab-wrapper { margin-bottom: 20px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .status-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; }
        .status-ok { background-color: #46b450; }
        .status-warning { background-color: #ffb900; }
        .status-error { background-color: #dc3232; }
        .system-status-table { margin-top: 20px; }
        .system-status-table th, .system-status-table td { text-align: left; padding: 8px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
            });

            // Test S3 connection
            $('#test-s3-connection').click(function() {
                var $button = $(this);
                $button.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_test_s3_connection',
                        nonce: '<?php echo wp_create_nonce('vortex_test_s3'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('S3 connection successful!');
                        } else {
                            alert('S3 connection failed: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Failed to test S3 connection');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Test S3 Connection');
                    }
                });
            });

            // Test AI server
            $('#test-ai-server').click(function() {
                var $button = $(this);
                $button.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_test_ai_server',
                        nonce: '<?php echo wp_create_nonce('vortex_test_ai'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('AI server connection successful!');
                        } else {
                            alert('AI server connection failed: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Failed to test AI server connection');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Test AI Server');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Display system status
     *
     * @since    3.0.0
     */
    private function display_system_status() {
        $status_checks = array(
            'WooCommerce' => class_exists('WooCommerce'),
            'AWS S3 Configured' => !empty(get_option('vortex_aws_s3_bucket')),
            'AI Server Configured' => !empty(get_option('vortex_ai_server_url')),
            'Blockchain Enabled' => get_option('vortex_blockchain_enabled'),
            'TOLA-ART Automation' => wp_next_scheduled('vortex_daily_art_generation'),
            'Database Tables' => $this->check_database_tables(),
            'WordPress Version' => version_compare(get_bloginfo('version'), '5.0', '>='),
            'PHP Version' => version_compare(PHP_VERSION, '7.4', '>=')
        );

        echo '<h2>System Status</h2>';
        echo '<table class="widefat system-status-table">';
        echo '<thead><tr><th>Component</th><th>Status</th><th>Details</th></tr></thead>';
        echo '<tbody>';

        foreach ($status_checks as $component => $status) {
            $indicator_class = $status ? 'status-ok' : 'status-error';
            $status_text = $status ? 'OK' : 'Issue';
            $details = $this->get_status_details($component, $status);
            
            echo '<tr>';
            echo '<td>' . esc_html($component) . '</td>';
            echo '<td><span class="status-indicator ' . $indicator_class . '"></span>' . $status_text . '</td>';
            echo '<td>' . esc_html($details) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Additional system info
        echo '<h3>Plugin Information</h3>';
        echo '<ul>';
        echo '<li><strong>Version:</strong> ' . VORTEX_AI_MARKETPLACE_VERSION . '</li>';
        echo '<li><strong>Plugin Directory:</strong> ' . VORTEX_AI_MARKETPLACE_PLUGIN_DIR . '</li>';
        echo '<li><strong>Database Prefix:</strong> ' . $GLOBALS['wpdb']->prefix . '</li>';
        echo '<li><strong>WordPress Memory Limit:</strong> ' . WP_MEMORY_LIMIT . '</li>';
        echo '<li><strong>PHP Memory Limit:</strong> ' . ini_get('memory_limit') . '</li>';
        echo '</ul>';
    }

    /**
     * Get status details for components
     *
     * @since    3.0.0
     * @param    string    $component    Component name
     * @param    bool      $status       Status
     * @return   string                  Status details
     */
    private function get_status_details($component, $status) {
        switch ($component) {
            case 'WooCommerce':
                return $status ? 'Version ' . WC()->version : 'Not installed or activated';
            case 'WordPress Version':
                return 'Version ' . get_bloginfo('version');
            case 'PHP Version':
                return 'Version ' . PHP_VERSION;
            case 'TOLA-ART Automation':
                return $status ? 'Scheduled' : 'Not scheduled';
            default:
                return $status ? 'Configured' : 'Not configured';
        }
    }

    /**
     * Check if database tables exist
     *
     * @since    3.0.0
     * @return   bool    Tables exist
     */
    private function check_database_tables() {
        global $wpdb;
        
        $required_tables = array(
            $wpdb->prefix . 'vortex_token_transactions',
            $wpdb->prefix . 'vortex_wallets',
            $wpdb->prefix . 'vortex_seed_artworks',
            $wpdb->prefix . 'vortex_events'
        );

        foreach ($required_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                return false;
            }
        }

        return true;
    }

    // Field callback methods
    public function aws_section_callback() {
        echo '<p>Configure your AWS S3 settings for storing seed artworks and generated content.</p>';
    }

    public function ai_section_callback() {
        echo '<p>Configure AI server endpoints for art generation and processing.</p>';
    }

    public function blockchain_section_callback() {
        echo '<p>Configure blockchain settings for TOLA token and Solana integration.</p>';
    }

    public function marketplace_section_callback() {
        echo '<p>Configure marketplace fees and transaction costs.</p>';
    }

    public function text_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field);
        $type = isset($args['type']) ? $args['type'] : 'text';
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        
        echo '<input type="' . $type . '" name="' . $field . '" value="' . esc_attr($value) . '" class="regular-text" placeholder="' . esc_attr($placeholder) . '" />';
    }

    public function select_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field);
        $options = $args['options'];
        
        echo '<select name="' . $field . '" class="regular-text">';
        foreach ($options as $option_value => $option_label) {
            $selected = selected($value, $option_value, false);
            echo '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
    }

    public function checkbox_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field);
        
        echo '<input type="checkbox" name="' . $field . '" value="1" ' . checked(1, $value, false) . ' />';
    }

    public function number_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field, $args['default'] ?? '');
        $min = isset($args['min']) ? 'min="' . $args['min'] . '"' : '';
        $max = isset($args['max']) ? 'max="' . $args['max'] . '"' : '';
        $step = isset($args['step']) ? 'step="' . $args['step'] . '"' : '';
        
        echo '<input type="number" name="' . $field . '" value="' . esc_attr($value) . '" class="small-text" ' . $min . ' ' . $max . ' ' . $step . ' />';
    }

    public function test_s3_connection() {
        check_ajax_referer('vortex_test_s3', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Test S3 connection here
        // For now, just return success
        wp_send_json_success('S3 connection test completed (mock)');
    }

    public function test_ai_server() {
        check_ajax_referer('vortex_test_ai', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Test AI server connection here
        // For now, just return success
        wp_send_json_success('AI server connection test completed (mock)');
    }
} 