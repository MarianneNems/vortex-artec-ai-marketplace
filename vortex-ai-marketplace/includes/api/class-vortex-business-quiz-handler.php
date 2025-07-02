<?php
/**
 * VORTEX Business Quiz API Handler
 *
 * @package Vortex_AI_Marketplace
 * @subpackage API
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Vortex_Business_Quiz_Handler
 * 
 * Handles REST API endpoints for the artist business quiz
 */
class Vortex_Business_Quiz_Handler {

    /**
     * Instance of this class
     *
     * @var Vortex_Business_Quiz_Handler
     */
    private static $instance = null;

    /**
     * Database table name for quiz responses
     *
     * @var string
     */
    private $quiz_table;

    /**
     * Get instance of this class
     *
     * @return Vortex_Business_Quiz_Handler
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
        global $wpdb;
        $this->quiz_table = $wpdb->prefix . 'vortex_quiz';
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_loaded', array($this, 'create_database_table'));
        add_action('vortex_generate_roadmap', array($this, 'generate_roadmap'), 10, 1);
        add_action('vortex_daily_quiz_reminder', array($this, 'send_daily_reminder'), 10, 1);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('vortex/v1', '/business-quiz', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_quiz_submission' ],
            'permission_callback' => function() {
                return current_user_can('edit_posts') && $this->check_rest_nonce('vortex_quiz');
            }
        ]);
    }

    /**
     * Check REST nonce
     *
     * @param string $action
     * @return bool
     */
    private function check_rest_nonce($action) {
        $nonce = null;
        
        if (isset($_SERVER['HTTP_X_WP_NONCE'])) {
            $nonce = $_SERVER['HTTP_X_WP_NONCE'];
        } elseif (isset($_REQUEST['_wpnonce'])) {
            $nonce = $_REQUEST['_wpnonce'];
        }
        
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Handle quiz submission
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_quiz_submission($request) {
        global $wpdb;

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'user_not_logged_in',
                __('You must be logged in to submit the quiz.', 'vortex-ai-marketplace'),
                array('status' => 403)
            );
        }

        // Get request data
        $data = $request->get_json_params();
        
        if (!$data) {
            $data = $request->get_params();
        }

        // Validate required inputs
        $validation_errors = $this->validate_quiz_inputs($data);
        if (!empty($validation_errors)) {
            return new WP_Error(
                'validation_failed',
                implode(' ', $validation_errors),
                array('status' => 400)
            );
        }

