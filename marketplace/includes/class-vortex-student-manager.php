/**
 * Class for managing student application and verification
 *
 * @link       https://www.vortexaimarketplace.com
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
 * Class for managing student applications, verifications and discount program
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Vortex AI Marketplace Team
 */
class Vortex_Student_Manager {

    /**
     * Application statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->register_hooks();
    }
    
    /**
     * Register all hooks for this class.
     *
     * @since    1.0.0
     */
    public function register_hooks() {
        // Register shortcode for student application form
        add_shortcode('vortex_student_application', array($this, 'render_student_application_form'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_submit_student_application', array($this, 'handle_student_application'));
        add_action('wp_ajax_nopriv_vortex_submit_student_application', array($this, 'handle_student_application'));
        add_action('wp_ajax_vortex_check_student_status', array($this, 'check_student_status'));
        add_action('wp_ajax_nopriv_vortex_check_student_status', array($this, 'check_student_status'));
        
        // Admin menu and pages
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Schedule daily check for expired student verifications
        if (!wp_next_scheduled('vortex_check_student_verifications')) {
            wp_schedule_event(time(), 'daily', 'vortex_check_student_verifications');
        }
        add_action('vortex_check_student_verifications', array($this, 'check_expired_verifications'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on pages with the student application shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vortex_student_application')) {
            wp_enqueue_style('vortex-student-application-styles', plugin_dir_url(dirname(__FILE__)) . 'public/css/vortex-student-application.css', array(), '1.0.0');
            wp_enqueue_script('vortex-student-registration', plugin_dir_url(dirname(__FILE__)) . 'js/vortex-student-registration.js', array('jquery'), '1.0.0', true);
            
            // Pass AJAX URL and nonce to script
            wp_localize_script('vortex-student-registration', 'vortex_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
            ));
        }
    }
    
    /**
     * Add admin menu items for student management
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-marketplace',
            'Student Applications',
            'Student Applications',
            'manage_options',
            'vortex-student-applications',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render the admin page for managing student applications
     */
    public function render_admin_page() {
        // Check for actions
        if (isset($_GET['action']) && isset($_GET['application_id']) && is_numeric($_GET['application_id'])) {
            $application_id = intval($_GET['application_id']);
            $action = sanitize_text_field($_GET['action']);
            
            if ($action === 'approve' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'approve_student_' . $application_id)) {
                $this->approve_student_application($application_id);
                add_settings_error('vortex_student_applications', 'application_approved', 'Student application approved successfully.', 'updated');
            } elseif ($action === 'reject' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'reject_student_' . $application_id)) {
                $this->reject_student_application($application_id);
                add_settings_error('vortex_student_applications', 'application_rejected', 'Student application rejected.', 'updated');
            }
        }
        
        // Display any errors or notices
        settings_errors('vortex_student_applications');
        
        // Get applications
        $applications = $this->get_student_applications();
        
        // Render admin interface
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/student-applications-admin.php';
    }
    
    /**
     * Render the student application form
     */
    public function render_student_application_form() {
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/shortcodes/student-application-form.php';
        return ob_get_clean();
    }
    
