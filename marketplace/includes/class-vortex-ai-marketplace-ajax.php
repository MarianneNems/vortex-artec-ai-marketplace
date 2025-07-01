<?php
/**
 * The AJAX functionality of the plugin.
 *
 * @link       https://vortexai.io
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The AJAX functionality of the plugin.
 *
 * Defines and handles all AJAX callbacks for the marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     VortexAI Team
 */
class Vortex_AI_Marketplace_Ajax {

    /**
     * Initialize the class and set its hooks.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->register_ajax_hooks();
    }

    /**
     * Register all the AJAX hooks
     *
     * @since    1.0.0
     */
    private function register_ajax_hooks() {
        // Artist quiz response analysis
        add_action('wp_ajax_vortex_analyze_quiz_responses', array($this, 'analyze_quiz_responses'));
        add_action('wp_ajax_nopriv_vortex_analyze_quiz_responses', array($this, 'analyze_quiz_responses'));
        
        // Agent chat message handling
        add_action('wp_ajax_vortex_agent_message', array($this, 'handle_agent_message'));
        add_action('wp_ajax_nopriv_vortex_agent_message', array($this, 'handle_agent_message'));
        
        // More AJAX hooks can be added here
    }
    
    /**
     * Handle AI agent messages
     */
    public function handle_agent_message() {
        // Check security nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_agent_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        
        // Get agent ID and message
        $agent_id = isset($_POST['agent_id']) ? sanitize_text_field($_POST['agent_id']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (empty($agent_id) || empty($message)) {
            wp_send_json_error(['message' => 'Missing required parameters.']);
        }
        
        // Get agent handler instance and generate response
        $agent_handler = new Vortex_AI_Agent_Handler();
        $response = $agent_handler->generate_agent_response($agent_id, $message);
        
        wp_send_json_success([
            'agent_id' => $agent_id,
            'response' => $response
        ]);
    }

    /**
     * Analyze artist quiz responses
     */
    public function analyze_quiz_responses() {
        // Check security nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_quiz_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        
        // Get form data
        $education = isset($_POST['education']) ? sanitize_text_field($_POST['education']) : '';
        $self_taught_years = isset($_POST['self_taught_years']) ? intval($_POST['self_taught_years']) : 0;
        $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '';
        $exhibitions = isset($_POST['exhibitions']) ? sanitize_text_field($_POST['exhibitions']) : '';
        $price_range = isset($_POST['price_range']) ? sanitize_text_field($_POST['price_range']) : '';
        $seed_art_commitment = isset($_POST['seed_art_commitment']) ? (bool) $_POST['seed_art_commitment'] : false;
        
        // Calculate score based on responses
        $score = 0;
        
        // Education scoring
        switch ($education) {
            case 'art_school':
                $score += 25;
                break;
            case 'university':
                $score += 20;
                break;
            case 'courses':
                $score += 15;
                break;
            case 'self_taught':
                // Self-taught with experience
                if ($self_taught_years > 5) {
                    $score += 15;
                } elseif ($self_taught_years > 2) {
                    $score += 10;
                } else {
                    $score += 5;
                }
                break;
            default:
                $score += 0;
        }
        
        // Exhibitions scoring
        switch ($exhibitions) {
            case 'many':
                $score += 25;
                break;
            case 'few':
                $score += 15;
                break;
            case 'online':
                $score += 10;
                break;
            case 'none':
                $score += 5;
                break;
            default:
                $score += 0;
        }
        
        // Price range scoring
        switch ($price_range) {
            case 'high':
                $score += 25;
                break;
            case 'medium':
                $score += 20;
                break;
            case 'low':
                $score += 15;
                break;
            case 'free':
                $score += 5;
                break;
            default:
                $score += 0;
        }
        
        // Seed art commitment bonus
        if ($seed_art_commitment) {
            $score += 25;
        }
        
        // Determine tier based on score
        $tier = '';
        $feedback = '';
        
        if ($score >= 75) {
            $tier = 'premium';
            $feedback = 'Outstanding! Your experience and commitment to quality artwork make you an excellent fit for our marketplace. We believe you\'ll be a valuable contributor to our community.';
        } elseif ($score >= 50) {
            $tier = 'standard';
            $feedback = 'Great! You have solid experience and potential. While you may not have the extensive background of our premium artists, you show promising talent that can flourish in our marketplace.';
        } elseif ($score >= 30) {
            $tier = 'novice';
            $feedback = 'Welcome to the marketplace! While you may be at the earlier stages of your artistic journey, we believe everyone starts somewhere. Focus on developing your skills and following our guidelines to grow your presence here.';
        } else {
            $tier = 'developing';
            $feedback = 'Thank you for your interest in our marketplace. Based on your responses, we recommend gaining more experience before fully diving into professional marketplaces. Consider building a portfolio and developing your skills further.';
        }
        
        // Send the response
        wp_send_json_success([
            'score' => $score,
            'tier' => $tier,
            'feedback' => $feedback
        ]);
    }
} 