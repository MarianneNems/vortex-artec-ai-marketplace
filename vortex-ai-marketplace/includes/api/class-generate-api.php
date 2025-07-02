<?php
/**
 * Generate API handler for HURAII AI artwork generation.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/api
 */
class Vortex_Generate_API {

    private $namespace;

    public function __construct() {
        $this->namespace = 'vortex/v1';
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/api/generate', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_artwork'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        register_rest_route($this->namespace, '/api/generate/status/(?P<job_id>[\w-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_generation_status'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));
    }

    public function generate_artwork($request) {
        $prompt = $request->get_param('prompt');
        $style = $request->get_param('style');
        $dimensions = $request->get_param('dimensions') ?: '1024x1024';
        $user_id = get_current_user_id();

        if (empty($prompt)) {
            return new WP_Error('missing_prompt', 'Prompt is required for artwork generation', array('status' => 400));
        }

        // Check user's plan limits
        $plan = get_user_meta($user_id, 'vortex_subscription_plan', true);
        if (!$this->check_generation_limits($user_id, $plan)) {
            return new WP_Error('limit_exceeded', 'Monthly generation limit exceeded', array('status' => 429));
        }

        // Create generation job
        $job_id = 'gen_' . uniqid();
        $generation_data = array(
            'job_id' => $job_id,
            'user_id' => $user_id,
            'prompt' => $prompt,
            'style' => $style,
            'dimensions' => $dimensions,
            'status' => 'processing',
            'created_at' => current_time('mysql'),
        );

        // Store generation job (in real implementation, this would be in database)
        update_user_meta($user_id, 'vortex_generation_' . $job_id, $generation_data);

        // Mock immediate completion for demo
        $this->complete_generation($job_id, $user_id);

        return new WP_REST_Response(array(
            'success' => true,
            'job_id' => $job_id,
            'status' => 'processing',
            'estimated_completion' => date('Y-m-d H:i:s', strtotime('+30 seconds')),
            'message' => 'Generation started successfully',
        ), 202);
    }

    public function get_generation_status($request) {
        $job_id = $request->get_param('job_id');
        $user_id = get_current_user_id();

        $generation_data = get_user_meta($user_id, 'vortex_generation_' . $job_id, true);
        
        if (empty($generation_data)) {
            return new WP_Error('job_not_found', 'Generation job not found', array('status' => 404));
        }

        return new WP_REST_Response($generation_data, 200);
    }

    private function check_generation_limits($user_id, $plan) {
        $limits = array(
            'starter' => 50,
            'pro' => 200,
            'studio' => -1, // Unlimited
        );

        if ($plan === 'studio') {
            return true; // Unlimited
        }

        $monthly_count = get_user_meta($user_id, 'vortex_monthly_generations_' . date('Y-m'), true) ?: 0;
        $limit = $limits[$plan] ?? 0;

        return $monthly_count < $limit;
    }

    private function complete_generation($job_id, $user_id) {
        // Mock completion - in real implementation, this would be triggered by GPU processing
        $generation_data = get_user_meta($user_id, 'vortex_generation_' . $job_id, true);
        $generation_data['status'] = 'completed';
        $generation_data['completed_at'] = current_time('mysql');
        $generation_data['result_urls'] = array(
            'https://example.com/generated/' . $job_id . '_1.png',
            'https://example.com/generated/' . $job_id . '_2.png',
        );

        update_user_meta($user_id, 'vortex_generation_' . $job_id, $generation_data);

        // Update monthly generation count
        $monthly_key = 'vortex_monthly_generations_' . date('Y-m');
        $current_count = get_user_meta($user_id, $monthly_key, true) ?: 0;
        update_user_meta($user_id, $monthly_key, $current_count + 1);
    }

    public function check_user_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        $plan = get_user_meta($user_id, 'vortex_subscription_plan', true);
        
        return !empty($plan); // User must have an active subscription
    }
} 