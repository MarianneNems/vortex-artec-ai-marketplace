<?php
/**
 * Real-time Collaboration System
 *
 * Implements real-time collaboration features for artists
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Real-time Collaboration Class
 */
class VORTEX_Realtime_Collaboration {
    /**
     * The single instance of this class
     */
    private static $instance = null;

    /**
     * Active collaboration sessions
     */
    private $active_sessions = array();

    /**
     * WebSocket server instance
     */
    private $websocket_server = null;

    /**
     * Get instance - Singleton pattern
     *
     * @return VORTEX_Realtime_Collaboration
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
        // Initialize WebSocket server if class exists
        if (class_exists('VORTEX_WebSocket_Server')) {
            $this->websocket_server = new VORTEX_WebSocket_Server('collaboration', 8081);
        }

        // Setup hooks
        $this->setup_hooks();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // AJAX handlers
        add_action('wp_ajax_vortex_create_collaboration_session', array($this, 'ajax_create_session'));
        add_action('wp_ajax_vortex_join_collaboration_session', array($this, 'ajax_join_session'));
        add_action('wp_ajax_vortex_leave_collaboration_session', array($this, 'ajax_leave_session'));
        add_action('wp_ajax_vortex_update_collaboration_canvas', array($this, 'ajax_update_canvas'));
        add_action('wp_ajax_vortex_send_collaboration_message', array($this, 'ajax_send_message'));
        
        // WebSocket message handlers
        if ($this->websocket_server) {
            $this->websocket_server->register_message_handler('join_session', array($this, 'handle_join_session'));
            $this->websocket_server->register_message_handler('leave_session', array($this, 'handle_leave_session'));
            $this->websocket_server->register_message_handler('canvas_update', array($this, 'handle_canvas_update'));
            $this->websocket_server->register_message_handler('cursor_update', array($this, 'handle_cursor_update'));
            $this->websocket_server->register_message_handler('chat_message', array($this, 'handle_chat_message'));
        }
        
        // Register shortcodes
        add_shortcode('vortex_collaboration_canvas', array($this, 'render_collaboration_canvas'));
        
        // Enqueue necessary scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_register_style(
            'vortex-realtime-collaboration', 
            plugin_dir_url(VORTEX_PLUGIN_FILE) . 'css/vortex-realtime-collaboration.css',
            array(),
            VORTEX_VERSION
        );
        
        wp_register_script(
            'vortex-realtime-collaboration',
            plugin_dir_url(VORTEX_PLUGIN_FILE) . 'js/vortex-realtime-collaboration.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-realtime-collaboration', 'vortexCollaboration', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_collaboration_nonce'),
            'websocket_url' => 'ws://' . $_SERVER['HTTP_HOST'] . ':8081',
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name,
            'is_artist' => current_user_can('vortex_artist')
        ));
    }

    /**
     * Create a new collaboration session
     *
     * @param string $title Session title
     * @param string $description Session description
     * @param array $settings Session settings
     * @return array Session data
     */
    public function create_session($title, $description = '', $settings = array()) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return array(
                'success' => false,
                'message' => 'User not logged in'
            );
        }
        
        // Generate a unique session ID
        $session_id = 'collab_' . uniqid();
        
        // Default settings
        $default_settings = array(
            'max_participants' => 5,
            'canvas_width' => 1200,
            'canvas_height' => 800,
            'tools' => array('brush', 'eraser', 'text', 'shape'),
            'access' => 'invite_only' // invite_only, public
        );
        
        // Merge with user settings
        $settings = wp_parse_args($settings, $default_settings);
        
        // Create session data
        $session_data = array(
            'id' => $session_id,
            'title' => $title,
            'description' => $description,
            'creator_id' => $user_id,
            'creator_name' => wp_get_current_user()->display_name,
            'created_at' => current_time('mysql'),
            'settings' => $settings,
            'participants' => array(
                $user_id => array(
                    'id' => $user_id,
                    'name' => wp_get_current_user()->display_name,
                    'role' => 'creator',
                    'joined_at' => current_time('mysql'),
                    'cursor' => array('x' => 0, 'y' => 0),
                    'active' => true
                )
            ),
            'canvas_state' => array(
                'version' => 1,
                'layers' => array(
                    array(
                        'id' => 'background',
                        'name' => 'Background',
                        'visible' => true,
                        'locked' => false,
                        'data' => ''
                    )
                ),
                'active_layer' => 'background'
            ),
            'chat_history' => array(),
            'is_active' => true
        );
        
        // Store in active sessions
        $this->active_sessions[$session_id] = $session_data;
        
        // Save to database
        $this->save_session_to_db($session_id, $session_data);
        
        return array(
            'success' => true,
            'session' => $session_data
        );
    }

    /**
     * Join an existing collaboration session
     *
     * @param string $session_id Session ID
     * @return array Result with session data
     */
    public function join_session($session_id) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return array(
                'success' => false,
                'message' => 'User not logged in'
            );
        }
        
        // Check if session exists
        if (!isset($this->active_sessions[$session_id])) {
            // Try to load from database
            $session_data = $this->load_session_from_db($session_id);
            
            if (!$session_data) {
                return array(
                    'success' => false,
                    'message' => 'Session not found'
                );
            }
            
            $this->active_sessions[$session_id] = $session_data;
        }
        
        $session = &$this->active_sessions[$session_id];
        
        // Check if session is active
        if (!$session['is_active']) {
            return array(
                'success' => false,
                'message' => 'Session is no longer active'
            );
        }
        
        // Check if user is already a participant
        if (isset($session['participants'][$user_id])) {
            // Update active status
            $session['participants'][$user_id]['active'] = true;
        } else {
            // Check if session is full
            if (count($session['participants']) >= $session['settings']['max_participants']) {
                return array(
                    'success' => false,
                    'message' => 'Session is full'
                );
            }
            
            // Add user as a participant
            $session['participants'][$user_id] = array(
                'id' => $user_id,
                'name' => wp_get_current_user()->display_name,
                'role' => 'participant',
                'joined_at' => current_time('mysql'),
                'cursor' => array('x' => 0, 'y' => 0),
                'active' => true
            );
        }
        
        // Add system message about user joining
        $session['chat_history'][] = array(
            'type' => 'system',
            'message' => wp_get_current_user()->display_name . ' joined the session',
            'timestamp' => current_time('mysql')
        );
        
        // Save to database
        $this->save_session_to_db($session_id, $session);
        
        // Notify other participants via WebSocket
        if ($this->websocket_server) {
            $this->websocket_server->broadcast('participant_joined', array(
                'session_id' => $session_id,
                'participant' => $session['participants'][$user_id]
            ));
        }
        
        return array(
            'success' => true,
            'session' => $session
        );
    }

    /**
     * Handle WebSocket canvas update
     *
     * @param array $data Update data
     * @return array Result
     */
    public function handle_canvas_update($data) {
        if (empty($data['session_id']) || empty($data['user_id']) || empty($data['update'])) {
            return array(
                'success' => false,
                'message' => 'Missing required data'
            );
        }
        
        $session_id = $data['session_id'];
        $user_id = $data['user_id'];
        $update = $data['update'];
        
        // Validate session
        if (!isset($this->active_sessions[$session_id])) {
            return array(
                'success' => false,
                'message' => 'Session not found'
            );
        }
        
        // Validate user is a participant
        if (!isset($this->active_sessions[$session_id]['participants'][$user_id])) {
            return array(
                'success' => false,
                'message' => 'User is not a participant in this session'
            );
        }
        
        // Update the canvas state
        $this->apply_canvas_update($session_id, $update);
        
        // Broadcast update to all participants
        if ($this->websocket_server) {
            $this->websocket_server->broadcast('canvas_updated', array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'update' => $update
            ));
        }
        
        return array(
            'success' => true,
            'message' => 'Canvas updated'
        );
    }

    /**
     * Apply canvas update to session
     *
     * @param string $session_id Session ID
     * @param array $update Update data
     */
    private function apply_canvas_update($session_id, $update) {
        if (!isset($this->active_sessions[$session_id])) {
            return;
        }
        
        $session = &$this->active_sessions[$session_id];
        
        // Increment canvas version
        $session['canvas_state']['version']++;
        
        // Apply update based on type
        if (isset($update['type'])) {
            switch ($update['type']) {
                case 'layer_add':
                    if (isset($update['layer'])) {
                        $session['canvas_state']['layers'][] = $update['layer'];
                    }
                    break;
                
                case 'layer_update':
                    if (isset($update['layer_id']) && isset($update['data'])) {
                        foreach ($session['canvas_state']['layers'] as &$layer) {
                            if ($layer['id'] === $update['layer_id']) {
                                $layer['data'] = $update['data'];
                                break;
                            }
                        }
                    }
                    break;
                
                case 'layer_delete':
                    if (isset($update['layer_id'])) {
                        foreach ($session['canvas_state']['layers'] as $key => $layer) {
                            if ($layer['id'] === $update['layer_id']) {
                                unset($session['canvas_state']['layers'][$key]);
                                break;
                            }
                        }
                        $session['canvas_state']['layers'] = array_values($session['canvas_state']['layers']);
                    }
                    break;
                    
                case 'full_update':
                    if (isset($update['canvas_state'])) {
                        $session['canvas_state'] = $update['canvas_state'];
                    }
                    break;
            }
        }
        
        // Save to database periodically
        if ($session['canvas_state']['version'] % 10 === 0) {
            $this->save_session_to_db($session_id, $session);
        }
    }

    /**
     * Save session data to database
     *
     * @param string $session_id Session ID
     * @param array $session_data Session data
     */
    private function save_session_to_db($session_id, $session_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_collaboration_sessions';
        
        // Check if session already exists in database
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE session_id = %s",
            $session_id
        ));
        
        if ($existing) {
            // Update existing record
            $wpdb->update(
                $table_name,
                array(
                    'data' => json_encode($session_data),
                    'updated_at' => current_time('mysql')
                ),
                array('session_id' => $session_id)
            );
        } else {
            // Create new record
            $wpdb->insert(
                $table_name,
                array(
                    'session_id' => $session_id,
                    'creator_id' => $session_data['creator_id'],
                    'title' => $session_data['title'],
                    'data' => json_encode($session_data),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                    'is_active' => 1
                )
            );
        }
    }

    /**
     * Load session data from database
     *
     * @param string $session_id Session ID
     * @return array|false Session data or false if not found
     */
    private function load_session_from_db($session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_collaboration_sessions';
        
        $data = $wpdb->get_var($wpdb->prepare(
            "SELECT data FROM $table_name WHERE session_id = %s",
            $session_id
        ));
        
        if ($data) {
            return json_decode($data, true);
        }
        
        return false;
    }

    /**
     * Create necessary database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Collaboration sessions table
        $table_name = $wpdb->prefix . 'vortex_collaboration_sessions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(50) NOT NULL,
            creator_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            data longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY session_id (session_id),
            KEY creator_id (creator_id),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Render collaboration canvas shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function render_collaboration_canvas($atts) {
        $atts = shortcode_atts(array(
            'session_id' => '',
            'width' => '800',
            'height' => '600',
            'show_chat' => 'true'
        ), $atts, 'vortex_collaboration_canvas');
        
        // Enqueue required assets
        wp_enqueue_style('vortex-realtime-collaboration');
        wp_enqueue_script('vortex-realtime-collaboration');
        
        // Start output buffer
        ob_start();
        
        if (empty($atts['session_id'])) {
            // Show session creation/join form
            include(plugin_dir_path(VORTEX_PLUGIN_FILE) . 'templates/collaboration/session-form.php');
        } else {
            // Show collaboration canvas
            $session_id = $atts['session_id'];
            $session = $this->load_session_from_db($session_id);
            
            if ($session) {
                include(plugin_dir_path(VORTEX_PLUGIN_FILE) . 'templates/collaboration/canvas.php');
            } else {
                echo '<p class="vortex-error">' . __('Collaboration session not found.', 'vortex-ai-marketplace') . '</p>';
            }
        }
        
        return ob_get_clean();
    }

    /**
     * AJAX handler for creating a collaboration session
     */
    public function ajax_create_session() {
        check_ajax_referer('vortex_collaboration_nonce', 'nonce');
        
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Please provide a session title.', 'vortex-ai-marketplace')));
            return;
        }
        
        $result = $this->create_session($title, $description, $settings);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX handler for joining a collaboration session
     */
    public function ajax_join_session() {
        check_ajax_referer('vortex_collaboration_nonce', 'nonce');
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        if (empty($session_id)) {
            wp_send_json_error(array('message' => __('Session ID is required.', 'vortex-ai-marketplace')));
            return;
        }
        
        $result = $this->join_session($session_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX handler for leaving a collaboration session
     */
    public function ajax_leave_session() {
        check_ajax_referer('vortex_collaboration_nonce', 'nonce');
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        if (empty($session_id)) {
            wp_send_json_error(array('message' => __('Session ID is required.', 'vortex-ai-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        if (!isset($this->active_sessions[$session_id])) {
            // Try to load from database
            $session_data = $this->load_session_from_db($session_id);
            
            if ($session_data) {
                $this->active_sessions[$session_id] = $session_data;
            } else {
                wp_send_json_error(array('message' => __('Session not found.', 'vortex-ai-marketplace')));
                return;
            }
        }
        
        // Mark user as inactive
        if (isset($this->active_sessions[$session_id]['participants'][$user_id])) {
            $this->active_sessions[$session_id]['participants'][$user_id]['active'] = false;
            
            // Add system message
            $this->active_sessions[$session_id]['chat_history'][] = array(
                'type' => 'system',
                'message' => wp_get_current_user()->display_name . ' left the session',
                'timestamp' => current_time('mysql')
            );
            
            // Save to database
            $this->save_session_to_db($session_id, $this->active_sessions[$session_id]);
            
            // Notify other participants
            if ($this->websocket_server) {
                $this->websocket_server->broadcast('participant_left', array(
                    'session_id' => $session_id,
                    'user_id' => $user_id
                ));
            }
            
            wp_send_json_success(array('message' => __('Left session successfully.', 'vortex-ai-marketplace')));
        } else {
            wp_send_json_error(array('message' => __('You are not a participant in this session.', 'vortex-ai-marketplace')));
        }
    }

    /**
     * AJAX handler for updating canvas
     */
    public function ajax_update_canvas() {
        check_ajax_referer('vortex_collaboration_nonce', 'nonce');
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $update = isset($_POST['update']) ? $_POST['update'] : array();
        
        if (empty($session_id) || empty($update)) {
            wp_send_json_error(array('message' => __('Missing required data.', 'vortex-ai-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        $result = $this->handle_canvas_update(array(
            'session_id' => $session_id,
            'user_id' => $user_id,
            'update' => $update
        ));
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX handler for sending chat message
     */
    public function ajax_send_message() {
        check_ajax_referer('vortex_collaboration_nonce', 'nonce');
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (empty($session_id) || empty($message)) {
            wp_send_json_error(array('message' => __('Missing required data.', 'vortex-ai-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        if (!isset($this->active_sessions[$session_id])) {
            // Try to load from database
            $session_data = $this->load_session_from_db($session_id);
            
            if ($session_data) {
                $this->active_sessions[$session_id] = $session_data;
            } else {
                wp_send_json_error(array('message' => __('Session not found.', 'vortex-ai-marketplace')));
                return;
            }
        }
        
        // Check if user is a participant
        if (!isset($this->active_sessions[$session_id]['participants'][$user_id])) {
            wp_send_json_error(array('message' => __('You are not a participant in this session.', 'vortex-ai-marketplace')));
            return;
        }
        
        // Add message to chat history
        $chat_message = array(
            'type' => 'user',
            'user_id' => $user_id,
            'user_name' => $this->active_sessions[$session_id]['participants'][$user_id]['name'],
            'message' => $message,
            'timestamp' => current_time('mysql')
        );
        
        $this->active_sessions[$session_id]['chat_history'][] = $chat_message;
        
        // Save to database
        $this->save_session_to_db($session_id, $this->active_sessions[$session_id]);
        
        // Broadcast message
        if ($this->websocket_server) {
            $this->websocket_server->broadcast('chat_message', array(
                'session_id' => $session_id,
                'message' => $chat_message
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Message sent.', 'vortex-ai-marketplace'),
            'chat_message' => $chat_message
        ));
    }
}

// Initialize on plugins loaded
function vortex_initialize_realtime_collaboration() {
    $instance = VORTEX_Realtime_Collaboration::get_instance();
    
    // Create database tables
    add_action('init', array($instance, 'create_tables'));
    
    return $instance;
}
add_action('plugins_loaded', 'vortex_initialize_realtime_collaboration', 15); 