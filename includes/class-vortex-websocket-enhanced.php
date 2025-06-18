<?php
/**
 * Enhanced WebSocket Server Implementation
 *
 * Improves the existing WebSocket server with better reliability, security, and scalability
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced WebSocket Server Class
 *
 * @since 1.1.0
 */
class VORTEX_WebSocket_Enhanced extends VORTEX_WebSocket_Server {
    /**
     * Connection states for clients
     */
    private $connection_states = array();
    
    /**
     * Authentication tokens
     */
    private $auth_tokens = array();
    
    /**
     * Connection retry settings
     */
    private $retry_settings = array(
        'max_retries' => 5,
        'retry_interval' => 3000, // ms
        'backoff_factor' => 1.5
    );
    
    /**
     * Server nodes for clustering
     */
    private $server_nodes = array();
    
    /**
     * Constructor
     *
     * @param string $channel Channel name
     * @param int $port Server port
     * @param string $host Server host
     */
    public function __construct($channel = 'default', $port = 8080, $host = '0.0.0.0') {
        parent::__construct($channel, $port, $host);
        
        // Initialize new properties
        $this->initialize_enhanced_features();
    }
    
    /**
     * Initialize enhanced features
     */
    private function initialize_enhanced_features() {
        // Set up connection states tracking
        add_action('wp_ajax_vortex_websocket_heartbeat', array($this, 'handle_heartbeat'));
        add_action('wp_ajax_nopriv_vortex_websocket_heartbeat', array($this, 'handle_heartbeat'));
        
        // Set up authentication
        add_action('wp_ajax_vortex_websocket_authenticate', array($this, 'generate_auth_token'));
        
        // Set up clustering if enabled
        if (defined('VORTEX_WEBSOCKET_CLUSTERING') && VORTEX_WEBSOCKET_CLUSTERING) {
            $this->initialize_clustering();
        }
        
        // Enqueue client script with enhanced features
        add_action('wp_enqueue_scripts', array($this, 'enqueue_enhanced_client_script'));
    }
    
