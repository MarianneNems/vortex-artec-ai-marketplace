<?php
/**
 * Enhanced VORTEX AI API class with full WooCommerce integration
 *
 * @since      3.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/api
 */

class Vortex_AI_API_Enhanced {

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.0.0
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register all REST API routes
     *
     * @since    3.0.0
     */
    public function register_routes() {
        $this->register_new_routes();
    }

    /**
     * Register REST API routes for AI generation and file uploads
     *
     * @since    3.0.0
     */
    public function register_new_routes() {
    // AI Generation endpoint
    register_rest_route('vortex/v1', '/generate', array(
        'methods' => 'POST',
        'callback' => array($this, 'handle_ai_generation'),
        'permission_callback' => array($this, 'check_user_permissions'),
        'args' => array(
            'prompt' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'AI generation prompt'
            ),
            'style' => array(
                'required' => false,
                'type' => 'string',
                'default' => 'realistic'
            ),
            'seed_artworks' => array(
                'required' => false,
                'type' => 'array',
                'description' => 'Array of seed artwork IDs'
            )
        )
    ));

    // Seed upload endpoint
    register_rest_route('vortex/v1', '/upload-seed', array(
        'methods' => 'POST',
        'callback' => array($this, 'handle_seed_upload'),
        'permission_callback' => array($this, 'check_user_permissions')
    ));

    // Get user's seed gallery
    register_rest_route('vortex/v1', '/seed-gallery', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_seed_gallery'),
        'permission_callback' => array($this, 'check_user_permissions')
    ));

    // Get user balance
    register_rest_route('vortex/v1', '/balance', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_user_balance'),
        'permission_callback' => array($this, 'check_user_permissions')
    ));
}

/**
 * Handle AI generation requests
 *
 * @since    3.0.0
 * @param    WP_REST_Request    $request    REST request object
 * @return   WP_REST_Response              REST response
 */
public function handle_ai_generation($request) {
    $user_id = get_current_user_id();
    
    // Check user plan and generation limits
    $plan_check = $this->check_generation_limits($user_id);
    if (!$plan_check['allowed']) {
        return new WP_REST_Response(array(
            'error' => $plan_check['message']
        ), 403);
    }

    // Get parameters
    $prompt = sanitize_text_field($request->get_param('prompt'));
    $style = sanitize_text_field($request->get_param('style')) ?: 'realistic';
    $seed_artworks = $request->get_param('seed_artworks') ?: array();

    // Validate prompt
    if (strlen($prompt) < 3) {
        return new WP_REST_Response(array(
            'error' => 'Prompt must be at least 3 characters long'
        ), 400);
    }

    // Prepare generation request
    $generation_data = array(
        'prompt' => $prompt,
        'style' => $style,
        'user_id' => $user_id,
        'seed_artworks' => $seed_artworks,
        'timestamp' => time()
    );

    // Forward to AI server
    $ai_result = $this->forward_to_ai_server($generation_data);
    
    if (!$ai_result['success']) {
        return new WP_REST_Response(array(
            'error' => 'AI generation failed: ' . $ai_result['error']
        ), 500);
    }

    // Deduct tokens for generation
    $this->deduct_generation_tokens($user_id);

    // Update user statistics
    $this->update_generation_stats($user_id);

    // Award milestone if first generation
    $this->check_first_generation_milestone($user_id);

    return new WP_REST_Response(array(
        'success' => true,
        'image_url' => $ai_result['image_url'],
        'generation_id' => $ai_result['generation_id'],
        'metadata' => $ai_result['metadata'],
        'remaining_tokens' => $this->get_user_tokens($user_id)
    ), 200);
}

/**
 * Handle seed artwork upload
 *
 * @since    3.0.0
 * @param    WP_REST_Request    $request    REST request object
 * @return   WP_REST_Response              REST response
 */
