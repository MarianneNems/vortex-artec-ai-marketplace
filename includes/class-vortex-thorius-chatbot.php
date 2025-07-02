<?php
/**
 * THORIUS Chatbot Interface
 * 
 * Front-end interface for THORIUS core (vault-locked)
 * Concierge • Guide • Supervisor • Security
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Thorius_Chatbot {
    
    private static $instance = null;
    private $vault_orchestrator;
    
    /**
     * Get instance
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
        $this->vault_orchestrator = VORTEX_RunPod_Vault_Orchestrator::get_instance();
        
        // Initialize chatbot interface
        add_action('wp_enqueue_scripts', array($this, 'enqueue_chatbot_assets'));
        add_action('wp_ajax_thorius_chat', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_thorius_chat', array($this, 'handle_chat_request'));
        add_action('wp_footer', array($this, 'render_chatbot_interface'));
        
        // Platform supervision hooks
        add_action('user_register', array($this, 'thorius_new_user_supervision'));
        add_action('wp_login', array($this, 'thorius_login_supervision'));
        add_filter('pre_comment_approved', array($this, 'thorius_comment_supervision'), 10, 2);
    }
    
    /**
     * Enqueue chatbot assets
     */
    public function enqueue_chatbot_assets() {
        wp_enqueue_script(
            'thorius-chatbot',
            plugin_dir_url(__FILE__) . '../assets/js/thorius-chatbot.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'thorius-chatbot',
            plugin_dir_url(__FILE__) . '../assets/css/thorius-chatbot.css',
            array(),
            '1.0.0'
        );
        
        wp_localize_script('thorius-chatbot', 'thorius_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('thorius_chat_nonce')
        ));
    }
    
    /**
     * Handle chat request (communicates with vault-locked THORIUS)
     */
    public function handle_chat_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'thorius_chat_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_message = sanitize_text_field($_POST['message']);
        $user_id = get_current_user_id();
        $context = array(
            'current_page' => sanitize_text_field($_POST['current_page'] ?? ''),
            'user_role' => wp_get_current_user()->roles[0] ?? 'visitor',
            'session_duration' => intval($_POST['session_duration'] ?? 0)
        );
        
        // Get response from vault-locked THORIUS
        $response = $this->vault_orchestrator->get_thorius_chat_response($user_message, $user_id, $context);
        
        // Log interaction for learning
        $this->log_chat_interaction($user_id, $user_message, $response);
        
        wp_send_json_success($response);
    }
    
    /**
     * Render chatbot interface
     */
    public function render_chatbot_interface() {
        ?>
        <div id="thorius-chatbot-container" class="thorius-hidden">
            <div id="thorius-chatbot-header">
                <div class="thorius-avatar">
                    <span class="thorius-status-indicator thorius-online"></span>
                    <img src="<?php echo plugin_dir_url(__FILE__) . '../assets/images/thorius-avatar.png'; ?>" alt="THORIUS">
                </div>
                <div class="thorius-info">
                    <h4>THORIUS</h4>
                    <p>Your Platform Concierge</p>
                </div>
                <button id="thorius-minimize" class="thorius-btn-minimize">−</button>
                <button id="thorius-close" class="thorius-btn-close">×</button>
            </div>
            <div id="thorius-chatbot-messages"></div>
            <div id="thorius-chatbot-input">
                <input type="text" id="thorius-message-input" placeholder="Ask THORIUS anything..." maxlength="500">
                <button id="thorius-send-btn">Send</button>
            </div>
            <div id="thorius-quick-actions">
                <button class="thorius-quick-btn" data-message="How do I get started?">Getting Started</button>
                <button class="thorius-quick-btn" data-message="Show me platform features">Features</button>
                <button class="thorius-quick-btn" data-message="I need help with my account">Account Help</button>
            </div>
        </div>
        <button id="thorius-chatbot-toggle" class="thorius-chat-toggle">
            <span class="thorius-notification-badge thorius-hidden">1</span>
            💬 THORIUS
        </button>
        <?php
    }
    
    /**
     * ===== PLATFORM SUPERVISION METHODS =====
     */
    
    /**
     * THORIUS supervision for new user registration
     */
    public function thorius_new_user_supervision($user_id) {
        $supervision_result = $this->vault_orchestrator->thorius_supervision_check(
            $user_id,
            'new_user_registration',
            array(
                'registration_time' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            )
        );
        
        // Act on supervision result
        if (!$supervision_result['approved']) {
            // Flag for manual review
            update_user_meta($user_id, 'thorius_flagged', true);
            update_user_meta($user_id, 'thorius_warnings', $supervision_result['warnings']);
        }
        
        // Welcome new user
        $this->send_thorius_welcome_message($user_id);
    }
    
    /**
     * THORIUS supervision for user login
     */
    public function thorius_login_supervision($user_login) {
        $user = get_user_by('login', $user_login);
        if (!$user) return;
        
        $supervision_result = $this->vault_orchestrator->thorius_supervision_check(
            $user->ID,
            'user_login',
            array(
                'login_time' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'failed_attempts' => get_user_meta($user->ID, 'failed_login_attempts', true) ?? 0
            )
        );
        
        // Update user supervision status
        update_user_meta($user->ID, 'thorius_last_supervision', current_time('mysql'));
        
        if (!empty($supervision_result['warnings'])) {
            update_user_meta($user->ID, 'thorius_active_warnings', $supervision_result['warnings']);
        }
    }
    
    /**
     * THORIUS supervision for comments
     */
    public function thorius_comment_supervision($approved, $comment_data) {
        if (!is_user_logged_in()) return $approved;
        
        $user_id = get_current_user_id();
        $supervision_result = $this->vault_orchestrator->thorius_supervision_check(
            $user_id,
            'comment_submission',
            array(
                'comment_content' => $comment_data['comment_content'],
                'comment_length' => strlen($comment_data['comment_content']),
                'post_id' => $comment_data['comment_post_ID'],
                'submission_time' => current_time('mysql')
            )
        );
        
        if (!$supervision_result['approved']) {
            // Hold comment for moderation
            return 0;
        }
        
        return $approved;
    }
    
    /**
     * Send THORIUS welcome message to new user
     */
    private function send_thorius_welcome_message($user_id) {
        $user = get_user_by('ID', $user_id);
        $welcome_message = "Welcome to VORTEX, {$user->display_name}! 🎨\n\n";
        $welcome_message .= "I'm THORIUS, your personal platform concierge. I'm here to:\n";
        $welcome_message .= "• Guide you through the platform\n";
        $welcome_message .= "• Help with any questions\n";
        $welcome_message .= "• Ensure a safe, creative environment\n";
        $welcome_message .= "• Connect you with amazing opportunities\n\n";
        $welcome_message .= "Click the chat icon anytime to talk with me!";
        
        // Store welcome message for delivery
        update_user_meta($user_id, 'thorius_welcome_message', $welcome_message);
        update_user_meta($user_id, 'thorius_welcome_pending', true);
    }
    
    /**
     * Log chat interaction for continuous learning
     */
    private function log_chat_interaction($user_id, $user_message, $thorius_response) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_thorius_interactions',
            array(
                'user_id' => $user_id,
                'user_message' => $user_message,
                'thorius_response' => json_encode($thorius_response),
                'response_type' => $thorius_response['response_type'],
                'escalation_needed' => $thorius_response['escalation_needed'] ? 1 : 0,
                'interaction_time' => current_time('mysql'),
                'user_satisfaction' => null // Will be updated via feedback
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%d')
        );
    }
    
    /**
     * Get THORIUS supervision dashboard data
     */
    public function get_supervision_dashboard_data() {
        global $wpdb;
        
        $data = array(
            'total_interactions_today' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}vortex_thorius_interactions 
                WHERE DATE(interaction_time) = CURDATE()
            "),
            'active_warnings' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->usermeta} 
                WHERE meta_key = 'thorius_active_warnings' AND meta_value != ''
            "),
            'escalations_pending' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}vortex_thorius_interactions 
                WHERE escalation_needed = 1 AND DATE(interaction_time) = CURDATE()
            "),
            'avg_response_satisfaction' => $wpdb->get_var("
                SELECT AVG(user_satisfaction) FROM {$wpdb->prefix}vortex_thorius_interactions 
                WHERE user_satisfaction IS NOT NULL AND DATE(interaction_time) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ")
        );
        
        return $data;
    }
}

// Initialize THORIUS chatbot
VORTEX_Thorius_Chatbot::get_instance(); 