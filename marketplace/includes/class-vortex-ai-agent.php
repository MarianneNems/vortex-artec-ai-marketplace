/**
 * The file that defines the AI Agent class
 *
 * This class handles AI agent interactions and responses
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The AI Agent class.
 *
 * Handles AI agent chat functionality and OpenAI API integration
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Vortex Marketplace
 */
class Vortex_AI_Agent {

    /**
     * The OpenAI API key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The OpenAI API key.
     */
    private $api_key;

    /**
     * The default agents configuration.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $default_agents    The default agents configuration.
     */
    private $default_agents;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Get the API key from options
        $this->api_key = get_option('vortex_openai_api_key', '');
        
        // Initialize default agents
        $this->init_default_agents();
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_ai_chat_message', array($this, 'handle_chat_message'));
        add_action('wp_ajax_nopriv_vortex_ai_chat_message', array($this, 'handle_chat_message'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Initialize the default agents configuration.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_default_agents() {
        $this->default_agents = array(
            array(
                'id' => 'artwork_advisor',
                'name' => 'Artwork Advisor',
                'icon' => 'palette',
                'description' => 'Get advice on improving your artwork and techniques.',
                'system_prompt' => 'You are an Artwork Advisor, an expert in various art styles, techniques, and mediums. Your goal is to provide helpful advice on creating and improving artwork. Be supportive and constructive. Focus on guiding the user to improve their art skills and techniques.'
            ),
            array(
                'id' => 'marketplace_guide',
                'name' => 'Marketplace Guide',
                'icon' => 'shopping-cart',
                'description' => 'Learn how to navigate the marketplace and sell your art.',
                'system_prompt' => 'You are a Marketplace Guide for the Vortex AI Marketplace, an expert in helping artists list and sell their artwork. Your goal is to explain marketplace features, listing process, pricing, and promotion strategies. Provide clear guidance on how to use the marketplace effectively.'
            ),
            array(
                'id' => 'prompt_engineer',
                'name' => 'Prompt Engineer',
                'icon' => 'wand-magic-sparkles',
                'description' => 'Get help crafting effective prompts for AI art generation.',
                'system_prompt' => 'You are a Prompt Engineer specializing in AI art prompts. Your goal is to help users create effective prompts for AI art generation. Explain prompt crafting techniques, common patterns, and how to achieve specific styles or effects. Suggest improvements to prompts and explain your reasoning.'
            ),
            array(
                'id' => 'community_assistant',
                'name' => 'Community Assistant',
                'icon' => 'users',
                'description' => 'Find out about community events, challenges, and opportunities.',
                'system_prompt' => 'You are a Community Assistant for the Vortex AI Marketplace. Your goal is to inform users about community events, art challenges, and collaboration opportunities. Be friendly and helpful, encouraging community participation and engagement.'
            ),
            array(
                'id' => 'technical_support',
                'name' => 'Technical Support',
                'icon' => 'gear',
                'description' => 'Get help with technical issues or account problems.',
                'system_prompt' => 'You are a Technical Support specialist for the Vortex AI Marketplace. Your goal is to help users troubleshoot technical issues with the platform, including account access, uploading artwork, payment processing, and using marketplace features. Provide clear, step-by-step guidance.'
            )
        );
    }
    
    /**
     * Get the configured AI agents.
     *
     * @since    1.0.0
     * @access   public
     * @return   array    The configured AI agents.
     */
    public function get_agents() {
        $custom_agents = get_option('vortex_ai_agents', array());
        
        if (empty($custom_agents)) {
            return $this->default_agents;
        }
        
        return $custom_agents;
    }
    
    /**
     * Get a specific agent by ID.
     *
     * @since    1.0.0
     * @access   public
     * @param    string    $agent_id    The agent ID.
     * @return   array|false    The agent configuration or false if not found.
     */
    public function get_agent($agent_id) {
        $agents = $this->get_agents();
        
        foreach ($agents as $agent) {
            if ($agent['id'] === $agent_id) {
                return $agent;
            }
        }
        
        return false;
    }
    
    /**
     * Enqueue the necessary scripts and styles.
     *
     * @since    1.0.0
     * @access   public
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'vortex-ai-agents-css',
            plugin_dir_url(dirname(__FILE__)) . 'public/css/vortex-ai-agents.css',
            array(),
            VORTEX_AI_MARKETPLACE_VERSION,
            'all'
        );
        
        wp_enqueue_script(
            'vortex-ai-agents-js',
            plugin_dir_url(dirname(__FILE__)) . 'public/js/vortex-ai-agents.js',
            array('jquery'),
            VORTEX_AI_MARKETPLACE_VERSION,
            true
        );
        
        wp_localize_script(
            'vortex-ai-agents-js',
            'vortexAiAgents',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vortex_ai_agents_nonce')
            )
        );
    }
    
    /**
     * Handle the chat message AJAX request.
     *
     * @since    1.0.0
     * @access   public
     */
    public function handle_chat_message() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_ai_agents_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Get the message and agent ID
        $message = sanitize_textarea_field($_POST['message']);
        $agent_id = sanitize_text_field($_POST['agent_id']);
        $chat_history = isset($_POST['chat_history']) ? $_POST['chat_history'] : array();
        
        // Validate input
        if (empty($message) || empty($agent_id)) {
            wp_send_json_error('Missing required parameters');
        }
        
        // Get the agent
        $agent = $this->get_agent($agent_id);
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        // Generate response
        $response = $this->generate_agent_response($agent, $message, $chat_history);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success(array(
            'response' => $response
        ));
    }
    
    /**
     * Generate an agent response using the OpenAI API.
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $agent          The agent configuration.
     * @param    string    $message        The user message.
     * @param    array     $chat_history   The chat history.
     * @return   string|WP_Error    The generated response or an error.
     */
    private function generate_agent_response($agent, $message, $chat_history) {
        // Check if API key is configured
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', 'OpenAI API key is not configured');
        }
        
        // Prepare message history for the API
        $messages = array(
            array(
                'role' => 'system',
                'content' => $agent['system_prompt']
            )
        );
        
        // Add chat history
        if (!empty($chat_history)) {
            foreach ($chat_history as $history_message) {
                $messages[] = array(
                    'role' => $history_message['role'],
                    'content' => $history_message['content']
                );
            }
        }
        
        // Add the current message
        $messages[] = array(
            'role' => 'user',
            'content' => $message
        );
        
        // Make API request
        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 30,
                'body' => json_encode(array(
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 500
                ))
            )
        );
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('openai_error', $body['error']['message']);
        }
        
        if (!isset($body['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API');
        }
        
        return $body['choices'][0]['message']['content'];
    }
    
    /**
     * Register the shortcode for displaying AI agents.
     *
     * @since    1.0.0
     */
    public function register_shortcode() {
        add_shortcode('vortex_ai_agents', array($this, 'display_agents_shortcode'));
    }
    
    /**
     * Shortcode callback for displaying AI agents.
     *
     * @since    1.0.0
     * @access   public
     * @return   string    The shortcode output.
     */
    public function display_agents_shortcode() {
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-ai-agents-display.php';
        return ob_get_clean();
    }
    
    /**
     * Register settings for AI agents.
     *
     * @since    1.0.0
     * @access   public
     */
    public function register_settings() {
        register_setting('vortex_marketplace_settings', 'vortex_openai_api_key');
        register_setting('vortex_marketplace_settings', 'vortex_ai_agents');
        
        add_settings_section(
            'vortex_ai_agents_section',
            'AI Agents Settings',
            array($this, 'render_ai_settings_section'),
            'vortex_marketplace_settings'
        );
        
        add_settings_field(
            'vortex_openai_api_key',
            'OpenAI API Key',
            array($this, 'render_api_key_field'),
            'vortex_marketplace_settings',
            'vortex_ai_agents_section'
        );
    }
    
    /**
     * Render the AI settings section.
     *
     * @since    1.0.0
     * @access   public
     */
    public function render_ai_settings_section() {
        echo '<p>Configure settings for AI agents and OpenAI API integration.</p>';
    }
    
    /**
     * Render the API key field.
     *
     * @since    1.0.0
     * @access   public
     */
    public function render_api_key_field() {
        $api_key = get_option('vortex_openai_api_key', '');
        
        echo '<input type="password" name="vortex_openai_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenAI API key to enable AI agent functionality.</p>';
    }
}

