<?php
/**
 * HURAII GPU Interface Backend Handler
 *
 * Handles AJAX requests, file uploads, feedback tracking, and AI agent coordination
 * for the comprehensive HURAII GPU interface system.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_HURAII_GPU_Backend {
    
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
    /**
     * AI agents configuration
     */
    private $agents = array(
        'huraii' => array(
            'name' => 'HURAII',
            'type' => 'GPU',
            'capabilities' => array('generate', 'upscale', 'describe', 'transform'),
            'model' => 'stable-diffusion-xl'
        ),
        'cloe' => array(
            'name' => 'CLOE',
            'type' => 'CPU',
            'capabilities' => array('market_analysis', 'recommendations', 'trends'),
            'model' => 'gpt-3.5-turbo'
        ),
        'horace' => array(
            'name' => 'HORACE',
            'type' => 'CPU',
            'capabilities' => array('content_analysis', 'writing', 'curation'),
            'model' => 'gpt-3.5-turbo'
        ),
        'thorius' => array(
            'name' => 'THORIUS',
            'type' => 'CPU',
            'capabilities' => array('learning', 'guidance', 'education'),
            'model' => 'gpt-3.5-turbo'
        ),
        'archer' => array(
            'name' => 'ARCHER',
            'type' => 'CPU',
            'capabilities' => array('orchestration', 'strategy', 'coordination'),
            'model' => 'gpt-4'
        )
    );
    
    /**
     * Feedback database table
     */
    private $feedback_table;
    
    /**
     * Uploads table
     */
    private $uploads_table;
    
    /**
     * Get the singleton instance
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
        
        $this->feedback_table = $wpdb->prefix . 'vortex_agent_feedback';
        $this->uploads_table = $wpdb->prefix . 'vortex_huraii_uploads';
        
        $this->init_hooks();
        $this->create_tables();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // AJAX handlers for logged-in users
        add_action('wp_ajax_huraii_upload_file', array($this, 'handle_file_upload'));
        add_action('wp_ajax_huraii_save_feedback', array($this, 'save_feedback'));
        add_action('wp_ajax_huraii_load_satisfaction', array($this, 'load_satisfaction'));
        add_action('wp_ajax_huraii_generate_content', array($this, 'generate_content'));
        add_action('wp_ajax_huraii_track_interaction', array($this, 'track_interaction'));
        add_action('wp_ajax_huraii_analyze_file', array($this, 'analyze_file'));
        
        // AJAX handlers for non-logged-in users (if needed)
        add_action('wp_ajax_nopriv_huraii_upload_file', array($this, 'handle_file_upload'));
        add_action('wp_ajax_nopriv_huraii_save_feedback', array($this, 'save_feedback'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Agent feedback table
        $feedback_sql = "CREATE TABLE IF NOT EXISTS {$this->feedback_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            agent varchar(50) NOT NULL,
            feedback enum('like','dislike') NOT NULL,
            session_id varchar(100) DEFAULT NULL,
            timestamp bigint(20) UNSIGNED NOT NULL,
            context longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY agent (agent),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Uploads table
        $uploads_sql = "CREATE TABLE IF NOT EXISTS {$this->uploads_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            filename varchar(255) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_type varchar(100) NOT NULL,
            file_size bigint(20) UNSIGNED NOT NULL,
            upload_path varchar(500) NOT NULL,
            file_url varchar(500) NOT NULL,
            agent_assigned varchar(50) DEFAULT NULL,
            processing_status enum('pending','processing','completed','failed') DEFAULT 'pending',
            metadata longtext DEFAULT NULL,
            upload_time timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY agent_assigned (agent_assigned)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($feedback_sql);
        dbDelta($uploads_sql);
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (is_page() || is_single()) {
            wp_enqueue_script(
                'huraii-gpu-interface',
                plugin_dir_url(__FILE__) . '../assets/js/huraii-gpu-interface.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_enqueue_style(
                'huraii-gpu-interface',
                plugin_dir_url(__FILE__) . '../assets/css/huraii-gpu-interface.css',
                array(),
                '1.0.0'
            );
            
            // Localize script with data
            wp_localize_script('huraii-gpu-interface', 'huraiiGPUData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('huraii_gpu_nonce'),
                'userId' => get_current_user_id(),
                'agents' => $this->agents,
                'maxFileSize' => wp_max_upload_size(),
                'uploadDir' => wp_upload_dir()
            ));
        }
    }
    
    /**
     * Handle file upload
     */
    public function handle_file_upload() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'huraii_gpu_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        $user_id = get_current_user_id();
        $file_type = sanitize_text_field($_POST['type']);
        $agent = sanitize_text_field($_POST['agent']);
        
        // Validate agent
        if (!array_key_exists($agent, $this->agents)) {
            wp_send_json_error('Invalid agent specified');
        }
        
        // Handle file upload
        if (!isset($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['file'];
        
        // Validate file
        $validation = $this->validate_upload($file, $file_type);
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        }
        
        // Process upload
        $upload_result = $this->process_file_upload($file, $file_type, $agent, $user_id);
        
        if (is_wp_error($upload_result)) {
            wp_send_json_error($upload_result->get_error_message());
        }
        
        wp_send_json_success($upload_result);
    }
    
    /**
     * Validate file upload
     */
    private function validate_upload($file, $type) {
        // Check file size
        $max_size = wp_max_upload_size();
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', 'File size exceeds maximum allowed size');
        }
        
        // Check file type
        $allowed_types = array(
            'image' => array('image/jpeg', 'image/png', 'image/webp', 'image/gif'),
            'document' => array('application/pdf', 'application/msword', 'text/plain', 
                              'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
        );
        
        if (!isset($allowed_types[$type]) || !in_array($file['type'], $allowed_types[$type])) {
            return new WP_Error('invalid_file_type', 'File type not allowed');
        }
        
        return true;
    }
    
    /**
     * Process file upload
     */
    private function process_file_upload($file, $type, $agent, $user_id) {
        global $wpdb;
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $huraii_dir = $upload_dir['basedir'] . '/huraii-uploads/' . $user_id;
        
        if (!file_exists($huraii_dir)) {
            wp_mkdir_p($huraii_dir);
        }
        
        // Generate unique filename
        $file_info = pathinfo($file['name']);
        $filename = uniqid() . '_' . sanitize_file_name($file_info['filename']) . '.' . $file_info['extension'];
        $file_path = $huraii_dir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return new WP_Error('upload_failed', 'Failed to move uploaded file');
        }
        
        // Generate file URL
        $file_url = $upload_dir['baseurl'] . '/huraii-uploads/' . $user_id . '/' . $filename;
        
        // Store in database
        $result = $wpdb->insert(
            $this->uploads_table,
            array(
                'user_id' => $user_id,
                'filename' => $filename,
                'original_name' => $file['name'],
                'file_type' => $file['type'],
                'file_size' => $file['size'],
                'upload_path' => $file_path,
                'file_url' => $file_url,
                'agent_assigned' => $agent,
                'processing_status' => 'pending',
                'metadata' => json_encode(array(
                    'upload_type' => $type,
                    'upload_time' => current_time('mysql'),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ))
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to save file information');
        }
        
        $file_id = $wpdb->insert_id;
        
        // Process file based on type and agent
        $this->queue_file_processing($file_id, $type, $agent);
        
        return array(
            'file_id' => $file_id,
            'file_url' => $file_url,
            'filename' => $filename,
            'original_name' => $file['name'],
            'file_size' => $file['size'],
            'agent' => $agent,
            'status' => 'uploaded'
        );
    }
    
    /**
     * Queue file for processing
     */
    private function queue_file_processing($file_id, $type, $agent) {
        // This would typically queue the file for background processing
        // For now, we'll just update the status
        global $wpdb;
        
        $wpdb->update(
            $this->uploads_table,
            array('processing_status' => 'queued'),
            array('id' => $file_id),
            array('%s'),
            array('%d')
        );
        
        // Here you would integrate with your AI processing pipeline
        // For example, send to RunPod vault for processing
    }
    
    /**
     * Save user feedback
     */
    public function save_feedback() {
        global $wpdb;
        
        $agent = sanitize_text_field($_POST['agent']);
        $feedback = sanitize_text_field($_POST['feedback']);
        $user_id = get_current_user_id();
        $timestamp = intval($_POST['timestamp']);
        
        // Validate input
        if (!in_array($feedback, array('like', 'dislike'))) {
            wp_send_json_error('Invalid feedback type');
        }
        
        if (!array_key_exists($agent, $this->agents)) {
            wp_send_json_error('Invalid agent');
        }
        
        // Save feedback
        $result = $wpdb->insert(
            $this->feedback_table,
            array(
                'user_id' => $user_id,
                'agent' => $agent,
                'feedback' => $feedback,
                'session_id' => $this->get_session_id(),
                'timestamp' => $timestamp,
                'context' => json_encode(array(
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'referer' => $_SERVER['HTTP_REFERER'] ?? '',
                    'ip_address' => $this->get_client_ip()
                ))
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to save feedback');
        }
        
        // Update agent satisfaction metrics
        $this->update_agent_metrics($agent);
        
        wp_send_json_success(array(
            'message' => 'Feedback saved successfully',
            'agent' => $agent,
            'feedback' => $feedback
        ));
    }
    
    /**
     * Load satisfaction data
     */
    public function load_satisfaction() {
        global $wpdb;
        
        $user_id = get_current_user_id();
        
        $satisfaction_data = array();
        
        foreach ($this->agents as $agent_key => $agent_info) {
            // Get feedback counts for this agent
            $likes = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->feedback_table} 
                 WHERE agent = %s AND feedback = 'like' AND user_id = %d",
                $agent_key, $user_id
            ));
            
            $dislikes = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->feedback_table} 
                 WHERE agent = %s AND feedback = 'dislike' AND user_id = %d",
                $agent_key, $user_id
            ));
            
            $total = $likes + $dislikes;
            $satisfaction = $total > 0 ? ($likes / $total) * 100 : 0;
            
            $satisfaction_data[$agent_key] = array(
                'likes' => intval($likes),
                'dislikes' => intval($dislikes),
                'satisfaction' => round($satisfaction, 2)
            );
        }
        
        wp_send_json_success($satisfaction_data);
    }
    
    /**
     * Generate content
     */
    public function generate_content() {
        $generation_data = json_decode(stripslashes($_POST['generation_data']), true);
        
        if (!$generation_data) {
            wp_send_json_error('Invalid generation data');
        }
        
        // Validate and sanitize generation data
        $prompt = sanitize_textarea_field($generation_data['prompt']);
        $type = sanitize_text_field($generation_data['type']);
        $mode = sanitize_text_field($generation_data['mode']);
        $agent = sanitize_text_field($generation_data['agent']);
        
        // Process generation based on mode
        switch ($mode) {
            case 'gpu':
                $result = $this->process_gpu_generation($prompt, $type, $agent);
                break;
            case 'cpu':
                $result = $this->process_cpu_generation($prompt, $type, $agent);
                break;
            case 'hybrid':
                $result = $this->process_hybrid_generation($prompt, $type, $generation_data);
                break;
            default:
                wp_send_json_error('Invalid processing mode');
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Process GPU generation (HURAII)
     */
    private function process_gpu_generation($prompt, $type, $agent) {
        // This would interface with your GPU processing system
        // For now, return a mock response
        
        return array(
            'type' => 'image',
            'results' => array(
                array(
                    'url' => 'https://example.com/generated-image-1.jpg',
                    'prompt' => $prompt,
                    'agent' => $agent,
                    'processing_time' => '15.2s',
                    'model' => 'stable-diffusion-xl'
                )
            ),
            'metadata' => array(
                'generation_time' => current_time('mysql'),
                'processing_mode' => 'gpu',
                'agent_used' => $agent
            )
        );
    }
    
    /**
     * Process CPU generation
     */
    private function process_cpu_generation($prompt, $type, $agent) {
        // Interface with CPU agents for text-based generation
        
        return array(
            'type' => 'text',
            'results' => array(
                array(
                    'content' => "Generated content based on: {$prompt}",
                    'agent' => $agent,
                    'processing_time' => '2.1s',
                    'model' => $this->agents[$agent]['model']
                )
            ),
            'metadata' => array(
                'generation_time' => current_time('mysql'),
                'processing_mode' => 'cpu',
                'agent_used' => $agent
            )
        );
    }
    
    /**
     * Process hybrid generation
     */
    private function process_hybrid_generation($prompt, $type, $data) {
        // First use GPU for description, then CPU for generation
        
        return array(
            'type' => 'hybrid',
            'results' => array(
                array(
                    'description_phase' => 'GPU analysis completed',
                    'generation_phase' => 'CPU generation completed',
                    'final_result' => "Hybrid result for: {$prompt}",
                    'processing_time' => '18.7s'
                )
            ),
            'metadata' => array(
                'generation_time' => current_time('mysql'),
                'processing_mode' => 'hybrid',
                'phases' => array('gpu_describe', 'cpu_generate')
            )
        );
    }
    
    /**
     * Track user interactions
     */
    public function track_interaction() {
        global $wpdb;
        
        $action = sanitize_text_field($_POST['interaction_action']);
        $data = sanitize_textarea_field($_POST['interaction_data']);
        $timestamp = intval($_POST['timestamp']);
        $user_id = intval($_POST['user_id']);
        
        // Store interaction for learning and analytics
        $interactions_table = $wpdb->prefix . 'vortex_user_interactions';
        
        // Create table if it doesn't exist
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$interactions_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            action varchar(100) NOT NULL,
            data longtext DEFAULT NULL,
            timestamp bigint(20) UNSIGNED NOT NULL,
            session_id varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY timestamp (timestamp)
        ) {$wpdb->get_charset_collate()};");
        
        $wpdb->insert(
            $interactions_table,
            array(
                'user_id' => $user_id,
                'action' => $action,
                'data' => $data,
                'timestamp' => $timestamp,
                'session_id' => $this->get_session_id()
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
        
        wp_send_json_success(array('tracked' => true));
    }
    
    /**
     * Analyze uploaded file
     */
    public function analyze_file() {
        // This would process file analysis using the assigned agent
        $file_id = intval($_POST['file_id']);
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $agent = sanitize_text_field($_POST['agent']);
        
        // Mock analysis result
        $analysis_result = array(
            'summary' => "Analysis of uploaded file using {$agent}",
            'details' => "Detailed analysis based on prompt: {$prompt}",
            'agent_used' => $agent,
            'processing_time' => '3.2s',
            'confidence' => 87.5
        );
        
        wp_send_json_success($analysis_result);
    }
    
    /**
     * Update agent metrics
     */
    private function update_agent_metrics($agent) {
        // Update real-time agent performance metrics
        // This would typically update a metrics table or cache
    }
    
    /**
     * Get session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Get agent status
     */
    public function get_agent_status($agent) {
        if (!array_key_exists($agent, $this->agents)) {
            return false;
        }
        
        return array(
            'name' => $this->agents[$agent]['name'],
            'type' => $this->agents[$agent]['type'],
            'status' => 'active', // This would check actual agent status
            'capabilities' => $this->agents[$agent]['capabilities'],
            'load' => rand(10, 95) // Mock load percentage
        );
    }
    
    /**
     * Get all agents status
     */
    public function get_all_agents_status() {
        $status = array();
        foreach ($this->agents as $key => $agent) {
            $status[$key] = $this->get_agent_status($key);
        }
        return $status;
    }
}

// Initialize the backend handler
Vortex_HURAII_GPU_Backend::get_instance(); 