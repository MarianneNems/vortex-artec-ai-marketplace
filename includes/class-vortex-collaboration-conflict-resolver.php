<?php
/**
 * Collaboration Conflict Resolver
 *
 * Handles conflicts that arise during real-time collaborative editing
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Collaboration Conflict Resolver Class
 *
 * @since 1.1.0
 */
class VORTEX_Collaboration_Conflict_Resolver {
    /**
     * The single instance of this class
     */
    private static $instance = null;

    /**
     * Operation sequence numbers for each session
     */
    private $sequence_numbers = array();
    
    /**
     * Operation history for each session
     */
    private $operation_history = array();
    
    /**
     * Conflict resolution strategies
     */
    private $resolution_strategies = array();

    /**
     * Get instance - Singleton pattern
     *
     * @return VORTEX_Collaboration_Conflict_Resolver
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
        $this->init_resolution_strategies();
        
        // Setup hooks
        add_filter('vortex_collaboration_canvas_update_pre', array($this, 'check_for_conflicts'), 10, 3);
        add_action('vortex_collaboration_conflict_detected', array($this, 'resolve_conflict'), 10, 4);
        add_action('vortex_collaboration_session_created', array($this, 'initialize_session_tracking'), 10, 2);
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_resolve_collaboration_conflict', array($this, 'ajax_handle_conflict_resolution'));
    }

    /**
     * Initialize resolution strategies
     */
    private function init_resolution_strategies() {
        // Default strategies
        $this->resolution_strategies = array(
            'timestamp' => array($this, 'resolve_by_timestamp'),
            'priority' => array($this, 'resolve_by_user_priority'),
            'merge' => array($this, 'resolve_by_merging'),
            'consensus' => array($this, 'resolve_by_consensus'),
            'latest' => array($this, 'resolve_by_latest_operation')
        );
        
        // Allow extensions
        $this->resolution_strategies = apply_filters('vortex_collaboration_conflict_strategies', $this->resolution_strategies);
    }

    /**
     * Initialize session tracking
     *
     * @param string $session_id Session ID
     * @param array $session_data Session data
     */
    public function initialize_session_tracking($session_id, $session_data) {
        $this->sequence_numbers[$session_id] = 0;
        $this->operation_history[$session_id] = array();
    }

    /**
     * Check for conflicts in canvas updates
     *
     * @param array $update Update data
     * @param string $session_id Session ID
     * @param int $user_id User ID
     * @return array Possibly modified update data
     */
    public function check_for_conflicts($update, $session_id, $user_id) {
        // Skip checks for certain update types
        if (isset($update['type']) && in_array($update['type'], array('cursor_update', 'chat_message'))) {
            return $update;
        }
        
        // Ensure session is being tracked
        if (!isset($this->sequence_numbers[$session_id])) {
            $this->sequence_numbers[$session_id] = 0;
            $this->operation_history[$session_id] = array();
        }
        
        // Check for client-provided sequence number
        $client_sequence = isset($update['sequence']) ? intval($update['sequence']) : $this->sequence_numbers[$session_id];
        
        // If client sequence is behind server sequence, we have a conflict
        if ($client_sequence < $this->sequence_numbers[$session_id]) {
            $conflict_info = array(
                'server_sequence' => $this->sequence_numbers[$session_id],
                'client_sequence' => $client_sequence,
                'user_id' => $user_id,
                'timestamp' => isset($update['timestamp']) ? $update['timestamp'] : time(),
                'update' => $update
            );
            
            // Log the conflict
            $this->log_conflict($session_id, $conflict_info);
            
            // Try to resolve automatically
            $resolved_update = $this->attempt_auto_resolution($session_id, $conflict_info);
            
            if ($resolved_update) {
                return $resolved_update;
            } else {
                // Couldn't auto-resolve, trigger conflict resolution action
                do_action('vortex_collaboration_conflict_detected', $session_id, $user_id, $update, $conflict_info);
                
                // Return a conflict message instead of the update
                return array(
                    'type' => 'conflict',
                    'server_sequence' => $this->sequence_numbers[$session_id],
                    'client_sequence' => $client_sequence,
                    'requires_resolution' => true,
                    'message' => __('Edit conflict detected. Another user has modified this area.', 'vortex-ai-marketplace')
                );
            }
        }
        
        // No conflict, track this operation
        $this->sequence_numbers[$session_id]++;
        $this->track_operation($session_id, $user_id, $update);
        
        // Add sequence number to update
        $update['sequence'] = $this->sequence_numbers[$session_id];
        
        return $update;
    }