        // Check if user has already submitted quiz
        $existing_quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->quiz_table} WHERE user_id = %d",
            $user_id
        ));

        if ($existing_quiz) {
            return new WP_Error(
                'quiz_already_submitted',
                __('You have already submitted the business quiz.', 'vortex-ai-marketplace'),
                array('status' => 400)
            );
        }

        // Prepare quiz data for database
        $quiz_data = array(
            'user_id' => $user_id,
            'dob' => sanitize_text_field($data['dob']),
            'pob' => sanitize_text_field($data['pob']),
            'tob' => sanitize_text_field($data['tob']),
            'answers' => wp_json_encode($data['answers']),
            'submission_timestamp' => current_time('mysql'),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'status' => 'completed'
        );

        // Insert quiz data into database
        $result = $wpdb->insert($this->quiz_table, $quiz_data);

        if ($result === false) {
            return new WP_Error(
                'database_error',
                __('Failed to save quiz data. Please try again.', 'vortex-ai-marketplace'),
                array('status' => 500)
            );
        }

        // Update user meta
        update_user_meta($user_id, 'vortex_quiz_completed', true);
        update_user_meta($user_id, 'vortex_quiz_completion_date', current_time('mysql'));

        // Schedule roadmap generation (immediate)
        wp_schedule_single_event(time(), 'vortex_generate_roadmap', array($user_id));

        // Schedule daily reminders for 30 days at 9:00 AM user timezone
        $this->schedule_daily_reminders($user_id);

        // Trigger completion actions
        do_action('vortex_quiz_completed', $user_id, $data);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Quiz submitted. Horace will craft your 30-day roadmap and daily guidance PDF via email.'
        ), 200);
    }

    /**
     * Validate quiz inputs
     *
     * @param array $data
     * @return array
     */
    private function validate_quiz_inputs($data) {
        $errors = array();

        // Validate DOB
        if (empty($data['dob'])) {
            $errors[] = 'Date of birth is required.';
        } elseif (!$this->validate_date($data['dob'])) {
            $errors[] = 'Invalid date of birth format.';
        }

        // Validate POB
        if (empty($data['pob'])) {
            $errors[] = 'Place of birth is required.';
        }

        // Validate TOB
        if (empty($data['tob'])) {
            $errors[] = 'Time of birth is required.';
        }

        // Validate answers
        if (empty($data['answers']) || !is_array($data['answers'])) {
            $errors[] = 'Quiz answers are required.';
        } else {
            // Check minimum number of questions answered
            if (count($data['answers']) < 10) {
                $errors[] = 'Please answer all quiz questions.';
            }
        }

        return $errors;
    }

    /**
     * Validate date format
     *
     * @param string $date
     * @return bool
     */
    private function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
    }

    /**
     * Schedule daily reminders for 30 days
     *
     * @param int $user_id
     */
    private function schedule_daily_reminders($user_id) {
        // Get user timezone (default to UTC if not set)
        $timezone = get_user_meta($user_id, 'timezone', true);
        if (!$timezone) {
            $timezone = 'UTC';
        }

        // Calculate 9:00 AM in user timezone for next 30 days
        $user_timezone = new DateTimeZone($timezone);
        $utc_timezone = new DateTimeZone('UTC');

        for ($day = 1; $day <= 30; $day++) {
            // Create 9:00 AM datetime for this day in user timezone
            $reminder_time = new DateTime("now +{$day} days", $user_timezone);
            $reminder_time->setTime(9, 0, 0);
            
            // Convert to UTC for scheduling
            $reminder_time->setTimezone($utc_timezone);
            
            // Schedule the reminder
            wp_schedule_single_event(
                $reminder_time->getTimestamp(),
                'vortex_daily_quiz_reminder',
                array($user_id, $day)
            );
        }
    }

    /**
     * Generate roadmap and PDF
     *
     * @param int $user_id
     */
    public function generate_roadmap($user_id) {
        global $wpdb;

        // Get quiz data
        $quiz_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->quiz_table} WHERE user_id = %d",
            $user_id
        ));

        if (!$quiz_data) {
            error_log("VORTEX: No quiz data found for user {$user_id}");
            return;
        }

        // Get user data
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            error_log("VORTEX: User {$user_id} not found");
            return;
        }

        // Generate roadmap content based on quiz responses
        $roadmap_content = $this->create_roadmap_content($user, $quiz_data);

        // Generate PDF
        $pdf_path = $this->generate_roadmap_pdf($user, $roadmap_content);

        if ($pdf_path) {
            // Send email with PDF attachment
            $this->send_roadmap_email($user, $pdf_path);
            
            // Store roadmap data in user meta
            update_user_meta($user_id, 'vortex_roadmap_generated', true);
            update_user_meta($user_id, 'vortex_roadmap_content', $roadmap_content);
            update_user_meta($user_id, 'vortex_roadmap_pdf_path', $pdf_path);
        }
    }

    /**
     * Create roadmap content based on user responses
     *
     * @param WP_User $user
     * @param object $quiz_data
     * @return array
     */
    private function create_roadmap_content($user, $quiz_data) {
        $answers = json_decode($quiz_data->answers, true);
        
        // Analyze birth date for astrological insights
        $birth_date = new DateTime($quiz_data->dob);
        $zodiac_sign = $this->calculate_zodiac_sign($birth_date);
        
        // Generate 30-day roadmap
        $roadmap = array(
            'user_name' => $user->display_name,
            'generation_date' => current_time('F j, Y'),
            'zodiac_sign' => $zodiac_sign,
            'birth_info' => array(
                'date' => $birth_date->format('F j, Y'),
                'place' => $quiz_data->pob,
                'time' => $quiz_data->tob
            ),
            'personality_analysis' => $this->analyze_personality($answers, $zodiac_sign),
            'business_strengths' => $this->identify_business_strengths($answers, $zodiac_sign),
            'daily_actions' => $this->generate_daily_actions($answers, $zodiac_sign),
            'weekly_milestones' => $this->generate_weekly_milestones($answers),
            'inspirational_quotes' => $this->get_inspirational_quotes($zodiac_sign)
        );

        return $roadmap;
    }

    /**
     * Calculate zodiac sign from birth date
     *
     * @param DateTime $birth_date
     * @return string
     */
    private function calculate_zodiac_sign($birth_date) {
        $month = (int) $birth_date->format('n');
        $day = (int) $birth_date->format('j');

        $zodiac_signs = array(
            array('sign' => 'Capricorn', 'start' => array(12, 22), 'end' => array(1, 19)),
            array('sign' => 'Aquarius', 'start' => array(1, 20), 'end' => array(2, 18)),
            array('sign' => 'Pisces', 'start' => array(2, 19), 'end' => array(3, 20)),
            array('sign' => 'Aries', 'start' => array(3, 21), 'end' => array(4, 19)),
            array('sign' => 'Taurus', 'start' => array(4, 20), 'end' => array(5, 20)),
            array('sign' => 'Gemini', 'start' => array(5, 21), 'end' => array(6, 20)),
            array('sign' => 'Cancer', 'start' => array(6, 21), 'end' => array(7, 22)),
            array('sign' => 'Leo', 'start' => array(7, 23), 'end' => array(8, 22)),
            array('sign' => 'Virgo', 'start' => array(8, 23), 'end' => array(9, 22)),
            array('sign' => 'Libra', 'start' => array(9, 23), 'end' => array(10, 22)),
            array('sign' => 'Scorpio', 'start' => array(10, 23), 'end' => array(11, 21)),
            array('sign' => 'Sagittarius', 'start' => array(11, 22), 'end' => array(12, 21))
        );

        foreach ($zodiac_signs as $sign_data) {
            if ($this->is_date_in_range($month, $day, $sign_data['start'], $sign_data['end'])) {
                return $sign_data['sign'];
            }
        }

        return 'Leo'; // Default fallback
    }

    /**
     * Check if date is in zodiac range
     *
     * @param int $month
     * @param int $day
     * @param array $start
     * @param array $end
     * @return bool
     */
    private function is_date_in_range($month, $day, $start, $end) {
        list($start_month, $start_day) = $start;
        list($end_month, $end_day) = $end;

        if ($start_month === $end_month) {
            return $month === $start_month && $day >= $start_day && $day <= $end_day;
        } else {
            return ($month === $start_month && $day >= $start_day) || 
                   ($month === $end_month && $day <= $end_day);
        }
    }

    /**
     * Generate PDF from roadmap content
     *
     * @param WP_User $user
     * @param array $roadmap_content
     * @return string|false
     */
    private function generate_roadmap_pdf($user, $roadmap_content) {
        // Check if TCPDF is available, otherwise use DomPDF
        if (class_exists('TCPDF')) {
            return $this->generate_pdf_tcpdf($user, $roadmap_content);
        } else {
            return $this->generate_pdf_dompdf($user, $roadmap_content);
        }
    }

    /**
     * Generate PDF using TCPDF
     *
     * @param WP_User $user
     * @param array $roadmap_content
     * @return string|false
     */
    private function generate_pdf_tcpdf($user, $roadmap_content) {
        require_once(ABSPATH . 'wp-content/plugins/tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('VORTEX AI Marketplace');
        $pdf->SetAuthor('Horace - VORTEX AI');
        $pdf->SetTitle('30-Day Artist Business Roadmap - ' . $user->display_name);
        $pdf->SetSubject('Personalized Business Strategy');

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Add a page
        $pdf->AddPage();

        // Generate HTML content
        $html = $this->generate_roadmap_html($roadmap_content);
        
        // Write HTML to PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Save PDF to uploads directory
        $upload_dir = wp_upload_dir();
        $pdf_filename = 'vortex-roadmap-' . $user->ID . '-' . date('Y-m-d') . '.pdf';
        $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;
        
        $pdf->Output($pdf_path, 'F');

        return file_exists($pdf_path) ? $pdf_path : false;
    }

    /**
     * Generate PDF using DomPDF (fallback)
     *
     * @param WP_User $user
     * @param array $roadmap_content
     * @return string|false
     */
    private function generate_pdf_dompdf($user, $roadmap_content) {
        // For now, create a simple text-based PDF alternative
        $upload_dir = wp_upload_dir();
        $pdf_filename = 'vortex-roadmap-' . $user->ID . '-' . date('Y-m-d') . '.html';
        $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;
        
        $html = $this->generate_roadmap_html($roadmap_content);
        
        if (file_put_contents($pdf_path, $html)) {
            return $pdf_path;
        }
        
        return false;
    }

    /**
     * Generate HTML content for roadmap
     *
     * @param array $roadmap_content
     * @return string
     */
    private function generate_roadmap_html($roadmap_content) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>30-Day Artist Business Roadmap</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { text-align: center; border-bottom: 3px solid #007cba; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #007cba; font-size: 28px; margin-bottom: 10px; }
                .zodiac-info { background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 20px 0; }
                .section { margin: 25px 0; }
                .section h2 { color: #007cba; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
                .daily-action { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #28a745; }
                .milestone { background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ffc107; }
                .quote { font-style: italic; text-align: center; background: #e7f3ff; padding: 15px; border-radius: 6px; margin: 15px 0; }
                ul { padding-left: 20px; }
                li { margin: 8px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🌟 Your 30-Day Artist Business Roadmap</h1>
                <h2>Crafted by Horace for <?php echo esc_html($roadmap_content['user_name']); ?></h2>
                <p>Generated on <?php echo esc_html($roadmap_content['generation_date']); ?></p>
            </div>

            <div class="zodiac-info">
                <h3>✨ Your Cosmic Business Profile</h3>
                <p><strong>Zodiac Sign:</strong> <?php echo esc_html($roadmap_content['zodiac_sign']); ?></p>
                <p><strong>Born:</strong> <?php echo esc_html($roadmap_content['birth_info']['date']); ?> at <?php echo esc_html($roadmap_content['birth_info']['time']); ?> in <?php echo esc_html($roadmap_content['birth_info']['place']); ?></p>
            </div>

            <div class="section">
                <h2>🎯 Your Business Personality Analysis</h2>
                <p><?php echo esc_html($roadmap_content['personality_analysis']); ?></p>
            </div>

            <div class="section">
                <h2>💪 Your Key Business Strengths</h2>
                <ul>
                    <?php foreach ($roadmap_content['business_strengths'] as $strength): ?>
                        <li><?php echo esc_html($strength); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="section">
                <h2>📅 Your 30-Day Action Plan</h2>
                <?php foreach ($roadmap_content['daily_actions'] as $day => $action): ?>
                    <div class="daily-action">
                        <strong>Day <?php echo $day; ?>:</strong> <?php echo esc_html($action); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="section">
                <h2>🏆 Weekly Milestones</h2>
                <?php foreach ($roadmap_content['weekly_milestones'] as $week => $milestone): ?>
                    <div class="milestone">
                        <strong>Week <?php echo $week; ?>:</strong> <?php echo esc_html($milestone); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="section">
                <h2>🌟 Daily Inspiration</h2>
                <?php foreach ($roadmap_content['inspirational_quotes'] as $quote): ?>
                    <div class="quote">"<?php echo esc_html($quote); ?>"</div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 40px; text-align: center; border-top: 2px solid #007cba; padding-top: 20px;">
                <p><strong>Remember:</strong> This roadmap is your guide, but trust your intuition and adapt as needed.</p>
                <p>Your artistic journey is unique - let your creativity lead the way! 🎨</p>
                <p style="color: #007cba; font-weight: bold;">- Horace, Your AI Business Mentor</p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Send roadmap email with PDF attachment
     *
     * @param WP_User $user
     * @param string $pdf_path
     */
    private function send_roadmap_email($user, $pdf_path) {
        $subject = 'Your 30-Day Artist Business Roadmap is Ready! 🚀';
        
        $message = "
        <h2>🌟 Your Personalized Roadmap Awaits!</h2>
        <p>Dear {$user->display_name},</p>
        
        <p>Horace has completed your personalized 30-day Artist Business Roadmap! This comprehensive guide is tailored specifically to your responses, birth chart, and artistic goals.</p>
        
        <div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>📋 What's in Your Roadmap:</h3>
            <ul>
                <li>🎯 Personalized business personality analysis</li>
                <li>💪 Your unique artistic business strengths</li>
                <li>📅 30 days of targeted daily actions</li>
                <li>🏆 Weekly milestone checkpoints</li>
                <li>✨ Zodiac-aligned business strategies</li>
                <li>🌟 Daily inspirational guidance</li>
            </ul>
        </div>
        
        <p><strong>Your roadmap is attached as a PDF.</strong> Print it out, save it to your device, or reference it daily as you build your artistic business empire!</p>
        
        <div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
            <h4>🔔 Daily Reminders Starting Tomorrow</h4>
            <p>You'll receive daily motivational emails at 9:00 AM with specific guidance for each day of your journey. These bite-sized actions will keep you on track and motivated!</p>
        </div>
        
        <p>Remember, this roadmap is your North Star, but trust your artistic intuition and adapt as your journey unfolds.</p>
        
        <p>Here's to your artistic success! 🎨</p>
        
        <p><strong>Horace</strong><br>
        Your AI Business Mentor<br>
        VORTEX AI Marketplace</p>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $attachments = array($pdf_path);

        wp_mail($user->user_email, $subject, $message, $headers, $attachments);
    }

    /**
     * Send daily reminder
     *
     * @param int $user_id
     * @param int $day_number
     */
    public function send_daily_reminder($user_id, $day_number) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }

        // Get roadmap content
        $roadmap_content = get_user_meta($user_id, 'vortex_roadmap_content', true);
        if (!$roadmap_content) {
            return;
        }

        $daily_action = isset($roadmap_content['daily_actions'][$day_number]) ? 
                       $roadmap_content['daily_actions'][$day_number] : 
                       'Focus on your artistic growth and trust your creative instincts.';

        $subject = "Day {$day_number}: Your Daily Artist Business Guidance 🌟";
        
        $message = "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h2 style='margin: 0; font-size: 24px;'>Day {$day_number} of Your Journey</h2>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>Daily guidance from Horace</p>
            </div>
            
            <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                <h3 style='color: #333; margin-top: 0;'>🎯 Today's Focus:</h3>
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007cba; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 16px; line-height: 1.6; color: #333;'>{$daily_action}</p>
                </div>
                
                <div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0; font-style: italic; text-align: center; color: #0066cc;'>
                        \"Every great artist was first an amateur. Take today's step with confidence!\"
                    </p>
                </div>
                
                <div style='text-align: center; margin: 25px 0;'>
                    <p style='color: #666; margin-bottom: 15px;'>Track your progress and stay motivated:</p>
                    <a href='" . home_url('/dashboard/roadmap/') . "' style='background: #007cba; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>
                        View Full Roadmap
                    </a>
                </div>
                
                <hr style='border: none; border-top: 1px solid #eee; margin: 25px 0;'>
                
                <p style='text-align: center; color: #666; font-size: 14px; margin: 0;'>
                    You're " . (30 - $day_number) . " days away from completing your transformation!<br>
                    Keep going, {$user->display_name} - your artistic business awaits! 🚀
                </p>
            </div>
        </div>
        ";

        wp_mail($user->user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Analyze personality based on quiz responses
     *
     * @param array $answers
     * @param string $zodiac_sign
     * @return string
     */
    private function analyze_personality($answers, $zodiac_sign) {
        // Simple personality analysis based on answer patterns
        $analysis = "As a {$zodiac_sign}, you bring unique cosmic energy to your artistic business. ";
        
        // Analyze answer patterns (a, b, c, d choices)
        $answer_counts = array_count_values($answers);
        $dominant_pattern = array_search(max($answer_counts), $answer_counts);
        
        switch ($dominant_pattern) {
            case 'a':
                $analysis .= "Your responses show you're a natural innovator with leadership qualities. You prefer taking initiative and aren't afraid to be first.";
                break;
            case 'b':
                $analysis .= "Your responses indicate you're methodical and practical. You build solid foundations and prefer sustainable growth over quick wins.";
                break;
            case 'c':
                $analysis .= "Your responses reveal strong communication and collaboration skills. You thrive in community and value relationships in business.";
                break;
            case 'd':
                $analysis .= "Your responses show deep intuition and emotional intelligence. You create meaningful connections and transformative experiences.";
                break;
            default:
                $analysis .= "Your balanced responses show versatility and adaptability in your approach to business.";
        }
        
        return $analysis;
    }

    /**
     * Identify business strengths
     *
     * @param array $answers
     * @param string $zodiac_sign
     * @return array
     */
    private function identify_business_strengths($answers, $zodiac_sign) {
        $strengths = array();
        
        // Base strengths on zodiac sign
        $zodiac_strengths = array(
            'Aries' => array('Leadership', 'Innovation', 'Quick Decision Making'),
            'Taurus' => array('Persistence', 'Quality Focus', 'Financial Wisdom'),
            'Gemini' => array('Communication', 'Adaptability', 'Networking'),
            'Cancer' => array('Intuition', 'Customer Care', 'Emotional Intelligence'),
            'Leo' => array('Creativity', 'Personal Branding', 'Inspiration'),
            'Virgo' => array('Attention to Detail', 'Organization', 'Service Excellence'),
            'Libra' => array('Balance', 'Aesthetic Sense', 'Partnership Building'),
            'Scorpio' => array('Intensity', 'Transformation', 'Deep Research'),
            'Sagittarius' => array('Vision', 'Adventure', 'Teaching Ability'),
            'Capricorn' => array('Ambition', 'Structure', 'Long-term Planning'),
            'Aquarius' => array('Innovation', 'Community Building', 'Unique Perspective'),
            'Pisces' => array('Intuition', 'Creativity', 'Empathy')
        );
        
        $strengths = isset($zodiac_strengths[$zodiac_sign]) ? $zodiac_strengths[$zodiac_sign] : array('Creativity', 'Passion', 'Dedication');
        
        // Add 2-3 additional strengths based on answer patterns
        $answer_counts = array_count_values($answers);
        if ($answer_counts['a'] ?? 0 > 3) $strengths[] = 'Bold Initiative';
        if ($answer_counts['b'] ?? 0 > 3) $strengths[] = 'Practical Wisdom';
        if ($answer_counts['c'] ?? 0 > 3) $strengths[] = 'Social Connection';
        if ($answer_counts['d'] ?? 0 > 3) $strengths[] = 'Emotional Depth';
        
        return array_unique($strengths);
    }

    /**
     * Generate daily actions
     *
     * @param array $answers
     * @param string $zodiac_sign
     * @return array
     */
    private function generate_daily_actions($answers, $zodiac_sign) {
        $actions = array();
        
        // Base actions for 30 days
        $base_actions = array(
            1 => "Set up your dedicated creative workspace and define your artistic mission statement",
            2 => "Research your ideal customer and create a detailed avatar of your perfect collector",
            3 => "Audit your current portfolio and identify your strongest pieces",
            4 => "Create a simple website or update your online presence with your best work",
            5 => "Write compelling descriptions for your top 5 artworks",
            6 => "Research pricing strategies and set prices for your current pieces",
            7 => "Week 1 Review: Reflect on your progress and adjust your workspace setup",
            8 => "Connect with 3 potential customers or fellow artists on social media",
            9 => "Create one new piece inspired by your target audience's preferences",
            10 => "Write a blog post or social media content about your artistic process",
            11 => "Research local galleries, markets, or online platforms for potential sales",
            12 => "Organize your digital files and create a backup system for your work",
            13 => "Practice your artist elevator pitch - 30 seconds about who you are and what you create",
            14 => "Week 2 Review: Assess your online presence and customer connections",
            15 => "Create a simple marketing calendar for the next month",
            16 => "Experiment with a new technique or medium to expand your artistic range",
            17 => "Reach out to one potential collaboration partner or mentor",
            18 => "Document your artistic process with photos or videos for social media",
            19 => "Research and apply to one exhibition, market, or online gallery",
            20 => "Create a simple budget for your art business expenses and projected income",
            21 => "Week 3 Review: Evaluate your marketing efforts and artistic growth",
            22 => "Launch a small promotion or special offer for your artwork",
            23 => "Write testimonials requests to past customers or create case studies",
            24 => "Plan and schedule your next major art project or series",
            25 => "Update your portfolio with recent work and refresh your artist statement",
            26 => "Network at a local art event or join an online artist community",
            27 => "Create a simple email list signup and send your first newsletter",
            28 => "Week 4 Review: Analyze what worked best and plan your next month",
            29 => "Set three specific goals for your next 30-day period",
            30 => "Celebrate your progress and plan a reward for completing your journey!"
        );
        
        return $base_actions;
    }

    /**
     * Generate weekly milestones
     *
     * @param array $answers
     * @return array
     */
    private function generate_weekly_milestones($answers) {
        return array(
            1 => "Foundation Week: Workspace setup, mission clarity, and portfolio audit completed",
            2 => "Connection Week: Online presence established and customer connections made",
            3 => "Growth Week: New artwork created, marketing planned, and opportunities pursued",
            4 => "Launch Week: Promotion executed, network expanded, and future goals set"
        );
    }

    /**
     * Get inspirational quotes based on zodiac sign
     *
     * @param string $zodiac_sign
     * @return array
     */
    private function get_inspirational_quotes($zodiac_sign) {
        $quotes = array(
            "Your art is not what you do, it's who you are. Trust the process.",
            "Every artist was first an amateur. Every expert was once a beginner.",
            "Creativity takes courage. Your unique voice matters.",
            "Success is not the key to happiness. Happiness is the key to success.",
            "The way to get started is to quit talking and begin doing."
        );
        
        return $quotes;
    }

    /**
     * Create database table for quiz responses
     */
    public function create_database_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->quiz_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            dob date NOT NULL,
            pob varchar(255) NOT NULL,
            tob time NOT NULL,
            answers longtext NOT NULL,
            submission_timestamp datetime NOT NULL,
            ip_address varchar(45) DEFAULT '',
            user_agent text DEFAULT '',
            status enum('completed','partial','archived') DEFAULT 'completed',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY submission_timestamp (submission_timestamp),
            KEY status (status),
            UNIQUE KEY unique_user_completion (user_id, status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the class
Vortex_Business_Quiz_Handler::get_instance();