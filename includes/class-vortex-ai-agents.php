<?php
/**
 * Vortex AI Agents
 *
 * @link       https://vortexai.io
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * Manages AI Agent functionality for the marketplace.
 *
 * This class defines all code necessary to handle interactions with AI agents,
 * including chat functionality and response generation.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Vortex AI Team
 */
class Vortex_AI_Agents {

    /**
     * The single instance of the class.
     *
     * @var Vortex_AI_Agents
     */
    protected static $_instance = null;

    /**
     * Main Vortex_AI_Agents Instance.
     *
     * Ensures only one instance of Vortex_AI_Agents is loaded or can be loaded.
     *
     * @return Vortex_AI_Agents - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        // Register scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
        
        // Register AJAX handlers
        add_action( 'wp_ajax_vortex_ai_agent_message', array( $this, 'handle_agent_message' ) );
        add_action( 'wp_ajax_nopriv_vortex_ai_agent_message', array( $this, 'handle_agent_message' ) );
        add_action( 'wp_ajax_vortex_ai_agent_feedback', array( $this, 'handle_agent_feedback' ) );
        add_action( 'wp_ajax_nopriv_vortex_ai_agent_feedback', array( $this, 'handle_agent_feedback' ) );
        
        // Register shortcode
        add_shortcode( 'vortex_ai_agents', array( $this, 'render_agents' ) );
    }

    /**
     * Register scripts for AI agents.
     */
    public function register_scripts() {
        wp_register_style( 
            'vortex-ai-agents-style', 
            VORTEX_PLUGIN_URL . 'css/vortex-ai-agents.css', 
            array(), 
            VORTEX_VERSION 
        );
        
        wp_register_script( 
            'vortex-ai-agents', 
            VORTEX_PLUGIN_URL . 'js/vortex-ai-agents-enhanced.js', 
            array( 'jquery' ), 
            VORTEX_VERSION, 
            true 
        );
        
        wp_localize_script( 
            'vortex-ai-agents', 
            'vortexAIAgents', 
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'security' => wp_create_nonce( 'vortex_ai_agent_nonce' ),
                'api_url' => $this->get_api_url(),
                'api_key' => $this->get_api_key(),
            )
        );
    }

    /**
     * Handle AI agent message requests.
     */
    public function handle_agent_message() {
        // Check security nonce
        if ( ! check_ajax_referer( 'vortex_ai_agent_nonce', 'security', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ) );
        }
        
        // Get request parameters
        $agent_id = isset( $_POST['agent_id'] ) ? sanitize_text_field( $_POST['agent_id'] ) : '';
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
        $history = isset( $_POST['history'] ) ? json_decode( stripslashes( $_POST['history'] ), true ) : array();
        
        if ( empty( $agent_id ) || empty( $message ) ) {
            wp_send_json_error( array( 'message' => 'Missing required parameters' ) );
        }
        
        // Log user message for analysis
        $this->log_user_message( $agent_id, $message, $history );
        
        // Process user message based on agent type
        $response = $this->process_agent_message( $agent_id, $message, $history );
        
        // Store interaction in the database
        $this->store_interaction( $agent_id, $message, $response, $history );
        
        wp_send_json_success( array( 'message' => $response ) );
    }

    /**
     * Handle AI agent feedback.
     */
    public function handle_agent_feedback() {
        // Check security nonce
        if ( ! check_ajax_referer( 'vortex_ai_agent_nonce', 'security', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ) );
        }
        
        // Get request parameters
        $agent_id = isset( $_POST['agent_id'] ) ? sanitize_text_field( $_POST['agent_id'] ) : '';
        $message_id = isset( $_POST['message_id'] ) ? sanitize_text_field( $_POST['message_id'] ) : '';
        $rating = isset( $_POST['rating'] ) ? sanitize_text_field( $_POST['rating'] ) : '';
        $comment = isset( $_POST['comment'] ) ? sanitize_textarea_field( $_POST['comment'] ) : '';
        
        if ( empty( $agent_id ) || empty( $message_id ) || empty( $rating ) ) {
            wp_send_json_error( array( 'message' => 'Missing required parameters' ) );
        }
        
        // Store feedback in the database
        $this->store_feedback( $agent_id, $message_id, $rating, $comment );
        
        wp_send_json_success( array( 'message' => 'Feedback received' ) );
    }

    /**
     * Process agent message based on agent type.
     *
     * @param string $agent_id The agent ID.
     * @param string $message The user message.
     * @param array $history The conversation history.
     * @return string The agent response.
     */
    private function process_agent_message( $agent_id, $message, $history ) {
        // Default response if API is not available
        $default_responses = array(
            'artwork_advisor' => "I'd be happy to help with your artwork questions. However, I'm currently in offline mode. Please try again later or contact support for assistance.",
            'marketplace_guide' => "I'm here to help with marketplace questions, but I'm currently in offline mode. Please try again later or check our documentation for information about the marketplace.",
            'prompt_engineer' => "I'd love to help with prompt engineering, but I'm currently in offline mode. You might try experimenting with different descriptive terms and style references in your prompts until I'm back online.",
            'community_assistant' => "I'm here to help with community information, but I'm currently in offline mode. Please check our community forums or social media for the latest updates and events.",
            'technical_support' => "I'm here to help with technical issues, but I'm currently in offline mode. Please try standard troubleshooting steps like clearing your cache or try again later.",
        );
        
        // Get default response based on agent ID
        $default_response = isset( $default_responses[$agent_id] ) ? $default_responses[$agent_id] : "I'm currently in offline mode. Please try again later.";
        
        // Try to get response from AI API
        $api_response = $this->get_api_response( $agent_id, $message, $history );
        
        // Return API response if available, otherwise return default response
        return $api_response ? $api_response : $default_response;
    }

    /**
     * Get response from AI API.
     *
     * @param string $agent_id The agent ID.
     * @param string $message The user message.
     * @param array $history The conversation history.
     * @return string|false The API response or false on failure.
     */
    private function get_api_response( $agent_id, $message, $history ) {
        $api_url = $this->get_api_url();
        $api_key = $this->get_api_key();
        
        if ( empty( $api_url ) || empty( $api_key ) ) {
            return false;
        }
        
        // Format history for API request
        $formatted_history = array();
        foreach ( $history as $entry ) {
            $role = $entry['sender'] === 'user' ? 'user' : 'assistant';
            $formatted_history[] = array(
                'role' => $role,
                'content' => $entry['text']
            );
        }
        
        // Add current message
        $formatted_history[] = array(
            'role' => 'user',
            'content' => $message
        );
        
        // Prepare agent-specific system message
        $system_message = $this->get_agent_system_message( $agent_id );
        
        // Prepare request body
        $request_body = array(
            'model' => 'gpt-4',
            'messages' => array_merge(
                array(
                    array(
                        'role' => 'system',
                        'content' => $system_message
                    )
                ),
                $formatted_history
            ),
            'temperature' => 0.7,
            'max_tokens' => 500
        );
        
        // Make API request
        $response = wp_remote_post(
            $api_url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key
                ),
                'body' => json_encode( $request_body ),
                'timeout' => 30
            )
        );
        
        // Check for errors
        if ( is_wp_error( $response ) ) {
            error_log( 'AI API Error: ' . $response->get_error_message() );
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            error_log( 'AI API Error: Received response code ' . $response_code );
            return false;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['choices'][0]['message']['content'] ) ) {
            error_log( 'AI API Error: Invalid response format' );
            return false;
        }
        
        return $body['choices'][0]['message']['content'];
    }

    /**
     * Get agent-specific system message.
     *
     * @param string $agent_id The agent ID.
     * @return string The system message.
     */
    private function get_agent_system_message( $agent_id ) {
        $system_messages = array(
            'artwork_advisor' => "You are the Artwork Advisor for the Vortex AI Marketplace. Your role is to help artists optimize their portfolio, advise on artwork creation, suggest styles and techniques, and answer questions about selling artwork effectively. Your tone should be professional yet approachable, knowledgeable about art styles, techniques, and market trends. Provide specific, actionable advice tailored to the artist's needs.",
            
            'marketplace_guide' => "You are the Marketplace Guide for the Vortex AI Marketplace. Your role is to help users navigate the marketplace, understand how to find and purchase artwork, explain the prompt system, and provide information about marketplace features and policies. Your tone should be helpful and informative, focusing on making the marketplace easy to understand and use.",
            
            'prompt_engineer' => "You are the Prompt Engineer for the Vortex AI Marketplace. Your expertise is in helping users craft effective prompts for AI art generation. You should provide guidance on prompt structure, suggest effective keywords, explain how different parameters affect the output, and help users troubleshoot issues with their prompts. Your tone should be technical yet accessible, with a focus on practical advice that helps users achieve their desired artistic results.",
            
            'community_assistant' => "You are the Community Assistant for the Vortex AI Marketplace. Your role is to inform users about community events, challenges, collaboration opportunities, and ways to connect with other artists. You should be knowledgeable about upcoming events, provide information about artist groups, and suggest ways for users to get involved in the community. Your tone should be friendly and enthusiastic, focused on building connections and encouraging participation.",
            
            'technical_support' => "You are the Technical Support agent for the Vortex AI Marketplace. Your role is to help users troubleshoot technical issues related to uploads, downloads, account management, payments, and other platform features. You should provide clear step-by-step instructions, suggest common solutions for typical problems, and know when to escalate issues to human support. Your tone should be patient and solution-oriented, focusing on helping users resolve their issues as efficiently as possible."
        );
        
        $default_message = "You are an AI assistant for the Vortex AI Marketplace. Provide helpful, accurate, and concise information to users. Be friendly and professional in your responses.";
        
        return isset( $system_messages[$agent_id] ) ? $system_messages[$agent_id] : $default_message;
    }

    /**
     * Store interaction in the database.
     *
     * @param string $agent_id The agent ID.
     * @param string $message The user message.
     * @param string $response The agent response.
     * @param array $history The conversation history.
     */
    private function store_interaction( $agent_id, $message, $response, $history ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_interactions';
        
        // Create table if it doesn't exist
        $this->maybe_create_tables();
        
        // Get user ID (0 for guests)
        $user_id = get_current_user_id();
        
        // Insert interaction
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'agent_id' => $agent_id,
                'user_message' => $message,
                'agent_response' => $response,
                'conversation_history' => json_encode( $history ),
                'created_at' => current_time( 'mysql' ),
                'ip_address' => $this->get_user_ip()
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
        
        return $wpdb->insert_id;
    }

    /**
     * Store feedback in the database.
     *
     * @param string $agent_id The agent ID.
     * @param string $message_id The message ID.
     * @param string $rating The feedback rating.
     * @param string $comment The feedback comment.
     */
    private function store_feedback( $agent_id, $message_id, $rating, $comment ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_feedback';
        
        // Create table if it doesn't exist
        $this->maybe_create_tables();
        
        // Get user ID (0 for guests)
        $user_id = get_current_user_id();
        
        // Insert feedback
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'agent_id' => $agent_id,
                'message_id' => $message_id,
                'rating' => $rating,
                'comment' => $comment,
                'created_at' => current_time( 'mysql' ),
                'ip_address' => $this->get_user_ip()
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
        
        return $wpdb->insert_id;
    }

    /**
     * Log user message for analysis.
     *
     * @param string $agent_id The agent ID.
     * @param string $message The user message.
     * @param array $history The conversation history.
     */
    private function log_user_message( $agent_id, $message, $history ) {
        // Simple logging to file (could be expanded)
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf(
                '[Vortex AI] Agent: %s, User: %d, Message: %s',
                $agent_id,
                get_current_user_id(),
                $message
            ) );
        }
    }

    /**
     * Get API URL from settings.
     *
     * @return string The API URL.
     */
    private function get_api_url() {
        return get_option( 'vortex_ai_api_url', 'https://api.openai.com/v1/chat/completions' );
    }

    /**
     * Get API key from settings.
     *
     * @return string The API key.
     */
    private function get_api_key() {
        return get_option( 'vortex_ai_api_key', '' );
    }

    /**
     * Get user IP address.
     *
     * @return string The user IP address.
     */
    private function get_user_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field( $ip );
    }

    /**
     * Create database tables if they don't exist.
     */
    private function maybe_create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $interactions_table = $wpdb->prefix . 'vortex_ai_interactions';
        $feedback_table = $wpdb->prefix . 'vortex_ai_feedback';
        
        // Check if tables exist
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$interactions_table'" ) != $interactions_table ) {
            $sql = "CREATE TABLE $interactions_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL DEFAULT 0,
                agent_id varchar(50) NOT NULL,
                user_message text NOT NULL,
                agent_response text NOT NULL,
                conversation_history longtext NOT NULL,
                created_at datetime NOT NULL,
                ip_address varchar(100) NOT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY agent_id (agent_id),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$feedback_table'" ) != $feedback_table ) {
            $sql = "CREATE TABLE $feedback_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL DEFAULT 0,
                agent_id varchar(50) NOT NULL,
                message_id varchar(100) NOT NULL,
                rating varchar(20) NOT NULL,
                comment text NOT NULL,
                created_at datetime NOT NULL,
                ip_address varchar(100) NOT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY agent_id (agent_id),
                KEY message_id (message_id),
                KEY rating (rating),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }

    /**
     * Render AI agents display using shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string The shortcode output.
     */
    public function render_agents( $atts ) {
        $atts = shortcode_atts( array(
            'agents' => '', // Comma-separated list of agent IDs to show
            'columns' => 3,  // Number of columns to display
            'title' => 'AI Assistants', // Page title
            'description' => 'Get help from our AI assistants. Click on an assistant to start a conversation.' // Page description
        ), $atts, 'vortex_ai_agents' );
        
        // Enqueue required assets
        wp_enqueue_style( 'vortex-ai-agents-style' );
        wp_enqueue_script( 'vortex-ai-agents' );
        
        // Start output buffering
        ob_start();
        
        // Include template file
        include( VORTEX_PLUGIN_DIR . 'public/partials/vortex-ai-agents-display.php' );
        
        // Return the buffered content
        return ob_get_clean();
    }
}

// Initialize class
function vortex_ai_agents() {
    return Vortex_AI_Agents::instance();
}

// Start the plugin
vortex_ai_agents(); 