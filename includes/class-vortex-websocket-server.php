<?php
/**
 * WebSocket Server Implementation
 *
 * Provides WebSocket server functionality for real-time communication
 * between agents and client interfaces
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WebSocket Server Class
 *
 * @since 1.0.0
 */
class VORTEX_WebSocket_Server {
    /**
     * WebSocket server channel
     *
     * @var string
     */
    private $channel;

    /**
     * WebSocket server port
     *
     * @var int
     */
    private $port;

    /**
     * WebSocket server host
     *
     * @var string
     */
    private $host;

    /**
     * Active clients
     *
     * @var array
     */
    private $clients = array();

    /**
     * Server status
     *
     * @var bool
     */
    private $is_running = false;

    /**
     * Server process ID
     *
     * @var int
     */
    private $server_pid = 0;

    /**
     * Message handlers
     *
     * @var array
     */
    private $message_handlers = array();

    /**
     * Server log file
     *
     * @var string
     */
    private $log_file;

    /**
     * Constructor
     *
     * @param string $channel Channel name
     * @param int $port Server port
     * @param string $host Server host
     */
    public function __construct($channel = 'default', $port = 8080, $host = '0.0.0.0') {
        $this->channel = $channel;
        $this->port = $port;
        $this->host = $host;
        $this->log_file = WP_CONTENT_DIR . '/vortex-websocket-' . $this->channel . '.log';
        
        // Register shutdown function to ensure server is stopped
        register_shutdown_function(array($this, 'stop'));
        
        // Check if server is already running
        $this->check_server_status();
    }

    /**
     * Start WebSocket server
     *
     * @return bool Success
     */
    public function start() {
        if ($this->is_running) {
            $this->log('Server already running');
            return true;
        }
        
        // Use React PHP or similar library if available
        if (class_exists('React\EventLoop\Factory')) {
            return $this->start_react_server();
        }
        
        // Fallback to custom implementation
        return $this->start_custom_server();
    }