public function handle_seed_upload($request) {
    $user_id = get_current_user_id();

    // Check if file was uploaded
    if (empty($_FILES['file'])) {
        return new WP_REST_Response(array(
            'error' => 'No file uploaded'
        ), 400);
    }

    // Use seed art manager to process upload
    if (!class_exists('Vortex_Seed_Art_Manager')) {
        return new WP_REST_Response(array(
            'error' => 'Seed art manager not available'
        ), 500);
    }

    $seed_manager = new Vortex_Seed_Art_Manager();
    $upload_result = $seed_manager->process_seed_upload($_FILES['file'], $user_id);

    if ($upload_result['success']) {
        return new WP_REST_Response($upload_result, 200);
    } else {
        return new WP_REST_Response(array(
            'error' => $upload_result['error']
        ), 400);
    }
}

/**
 * Get user's seed gallery
 *
 * @since    3.0.0
 * @param    WP_REST_Request    $request    REST request object
 * @return   WP_REST_Response              REST response
 */
public function get_seed_gallery($request) {
    $user_id = get_current_user_id();

    if (!class_exists('Vortex_Seed_Art_Manager')) {
        return new WP_REST_Response(array(
            'error' => 'Seed art manager not available'
        ), 500);
    }

    $seed_manager = new Vortex_Seed_Art_Manager();
    $artworks = $seed_manager->get_user_seed_artworks($user_id);

    return new WP_REST_Response(array(
        'success' => true,
        'artworks' => $artworks,
        'count' => count($artworks)
    ), 200);
}

/**
 * Get user's TOLA balance
 *
 * @since    3.0.0
 * @param    WP_REST_Request    $request    REST request object
 * @return   WP_REST_Response              REST response
 */
public function get_user_balance($request) {
    $user_id = get_current_user_id();

    if (!class_exists('Vortex_AI_Marketplace_Wallet')) {
        return new WP_REST_Response(array(
            'error' => 'Wallet system not available'
        ), 500);
    }

    $wallet = new Vortex_AI_Marketplace_Wallet();
    $balance = $wallet->get_balance($user_id);

    return new WP_REST_Response(array(
        'success' => true,
        'balance' => $balance,
        'formatted' => number_format($balance) . ' TOLA'
    ), 200);
}

/**
 * Forward generation request to AI server
 *
 * @since    3.0.0
 * @param    array    $generation_data    Generation parameters
 * @return   array                        AI server response
 */
private function forward_to_ai_server($generation_data) {
    $ai_server_url = get_option('vortex_ai_server_url', '');
    
    if (empty($ai_server_url)) {
        // Return mock response for development
        return array(
            'success' => true,
            'image_url' => 'https://mock-ai-server.com/generated-image-' . time() . '.jpg',
            'generation_id' => 'gen_' . time() . '_' . wp_generate_password(8, false),
            'metadata' => array(
                'prompt' => $generation_data['prompt'],
                'style' => $generation_data['style'],
                'generated_at' => current_time('mysql')
            )
        );
    }

    // Prepare request
    $request_body = array(
        'prompt' => $generation_data['prompt'],
        'style' => $generation_data['style'],
        'user_id' => $generation_data['user_id'],
        'seed_artworks' => $generation_data['seed_artworks']
    );

    $response = wp_remote_post($ai_server_url . '/generate', array(
        'timeout' => 60,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('vortex_ai_server_token', '')
        ),
        'body' => json_encode($request_body)
    ));

    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'error' => $response->get_error_message()
        );
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code !== 200) {
        return array(
            'success' => false,
            'error' => 'AI server error: ' . $response_code
        );
    }

    $ai_response = json_decode($response_body, true);
    
    if (!$ai_response || !isset($ai_response['image_url'])) {
        return array(
            'success' => false,
            'error' => 'Invalid AI server response'
        );
    }

    return array(
        'success' => true,
        'image_url' => $ai_response['image_url'],
        'generation_id' => $ai_response['generation_id'] ?? 'gen_' . time(),
        'metadata' => $ai_response['metadata'] ?? array()
    );
}

