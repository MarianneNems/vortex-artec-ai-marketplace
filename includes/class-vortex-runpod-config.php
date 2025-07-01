<?php
/**
 * VortexArtec RunPod Configuration
 *
 * Manages RunPod AI server configuration and integration
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * RunPod Configuration Class
 */
class Vortex_RunPod_Config {
    
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
    /**
     * RunPod server configuration
     */
    private $config = array();
    
    /**
     * Get the singleton instance
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
        $this->load_config();
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Load RunPod configuration
     */
    private function load_config() {
        $this->config = array(
            'primary_url' => get_option('vortex_runpod_primary_url', 'https://4416007023f09466f6.gradio.live'),
            'backup_urls' => get_option('vortex_runpod_backup_urls', array()),
            'api_timeout' => get_option('vortex_runpod_timeout', 120),
            'max_retries' => get_option('vortex_runpod_max_retries', 3),
            'model_name' => get_option('vortex_runpod_model', 'sd_xl_base_1.0.safetensors'),
            'default_steps' => get_option('vortex_runpod_steps', 30),
            'default_cfg_scale' => get_option('vortex_runpod_cfg_scale', 7.5),
            'default_sampler' => get_option('vortex_runpod_sampler', 'DPM++ 2M Karras'),
            'health_check_interval' => get_option('vortex_runpod_health_interval', 300), // 5 minutes
            'auto_failover' => get_option('vortex_runpod_auto_failover', true),
            'enable_logging' => get_option('vortex_runpod_logging', true),
            'aws_s3_backup' => get_option('vortex_runpod_s3_backup', true),
            's3_bucket' => get_option('vortex_runpod_s3_bucket', 'vortexartec.com-client-art'),
            's3_region' => get_option('vortex_runpod_s3_region', 'us-east-2')
        );
    }
    
    /**
     * Register settings for WordPress admin
     */
    public function register_settings() {
        // RunPod Server Settings
        register_setting('vortex_runpod_settings', 'vortex_runpod_primary_url');
        register_setting('vortex_runpod_settings', 'vortex_runpod_backup_urls');
        register_setting('vortex_runpod_settings', 'vortex_runpod_timeout');
        register_setting('vortex_runpod_settings', 'vortex_runpod_max_retries');
        register_setting('vortex_runpod_settings', 'vortex_runpod_model');
        register_setting('vortex_runpod_settings', 'vortex_runpod_steps');
        register_setting('vortex_runpod_settings', 'vortex_runpod_cfg_scale');
        register_setting('vortex_runpod_settings', 'vortex_runpod_sampler');
        register_setting('vortex_runpod_settings', 'vortex_runpod_health_interval');
        register_setting('vortex_runpod_settings', 'vortex_runpod_auto_failover');
        register_setting('vortex_runpod_settings', 'vortex_runpod_logging');
        register_setting('vortex_runpod_settings', 'vortex_runpod_s3_backup');
        register_setting('vortex_runpod_settings', 'vortex_runpod_s3_bucket');
        register_setting('vortex_runpod_settings', 'vortex_runprod_s3_region');
    }
    
    /**
     * Get configuration value
     */
    public function get($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    /**
     * Set configuration value
     */
    public function set($key, $value) {
        $this->config[$key] = $value;
        update_option('vortex_runpod_' . $key, $value);
    }
    
    /**
     * Get all configuration
     */
    public function get_all() {
        return $this->config;
    }
    
    /**
     * Test RunPod server connection
     */
    public function test_connection($url = null) {
        $test_url = $url ?: $this->get('primary_url');
        $health_endpoint = $test_url . '/sdapi/v1/options';
        
        $response = wp_remote_get($health_endpoint, array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
                'code' => 'connection_error'
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code === 200) {
            $data = json_decode($response_body, true);
            return array(
                'success' => true,
                'message' => 'Connection successful',
                'server_info' => array(
                    'model' => isset($data['sd_model_checkpoint']) ? $data['sd_model_checkpoint'] : 'Unknown',
                    'response_time' => 'OK'
                )
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Server responded with code: ' . $response_code,
                'code' => 'server_error'
            );
        }
    }
    
    /**
     * Get available models from RunPod server
     */
    public function get_available_models($url = null) {
        $models_url = ($url ?: $this->get('primary_url')) . '/sdapi/v1/sd-models';
        
        $response = wp_remote_get($models_url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            return is_array($data) ? $data : array();
        }
        
        return array();
    }
    
    /**
     * Get server status and health
     */
    public function get_server_status() {
        $primary_status = $this->test_connection();
        $backup_statuses = array();
        
        foreach ($this->get('backup_urls', array()) as $backup_url) {
            $backup_statuses[] = array(
                'url' => $backup_url,
                'status' => $this->test_connection($backup_url)
            );
        }
        
        return array(
            'primary' => array(
                'url' => $this->get('primary_url'),
                'status' => $primary_status
            ),
            'backups' => $backup_statuses,
            'last_check' => current_time('mysql')
        );
    }
    
    /**
     * Update server URL with automatic validation
     */
    public function update_server_url($new_url) {
        // Validate URL format
        if (!filter_var($new_url, FILTER_VALIDATE_URL)) {
            return array(
                'success' => false,
                'message' => 'Invalid URL format'
            );
        }
        
        // Test connection to new URL
        $test_result = $this->test_connection($new_url);
        
        if ($test_result['success']) {
            $this->set('primary_url', $new_url);
            return array(
                'success' => true,
                'message' => 'Server URL updated successfully',
                'server_info' => $test_result['server_info']
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Could not connect to new server: ' . $test_result['message']
            );
        }
    }
    
    /**
     * Log RunPod events
     */
    public function log($message, $level = 'info', $context = array()) {
        if (!$this->get('enable_logging')) {
            return;
        }
        
        error_log(sprintf(
            '[VortexArtec RunPod] [%s] %s %s',
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        ));
    }
} 