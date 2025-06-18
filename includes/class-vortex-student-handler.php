<?php
/**
 * Student Application and Registration Handler
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @link       https://www.vortexaimarketplace.com
 * @since      1.0.0
 */

/**
 * Student Application and Registration Handler
 *
 * This class handles all student application processing and registration functions.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Vortex Team
 */
class Vortex_Student_Handler {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Register all hooks for this class.
     *
     * @since    1.0.0
     */
    private function init_hooks() {
        // Form submission handlers
        add_action('wp_ajax_vortex_submit_student_application', array($this, 'handle_student_application'));
        add_action('wp_ajax_nopriv_vortex_submit_student_application', array($this, 'handle_student_application'));
        
        add_action('wp_ajax_vortex_register_student', array($this, 'handle_student_registration'));
        add_action('wp_ajax_nopriv_vortex_register_student', array($this, 'handle_student_registration'));
        
        add_action('wp_ajax_vortex_check_student_status', array($this, 'check_student_status'));
        add_action('wp_ajax_nopriv_vortex_check_student_status', array($this, 'check_student_status'));
        
        // Add shortcode for student application form
        add_shortcode('vortex_student_application_form', array($this, 'render_student_application_form'));
        
        // Add meta boxes for admin
        add_action('add_meta_boxes', array($this, 'add_student_meta_boxes'));
        
        // Add student column to users list
        add_filter('manage_users_columns', array($this, 'add_student_status_column'));
        add_filter('manage_users_custom_column', array($this, 'populate_student_status_column'), 10, 3);
        
        // Schedule daily verification check
        if (!wp_next_scheduled('vortex_daily_student_verification_check')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_student_verification_check');
        }
        add_action('vortex_daily_student_verification_check', array($this, 'check_expired_verifications'));
    }
    