/**
 * Check user permissions for API access
 *
 * @since    3.0.0
 * @return   bool    Permission status
 */
public function check_user_permissions() {
    return current_user_can('edit_posts');
}

/**
 * Check generation limits for user
 *
 * @since    3.0.0
 * @param    int    $user_id    User ID
 * @return   array              Limit check result
 */
private function check_generation_limits($user_id) {
    $user_plan = get_user_meta($user_id, 'vortex_plan', true);
    
    if (!$user_plan) {
        return array(
            'allowed' => false,
            'message' => 'No active subscription plan'
        );
    }

    // Check monthly generation limits
    $current_month = date('Y-m');
    $monthly_generations = get_user_meta($user_id, "vortex_generations_{$current_month}", true) ?: 0;

    $limits = array(
        'artist-starter' => 50,
        'artist-pro' => 200,
        'artist-studio' => -1 // Unlimited
    );

    $user_limit = $limits[$user_plan] ?? 0;
    
    if ($user_limit !== -1 && $monthly_generations >= $user_limit) {
        return array(
            'allowed' => false,
            'message' => 'Monthly generation limit reached. Upgrade your plan for more generations.'
        );
    }

    // Check TOLA token balance
    $token_balance = $this->get_user_tokens($user_id);
    $generation_cost = 1; // 1 TOLA per generation

    if ($token_balance < $generation_cost) {
        return array(
            'allowed' => false,
            'message' => 'Insufficient TOLA tokens. Purchase more tokens to continue.'
        );
    }

    return array('allowed' => true);
}

/**
 * Deduct tokens for generation
 *
 * @since    3.0.0
 * @param    int    $user_id    User ID
 */
private function deduct_generation_tokens($user_id) {
    if (class_exists('Vortex_AI_Marketplace_Wallet')) {
        $wallet = new Vortex_AI_Marketplace_Wallet();
        $wallet->debit_tokens($user_id, 1); // 1 TOLA per generation
    }
}

/**
 * Get user's TOLA token balance
 *
 * @since    3.0.0
 * @param    int    $user_id    User ID
 * @return   int                Token balance
 */
private function get_user_tokens($user_id) {
    if (class_exists('Vortex_AI_Marketplace_Wallet')) {
        $wallet = new Vortex_AI_Marketplace_Wallet();
        return $wallet->get_balance($user_id);
    }
    return 0;
}

/**
 * Update generation statistics
 *
 * @since    3.0.0
 * @param    int    $user_id    User ID
 */
private function update_generation_stats($user_id) {
    // Update monthly count
    $current_month = date('Y-m');
    $monthly_key = "vortex_generations_{$current_month}";
    $current_count = get_user_meta($user_id, $monthly_key, true) ?: 0;
    update_user_meta($user_id, $monthly_key, $current_count + 1);

    // Update total count
    $total_count = get_user_meta($user_id, 'vortex_total_generations', true) ?: 0;
    update_user_meta($user_id, 'vortex_total_generations', $total_count + 1);
}

/**
 * Check and award first generation milestone
 *
 * @since    3.0.0
 * @param    int    $user_id    User ID
 */
private function check_first_generation_milestone($user_id) {
    $total_generations = get_user_meta($user_id, 'vortex_total_generations', true) ?: 0;
    
    if ($total_generations <= 1) { // First generation
        $completed_milestones = get_user_meta($user_id, 'vortex_completed_milestones', true) ?: array();
        
        if (!in_array('first_generation', $completed_milestones)) {
            $completed_milestones[] = 'first_generation';
            update_user_meta($user_id, 'vortex_completed_milestones', $completed_milestones);
            
            // Award bonus tokens
            if (class_exists('Vortex_AI_Marketplace_Wallet')) {
                $wallet = new Vortex_AI_Marketplace_Wallet();
                $wallet->credit_tokens($user_id, 15); // 15 TOLA bonus
            }
        }
    }
} 