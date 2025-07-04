<?php
/**
 * VORTEX Secret Sauce API Client
 * 
 * This is a thin API wrapper that calls the remote secret sauce microservice
 * instead of including proprietary code directly in the plugin.
 * 
 * @package VortexAI
 * @subpackage SecretSauce
 * @version 1.0.0
 * @security This client isolates proprietary algorithms from the main codebase
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Secret Sauce API Client Class
 * 
 * Handles all communication with the remote secret sauce microservice
 */
class VORTEX_Secret_Sauce_Client {
    
    /**
     * API endpoint for the secret sauce microservice
     */
    private $api_endpoint;
    
    /**
     * API key for authentication
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_endpoint = defined('VORTEX_SECRET_SAUCE_API_URL') 
            ? VORTEX_SECRET_SAUCE_API_URL 
            : 'https://api.vortexartec.com/secret-sauce';
        
        $this->api_key = defined('VORTEX_SECRET_SAUCE_API_KEY') 
            ? VORTEX_SECRET_SAUCE_API_KEY 
            : get_option('vortex_secret_sauce_api_key');
    }
    
    /**
     * Generate personalized seed art
     * 
     * @param array $params Art generation parameters
     * @return array|WP_Error Generated artwork data or error
     */
    public function generate_seed_art($params) {
        $endpoint = $this->api_endpoint . '/generate-seed-art';
        
        $request_data = array(
            'user_id' => get_current_user_id(),
            'zodiac_sign' => $params['zodiac_sign'] ?? '',
            'art_style' => $params['art_style'] ?? 'abstract',
            'color_palette' => $params['color_palette'] ?? 'vibrant',
            'sacred_geometry' => $params['sacred_geometry'] ?? true,
            'timestamp' => time(),
            'session_id' => session_id()
        );
        
        return $this->make_api_request($endpoint, $request_data);
    }
    
    /**
     * Analyze zodiac compatibility
     * 
     * @param array $params Analysis parameters
     * @return array|WP_Error Analysis results or error
     */
    public function analyze_zodiac_compatibility($params) {
        $endpoint = $this->api_endpoint . '/analyze-zodiac';
        
        $request_data = array(
            'primary_sign' => $params['primary_sign'],
            'secondary_sign' => $params['secondary_sign'] ?? '',
            'birth_date' => $params['birth_date'] ?? '',
            'analysis_type' => $params['analysis_type'] ?? 'compatibility',
            'timestamp' => time()
        );
        
        return $this->make_api_request($endpoint, $request_data);
    }
    
    /**
     * Orchestrate AI agents
     * 
     * @param array $params Orchestration parameters
     * @return array|WP_Error Orchestration results or error
     */
    public function orchestrate_agents($params) {
        $endpoint = $this->api_endpoint . '/orchestrate';
        
        $request_data = array(
            'agents' => $params['agents'] ?? ['thorius', 'huraii', 'cloe'],
            'task' => $params['task'] ?? 'analyze',
            'data' => $params['data'] ?? array(),
            'user_context' => array(
                'user_id' => get_current_user_id(),
                'role' => wp_get_current_user()->roles[0] ?? 'subscriber'
            ),
            'timestamp' => time()
        );
        
        return $this->make_api_request($endpoint, $request_data);
    }
    
    /**
     * Get secret sauce status
     * 
     * @return array|WP_Error Status information or error
     */
    public function get_status() {
        $endpoint = $this->api_endpoint . '/status';
        
        $request_data = array(
            'installation_id' => get_option('vortex_installation_id'),
            'timestamp' => time()
        );
        
        return $this->make_api_request($endpoint, $request_data, 'GET');
    }
    
    /**
     * Make API request to the secret sauce microservice
     * 
     * @param string $endpoint API endpoint URL
     * @param array $data Request data
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @return array|WP_Error API response or error
     */
    private function make_api_request($endpoint, $data = array(), $method = 'POST') {
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key,
            'X-Vortex-Client' => 'WordPress-Plugin',
            'X-Vortex-Version' => VORTEX_VERSION ?? '1.0.0',
            'User-Agent' => 'VORTEX-SecretSauce-Client/1.0.0'
        );
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
            'blocking' => true,
            'sslverify' => true
        );
        
        if ($method !== 'GET' && !empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        // Add query parameters for GET requests
        if ($method === 'GET' && !empty($data)) {
            $endpoint .= '?' . http_build_query($data);
        }
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            return new WP_Error(
                'api_request_failed',
                'Secret Sauce API request failed: ' . $response->get_error_message(),
                array('endpoint' => $endpoint)
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return new WP_Error(
                'api_response_error',
                'Secret Sauce API returned error: ' . $response_code,
                array(
                    'endpoint' => $endpoint,
                    'response_code' => $response_code,
                    'response_body' => $response_body
                )
            );
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'api_response_decode_error',
                'Failed to decode Secret Sauce API response',
                array('response_body' => $response_body)
            );
        }
        
        return $decoded_response;
    }
    
    /**
     * Check if secret sauce is available
     * 
     * @return bool True if available, false otherwise
     */
    public function is_available() {
        $status = $this->get_status();
        return !is_wp_error($status) && isset($status['status']) && $status['status'] === 'active';
    }
    
    /**
     * Get singleton instance
     * 
     * @return VORTEX_Secret_Sauce_Client
     */
    public static function get_instance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
}

/**
 * Initialize the Secret Sauce Client
 */
if (get_option('vortex_secret_sauce_enabled', false)) {
    VORTEX_Secret_Sauce_Client::get_instance();
}

/**
 * Helper function to get secret sauce client
 * 
 * @return VORTEX_Secret_Sauce_Client
 */
function vortex_get_secret_sauce_client() {
    return VORTEX_Secret_Sauce_Client::get_instance();
} 