<?php
/**
 * VORTEX Artist Business Quiz Shortcode
 *
 * @package Vortex_AI_Marketplace
 * @subpackage Shortcodes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Vortex_Artist_Business_Quiz
 * 
 * Handles the [vortex_artist_business_quiz] shortcode with monthly submission control
 */
class Vortex_Artist_Business_Quiz {

    /**
     * Instance of this class
     *
     * @var Vortex_Artist_Business_Quiz
     */
    private static $instance = null;

    /**
     * Get instance of this class
     *
     * @return Vortex_Artist_Business_Quiz
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_shortcode'));
    }

    /**
     * Register shortcode
     */
    public function register_shortcode() {
        add_shortcode('vortex_artist_business_quiz', array($this, 'render_quiz'));
    }

    /**
     * Render the business quiz
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public function render_quiz($atts = array(), $content = '') {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'title' => 'Strategic Business Assessment',
            'subtitle' => 'Unlock Your 30-Day Growth Plan',
            'redirect_url' => '',
            'theme' => 'professional'
        ), $atts, 'vortex_artist_business_quiz');

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $user_id = get_current_user_id();

        // Check monthly submission limit
        if ($this->has_submitted_this_month($user_id)) {
            return $this->render_monthly_limit_reached();
        }

        // Check user permissions (Pro+ tiers only)
        if (!$this->user_can_access_quiz()) {
            return $this->render_upgrade_required();
        }

        // Start output buffering
        ob_start();

        // Include the quiz template
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/artist-business-quiz.php';
        
        if (file_exists($template_path)) {
            // Pass variables to template
            $quiz_atts = $atts;
            $current_user_id = $user_id;
            
            include $template_path;
        } else {
            echo '<div class="vortex-error">Quiz template not found. Please contact support.</div>';
        }

        return ob_get_clean();
    }

    /**
     * Check if user has submitted quiz this calendar month
     *
     * @param int $user_id
     * @return bool
     */
    private function has_submitted_this_month($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_quiz_responses';
        $current_month = date('Y-m');
        
        $submission = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} 
             WHERE user_id = %d 
             AND DATE_FORMAT(submission_date, '%%Y-%%m') = %s 
             LIMIT 1",
            $user_id,
            $current_month
        ));
        
        return !empty($submission);
    }

    /**
     * Check if user can access the quiz
     *
     * @return bool
     */
    private function user_can_access_quiz() {
        $user = wp_get_current_user();
        
        // Check for Pro+ tier user roles
        $allowed_roles = array(
            'artist_pro',
            'artist_studio', 
            'artist_student',
            'artist_masters',
            'administrator'
        );

        foreach ($allowed_roles as $role) {
            if (in_array($role, $user->roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render login required message
     *
     * @return string
     */
    private function render_login_required() {
        return '
        <div class="vortex-quiz-access-notice login-required">
            <div class="notice-content">
                <h3>🔐 Login Required</h3>
                <p>Please log in to access the Strategic Business Assessment.</p>
                <div class="notice-actions">
                    <a href="' . wp_login_url(get_permalink()) . '" class="btn btn-primary">
                        Login
                    </a>
                    <a href="' . wp_registration_url() . '" class="btn btn-secondary">
                        Register
                    </a>
                </div>
            </div>
        </div>';
    }

    /**
     * Render upgrade required message
     *
     * @return string
     */
    private function render_upgrade_required() {
        return '
        <div class="vortex-quiz-access-notice upgrade-required">
            <div class="notice-content">
                <h3>✨ Upgrade Required</h3>
                <p>The Strategic Business Assessment is available for <strong>Pro tier and above</strong> members.</p>
                <p>Unlock personalized business strategies, 30-day milestone plans, and daily coaching from Horace!</p>
                
                <div class="upgrade-options">
                    <div class="upgrade-option">
                        <h4>Artist Pro - $59/month</h4>
                        <ul>
                            <li>✅ Strategic Assessment Access</li>
                            <li>✅ 30-Day Milestone Plan</li>
                            <li>✅ Daily Business Coaching</li>
                            <li>✅ 60 Workshop Hours</li>
                        </ul>
                        <a href="/membership/?level=artist_pro" class="btn btn-primary">
                            Upgrade to Pro
                        </a>
                    </div>
                    
                    <div class="upgrade-option featured">
                        <h4>Artist Studio - $99/month</h4>
                        <ul>
                            <li>✅ Everything in Pro</li>
                            <li>✅ Advanced Strategy Tools</li>
                            <li>✅ 120 Workshop Hours</li>
                            <li>✅ Priority Support</li>
                        </ul>
                        <a href="/membership/?level=artist_studio" class="btn btn-featured">
                            Upgrade to Studio
                        </a>
                    </div>
                </div>
                
                <p style="margin-top: 20px; text-align: center;">
                    <a href="/membership/" class="btn btn-outline">View All Plans</a>
                </p>
            </div>
        </div>';
    }

    /**
     * Render monthly limit reached message
     *
     * @return string
     */
    private function render_monthly_limit_reached() {
        $next_month = date('F Y', strtotime('first day of next month'));
        
        return '
        <div class="vortex-quiz-monthly-limit">
            <div class="limit-content">
                <h3>📅 Monthly Assessment Completed</h3>
                <p>You have already completed your Strategic Business Assessment for this month.</p>
                
                <div class="limit-info">
                    <div class="info-item">
                        <h4>📋 Your Current Plan</h4>
                        <p>Continue following your 30-day milestone roadmap. Check your email for daily coaching updates.</p>
                    </div>
                    
                    <div class="info-item">
                        <h4>🎯 Focus on Execution</h4>
                        <p>This month is about implementing your personalized strategy. Track your progress and stay consistent.</p>
                    </div>
                    
                    <div class="info-item">
                        <h4>📈 Next Assessment</h4>
                        <p>Your next Strategic Business Assessment will be available in <strong>' . $next_month . '</strong>.</p>
                    </div>
                </div>
                
                <div class="limit-actions">
                    <a href="/dashboard/" class="btn btn-primary">
                        View Dashboard
                    </a>
                    <a href="/milestones/" class="btn btn-secondary">
                        Track Progress
                    </a>
                </div>
                
                <div style="margin-top: 25px; text-align: center;">
                    <p style="font-size: 0.9em; color: #666;">
                        Need support with your current plan? <a href="/support/">Contact our team</a>
                    </p>
                </div>
            </div>
        </div>';
    }
}

// Initialize the class
Vortex_Artist_Business_Quiz::get_instance(); 