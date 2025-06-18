<?php
/**
 * VORTEX Realtime Orchestrator
 *
 * Enhances the existing orchestration system with continuous real-time cross-agent communication
 * without modifying the original orchestrator.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Realtime_Orchestrator Class
 *
 * @since 1.0.0
 */
class VORTEX_Realtime_Orchestrator {
    /**
     * The single instance of this class
     *
     * @since 1.0.0
     * @access private
     * @var VORTEX_Realtime_Orchestrator
     */
    private static $instance = null;

    /**
     * Reference to the main orchestrator
     *
     * @since 1.0.0
     * @access private
     * @var VORTEX_Orchestrator
     */
    private $main_orchestrator;

    /**
     * Active agent contexts
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $agent_contexts = array();

    /**
     * WebSocket server instance
     *
     * @since 1.0.0
     * @access private
     * @var object
     */
    private $websocket_server = null;

    /**
     * Get instance - Singleton pattern
     *
     * @since 1.0.0
     * @return VORTEX_Realtime_Orchestrator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Get reference to the main orchestrator
        if (class_exists('VORTEX_Orchestrator')) {
            $this->main_orchestrator = VORTEX_Orchestrator::get_instance();
        }

        // Initialize agent contexts
        $this->init_agent_contexts();

        // Setup hooks
        $this->setup_hooks();
    }

    /**
     * Initialize agent contexts
     *
     * @since 1.0.0
     */
    private function init_agent_contexts() {
        $this->agent_contexts = array(
            'huraii' => array(
                'active' => false,
                'last_update' => 0,
                'context_data' => array(),
                'shared_insights' => array(),
            ),
            'cloe' => array(
                'active' => false,
                'last_update' => 0,
                'context_data' => array(),
                'shared_insights' => array(),
            ),
            'business_strategist' => array(
                'active' => false,
                'last_update' => 0,
                'context_data' => array(),
                'shared_insights' => array(),
            ),
            'thorius' => array(
                'active' => false,
                'last_update' => 0,
                'context_data' => array(),
                'shared_insights' => array(),
            ),
        );
    }

