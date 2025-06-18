<?php
/**
 * The artist education functionality of the plugin.
 *
 * @link       https://vortexai.io
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The artist education functionality of the plugin.
 *
 * Handles artist education, workshop scheduling, and certification.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Vortex AI Team
 */
class Vortex_Artist_Education {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->register_hooks();
    }

    /**
     * Register the necessary hooks for the education functionality.
     *
     * @since    1.0.0
     */
    public function register_hooks() {
        // Add education tab to artist dashboard
        add_filter('vortex_artist_dashboard_tabs', array($this, 'add_education_tab'), 10, 1);
        
        // Handle AJAX actions
        add_action('wp_ajax_vortex_schedule_workshop', array($this, 'handle_workshop_scheduling'));
        add_action('wp_ajax_vortex_get_available_workshops', array($this, 'get_available_workshops'));
        add_action('wp_ajax_vortex_get_user_education_status', array($this, 'get_user_education_status'));
        
        // Shortcode for displaying artist education interface
        add_shortcode('vortex_artist_education', array($this, 'render_education_interface'));
    }

    /**
     * Add education tab to artist dashboard.
     *
     * @since    1.0.0
     * @param    array    $tabs    The existing dashboard tabs.
     * @return   array             The modified dashboard tabs.
     */
    public function add_education_tab($tabs) {
        $tabs['education'] = array(
            'title' => __('Education & Workshops', 'vortex-ai-marketplace'),
            'icon' => 'dashicons-welcome-learn-more',
            'content' => $this->get_education_tab_content()
        );
        
        return $tabs;
    }

    /**
     * Get content for the education tab.
     *
     * @since    1.0.0
     * @return   string    The HTML content for the education tab.
     */
    private function get_education_tab_content() {
        ob_start();
        echo do_shortcode('[vortex_artist_education]');
        return ob_get_clean();
    }

    /**
     * Render the artist education interface.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            The rendered HTML.
     */
    public function render_education_interface($atts = array()) {
        // Start buffering
        ob_start();
        
        // Check if user is logged in and is an artist
        if (!is_user_logged_in() || !in_array('vortex_artist', (array) wp_get_current_user()->roles)) {
            echo '<div class="vortex-notice vortex-error">';
            esc_html_e('You must be logged in as an artist to access education resources.', 'vortex-ai-marketplace');
            echo '</div>';
            return ob_get_clean();
        }
        
        // Get user education data
        $user_id = get_current_user_id();
        $education_package = get_user_meta($user_id, 'vortex_education_package', true);
        $workshop_hours_used = (int) get_user_meta($user_id, 'vortex_workshop_hours_used', true);
        $workshop_hours_total = $this->get_package_hours($education_package);
        $workshop_hours_remaining = $workshop_hours_total - $workshop_hours_used;
        $is_certified = (bool) get_user_meta($user_id, 'vortex_is_certified_artist', true);
        $scheduled_workshops = $this->get_user_scheduled_workshops($user_id);
        
        // Include template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/artist-education/dashboard.php';
        
        // Enqueue script with data
        wp_enqueue_script('vortex-artist-education', plugin_dir_url(dirname(__FILE__)) . 'public/js/vortex-artist-education.js', array('jquery'), '1.0.0', true);
        wp_localize_script('vortex-artist-education', 'vortexEducation', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_education_nonce'),
            'hours_remaining' => $workshop_hours_remaining,
            'hours_total' => $workshop_hours_total,
            'user_id' => $user_id
        ));
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Handle workshop scheduling AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_workshop_scheduling() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_education_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Check if user is logged in and is an artist
        if (!is_user_logged_in() || !in_array('vortex_artist', (array) wp_get_current_user()->roles)) {
            wp_send_json_error(array('message' => __('You must be logged in as an artist to schedule workshops.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Get workshop data
        $workshop_id = isset($_POST['workshop_id']) ? intval($_POST['workshop_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $time_slot = isset($_POST['time_slot']) ? sanitize_text_field($_POST['time_slot']) : '';
        
        if (!$workshop_id || !$date || !$time_slot) {
            wp_send_json_error(array('message' => __('Missing required information.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Check if user has hours remaining
        $user_id = get_current_user_id();
        $education_package = get_user_meta($user_id, 'vortex_education_package', true);
        $workshop_hours_used = (int) get_user_meta($user_id, 'vortex_workshop_hours_used', true);
        $workshop_hours_total = $this->get_package_hours($education_package);
        $workshop_duration = $this->get_workshop_duration($workshop_id);
        
        if ($workshop_hours_used + $workshop_duration > $workshop_hours_total) {
            wp_send_json_error(array('message' => __('You do not have enough workshop hours remaining.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Check if workshop is available at the requested time
        $available_workshops = $this->get_available_workshop_times($workshop_id, $date);
        $slot_available = false;
        
        foreach ($available_workshops as $available_slot) {
            if ($available_slot['time'] === $time_slot && $available_slot['available']) {
                $slot_available = true;
                break;
            }
        }
        
        if (!$slot_available) {
            wp_send_json_error(array('message' => __('This workshop time slot is no longer available.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Schedule the workshop
        $schedule_id = $this->schedule_workshop($user_id, $workshop_id, $date, $time_slot);
        
        if (!$schedule_id) {
            wp_send_json_error(array('message' => __('Failed to schedule workshop. Please try again.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Update user's workshop hours used
        update_user_meta($user_id, 'vortex_workshop_hours_used', $workshop_hours_used + $workshop_duration);
        
        // Get workshop details for the response
        $workshop = $this->get_workshop($workshop_id);
        
        wp_send_json_success(array(
            'message' => __('Workshop scheduled successfully!', 'vortex-ai-marketplace'),
            'schedule_id' => $schedule_id,
            'workshop' => $workshop,
            'date' => $date,
            'time_slot' => $time_slot,
            'hours_used' => $workshop_duration,
            'hours_remaining' => $workshop_hours_total - ($workshop_hours_used + $workshop_duration)
        ));
        exit;
    }

    /**
     * Get available workshops AJAX request handler.
     *
     * @since    1.0.0
     */
    public function get_available_workshops() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_education_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Get date if specified
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : date('Y-m-d');
        
        // Get all workshops
        $workshops = $this->get_all_workshops();
        
        // Get availability for each workshop
        foreach ($workshops as &$workshop) {
            $workshop['available_times'] = $this->get_available_workshop_times($workshop['id'], $date);
        }
        
        wp_send_json_success(array(
            'workshops' => $workshops,
            'date' => $date
        ));
        exit;
    }

    /**
     * Get user education status AJAX request handler.
     *
     * @since    1.0.0
     */
    public function get_user_education_status() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_education_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Check if user is logged in and is an artist
        if (!is_user_logged_in() || !in_array('vortex_artist', (array) wp_get_current_user()->roles)) {
            wp_send_json_error(array('message' => __('You must be logged in as an artist to view education status.', 'vortex-ai-marketplace')));
            exit;
        }
        
        $user_id = get_current_user_id();
        $education_package = get_user_meta($user_id, 'vortex_education_package', true);
        $workshop_hours_used = (int) get_user_meta($user_id, 'vortex_workshop_hours_used', true);
        $workshop_hours_total = $this->get_package_hours($education_package);
        $is_certified = (bool) get_user_meta($user_id, 'vortex_is_certified_artist', true);
        $scheduled_workshops = $this->get_user_scheduled_workshops($user_id);
        
        wp_send_json_success(array(
            'education_package' => $education_package,
            'hours_used' => $workshop_hours_used,
            'hours_total' => $workshop_hours_total,
            'hours_remaining' => $workshop_hours_total - $workshop_hours_used,
            'is_certified' => $is_certified,
            'scheduled_workshops' => $scheduled_workshops
        ));
        exit;
    }

    /**
     * Get all available workshops.
     *
     * @since    1.0.0
     * @return   array    List of workshops.
     */
    private function get_all_workshops() {
        // In a real implementation, this would query from a custom post type or database
        // For now, returning sample data
        return array(
            array(
                'id' => 1,
                'title' => 'Digital Art Fundamentals',
                'description' => 'Learn the basics of digital art creation including color theory, composition, and basic tools.',
                'duration' => 2, // hours
                'instructor' => 'Sarah Chen',
                'category' => 'digital',
                'level' => 'beginner',
                'image' => plugin_dir_url(dirname(__FILE__)) . 'public/images/workshop-digital-fundamentals.jpg'
            ),
            array(
                'id' => 2,
                'title' => 'Advanced Illustration Techniques',
                'description' => 'Master advanced illustration techniques for character design, environment creation, and storytelling.',
                'duration' => 3, // hours
                'instructor' => 'Michael Torres',
                'category' => 'illustration',
                'level' => 'advanced',
                'image' => plugin_dir_url(dirname(__FILE__)) . 'public/images/workshop-advanced-illustration.jpg'
            ),
            array(
                'id' => 3,
                'title' => 'AI Prompt Engineering Masterclass',
                'description' => 'Learn how to craft effective prompts for AI art generation and integrate AI tools into your creative workflow.',
                'duration' => 2, // hours
                'instructor' => 'Dr. Elena Patel',
                'category' => 'ai',
                'level' => 'intermediate',
                'image' => plugin_dir_url(dirname(__FILE__)) . 'public/images/workshop-prompt-engineering.jpg'
            ),
            array(
                'id' => 4,
                'title' => 'Portfolio Development & Presentation',
                'description' => 'Develop a professional art portfolio and learn how to present your work effectively to clients and galleries.',
                'duration' => 2, // hours
                'instructor' => 'Robert Wilson',
                'category' => 'business',
                'level' => 'all',
                'image' => plugin_dir_url(dirname(__FILE__)) . 'public/images/workshop-portfolio.jpg'
            ),
            array(
                'id' => 5,
                'title' => 'Traditional to Digital Art Transition',
                'description' => 'Bridge the gap between traditional and digital art mediums, focusing on transferring skills and techniques.',
                'duration' => 3, // hours
                'instructor' => 'Maria Gonzalez',
                'category' => 'mixed',
                'level' => 'intermediate',
                'image' => plugin_dir_url(dirname(__FILE__)) . 'public/images/workshop-traditional-digital.jpg'
            ),
        );
    }

    /**
     * Get a specific workshop by ID.
     *
     * @since    1.0.0
     * @param    int      $workshop_id    The workshop ID.
     * @return   array|null               The workshop data or null if not found.
     */
    private function get_workshop($workshop_id) {
        $workshops = $this->get_all_workshops();
        
        foreach ($workshops as $workshop) {
            if ($workshop['id'] === $workshop_id) {
                return $workshop;
            }
        }
        
        return null;
    }

    /**
     * Get workshop duration in hours.
     *
     * @since    1.0.0
     * @param    int      $workshop_id    The workshop ID.
     * @return   int                      The workshop duration in hours.
     */
    private function get_workshop_duration($workshop_id) {
        $workshop = $this->get_workshop($workshop_id);
        return $workshop ? $workshop['duration'] : 0;
    }

    /**
     * Get available time slots for a workshop on a specific date.
     *
     * @since    1.0.0
     * @param    int      $workshop_id    The workshop ID.
     * @param    string   $date           The date in Y-m-d format.
     * @return   array                    List of available time slots.
     */
    private function get_available_workshop_times($workshop_id, $date) {
        // In a real implementation, this would check database for availability
        // For now, generate sample availability data
        
        // Don't schedule in the past
        $current_date = date('Y-m-d');
        if ($date < $current_date) {
            return array();
        }
        
        // Generate time slots
        $time_slots = array(
            array('time' => '09:00 AM - 11:00 AM', 'available' => true),
            array('time' => '11:30 AM - 01:30 PM', 'available' => true),
            array('time' => '02:00 PM - 04:00 PM', 'available' => true),
            array('time' => '04:30 PM - 06:30 PM', 'available' => true),
            array('time' => '07:00 PM - 09:00 PM', 'available' => true)
        );
        
        // Randomly mark some as unavailable based on date and workshop ID
        $seed = intval(str_replace('-', '', $date)) + $workshop_id;
        srand($seed);
        
        foreach ($time_slots as $key => $slot) {
            // 30% chance of being unavailable
            if (rand(1, 10) <= 3) {
                $time_slots[$key]['available'] = false;
            }
        }
        
        return $time_slots;
    }

    /**
     * Schedule a workshop for a user.
     *
     * @since    1.0.0
     * @param    int      $user_id        The user ID.
     * @param    int      $workshop_id    The workshop ID.
     * @param    string   $date           The date in Y-m-d format.
     * @param    string   $time_slot      The time slot.
     * @return   int|false                The schedule ID or false on failure.
     */
    private function schedule_workshop($user_id, $workshop_id, $date, $time_slot) {
        // In a real implementation, this would create an entry in the database
        // For now, store in user meta as serialized data
        
        $workshop = $this->get_workshop($workshop_id);
        if (!$workshop) {
            return false;
        }
        
        $scheduled_workshops = get_user_meta($user_id, 'vortex_scheduled_workshops', true);
        if (!is_array($scheduled_workshops)) {
            $scheduled_workshops = array();
        }
        
        $schedule_id = time() . rand(100, 999); // Simple unique ID
        
        $scheduled_workshops[] = array(
            'id' => $schedule_id,
            'workshop_id' => $workshop_id,
            'title' => $workshop['title'],
            'instructor' => $workshop['instructor'],
            'date' => $date,
            'time_slot' => $time_slot,
            'duration' => $workshop['duration'],
            'status' => 'scheduled',
            'scheduled_at' => current_time('mysql')
        );
        
        update_user_meta($user_id, 'vortex_scheduled_workshops', $scheduled_workshops);
        
        return $schedule_id;
    }

    /**
     * Get all scheduled workshops for a user.
     *
     * @since    1.0.0
     * @param    int      $user_id    The user ID.
     * @return   array                List of scheduled workshops.
     */
    private function get_user_scheduled_workshops($user_id) {
        $scheduled_workshops = get_user_meta($user_id, 'vortex_scheduled_workshops', true);
        
        if (!is_array($scheduled_workshops)) {
            return array();
        }
        
        // Sort by date (ascending)
        usort($scheduled_workshops, function($a, $b) {
            $date_a = strtotime($a['date']);
            $date_b = strtotime($b['date']);
            return $date_a - $date_b;
        });
        
        return $scheduled_workshops;
    }

    /**
     * Get the total workshop hours for a package.
     *
     * @since    1.0.0
     * @param    string   $package    The package name.
     * @return   int                  The total hours.
     */
    private function get_package_hours($package) {
        // Default to standard package (72 hours per semester)
        if (empty($package)) {
            return 72;
        }
        
        // Package hours mapping
        $package_hours = array(
            'standard' => 72,    // Standard 72 hours per semester
            'premium' => 120,    // Premium package with more hours
            'professional' => 180 // Professional package with maximum hours
        );
        
        return isset($package_hours[$package]) ? $package_hours[$package] : 72;
    }
}

// Initialize the class
$vortex_artist_education = new Vortex_Artist_Education(); 