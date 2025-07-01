<?php
/**
 * RunPod AJAX Handlers
 * 
 * Processes AJAX requests for RunPod server management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/ajax
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The class that handles RunPod AJAX requests
 */
class Vortex_RunPod_AJAX {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX actions for admin
        add_action('wp_ajax_vortex_test_runpod_connection', array($this, 'test_runpod_connection'));
        add_action('wp_ajax_vortex_update_runpod_url', array($this, 'update_runpod_url'));
        add_action('wp_ajax_vortex_get_runpod_models', array($this, 'get_runpod_models'));
        add_action('wp_ajax_vortex_runpod_generate_test', array($this, 'generate_test_image'));
        
        // Public AJAX actions (for frontend use)
        add_action('wp_ajax_vortex_runpod_health_check', array($this, 'health_check'));
        add_action('wp_ajax_nopriv_vortex_runpod_health_check', array($this, 'health_check'));
    }
    
    /**
     * Test RunPod server connection
     */
    public function test_runpod_connection() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_runpod_test')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'vortex-ai-marketplace'),
                'code' => 'security_error'
            ));
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'vortex-ai-marketplace'),
                'code' => 'permission_error'
            ));
            return;
        }
        
        // Get RunPod configuration
        $runpod_config = Vortex_RunPod_Config::get_instance();
        
        // Test connection
        $test_result = $runpod_config->test_connection();
        
        // Log the test
        $runpod_config->log('Connection test performed', 'info', array(
            'result' => $test_result['success'],
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ));
        
        if ($test_result['success']) {
            wp_send_json_success($test_result);
        } else {
            wp_send_json_error($test_result);
        }
    }
    
    /**
     * Update RunPod server URL
     */
    public function update_runpod_url() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_runpod_update')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'vortex-ai-marketplace'),
                'code' => 'security_error'
            ));
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'vortex-ai-marketplace'),
                'code' => 'permission_error'
            ));
            return;
        }
        
        // Validate new URL
        if (empty($_POST['new_url'])) {
            wp_send_json_error(array(
                'message' => __('Server URL is required', 'vortex-ai-marketplace'),
                'code' => 'validation_error'
            ));
            return;
        }
        
        $new_url = esc_url_raw($_POST['new_url']);
        
        // Get RunPod configuration and update URL
        $runpod_config = Vortex_RunPod_Config::get_instance();
        $update_result = $runpod_config->update_server_url($new_url);
        
        // Log the update
        $runpod_config->log('Server URL update attempted', 'info', array(
            'new_url' => $new_url,
            'result' => $update_result['success'],
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ));
        
        if ($update_result['success']) {
            wp_send_json_success($update_result);
        } else {
            wp_send_json_error($update_result);
        }
    }
    
    /**
     * Get available models from RunPod server
     */
    public function get_runpod_models() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_runpod_models')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'vortex-ai-marketplace'),
                'code' => 'security_error'
            ));
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'vortex-ai-marketplace'),
                'code' => 'permission_error'
            ));
            return;
        }
        
        // Get RunPod configuration
        $runpod_config = Vortex_RunPod_Config::get_instance();
        
        // Get available models
        $models = $runpod_config->get_available_models();
        
        if (!empty($models)) {
            wp_send_json_success(array(
                'models' => $models,
                'count' => count($models),
                'message' => sprintf(__('Found %d models', 'vortex-ai-marketplace'), count($models))
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No models found or server unreachable', 'vortex-ai-marketplace'),
                'code' => 'no_models'
            ));
        }
    }
    
    /**
     * Generate test image to verify RunPod functionality
     */
    public function generate_test_image() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_runpod_test_generate')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'vortex-ai-marketplace'),
                'code' => 'security_error'
            ));
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'vortex-ai-marketplace'),
                'code' => 'permission_error'
            ));
            return;
        }
        
        // Get RunPod configuration
        $runpod_config = Vortex_RunPod_Config::get_instance();
        
        // Test generation request
        $test_url = $runpod_config->get('primary_url') . '/sdapi/v1/txt2img';
        
        $test_prompt = 'simple colorful geometric abstract art, test image';
        
        $request_body = array(
            'prompt' => $test_prompt,
            'negative_prompt' => 'low quality, blurry',
            'steps' => 10, // Fast generation for testing
            'cfg_scale' => 7.5,
            'width' => 512,
            'height' => 512,
            'sampler_name' => 'DPM++ 2M Karras',
            'batch_size' => 1,
            'n_iter' => 1,
            'restore_faces' => false,
            'tiling' => false
        );
        
        $response = wp_remote_post($test_url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($request_body),
            'timeout' => 60 // Shorter timeout for test
        ));
        
        if (is_wp_error($response)) {
            $runpod_config->log('Test generation failed', 'error', array(
                'error' => $response->get_error_message(),
                'user_id' => get_current_user_id()
            ));
            
            wp_send_json_error(array(
                'message' => __('Test generation failed: ', 'vortex-ai-marketplace') . $response->get_error_message(),
                'code' => 'generation_error'
            ));
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200 && isset($response_body['images']) && !empty($response_body['images'])) {
            $runpod_config->log('Test generation successful', 'info', array(
                'image_count' => count($response_body['images']),
                'user_id' => get_current_user_id()
            ));
            
            wp_send_json_success(array(
                'message' => __('Test image generated successfully!', 'vortex-ai-marketplace'),
                'image_count' => count($response_body['images']),
                'generation_time' => isset($response_body['info']) ? $response_body['info'] : 'Unknown'
            ));
        } else {
            $runpod_config->log('Test generation failed', 'error', array(
                'response_code' => $response_code,
                'response_body' => $response_body,
                'user_id' => get_current_user_id()
            ));
            
            wp_send_json_error(array(
                'message' => __('Test generation failed - invalid response from server', 'vortex-ai-marketplace'),
                'code' => 'invalid_response',
                'details' => array(
                    'response_code' => $response_code,
                    'has_images' => isset($response_body['images'])
                )
            ));
        }
    }
    
    /**
     * Health check for RunPod server (public endpoint)
     */
    public function health_check() {
        // Get RunPod configuration
        $runpod_config = Vortex_RunPod_Config::get_instance();
        
        // Get cached health status first
        $cached_health = get_transient('vortex_runpod_health');
        
        if ($cached_health !== false) {
            wp_send_json_success(array(
                'status' => $cached_health ? 'online' : 'offline',
                'cached' => true,
                'timestamp' => get_transient('vortex_runpod_health_timestamp')
            ));
            return;
        }
        
        // Perform actual health check
        $health_status = $runpod_config->check_runpod_health();
        
        // Cache the result
        set_transient('vortex_runpod_health_timestamp', current_time('mysql'), 5 * MINUTE_IN_SECONDS);
        
        wp_send_json_success(array(
            'status' => $health_status ? 'online' : 'offline',
            'cached' => false,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Update generation statistics
     */
    public function update_generation_stats($successful = true) {
        if ($successful) {
            // Increment total generations
            $total = get_option('vortex_runpod_total_generations', 0);
            update_option('vortex_runpod_total_generations', $total + 1);
            
            // Update today's count
            $today = date('Y-m-d');
            $today_option = 'vortex_runpod_generations_' . $today;
            $today_count = get_option($today_option, 0);
            update_option($today_option, $today_count + 1);
            update_option('vortex_runpod_today_generations', $today_count + 1);
        }
    }
} 