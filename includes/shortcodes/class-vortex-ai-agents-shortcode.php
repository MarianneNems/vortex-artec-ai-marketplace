<?php
/**
 * AI Agents Shortcode
 *
 * @package Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * AI Agents Shortcode Handler
 */
class Vortex_AI_Agents_Shortcode {

    /**
     * Initialize the class
     */
    public static function init() {
        add_shortcode('vortex_ai_agents_dashboard', array(__CLASS__, 'render_ai_agents_dashboard'));
    }

    /**
     * Render AI agents in the dashboard
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered output
     */
    public static function render_ai_agents_dashboard($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_role' => '',
            'layout' => 'grid', // grid or list
        ), $atts, 'vortex_ai_agents_dashboard');

        // Check user role if specified
        if (!empty($atts['user_role'])) {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return ''; // Not logged in
            }

            $user = get_userdata($user_id);
            if (!in_array($atts['user_role'], $user->roles)) {
                return ''; // User doesn't have required role
            }
        }

        // Enqueue required scripts and styles
        wp_enqueue_style(
            'vortex-ai-agents-css',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'css/vortex-ai-agents.css',
            array(),
            VORTEX_VERSION
        );
        
        wp_enqueue_script(
            'vortex-ai-agents-js',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'js/vortex-ai-agents.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-ai-agents-js', 'vortexAIAgents', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('vortex_ai_agent_security')
        ));

        // Start output buffer
        ob_start();
        
        // Include dashboard template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/dashboard-ai-agents.php';
        
        // Return rendered output
        return ob_get_clean();
    }
} 