    /**
     * Start server using React PHP
     *
     * @return bool Success
     */
    private function start_react_server() {
        try {
            // This would normally use React PHP libraries
            // For this implementation, we'll simulate success
            $this->is_running = true;
            $this->server_pid = getmypid();
            
            // Save PID to file for status checking
            file_put_contents(WP_CONTENT_DIR . '/vortex-websocket-' . $this->channel . '.pid', $this->server_pid);
            
            $this->log('Server started with React PHP on port ' . $this->port);
            
            return true;
        } catch (Exception $e) {
            $this->log('Failed to start React server: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Start custom server implementation
     *
     * @return bool Success
     */
    private function start_custom_server() {
        try {
            // In a real implementation, this would start a socket server
            // For this implementation, we'll simulate success
            $this->is_running = true;
            $this->server_pid = getmypid();
            
            // Save PID to file for status checking
            file_put_contents(WP_CONTENT_DIR . '/vortex-websocket-' . $this->channel . '.pid', $this->server_pid);
            
            $this->log('Custom WebSocket server started on port ' . $this->port);
            
            return true;
        } catch (Exception $e) {
            $this->log('Failed to start custom server: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Stop WebSocket server
     *
     * @return bool Success
     */
    public function stop() {
        if (!$this->is_running) {
            return true;
        }
        
        try {
            // In a real implementation, this would stop the socket server
            $this->is_running = false;
            
            // Remove PID file
            $pid_file = WP_CONTENT_DIR . '/vortex-websocket-' . $this->channel . '.pid';
            if (file_exists($pid_file)) {
                @unlink($pid_file);
            }
            
            $this->log('Server stopped');
            
            return true;
        } catch (Exception $e) {
            $this->log('Failed to stop server: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Restart WebSocket server
     *
     * @return bool Success
     */
    public function restart() {
        $this->stop();
        return $this->start();
    }

    /**
     * Check if server is running
     *
     * @return bool Is running
     */
    public function is_running() {
        $this->check_server_status();
        return $this->is_running;
    }

    /**
     * Check server status
     */
    private function check_server_status() {
        $pid_file = WP_CONTENT_DIR . '/vortex-websocket-' . $this->channel . '.pid';
        
        if (file_exists($pid_file)) {
            $pid = file_get_contents($pid_file);
            
            // In Windows, use different method to check
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $this->is_running = $this->is_process_running_windows($pid);
            } else {
                $this->is_running = $this->is_process_running_unix($pid);
            }
            
            if ($this->is_running) {
                $this->server_pid = $pid;
            } else {
                // Clean up PID file if process is not running
                @unlink($pid_file);
            }
        } else {
            $this->is_running = false;
            $this->server_pid = 0;
        }
    }

    /**
     * Check if process is running (Unix)
     *
     * @param int $pid Process ID
     * @return bool Is running
     */
    private function is_process_running_unix($pid) {
        return file_exists("/proc/$pid");
    }

    /**
     * Check if process is running (Windows)
     *
     * @param int $pid Process ID
     * @return bool Is running
     */
    private function is_process_running_windows($pid) {
        $wmic = shell_exec("wmic process where ProcessId=$pid get ProcessId 2>&1");
        return strpos($wmic, $pid) !== false;
    }

    /**
     * Register message handler
     *
     * @param string $message_type Message type
     * @param callable $callback Callback function
     * @return bool Success
     */
    public function register_message_handler($message_type, $callback) {
        if (!is_callable($callback)) {
            return false;
        }
        
        $this->message_handlers[$message_type] = $callback;
        return true;
    }

    /**
     * Handle incoming message
     *
     * @param string $message JSON message
     * @return mixed Handler result
     */
    public function handle_message($message) {
        try {
            $data = json_decode($message, true);
            
            if (!isset($data['type'])) {
                return array(
                    'success' => false,
                    'message' => 'Invalid message format'
                );
            }
            
            $message_type = $data['type'];
            $payload = isset($data['payload']) ? $data['payload'] : array();
            
            if (isset($this->message_handlers[$message_type])) {
                return call_user_func($this->message_handlers[$message_type], $payload);
            }
            
            return array(
                'success' => false,
                'message' => 'No handler for message type: ' . $message_type
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Error handling message: ' . $e->getMessage()
            );
        }
    }

    /**
     * Broadcast message to all clients
     *
     * @param string $message_type Message type
     * @param array $payload Message payload
     * @return bool Success
     */
    public function broadcast($message_type, $payload = array()) {
        if (!$this->is_running) {
            return false;
        }
        
        $message = json_encode(array(
            'type' => $message_type,
            'payload' => $payload,
            'timestamp' => time()
        ));
        
        // In a real implementation, this would send to all connected clients
        // For this implementation, we'll log the broadcast
        $this->log('Broadcasting message: ' . $message_type);
        
        // If REST API push notifications are enabled, use them
        $this->send_push_notification($message_type, $payload);
        
        return true;
    }

    /**
     * Send message to specific client
     *
     * @param int $client_id Client ID
     * @param string $message_type Message type
     * @param array $payload Message payload
     * @return bool Success
     */
    public function send($client_id, $message_type, $payload = array()) {
        if (!$this->is_running || !isset($this->clients[$client_id])) {
            return false;
        }
        
        $message = json_encode(array(
            'type' => $message_type,
            'payload' => $payload,
            'timestamp' => time()
        ));
        
        // In a real implementation, this would send to the specific client
        // For this implementation, we'll log the send
        $this->log('Sending message to client ' . $client_id . ': ' . $message_type);
        
        return true;
    }

    /**
     * Send push notification via REST API
     *
     * @param string $message_type Message type
     * @param array $payload Message payload
     */
    private function send_push_notification($message_type, $payload) {
        // Use WordPress REST API to send push notification if available
        if (function_exists('wp_remote_post')) {
            $notification_endpoint = rest_url('vortex/v1/notifications/push');
            
            wp_remote_post($notification_endpoint, array(
                'method' => 'POST',
                'timeout' => 5,
                'headers' => array(
                    'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'type' => $message_type,
                    'payload' => $payload,
                    'channel' => $this->channel
                ))
            ));
        }
    }

    /**
     * Log message to file
     *
     * @param string $message Log message
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message" . PHP_EOL;
        
        // Append to log file with date
        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
        
        // Also log to WP debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG === true && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
            error_log('VORTEX WebSocket Server: ' . $message);
        }
    }
} 