    /**
     * Handle student application submission
     */
    public function handle_student_application() {
        // Check nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_student_application_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Validate required fields
        $required_fields = array('full_name', 'email', 'institution', 'program', 'graduation_year');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                wp_send_json_error(array('message' => 'Please fill in all required fields.'));
            }
        }
        
        // Validate checkboxes
        if (!isset($_POST['confirm_student']) || $_POST['confirm_student'] !== 'yes') {
            wp_send_json_error(array('message' => 'You must confirm your student status.'));
        }
        
        if (!isset($_POST['agree_terms']) || $_POST['agree_terms'] !== 'yes') {
            wp_send_json_error(array('message' => 'You must agree to the terms and conditions.'));
        }
        
        // Handle file upload
        $verification_document_url = '';
        if (isset($_FILES['verification_document']) && $_FILES['verification_document']['error'] === 0) {
            $upload = $this->handle_document_upload($_FILES['verification_document']);
            
            if (is_wp_error($upload)) {
                wp_send_json_error(array('message' => $upload->get_error_message()));
            }
            
            $verification_document_url = $upload['url'];
        }
        
        // Prepare application data
        $application_data = array(
            'full_name' => sanitize_text_field($_POST['full_name']),
            'email' => sanitize_email($_POST['email']),
            'institution' => sanitize_text_field($_POST['institution']),
            'program' => sanitize_text_field($_POST['program']),
            'graduation_year' => sanitize_text_field($_POST['graduation_year']),
            'student_id' => isset($_POST['student_id']) ? sanitize_text_field($_POST['student_id']) : '',
            'verification_document' => $verification_document_url,
            'verification_code' => isset($_POST['verification_code']) ? sanitize_text_field($_POST['verification_code']) : '',
            'status' => self::STATUS_PENDING,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        );
        
        // Insert application into database
        $result = $this->save_student_application($application_data);
        
        if ($result) {
            // Send notification emails
            $this->send_application_notifications($application_data);
            
            wp_send_json_success(array(
                'message' => 'Your student application has been submitted successfully. We will review your application and get back to you shortly.',
                'redirect_url' => home_url('/student-application-confirmation/')
            ));
        } else {
            wp_send_json_error(array('message' => 'There was an error processing your application. Please try again later.'));
        }
    }
    
    /**
     * Check student application status
     */
    public function check_student_status() {
        // Check nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_student_status_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Validate email
        if (!isset($_POST['email']) || empty($_POST['email']) || !is_email($_POST['email'])) {
            wp_send_json_error(array('message' => 'Please provide a valid email address.'));
        }
        
        $email = sanitize_email($_POST['email']);
        $application = $this->get_student_application_by_email($email);
        
        if (!$application) {
            wp_send_json_error(array('message' => 'No application found with the provided email address.'));
            return;
        }
        
        $status_messages = array(
            self::STATUS_PENDING => 'Your application is currently under review. We will notify you once a decision has been made.',
            self::STATUS_APPROVED => 'Congratulations! Your student status has been verified. You can now enjoy student discounts on the Vortex AI Marketplace.',
            self::STATUS_REJECTED => 'Unfortunately, your application could not be verified. Please contact support for more information.',
            self::STATUS_EXPIRED => 'Your student verification has expired. Please submit a new application to continue receiving student benefits.'
        );
        
        $status_classes = array(
            self::STATUS_PENDING => 'status-pending',
            self::STATUS_APPROVED => 'status-approved',
            self::STATUS_REJECTED => 'status-rejected',
            self::STATUS_EXPIRED => 'status-expired'
        );
        
        wp_send_json_success(array(
            'message' => $status_messages[$application->status],
            'status' => $application->status,
            'status_class' => $status_classes[$application->status],
            'updated_at' => $application->updated_at
        ));
    }
    
    /**
     * Save a student application to the database
     *
     * @param array $data Application data
     * @return int|bool Application ID on success, false on failure
     */
    private function save_student_application($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        
        // Check if table exists, create if not
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $this->create_database_tables();
        }
        
        // Check if email already exists
        $existing = $this->get_student_application_by_email($data['email']);
        
        if ($existing) {
            // Update existing application
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing->id)
            );
            
            return $result !== false ? $existing->id : false;
        } else {
            // Insert new application
            $result = $wpdb->insert($table_name, $data);
            
            return $result ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Get a student application by email
     *
     * @param string $email Email address
     * @return object|null Application object or null if not found
     */
    private function get_student_application_by_email($email) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s",
            $email
        ));
    }
    
    /**
     * Get all student applications
     *
     * @param array $args Optional arguments for filtering
     * @return array Array of application objects
     */
    private function get_student_applications($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        
        $defaults = array(
            'status' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = '';
        if (!empty($args['status'])) {
            $where = $wpdb->prepare("WHERE status = %s", $args['status']);
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $limit = '';
        if ($args['limit'] > 0) {
            $limit = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }
        
        return $wpdb->get_results(
            "SELECT * FROM $table_name $where ORDER BY $orderby $limit"
        );
    }
    
    /**
     * Handle verification document upload
     *
     * @param array $file File array from $_FILES
     * @return array|WP_Error Upload info or error
     */
    private function handle_document_upload($file) {
        // Require WordPress file handling functions
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Set up upload overrides
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array(
                'pdf' => 'application/pdf',
                'jpg|jpeg' => 'image/jpeg',
                'png' => 'image/png'
            )
        );
        
        // Handle the upload
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_error', $uploaded_file['error']);
        }
        
        return $uploaded_file;
    }
    
    /**
     * Approve a student application
     *
     * @param int $application_id Application ID
     * @return bool Success or failure
     */
    private function approve_student_application($application_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        
        // Get application data
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $application_id
        ));
        
        if (!$application) {
            return false;
        }
        
        // Update application status
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => self::STATUS_APPROVED,
                'updated_at' => current_time('mysql'),
                'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 year'))
            ),
            array('id' => $application_id)
        );
        
        if ($result) {
            // Find user with this email and apply student role/meta
            $user = get_user_by('email', $application->email);
            
            if ($user) {
                // Add student role
                $user->add_role('student');
                
                // Add student verification meta
                update_user_meta($user->ID, 'student_verified', true);
                update_user_meta($user->ID, 'student_institution', $application->institution);
                update_user_meta($user->ID, 'student_program', $application->program);
                update_user_meta($user->ID, 'student_verification_expiry', date('Y-m-d', strtotime('+1 year')));
            }
            
            // Send approval notification
            $this->send_status_notification($application, 'approved');
        }
        
        return $result ? true : false;
    }
    
    /**
     * Reject a student application
     *
     * @param int $application_id Application ID
     * @return bool Success or failure
     */
    private function reject_student_application($application_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        
        // Get application data
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $application_id
        ));
        
        if (!$application) {
            return false;
        }
        
        // Update application status
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => self::STATUS_REJECTED,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $application_id)
        );
        
        if ($result) {
            // Send rejection notification
            $this->send_status_notification($application, 'rejected');
        }
        
        return $result ? true : false;
    }
    
    /**
     * Send application notifications
     *
     * @param array $application_data Application data
     */
    private function send_application_notifications($application_data) {
        // Send confirmation to applicant
        $to = $application_data['email'];
        $subject = 'Your Student Application for Vortex AI Marketplace';
        
        $message = "Dear {$application_data['full_name']},\n\n";
        $message .= "Thank you for submitting your student application to Vortex AI Marketplace. Your application is now being reviewed.\n\n";
        $message .= "Application Details:\n";
        $message .= "Institution: {$application_data['institution']}\n";
        $message .= "Program: {$application_data['program']}\n";
        $message .= "Graduation Year: {$application_data['graduation_year']}\n\n";
        $message .= "You can check the status of your application by visiting our website and entering your email address.\n\n";
        $message .= "If you have any questions, please contact our support team.\n\n";
        $message .= "Best regards,\n";
        $message .= "The Vortex AI Marketplace Team";
        
        wp_mail($to, $subject, $message);
        
        // Notify admin
        $admin_email = get_option('admin_email');
        $admin_subject = 'New Student Application Received';
        
        $admin_message = "A new student application has been received:\n\n";
        $admin_message .= "Name: {$application_data['full_name']}\n";
        $admin_message .= "Email: {$application_data['email']}\n";
        $admin_message .= "Institution: {$application_data['institution']}\n";
        $admin_message .= "Program: {$application_data['program']}\n";
        $admin_message .= "Graduation Year: {$application_data['graduation_year']}\n";
        
        if (!empty($application_data['verification_document'])) {
            $admin_message .= "Verification Document: {$application_data['verification_document']}\n";
        }
        
        $admin_message .= "\nReview this application in the WordPress admin area.";
        
        wp_mail($admin_email, $admin_subject, $admin_message);
    }
    
    /**
     * Send status notification to applicant
     *
     * @param object $application Application object
     * @param string $status New status (approved or rejected)
     */
    private function send_status_notification($application, $status) {
        $to = $application->email;
        
        if ($status === 'approved') {
            $subject = 'Your Student Application has been Approved';
            
            $message = "Dear {$application->full_name},\n\n";
            $message .= "We are pleased to inform you that your student application for the Vortex AI Marketplace has been approved!\n\n";
            $message .= "You now have access to our student discounts and benefits. When logged into the marketplace, you will automatically receive the student discount on eligible purchases.\n\n";
            $message .= "Your student verification is valid for one year and will expire on " . date('F j, Y', strtotime('+1 year')) . ".\n\n";
            $message .= "If you have any questions, please contact our support team.\n\n";
            $message .= "Best regards,\n";
            $message .= "The Vortex AI Marketplace Team";
            
        } else {
            $subject = 'Update on Your Student Application';
            
            $message = "Dear {$application->full_name},\n\n";
            $message .= "We have reviewed your student application for the Vortex AI Marketplace, and unfortunately, we were unable to verify your student status at this time.\n\n";
            $message .= "This could be due to:\n";
            $message .= "- Incomplete or unclear documentation\n";
            $message .= "- Unable to verify your enrollment with the provided information\n";
            $message .= "- The provided documentation has expired\n\n";
            $message .= "You are welcome to submit a new application with updated documentation. If you believe this decision is in error, please contact our support team for assistance.\n\n";
            $message .= "Best regards,\n";
            $message .= "The Vortex AI Marketplace Team";
        }
        
        wp_mail($to, $subject, $message);
    }
    
    /**
     * Check for expired student verifications
     */
    public function check_expired_verifications() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        
        // Get expired applications
        $expired_applications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE status = %s AND expiration_date < %s",
                self::STATUS_APPROVED,
                current_time('mysql')
            )
        );
        
        foreach ($expired_applications as $application) {
            // Update application status
            $wpdb->update(
                $table_name,
                array(
                    'status' => self::STATUS_EXPIRED,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $application->id)
            );
            
            // Update user meta and role if user exists
            $user = get_user_by('email', $application->email);
            
            if ($user) {
                // Remove student role
                $user->remove_role('student');
                
                // Update student verification meta
                update_user_meta($user->ID, 'student_verified', false);
            }
            
            // Send expiration notification
            $this->send_expiration_notification($application);
        }
    }
    
    /**
     * Send expiration notification
     *
     * @param object $application Application object
     */
    private function send_expiration_notification($application) {
        $to = $application->email;
        $subject = 'Your Student Verification Has Expired';
        
        $message = "Dear {$application->full_name},\n\n";
        $message .= "Your student verification for the Vortex AI Marketplace has expired. To continue receiving student benefits, please submit a new verification.\n\n";
        $message .= "You can submit a new application by visiting our website and completing the student verification form.\n\n";
        $message .= "If you are no longer a student, you can disregard this message.\n\n";
        $message .= "Best regards,\n";
        $message .= "The Vortex AI Marketplace Team";
        
        wp_mail($to, $subject, $message);
    }
    
    /**
     * Create database tables for student applications
     */
    private function create_database_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_student_applications';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            full_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            institution varchar(100) NOT NULL,
            program varchar(100) NOT NULL,
            graduation_year varchar(20) NOT NULL,
            student_id varchar(50) DEFAULT '',
            verification_document varchar(255) DEFAULT '',
            verification_code varchar(20) DEFAULT '',
            status varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            expiration_date datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT '',
            PRIMARY KEY  (id),
            KEY email (email),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 