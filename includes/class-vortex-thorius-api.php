<?php
/**
 * THORIUS REST API
 *
 * Handles REST API endpoints for the THORIUS AI agent
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * THORIUS REST API Class
 */
class Vortex_Thorius_API {
    
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
    /**
     * THORIUS orchestrator instance
     */
    private $orchestrator = null;
    
    /**
     * API namespace
     */
    private $namespace = 'vortex/v1';
    
    /**
     * Get instance - Singleton pattern
     *
     * @return Vortex_Thorius_API
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
        // Load THORIUS orchestrator
        require_once plugin_dir_path(__FILE__) . 'agents/class-vortex-thorius-orchestrator.php';
        $this->orchestrator = new Vortex_Thorius_Orchestrator();
        
        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Process query endpoint
        register_rest_route($this->namespace, '/thorius/query', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_query'),
            'permission_callback' => array($this, 'check_query_permission'),
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'context' => array(
                    'required' => false,
                    'type' => 'object',
                    'default' => array(),
                ),
                'preferred_agent' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('huraii', 'cloe', 'strategist', 'thorius'),
                    'default' => 'thorius',
                ),
            ),
        ));
        
        // Process collaborative query endpoint
        register_rest_route($this->namespace, '/thorius/collaborative', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_collaborative_query'),
            'permission_callback' => array($this, 'check_query_permission'),
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'context' => array(
                    'required' => false,
                    'type' => 'object',
                    'default' => array(),
                ),
                'agents' => array(
                    'required' => false,
                    'type' => 'array',
                    'default' => array('huraii', 'cloe'),
                ),
            ),
        ));
        
        // Get agent status endpoint
        register_rest_route($this->namespace, '/thorius/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_agent_status'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
        
        // Process admin query endpoint
        register_rest_route($this->namespace, '/thorius/admin/query', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_admin_query'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'data_sources' => array(
                    'required' => false,
                    'type' => 'array',
                    'default' => array('analytics', 'marketplace', 'users'),
                ),
            ),
        ));
    }
    
    /**
     * Check permission for query endpoints
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function check_query_permission($request) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                __('You must be logged in to access this endpoint.', 'vortex-ai-marketplace'),
                array('status' => 401)
            );
        }
        
        // Apply rate limiting
        $user_id = get_current_user_id();
        $rate_limit = $this->check_rate_limit($user_id);
        
        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }
        
        return true;
    }
    
    /**
     * Check permission for admin endpoints
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function check_admin_permission($request) {
        // Check if user is admin
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('You must be an administrator to access this endpoint.', 'vortex-ai-marketplace'),
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Process query
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function process_query($request) {
        $query = $request->get_param('query');
        $context = $request->get_param('context');
        $preferred_agent = $request->get_param('preferred_agent');
        
        try {
            // Track query for rate limiting
            $this->track_query(get_current_user_id());
            
            // Process query through orchestrator
            $response = $this->orchestrator->process_query($query, $context, $preferred_agent);
            
            // Return response
            return new WP_REST_Response($response, 200);
        } catch (Exception $e) {
            return new WP_Error(
                'thorius_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Process collaborative query
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function process_collaborative_query($request) {
        $query = $request->get_param('query');
        $context = $request->get_param('context');
        $agents = $request->get_param('agents');
        
        try {
            // Track query for rate limiting
            $this->track_query(get_current_user_id());
            
            // Process collaborative query through orchestrator
            $response = $this->orchestrator->process_collaborative_query($query, $context, $agents);
            
            // Return response
            return new WP_REST_Response($response, 200);
        } catch (Exception $e) {
            return new WP_Error(
                'thorius_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Get agent status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_agent_status($request) {
        // Get agent status
        $status = array(
            'agents' => array(
                'huraii' => array(
                    'status' => 'active',
                    'version' => '1.0.0',
                    'capabilities' => array('image_generation', 'style_transfer'),
                ),
                'cloe' => array(
                    'status' => 'active',
                    'version' => '1.0.0',
                    'capabilities' => array('curation', 'personalization'),
                ),
                'strategist' => array(
                    'status' => 'active',
                    'version' => '1.0.0',
                    'capabilities' => array('market_analysis', 'growth_strategy'),
                ),
                'thorius' => array(
                    'status' => 'active',
                    'version' => '1.0.0',
                    'capabilities' => array('orchestration', 'security'),
                ),
            ),
            'system' => array(
                'status' => 'operational',
                'last_updated' => current_time('mysql'),
                'api_version' => '1.0.0',
            ),
        );
        
        return new WP_REST_Response($status, 200);
    }
    
    /**
     * Process admin query
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function process_admin_query($request) {
        $query = $request->get_param('query');
        $data_sources = $request->get_param('data_sources');
        
        try {
            // Process admin query through orchestrator
            $response = $this->orchestrator->process_admin_query($query, $data_sources);
            
            // Return response
            return new WP_REST_Response($response, 200);
        } catch (Exception $e) {
            return new WP_Error(
                'thorius_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Check rate limit
     *
     * @param int $user_id User ID
     * @return true|WP_Error True if within limit, WP_Error otherwise
     */
    private function check_rate_limit($user_id) {
        // Get rate limit settings
        $rate_limit = get_option('vortex_thorius_rate_limit', 100);
        $rate_window = get_option('vortex_thorius_rate_window', 3600); // 1 hour
        
        // Get user's query count
        $query_count = get_user_meta($user_id, 'vortex_thorius_query_count', true);
        $query_timestamp = get_user_meta($user_id, 'vortex_thorius_query_timestamp', true);
        
        if (!$query_count) {
            $query_count = 0;
        }
        
        if (!$query_timestamp) {
            $query_timestamp = time();
        }
        
        // Check if rate window has reset
        if (time() - $query_timestamp > $rate_window) {
            // Reset query count
            update_user_meta($user_id, 'vortex_thorius_query_count', 0);
            update_user_meta($user_id, 'vortex_thorius_query_timestamp', time());
            return true;
        }
        
        // Check if user has exceeded rate limit
        if ($query_count >= $rate_limit) {
            $reset_time = $query_timestamp + $rate_window - time();
            $reset_minutes = ceil($reset_time / 60);
            
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    __('Rate limit exceeded. Please try again in %d minutes.', 'vortex-ai-marketplace'),
                    $reset_minutes
                ),
                array('status' => 429)
            );
        }
        
        return true;
    }
    
    /**
     * Track query for rate limiting
     *
     * @param int $user_id User ID
     */
    private function track_query($user_id) {
        // Get current count
        $query_count = get_user_meta($user_id, 'vortex_thorius_query_count', true);
        
        if (!$query_count) {
            $query_count = 0;
        }
        
        // Increment count
        $query_count++;
        
        // Update user meta
        update_user_meta($user_id, 'vortex_thorius_query_count', $query_count);
        
        // Set timestamp if not set
        if (!get_user_meta($user_id, 'vortex_thorius_query_timestamp', true)) {
            update_user_meta($user_id, 'vortex_thorius_query_timestamp', time());
        }
    }
}

// Initialize THORIUS API
function vortex_thorius_api() {
    return Vortex_Thorius_API::get_instance();
}

// Initialize on plugins loaded
add_action('plugins_loaded', 'vortex_thorius_api'); 