    /**
     * Enqueue enhanced client script
     */
    public function enqueue_enhanced_client_script() {
        wp_register_script(
            'vortex-websocket-enhanced-client',
            plugin_dir_url(VORTEX_PLUGIN_FILE) . 'js/vortex-websocket-enhanced-client.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-websocket-enhanced-client', 'vortexWebSocketSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_websocket_nonce'),
            'websocket_url' => 'ws://' . $_SERVER['HTTP_HOST'] . ':' . $this->port,
            'channel' => $this->channel,
            'retry_settings' => $this->retry_settings,
            'heartbeat_interval' => 30000, // 30 seconds
        ));
    }
    
    /**
     * Start WebSocket server with enhanced features
     *
     * @return bool Success
     */
    public function start() {
        $result = parent::start();
        
        if ($result) {
            // Set up periodic cleanup of stale connections
            if (!wp_next_scheduled('vortex_websocket_cleanup')) {
                wp_schedule_event(time(), 'hourly', 'vortex_websocket_cleanup');
            }
            
            // Register cleanup callback
            add_action('vortex_websocket_cleanup', array($this, 'cleanup_stale_connections'));
        }
        
        return $result;
    }
    
    /**
     * Handle client connection with authentication
     *
     * @param resource $client Client socket
     * @param array $headers Connection headers
     * @return bool Success
     */
    public function handle_connect($client, $headers) {
        // Verify authentication token in headers
        $is_authenticated = false;
        
        if (isset($headers['Sec-WebSocket-Protocol'])) {
            $protocols = explode(', ', $headers['Sec-WebSocket-Protocol']);
            foreach ($protocols as $protocol) {
                if (strpos($protocol, 'auth-token-') === 0) {
                    $token = substr($protocol, 11);
                    if ($this->verify_auth_token($token)) {
                        $is_authenticated = true;
                        break;
                    }
                }
            }
        }
        
        if (!$is_authenticated) {
            // Send authentication error and close
            $this->send_close_frame($client, 4401, 'Unauthorized');
            return false;
        }
        
        // Initialize connection state
        $client_id = $this->get_client_id($client);
        $this->connection_states[$client_id] = array(
            'connected_at' => time(),
            'last_activity' => time(),
            'reconnect_count' => 0,
            'ip_address' => isset($headers['X-Forwarded-For']) ? $headers['X-Forwarded-For'] : $_SERVER['REMOTE_ADDR'],
            'user_agent' => isset($headers['User-Agent']) ? $headers['User-Agent'] : '',
        );
        
        return parent::handle_connect($client, $headers);
    }
    
    /**
     * Handle heartbeat request
     */
    public function handle_heartbeat() {
        check_ajax_referer('vortex_websocket_nonce', 'nonce');
        
        $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
        
        if (!empty($client_id) && isset($this->connection_states[$client_id])) {
            $this->connection_states[$client_id]['last_activity'] = time();
            wp_send_json_success(array('status' => 'connected'));
        } else {
            wp_send_json_error(array('status' => 'disconnected'));
        }
    }
    
    /**
     * Generate authentication token
     */
    public function generate_auth_token() {
        check_ajax_referer('vortex_websocket_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User not authenticated'));
            return;
        }
        
        $user_id = get_current_user_id();
        $token = wp_generate_password(32, false);
        $expiry = time() + 3600; // 1 hour
        
        $this->auth_tokens[$token] = array(
            'user_id' => $user_id,
            'created_at' => time(),
            'expires_at' => $expiry,
        );
        
        wp_send_json_success(array(
            'token' => $token,
            'expires_at' => $expiry,
        ));
    }
    
    /**
     * Verify authentication token
     *
     * @param string $token Auth token
     * @return bool Is valid
     */
    private function verify_auth_token($token) {
        if (empty($token) || !isset($this->auth_tokens[$token])) {
            return false;
        }
        
        $auth_data = $this->auth_tokens[$token];
        
        // Check if token has expired
        if (time() > $auth_data['expires_at']) {
            unset($this->auth_tokens[$token]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize clustering support
     */
    private function initialize_clustering() {
        // Load server nodes from configuration
        $this->server_nodes = apply_filters('vortex_websocket_cluster_nodes', array(
            array('host' => $this->host, 'port' => $this->port)
        ));
        
        // Set up node discovery if enabled
        if (defined('VORTEX_WEBSOCKET_AUTO_DISCOVERY') && VORTEX_WEBSOCKET_AUTO_DISCOVERY) {
            add_action('init', array($this, 'discover_server_nodes'));
        }
        
        // Register node health check
        add_action('wp_ajax_nopriv_vortex_websocket_node_health', array($this, 'handle_node_health_check'));
    }
    
    /**
     * Discover other server nodes
     */
    public function discover_server_nodes() {
        // Implementation would depend on deployment environment
        // This is a placeholder for auto-discovery logic
        $discovered_nodes = apply_filters('vortex_websocket_discovered_nodes', array());
        
        if (!empty($discovered_nodes)) {
            $this->server_nodes = array_merge($this->server_nodes, $discovered_nodes);
            $this->server_nodes = array_unique($this->server_nodes, SORT_REGULAR);
        }
    }
    
    /**
     * Handle node health check
     */
    public function handle_node_health_check() {
        wp_send_json_success(array(
            'status' => $this->is_running() ? 'running' : 'stopped',
            'channel' => $this->channel,
            'clients' => count($this->clients),
            'uptime' => time() - $this->server_pid_time,
        ));
    }
    
    /**
     * Broadcast message to all clients across cluster
     *
     * @param string $message_type Message type
     * @param array $payload Message payload
     * @return bool Success
     */
    public function broadcast($message_type, $payload = array()) {
        $result = parent::broadcast($message_type, $payload);
        
        // If clustering is enabled, broadcast to other nodes
        if (!empty($this->server_nodes) && count($this->server_nodes) > 1) {
            $this->broadcast_to_cluster($message_type, $payload);
        }
        
        return $result;
    }
    
    /**
     * Broadcast message to all nodes in the cluster
     *
     * @param string $message_type Message type
     * @param array $payload Message payload
     */
    private function broadcast_to_cluster($message_type, $payload) {
        $data = json_encode(array(
            'type' => $message_type,
            'payload' => $payload,
            'timestamp' => time(),
            'source_node' => array('host' => $this->host, 'port' => $this->port),
        ));
        
        foreach ($this->server_nodes as $node) {
            // Skip self
            if ($node['host'] === $this->host && $node['port'] === $this->port) {
                continue;
            }
            
            // Send to other node
            $this->send_to_node($node, $data);
        }
    }
    
    /**
     * Send data to another node
     *
     * @param array $node Node info
     * @param string $data Data to send
     * @return bool Success
     */
    private function send_to_node($node, $data) {
        // In a real implementation, this would use cURL or socket connections
        // For this improvement, we're simulating with a REST API call
        
        if (function_exists('wp_remote_post')) {
            $node_url = 'http://' . $node['host'] . ':' . $node['port'] . '/cluster-message';
            
            $response = wp_remote_post($node_url, array(
                'method' => 'POST',
                'timeout' => 5,
                'headers' => array('Content-Type' => 'application/json'),
                'body' => $data,
            ));
            
            return !is_wp_error($response) && 
                   wp_remote_retrieve_response_code($response) === 200;
        }
        
        return false;
    }
    
    /**
     * Cleanup stale connections
     */
    public function cleanup_stale_connections() {
        $now = time();
        $timeout = 300; // 5 minutes of inactivity
        
        foreach ($this->connection_states as $client_id => $state) {
            if ($now - $state['last_activity'] > $timeout) {
                // Close connection if client still exists
                if (isset($this->clients[$client_id])) {
                    $this->send_close_frame($this->clients[$client_id], 4000, 'Connection timeout');
                }
                
                // Remove from tracking
                unset($this->connection_states[$client_id]);
            }
        }
        
        // Also cleanup expired auth tokens
        foreach ($this->auth_tokens as $token => $data) {
            if ($now > $data['expires_at']) {
                unset($this->auth_tokens[$token]);
            }
        }
    }
    
    /**
     * Send close frame to client
     *
     * @param resource $client Client socket
     * @param int $code Close code
     * @param string $reason Close reason
     */
    private function send_close_frame($client, $code, $reason) {
        // In a real implementation, this would construct and send a WebSocket close frame
        // For this improvement, we're simulating with a log entry
        $this->log('Sending close frame to client: ' . $code . ' - ' . $reason);
    }
    
    /**
     * Get client ID from socket
     *
     * @param resource $client Client socket
     * @return string Client ID
     */
    private function get_client_id($client) {
        // In a real implementation, this would get a unique identifier for the socket
        // For this improvement, we're using a resource ID or hash
        return spl_object_hash($client);
    }
    
    /**
     * Stop WebSocket server with enhanced cleanup
     *
     * @return bool Success
     */
    public function stop() {
        // Notify all clients before closing
        $this->broadcast('server_shutdown', array(
            'message' => 'Server shutting down',
            'reconnect_delay' => 10000, // 10 seconds
        ));
        
        // Clear scheduled events
        wp_clear_scheduled_hook('vortex_websocket_cleanup');
        
        return parent::stop();
    }
}

/**
 * Initialize the Enhanced WebSocket Server
 *
 * @param string $channel Channel name
 * @param int $port Server port
 * @param string $host Server host
 * @return VORTEX_WebSocket_Enhanced
 */
function vortex_websocket_enhanced_init($channel = 'default', $port = 8080, $host = '0.0.0.0') {
    return new VORTEX_WebSocket_Enhanced($channel, $port, $host);
} 