    /**
     * Track an operation in history
     *
     * @param string $session_id Session ID
     * @param int $user_id User ID
     * @param array $update Update data
     */
    private function track_operation($session_id, $user_id, $update) {
        // Store up to 50 recent operations per session
        if (!isset($this->operation_history[$session_id])) {
            $this->operation_history[$session_id] = array();
        }
        
        // Add operation to history
        $this->operation_history[$session_id][] = array(
            'sequence' => $this->sequence_numbers[$session_id],
            'user_id' => $user_id,
            'timestamp' => isset($update['timestamp']) ? $update['timestamp'] : time(),
            'update' => $update
        );
        
        // Limit history size
        if (count($this->operation_history[$session_id]) > 50) {
            array_shift($this->operation_history[$session_id]);
        }
    }

    /**
     * Log conflict for later analysis
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     */
    private function log_conflict($session_id, $conflict_info) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_collaboration_conflicts',
            array(
                'session_id' => $session_id,
                'user_id' => $conflict_info['user_id'],
                'client_sequence' => $conflict_info['client_sequence'],
                'server_sequence' => $conflict_info['server_sequence'],
                'conflict_data' => json_encode($conflict_info),
                'created_at' => current_time('mysql'),
                'resolved' => 0
            )
        );
    }

    /**
     * Attempt automatic conflict resolution
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @return array|false Resolved update or false if manual resolution needed
     */
    private function attempt_auto_resolution($session_id, $conflict_info) {
        // Try to determine resolution strategy
        $strategy = 'timestamp'; // Default strategy
        
        // Check for session-specific strategy
        $session_strategy = get_option('vortex_conflict_strategy_' . $session_id, false);
        if ($session_strategy && isset($this->resolution_strategies[$session_strategy])) {
            $strategy = $session_strategy;
        }
        
        // Try to resolve using the selected strategy
        if (isset($this->resolution_strategies[$strategy])) {
            $resolved_update = call_user_func($this->resolution_strategies[$strategy], $session_id, $conflict_info);
            
            if ($resolved_update) {
                // Mark conflict as resolved
                $this->mark_conflict_resolved($session_id, $conflict_info, $strategy);
                return $resolved_update;
            }
        }
        
        return false;
    }

    /**
     * Mark conflict as resolved
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @param string $strategy Strategy used
     */
    private function mark_conflict_resolved($session_id, $conflict_info, $strategy) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'vortex_collaboration_conflicts',
            array(
                'resolved' => 1,
                'resolution_strategy' => $strategy,
                'resolved_at' => current_time('mysql')
            ),
            array(
                'session_id' => $session_id,
                'user_id' => $conflict_info['user_id'],
                'client_sequence' => $conflict_info['client_sequence'],
                'server_sequence' => $conflict_info['server_sequence']
            )
        );
    }

    /**
     * Resolve conflict
     *
     * @param string $session_id Session ID
     * @param int $user_id User ID
     * @param array $update Update data
     * @param array $conflict_info Conflict information
     */
    public function resolve_conflict($session_id, $user_id, $update, $conflict_info) {
        // If we got here, we need to broadcast a conflict notification
        $this->broadcast_conflict_notification($session_id, $user_id, $conflict_info);
    }

    /**
     * Broadcast conflict notification to session participants
     *
     * @param string $session_id Session ID
     * @param int $user_id User ID
     * @param array $conflict_info Conflict information
     */
    private function broadcast_conflict_notification($session_id, $user_id, $conflict_info) {
        if (class_exists('VORTEX_WebSocket_Server')) {
            $websocket = new VORTEX_WebSocket_Server('collaboration');
            $websocket->broadcast('conflict_notification', array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'conflict_info' => $conflict_info,
                'resolution_options' => $this->get_conflict_resolution_options()
            ));
        }
    }

    /**
     * Get available conflict resolution options
     *
     * @return array Resolution options
     */
    private function get_conflict_resolution_options() {
        return array(
            'keep_yours' => __('Keep your changes', 'vortex-ai-marketplace'),
            'keep_theirs' => __('Use their changes', 'vortex-ai-marketplace'),
            'merge' => __('Attempt to merge changes', 'vortex-ai-marketplace'),
            'manual' => __('Resolve manually', 'vortex-ai-marketplace')
        );
    }

    /**
     * AJAX handler for conflict resolution
     */
    public function ajax_handle_conflict_resolution() {
        check_ajax_referer('vortex_collaboration_nonce', 'nonce');
        
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $choice = isset($_POST['resolution']) ? sanitize_text_field($_POST['resolution']) : '';
        $conflict_data = isset($_POST['conflict_data']) ? $_POST['conflict_data'] : array();
        
        if (empty($session_id) || empty($choice)) {
            wp_send_json_error(array('message' => __('Missing required data.', 'vortex-ai-marketplace')));
            return;
        }
        
        // Process resolution
        $resolved_update = $this->process_resolution_choice($session_id, $choice, $conflict_data);
        
        if ($resolved_update) {
            wp_send_json_success(array(
                'message' => __('Conflict resolved successfully.', 'vortex-ai-marketplace'),
                'update' => $resolved_update
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to resolve conflict.', 'vortex-ai-marketplace')));
        }
    }

    /**
     * Process resolution choice
     *
     * @param string $session_id Session ID
     * @param string $choice Resolution choice
     * @param array $conflict_data Conflict data
     * @return array|false Resolved update or false
     */
    private function process_resolution_choice($session_id, $choice, $conflict_data) {
        switch ($choice) {
            case 'keep_yours':
                // Reapply client changes with new sequence number
                $update = $conflict_data['update'];
                $update['sequence'] = $this->sequence_numbers[$session_id] + 1;
                $this->sequence_numbers[$session_id]++;
                return $update;
                
            case 'keep_theirs':
                // Simply acknowledge the server's version is accepted
                return array(
                    'type' => 'conflict_resolved',
                    'resolution' => 'keep_theirs',
                    'sequence' => $this->sequence_numbers[$session_id]
                );
                
            case 'merge':
                // Try to merge changes
                return $this->resolve_by_merging($session_id, $conflict_data);
                
            case 'manual':
                // Send entire canvas state for manual resolution
                return array(
                    'type' => 'manual_resolution',
                    'sequence' => $this->sequence_numbers[$session_id],
                    'server_state' => $this->get_current_canvas_state($session_id),
                    'conflict_data' => $conflict_data
                );
        }
        
        return false;
    }

    /**
     * Get current canvas state for a session
     *
     * @param string $session_id Session ID
     * @return array Canvas state
     */
    private function get_current_canvas_state($session_id) {
        global $wpdb;
        
        $session_data = $wpdb->get_var($wpdb->prepare(
            "SELECT data FROM {$wpdb->prefix}vortex_collaboration_sessions WHERE session_id = %s",
            $session_id
        ));
        
        if ($session_data) {
            $data = json_decode($session_data, true);
            return isset($data['canvas_state']) ? $data['canvas_state'] : array();
        }
        
        return array();
    }

    /**
     * Resolve conflict by timestamp
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @return array|false Resolved update or false
     */
    public function resolve_by_timestamp($session_id, $conflict_info) {
        // Find operations that occurred between client sequence and server sequence
        $conflicting_operations = array();
        
        foreach ($this->operation_history[$session_id] as $operation) {
            if ($operation['sequence'] > $conflict_info['client_sequence'] && 
                $operation['sequence'] <= $this->sequence_numbers[$session_id]) {
                $conflicting_operations[] = $operation;
            }
        }
        
        if (empty($conflicting_operations)) {
            // No actual conflict, just update the sequence
            $update = $conflict_info['update'];
            $update['sequence'] = $this->sequence_numbers[$session_id] + 1;
            $this->sequence_numbers[$session_id]++;
            return $update;
        }
        
        // Check timestamps to determine which operation to keep
        $client_timestamp = isset($conflict_info['timestamp']) ? $conflict_info['timestamp'] : 0;
        
        foreach ($conflicting_operations as $operation) {
            $server_timestamp = isset($operation['timestamp']) ? $operation['timestamp'] : 0;
            
            // If client operation is newer, use it
            if ($client_timestamp > $server_timestamp) {
                $update = $conflict_info['update'];
                $update['sequence'] = $this->sequence_numbers[$session_id] + 1;
                $this->sequence_numbers[$session_id]++;
                return $update;
            }
        }
        
        // Default to server's version (latest operations)
        return false;
    }

    /**
     * Resolve conflict by user priority
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @return array|false Resolved update or false
     */
    public function resolve_by_user_priority($session_id, $conflict_info) {
        // Get session data to check user roles
        global $wpdb;
        
        $session_data = $wpdb->get_var($wpdb->prepare(
            "SELECT data FROM {$wpdb->prefix}vortex_collaboration_sessions WHERE session_id = %s",
            $session_id
        ));
        
        if (!$session_data) {
            return false;
        }
        
        $session = json_decode($session_data, true);
        
        if (!isset($session['participants'][$conflict_info['user_id']])) {
            return false;
        }
        
        // Check client user role
        $client_role = $session['participants'][$conflict_info['user_id']]['role'];
        $client_priority = $this->get_role_priority($client_role);
        
        // Find conflicting operations and their user roles
        $conflicting_operations = array();
        
        foreach ($this->operation_history[$session_id] as $operation) {
            if ($operation['sequence'] > $conflict_info['client_sequence'] && 
                $operation['sequence'] <= $this->sequence_numbers[$session_id]) {
                $conflicting_operations[] = $operation;
            }
        }
        
        if (empty($conflicting_operations)) {
            // No actual conflict, just update the sequence
            $update = $conflict_info['update'];
            $update['sequence'] = $this->sequence_numbers[$session_id] + 1;
            $this->sequence_numbers[$session_id]++;
            return $update;
        }
        
        // Check if client has higher priority than all conflicting operations
        $highest_priority = true;
        
        foreach ($conflicting_operations as $operation) {
            if (!isset($session['participants'][$operation['user_id']])) {
                continue;
            }
            
            $operation_role = $session['participants'][$operation['user_id']]['role'];
            $operation_priority = $this->get_role_priority($operation_role);
            
            if ($operation_priority >= $client_priority) {
                $highest_priority = false;
                break;
            }
        }
        
        if ($highest_priority) {
            // Client has highest priority, use their update
            $update = $conflict_info['update'];
            $update['sequence'] = $this->sequence_numbers[$session_id] + 1;
            $this->sequence_numbers[$session_id]++;
            return $update;
        }
        
        // Default to server's version
        return false;
    }

    /**
     * Get role priority
     *
     * @param string $role User role
     * @return int Priority (higher is more important)
     */
    private function get_role_priority($role) {
        switch ($role) {
            case 'creator':
                return 100;
            case 'admin':
                return 90;
            case 'moderator':
                return 80;
            case 'editor':
                return 70;
            case 'participant':
                return 50;
            case 'viewer':
                return 10;
            default:
                return 0;
        }
    }

    /**
     * Resolve conflict by merging changes
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @return array|false Resolved update or false
     */
    public function resolve_by_merging($session_id, $conflict_info) {
        // This is a complex operation that depends on the update type
        $update_type = isset($conflict_info['update']['type']) ? $conflict_info['update']['type'] : '';
        
        switch ($update_type) {
            case 'drawing':
                // For drawing, we can often merge strokes
                return $this->merge_drawing_operations($session_id, $conflict_info);
                
            case 'layer_update':
                // For layer updates, try to merge if possible
                return $this->merge_layer_operations($session_id, $conflict_info);
                
            case 'text_update':
                // For text updates, try to use operational transforms
                return $this->merge_text_operations($session_id, $conflict_info);
                
            default:
                // For other types, we can't easily merge
                return false;
        }
    }

    /**
     * Merge drawing operations
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @return array|false Resolved update or false
     */
    private function merge_drawing_operations($session_id, $conflict_info) {
        // For drawing operations, we can often simply apply both
        // This is a simplified implementation - a real one would be more complex
        
        // Generate a merged operation
        $update = array(
            'type' => 'merged_drawing',
            'sequence' => $this->sequence_numbers[$session_id] + 1,
            'client_operation' => $conflict_info['update'],
            'merged' => true,
            'message' => __('Changes have been merged.', 'vortex-ai-marketplace')
        );
        
        $this->sequence_numbers[$session_id]++;
        return $update;
    }

    /**
     * Merge layer operations
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @return array|false Resolved update or false
     */
    private function merge_layer_operations($session_id, $conflict_info) {
        // Layer merging would require complex logic
        // This is a placeholder implementation
        return false;
    }

    /**
     * Merge text operations
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @return array|false Resolved update or false
     */
    private function merge_text_operations($session_id, $conflict_info) {
        // Text merging would require operational transform
        // This is a placeholder implementation
        return false;
    }

    /**
     * Resolve by consensus
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @return array|false Resolved update or false
     */
    public function resolve_by_consensus($session_id, $conflict_info) {
        // Consensus requires user interaction
        // This method would trigger a voting mechanism
        return false;
    }

    /**
     * Resolve by latest operation
     *
     * @param string $session_id Session ID
     * @param array $conflict_info Conflict information
     * @return array|false Resolved update or false
     */
    public function resolve_by_latest_operation($session_id, $conflict_info) {
        // Simply take the latest operation by sequence number
        return false; // Use server's version
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Conflicts table
        $table_name = $wpdb->prefix . 'vortex_collaboration_conflicts';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            client_sequence int(11) NOT NULL,
            server_sequence int(11) NOT NULL,
            conflict_data longtext NOT NULL,
            resolution_strategy varchar(50) DEFAULT NULL,
            resolved tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            resolved_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY resolved (resolved)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize on plugins loaded
function vortex_initialize_collaboration_conflict_resolver() {
    $resolver = VORTEX_Collaboration_Conflict_Resolver::get_instance();
    
    // Create database tables
    add_action('init', array($resolver, 'create_tables'));
    
    return $resolver;
}
add_action('plugins_loaded', 'vortex_initialize_collaboration_conflict_resolver', 20); 