    /**
     * Handle student application submission
     *
     * @since    1.0.0
     */
    public function handle_student_application() {
        // Verify nonce
        if (!isset($_POST['vortex_student_application_nonce']) || 
            !wp_verify_nonce($_POST['vortex_student_application_nonce'], 'vortex_student_application_action')) {
            wp_send_json_error(array('message' => 'Security verification failed.'));
            return;
        }
        
        // Validate required fields
        $required_fields = array('name', 'email', 'institution', 'program', 'graduation_year');
        $errors = array();
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode(' ', $errors)));
            return;
        }
        
        // Sanitize input data
        $application_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'institution' => sanitize_text_field($_POST['institution']),
            'program' => sanitize_text_field($_POST['program']),
            'graduation_year' => intval($_POST['graduation_year']),
            'student_id' => isset($_POST['student_id']) ? sanitize_text_field($_POST['student_id']) : '',
            'application_date' => current_time('mysql'),
            'status' => 'pending'
        );
        
        // Check if this email already has an application
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s AND status IN ('pending', 'approved')",
            $application_data['email']
        ));
        
        if ($existing > 0) {
            wp_send_json_error(array('message' => 'An application with this email already exists.'));
            return;
        }
        
        // Handle file upload for student ID verification
        $student_id_file = '';
        if (!empty($_FILES['student_id_verification']['name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            
            $upload_overrides = array('test_form' => false);
            $uploaded_file = wp_handle_upload($_FILES['student_id_verification'], $upload_overrides);
            
            if (isset($uploaded_file['error'])) {
                wp_send_json_error(array('message' => 'File upload error: ' . $uploaded_file['error']));
                return;
            } else {
                $student_id_file = $uploaded_file['url'];
                $application_data['student_id_verification'] = $student_id_file;
            }
        }
        
        // Insert application into database
        $result = $wpdb->insert($table_name, $application_data);
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to submit application. Please try again.'));
            return;
        }
        
        // Send notification email to admin
        $admin_email = get_option('admin_email');
        $subject = 'New Student Application Received';
        $message = "A new student application has been submitted:\n\n";
        $message .= "Name: {$application_data['name']}\n";
        $message .= "Email: {$application_data['email']}\n";
        $message .= "Institution: {$application_data['institution']}\n";
        $message .= "Program: {$application_data['program']}\n";
        $message .= "Graduation Year: {$application_data['graduation_year']}\n";
        
        wp_mail($admin_email, $subject, $message);
        
        // Send confirmation email to applicant
        $subject = 'Your Student Application Has Been Received';
        $message = "Dear {$application_data['name']},\n\n";
        $message .= "Thank you for submitting your student application to Vortex AI Marketplace. ";
        $message .= "We have received your information and will review it shortly. ";
        $message .= "You will be notified once your application has been processed.\n\n";
        $message .= "Best regards,\n";
        $message .= "The Vortex AI Marketplace Team";
        
        wp_mail($application_data['email'], $subject, $message);
        
        wp_send_json_success(array(
            'message' => 'Your application has been submitted successfully. We will review it shortly.'
        ));
    }
    
    /**
     * Handle student registration
     *
     * @since    1.0.0
     */
    public function handle_student_registration() {
        // Verify nonce
        if (!isset($_POST['vortex_student_registration_nonce']) || 
            !wp_verify_nonce($_POST['vortex_student_registration_nonce'], 'vortex_student_registration_action')) {
            wp_send_json_error(array('message' => 'Security verification failed.'));
            return;
        }
        
        // Validate required fields
        $required_fields = array('username', 'email', 'password', 'institution', 'program', 'graduation_year');
        $errors = array();
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode(' ', $errors)));
            return;
        }
        
        // Check if student has been pre-approved
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        
        $approved = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s AND status = 'approved'",
            sanitize_email($_POST['email'])
        ));
        
        // If not pre-approved, create a pending application
        if ($approved == 0) {
            $application_data = array(
                'name' => sanitize_text_field($_POST['first_name'] . ' ' . $_POST['last_name']),
                'email' => sanitize_email($_POST['email']),
                'institution' => sanitize_text_field($_POST['institution']),
                'program' => sanitize_text_field($_POST['program']),
                'graduation_year' => intval($_POST['graduation_year']),
                'application_date' => current_time('mysql'),
                'status' => 'pending'
            );
            
            $wpdb->insert($table_name, $application_data);
        }
        
        // Create the user
        $userdata = array(
            'user_login' => sanitize_user($_POST['username']),
            'user_email' => sanitize_email($_POST['email']),
            'user_pass' => $_POST['password'], // Password is sanitized by wp_insert_user
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'role' => 'subscriber' // Default role
        );
        
        $user_id = wp_insert_user($userdata);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            return;
        }
        
        // Save student meta data
        update_user_meta($user_id, 'vortex_institution', sanitize_text_field($_POST['institution']));
        update_user_meta($user_id, 'vortex_program', sanitize_text_field($_POST['program']));
        update_user_meta($user_id, 'vortex_graduation_year', intval($_POST['graduation_year']));
        update_user_meta($user_id, 'vortex_student_status', $approved > 0 ? 'approved' : 'pending');
        
        // If pre-approved, assign student role
        if ($approved > 0) {
            $user = new WP_User($user_id);
            $user->add_role('vortex_student');
            
            // Send welcome email
            $subject = 'Welcome to Vortex AI Marketplace - Student Account Activated';
            $message = "Dear {$_POST['first_name']},\n\n";
            $message .= "Congratulations! Your student account has been activated on Vortex AI Marketplace. ";
            $message .= "You now have access to student-exclusive features and discounts.\n\n";
            $message .= "Login to your account at " . home_url('/login') . "\n\n";
            $message .= "Best regards,\n";
            $message .= "The Vortex AI Marketplace Team";
            
            wp_mail($_POST['email'], $subject, $message);
            
            wp_send_json_success(array(
                'message' => 'Registration successful! Your student account has been activated.',
                'redirect' => home_url('/student-dashboard')
            ));
        } else {
            // Send pending notification
            $subject = 'Vortex AI Marketplace - Student Verification Pending';
            $message = "Dear {$_POST['first_name']},\n\n";
            $message .= "Thank you for registering with Vortex AI Marketplace. ";
            $message .= "Your student status is currently pending verification. ";
            $message .= "We will review your information and notify you once your student account has been approved.\n\n";
            $message .= "Best regards,\n";
            $message .= "The Vortex AI Marketplace Team";
            
            wp_mail($_POST['email'], $subject, $message);
            
            wp_send_json_success(array(
                'message' => 'Registration successful! Your student status is pending verification.',
                'redirect' => home_url('/my-account')
            ));
        }
    }
    
    /**
     * Render student application form via shortcode
     *
     * @since    1.0.0
     * @return   string  HTML form
     */
    public function render_student_application_form() {
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/shortcodes/student-application-form.php';
        return ob_get_clean();
    }
    
    /**
     * Add meta boxes for student applications in admin
     *
     * @since    1.0.0
     */
    public function add_student_meta_boxes() {
        add_meta_box(
            'vortex_student_info',
            'Student Information',
            array($this, 'render_student_meta_box'),
            'user',
            'normal'
        );
    }
    
    /**
     * Render student meta box in user profile
     *
     * @since    1.0.0
     * @param    WP_User $user  User object
     */
    public function render_student_meta_box($user) {
        $student_status = get_user_meta($user->ID, 'vortex_student_status', true);
        $institution = get_user_meta($user->ID, 'vortex_institution', true);
        $program = get_user_meta($user->ID, 'vortex_program', true);
        $graduation_year = get_user_meta($user->ID, 'vortex_graduation_year', true);
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="vortex_student_status">Student Status</label></th>';
        echo '<td>';
        echo '<select name="vortex_student_status" id="vortex_student_status">';
        echo '<option value="pending" ' . selected($student_status, 'pending', false) . '>Pending</option>';
        echo '<option value="approved" ' . selected($student_status, 'approved', false) . '>Approved</option>';
        echo '<option value="rejected" ' . selected($student_status, 'rejected', false) . '>Rejected</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="vortex_institution">Institution</label></th>';
        echo '<td><input type="text" name="vortex_institution" id="vortex_institution" value="' . esc_attr($institution) . '" class="regular-text" /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="vortex_program">Program/Major</label></th>';
        echo '<td><input type="text" name="vortex_program" id="vortex_program" value="' . esc_attr($program) . '" class="regular-text" /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="vortex_graduation_year">Graduation Year</label></th>';
        echo '<td><input type="number" name="vortex_graduation_year" id="vortex_graduation_year" value="' . esc_attr($graduation_year) . '" class="regular-text" /></td>';
        echo '</tr>';
        echo '</table>';
        
        // Add nonce for security
        wp_nonce_field('vortex_save_student_meta', 'vortex_student_meta_nonce');
    }
    
    /**
     * Add student status column to users list
     *
     * @since    1.0.0
     * @param    array $columns  Array of columns
     * @return   array Modified columns
     */
    public function add_student_status_column($columns) {
        $columns['student_status'] = 'Student Status';
        return $columns;
    }
    
    /**
     * Populate student status column
     *
     * @since    1.0.0
     * @param    string $output      Column output
     * @param    string $column_name Column name
     * @param    int    $user_id     User ID
     * @return   string Column content
     */
    public function populate_student_status_column($output, $column_name, $user_id) {
        if ($column_name === 'student_status') {
            $status = get_user_meta($user_id, 'vortex_student_status', true);
            if (!empty($status)) {
                $status_labels = array(
                    'pending' => '<span style="color:#f0ad4e;">Pending</span>',
                    'approved' => '<span style="color:#5cb85c;">Approved</span>',
                    'rejected' => '<span style="color:#d9534f;">Rejected</span>'
                );
                return isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
            }
            return 'N/A';
        }
        return $output;
    }
    
    /**
     * Create the student applications database table
     *
     * @since    1.0.0
     */
    public static function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            institution varchar(100) NOT NULL,
            program varchar(100) NOT NULL,
            graduation_year int(4) NOT NULL,
            student_id varchar(100),
            student_id_verification varchar(255),
            application_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            review_date datetime DEFAULT NULL,
            status varchar(20) NOT NULL,
            notes text,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Check student status based on email
     */
    public function check_student_status() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_student_status_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }
        
        // Validate email
        if (empty($_POST['email']) || !is_email($_POST['email'])) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
            return;
        }
        
        $email = sanitize_email($_POST['email']);
        $user = get_user_by('email', $email);
        
        if (!$user) {
            wp_send_json_error(array('message' => 'No student application found for this email address.'));
            return;
        }
        
        // Get student status
        $terms = wp_get_object_terms($user->ID, 'student_status');
        $status = !empty($terms) ? $terms[0]->slug : 'none';
        
        // Get additional info
        $school = get_user_meta($user->ID, 'vortex_institution', true);
        $program = get_user_meta($user->ID, 'vortex_program', true);
        $expiration_date = get_user_meta($user->ID, 'vortex_expiration_date', true);
        
        // Format status message
        $status_message = 'Your application status: ';
        $discount_info = '';
        
        switch ($status) {
            case 'pending':
                $status_message .= 'Pending. Your application is being reviewed.';
                break;
                
            case 'verified':
                $status_message .= 'Verified! You have active student status.';
                if ($expiration_date) {
                    $status_message .= ' Valid until ' . date('F j, Y', strtotime($expiration_date));
                }
                $discount_info = 'As a verified student, you receive a 25% discount on all marketplace purchases.';
                break;
                
            case 'rejected':
                $status_message .= 'Not Approved. Your application did not meet our verification requirements.';
                break;
                
            case 'expired':
                $status_message .= 'Expired. Your student verification has expired.';
                break;
                
            default:
                $status_message .= 'No status found.';
        }
        
        wp_send_json_success(array(
            'status' => $status,
            'message' => $status_message,
            'school' => $school,
            'program' => $program,
            'expiration_date' => $expiration_date,
            'discount_info' => $discount_info,
        ));
    }

    /**
     * Check for expired student verifications daily
     */
    public function check_expired_verifications() {
        // Get all users with verified status
        $verified_term = get_term_by('slug', 'verified', 'student_status');
        $expired_term = get_term_by('slug', 'expired', 'student_status');
        
        if (!$verified_term || !$expired_term) {
            return;
        }
        
        $args = array(
            'taxonomy' => 'student_status',
            'term' => $verified_term->slug,
            'number' => -1,
        );
        
        $verified_users = get_users($args);
        $current_date = current_time('mysql');
        
        foreach ($verified_users as $user) {
            $expiration_date = get_user_meta($user->ID, 'vortex_expiration_date', true);
            
            if ($expiration_date && strtotime($expiration_date) < strtotime($current_date)) {
                // Update status to expired
                wp_remove_object_terms($user->ID, $verified_term->term_id, 'student_status');
                wp_set_object_terms($user->ID, array($expired_term->term_id), 'student_status');
                
                // Notify user
                wp_mail(
                    $user->user_email,
                    'Your Student Verification Has Expired',
                    "Hello " . $user->display_name . ",\n\nYour student verification for Vortex AI Marketplace has expired. If you're still a student, please submit a new verification request with your current student ID.\n\nThank you,\nVortex AI Marketplace Team"
                );
            }
        }
    }
}

// Create tables on plugin activation
register_activation_hook(__FILE__, array('Vortex_Student_Handler', 'create_tables')); 