    /**
     * Setup hooks
     *
     * @since 1.0.0
     */
    private function setup_hooks() {
        // Agent interaction hooks
        add_action('vortex_agent_interaction', array($this, 'process_agent_interaction'), 10, 3);
        
        // Real-time insights sharing
        add_action('vortex_agent_insight_generated', array($this, 'share_insight_with_agents'), 10, 3);
        
        // WebSocket server initialization
        add_action('init', array($this, 'init_websocket_server'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_get_unified_context', array($this, 'ajax_get_unified_context'));
        add_action('wp_ajax_nopriv_vortex_get_unified_context', array($this, 'ajax_get_unified_context'));
        add_action('wp_ajax_vortex_update_agent_context', array($this, 'ajax_update_agent_context'));
        
        // Initialize on page load
        add_action('wp_loaded', array($this, 'initialize_realtime_orchestration'));
    }

    /**
     * Initialize WebSocket server
     *
     * @since 1.0.0
     */
    public function init_websocket_server() {
        // Only initialize if WebSocket server class exists
        if (class_exists('VORTEX_WebSocket_Server')) {
            $this->websocket_server = new VORTEX_WebSocket_Server('ai-orchestration');
            
            // Register message handlers
            $this->websocket_server->register_message_handler('agent_update', array($this, 'handle_agent_websocket_update'));
            $this->websocket_server->register_message_handler('request_context', array($this, 'handle_context_request'));
            
            // Start the server if not already running
            if (!$this->websocket_server->is_running()) {
                $this->websocket_server->start();
            }
        }
    }

    /**
     * Handle agent WebSocket update
     *
     * @since 1.0.0
     * @param array $data Update data
     */
    public function handle_agent_websocket_update($data) {
        if (empty($data['agent']) || empty($data['context'])) {
            return array(
                'success' => false,
                'message' => 'Invalid agent update data'
            );
        }
        
        $agent = sanitize_text_field($data['agent']);
        $context = $data['context'];
        
        // Update agent context
        $this->update_agent_context($agent, $context);
        
        // Broadcast update to all connected clients
        $this->websocket_server->broadcast('agent_updated', array(
            'agent' => $agent,
            'timestamp' => time()
        ));
        
        return array(
            'success' => true,
            'message' => 'Agent context updated'
        );
    }

    /**
     * Handle context request
     *
     * @since 1.0.0
     * @param array $data Request data
     * @return array Response with unified context
     */
    public function handle_context_request($data) {
        return array(
            'success' => true,
            'unified_context' => $this->get_unified_context()
        );
    }

    /**
     * Process agent interaction
     *
     * @since 1.0.0
     * @param string $agent Agent identifier
     * @param string $action Action performed
     * @param array $data Interaction data
     */
    public function process_agent_interaction($agent, $action, $data) {
        // Ensure agent exists in our contexts
        if (!isset($this->agent_contexts[$agent])) {
            return;
        }
        
        // Mark agent as active
        $this->agent_contexts[$agent]['active'] = true;
        $this->agent_contexts[$agent]['last_update'] = time();
        
        // Update context data with new information
        if (!empty($data)) {
            $this->agent_contexts[$agent]['context_data'][$action] = $data;
            
            // Trigger immediate sharing of this context with other agents
            $this->share_context_with_agents($agent, $action, $data);
        }
        
        // Log the interaction
        $this->log_realtime_interaction($agent, $action, $data);
    }

    /**
     * Share insight with other agents
     *
     * @since 1.0.0
     * @param string $agent Agent that generated the insight
     * @param string $insight_type Type of insight
     * @param array $insight_data Insight data
     */
    public function share_insight_with_agents($agent, $insight_type, $insight_data) {
        // Store the insight in the source agent's context
        if (isset($this->agent_contexts[$agent])) {
            $this->agent_contexts[$agent]['shared_insights'][] = array(
                'type' => $insight_type,
                'data' => $insight_data,
                'timestamp' => time()
            );
            
            // Limit the number of stored insights
            if (count($this->agent_contexts[$agent]['shared_insights']) > 20) {
                array_shift($this->agent_contexts[$agent]['shared_insights']);
            }
        }
        
        // Prepare insight for sharing
        $insight_for_sharing = array(
            'source_agent' => $agent,
            'insight_type' => $insight_type,
            'data' => $insight_data,
            'timestamp' => time()
        );
        
        // Share with each active agent
        foreach ($this->agent_contexts as $target_agent => $context) {
            if ($target_agent !== $agent && $context['active']) {
                $this->deliver_insight_to_agent($target_agent, $insight_for_sharing);
            }
        }
        
        // Broadcast via WebSocket if available
        if ($this->websocket_server) {
            $this->websocket_server->broadcast('new_insight', array(
                'source_agent' => $agent,
                'insight_type' => $insight_type,
                'timestamp' => time()
            ));
        }
    }

    /**
     * Deliver insight to a specific agent
     *
     * @since 1.0.0
     * @param string $agent Target agent
     * @param array $insight Insight data
     */
    private function deliver_insight_to_agent($agent, $insight) {
        switch ($agent) {
            case 'huraii':
                if (class_exists('VORTEX_HURAII')) {
                    $huraii = VORTEX_HURAII::get_instance();
                    if (method_exists($huraii, 'process_cross_agent_insight')) {
                        $huraii->process_cross_agent_insight($insight);
                    }
                }
                break;
                
            case 'cloe':
                if (class_exists('VORTEX_CLOE')) {
                    $cloe = VORTEX_CLOE::get_instance();
                    if (method_exists($cloe, 'process_cross_agent_insight')) {
                        $cloe->process_cross_agent_insight($insight);
                    }
                }
                break;
                
            case 'business_strategist':
                if (class_exists('VORTEX_Business_Strategist')) {
                    $bs = VORTEX_Business_Strategist::get_instance();
                    if (method_exists($bs, 'process_cross_agent_insight')) {
                        $bs->process_cross_agent_insight($insight);
                    }
                }
                break;
                
            case 'thorius':
                if (class_exists('VORTEX_Thorius')) {
                    $thorius = VORTEX_Thorius::get_instance();
                    if (method_exists($thorius, 'process_cross_agent_insight')) {
                        $thorius->process_cross_agent_insight($insight);
                    }
                }
                break;
        }
    }

    /**
     * Share context with other agents
     *
     * @since 1.0.0
     * @param string $agent Source agent
     * @param string $context_type Type of context
     * @param array $context_data Context data
     */
    private function share_context_with_agents($agent, $context_type, $context_data) {
        // Prepare context for sharing
        $context_for_sharing = array(
            'source_agent' => $agent,
            'context_type' => $context_type,
            'data' => $context_data,
            'timestamp' => time()
        );
        
        // Share with each active agent
        foreach ($this->agent_contexts as $target_agent => $context) {
            if ($target_agent !== $agent && $context['active']) {
                $this->deliver_context_to_agent($target_agent, $context_for_sharing);
            }
        }
    }

    /**
     * Deliver context to a specific agent
     *
     * @since 1.0.0
     * @param string $agent Target agent
     * @param array $context Context data
     */
    private function deliver_context_to_agent($agent, $context) {
        switch ($agent) {
            case 'huraii':
                if (class_exists('VORTEX_HURAII')) {
                    $huraii = VORTEX_HURAII::get_instance();
                    if (method_exists($huraii, 'update_external_context')) {
                        $huraii->update_external_context($context);
                    }
                }
                break;
                
            case 'cloe':
                if (class_exists('VORTEX_CLOE')) {
                    $cloe = VORTEX_CLOE::get_instance();
                    if (method_exists($cloe, 'update_external_context')) {
                        $cloe->update_external_context($context);
                    }
                }
                break;
                
            case 'business_strategist':
                if (class_exists('VORTEX_Business_Strategist')) {
                    $bs = VORTEX_Business_Strategist::get_instance();
                    if (method_exists($bs, 'update_external_context')) {
                        $bs->update_external_context($context);
                    }
                }
                break;
                
            case 'thorius':
                if (class_exists('VORTEX_Thorius')) {
                    $thorius = VORTEX_Thorius::get_instance();
                    if (method_exists($thorius, 'update_external_context')) {
                        $thorius->update_external_context($context);
                    }
                }
                break;
        }
    }

    /**
     * Update agent context
     *
     * @since 1.0.0
     * @param string $agent Agent identifier
     * @param array $context Context data
     */
    public function update_agent_context($agent, $context) {
        if (!isset($this->agent_contexts[$agent])) {
            return;
        }
        
        $this->agent_contexts[$agent]['active'] = true;
        $this->agent_contexts[$agent]['last_update'] = time();
        $this->agent_contexts[$agent]['context_data'] = array_merge(
            $this->agent_contexts[$agent]['context_data'],
            $context
        );
        
        // Trigger context sharing
        foreach ($context as $context_type => $context_data) {
            $this->share_context_with_agents($agent, $context_type, $context_data);
        }
    }

    /**
     * Get unified context from all agents
     *
     * @since 1.0.0
     * @return array Unified context
     */
    public function get_unified_context() {
        $unified_context = array(
            'timestamp' => time(),
            'agents' => array()
        );
        
        foreach ($this->agent_contexts as $agent => $context) {
            if ($context['active']) {
                $unified_context['agents'][$agent] = array(
                    'last_update' => $context['last_update'],
                    'context_data' => $context['context_data'],
                    'recent_insights' => array_slice($context['shared_insights'], -5)
                );
            }
        }
        
        return $unified_context;
    }

    /**
     * Log realtime interaction
     *
     * @since 1.0.0
     * @param string $agent Agent identifier
     * @param string $action Action performed
     * @param array $data Interaction data
     */
    private function log_realtime_interaction($agent, $action, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_realtime_interactions',
            array(
                'agent' => $agent,
                'action' => $action,
                'data' => is_array($data) ? json_encode($data) : $data,
                'created_at' => current_time('mysql')
            )
        );
    }

    /**
     * Initialize realtime orchestration
     *
     * @since 1.0.0
     */
    public function initialize_realtime_orchestration() {
        // Create necessary database tables
        $this->create_tables();
        
        // Initialize connections with existing orchestrator
        if ($this->main_orchestrator) {
            // Sync any recent insights from the main orchestrator
            $recent_insights = $this->get_recent_insights_from_main_orchestrator(20);
            
            if (!empty($recent_insights)) {
                foreach ($recent_insights as $insight) {
                    $this->share_insight_with_agents(
                        $insight->agent_name,
                        $insight->insight_type,
                        json_decode($insight->insight_data, true)
                    );
                }
            }
        }
    }

    /**
     * Get recent insights from main orchestrator
     *
     * @since 1.0.0
     * @param int $limit Number of insights to retrieve
     * @return array Recent insights
     */
    private function get_recent_insights_from_main_orchestrator($limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_agent_insights 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Create necessary database tables
     *
     * @since 1.0.0
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Realtime interactions table
        $table_name = $wpdb->prefix . 'vortex_realtime_interactions';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent varchar(50) NOT NULL,
            action varchar(50) NOT NULL,
            data longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY agent (agent),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Use dbDelta for database updates
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * AJAX handler for getting unified context
     *
     * @since 1.0.0
     */
    public function ajax_get_unified_context() {
        // Check nonce for security
        check_ajax_referer('vortex_nonce', 'nonce');
        
        wp_send_json_success(array(
            'unified_context' => $this->get_unified_context()
        ));
    }

    /**
     * AJAX handler for updating agent context
     *
     * @since 1.0.0
     */
    public function ajax_update_agent_context() {
        // Check nonce for security
        check_ajax_referer('vortex_nonce', 'nonce');
        
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
        $context = isset($_POST['context']) ? $_POST['context'] : array();
        
        if (empty($agent) || empty($context) || !is_array($context)) {
            wp_send_json_error(array('message' => 'Invalid agent or context data'));
            return;
        }
        
        $this->update_agent_context($agent, $context);
        
        wp_send_json_success(array(
            'message' => 'Agent context updated successfully',
            'unified_context' => $this->get_unified_context()
        ));
    }
}

// Initialize the Realtime Orchestrator on plugins loaded
function vortex_initialize_realtime_orchestrator() {
    return VORTEX_Realtime_Orchestrator::get_instance();
}
add_action('plugins_loaded', 'vortex_initialize_realtime_orchestrator', 15); // Load after the main orchestrator 