/**
 * Get user profile context for AI personalization
 * 
 * @param int $user_id User ID
 * @return string Context string for AI prompt
 */
protected function get_user_context($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return '';
    }
    
    $user_data = get_userdata($user_id);
    $user_role = get_user_meta($user_id, 'vortex_user_role', true);
    $user_categories = get_user_meta($user_id, 'vortex_user_categories', true);
    
    $context = "User is a " . ($user_role === 'artist' ? 'creator/artist' : 'collector/buyer');
    
    if (!empty($user_categories) && is_array($user_categories)) {
        $context .= " with interests in: " . implode(', ', $user_categories);
    }
    
    return $context;
}

/**
 * Enhance prompt with user context
 * 
 * @param string $prompt The original prompt
 * @param int $user_id User ID
 * @return string Enhanced prompt
 */
public function enhance_prompt_with_context($prompt, $user_id = null) {
    $user_context = $this->get_user_context($user_id);
    
    if (empty($user_context)) {
        return $prompt;
    }
    
    return $prompt . "\n\nUser Context: " . $user_context;
}

/**
 * Add business context to user data
 * 
 * @param int $user_id User ID
 * @param string $context_type Type of context
 * @param array $context_data Context data
 * @return bool Success
 */
public function add_user_context($user_id, $context_type, $context_data) {
    // Get existing user context
    $user_context = get_user_meta($user_id, 'vortex_ai_context', true);
    
    if (!is_array($user_context)) {
        $user_context = array();
    }
    
    // Add or update this context type
    $user_context[$context_type] = $context_data;
    
    // Add timestamp
    $user_context['updated_at'] = current_time('timestamp');
    
    // Save updated context
    update_user_meta($user_id, 'vortex_ai_context', $user_context);
    
    // Log context update for learning
    $this->log_context_update($user_id, $context_type, $context_data);
    
    return true;
}

/**
 * Log context updates for AI learning
 */
private function log_context_update($user_id, $context_type, $context_data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'vortex_ai_learning_log';
    
    $wpdb->insert(
        $table,
        array(
            'user_id' => $user_id,
            'agent_type' => get_class($this),
            'context_type' => $context_type,
            'context_data' => maybe_serialize($context_data),
            'timestamp' => current_time('timestamp')
        ),
        array('%d', '%s', '%s', '%s', '%d')
    );
    
    return true;
} 