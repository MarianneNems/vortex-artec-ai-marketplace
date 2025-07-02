<?php
/**
 * The AWS S3 Seed Art Manager class.
 *
 * @since      3.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_Seed_Art_Manager {

    /**
     * AWS S3 bucket name
     *
     * @since    3.0.0
     * @access   private
     * @var      string    $bucket_name    S3 bucket name
     */
    private $bucket_name;

    /**
     * AWS credentials
     *
     * @since    3.0.0
     * @access   private
     * @var      array    $aws_config    AWS configuration
     */
    private $aws_config;

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.0.0
     */
    public function __construct() {
        $this->bucket_name = get_option('vortex_aws_s3_bucket', '');
        $this->aws_config = array(
            'access_key' => get_option('vortex_aws_access_key', ''),
            'secret_key' => get_option('vortex_aws_secret_key', ''),
            'region' => get_option('vortex_aws_region', 'us-east-1')
        );

        add_action('init', array($this, 'init_seed_art_system'));
        add_action('wp_ajax_vortex_upload_seed_art', array($this, 'handle_ajax_upload'));
        add_action('wp_ajax_vortex_delete_seed_art', array($this, 'handle_ajax_delete'));
        add_action('wp_ajax_vortex_get_seed_gallery', array($this, 'handle_ajax_gallery'));
    }

    /**
     * Initialize seed art system
     *
     * @since    3.0.0
     */
    public function init_seed_art_system() {
        $this->create_seed_art_tables();
    }

    /**
     * Create seed art database tables
     *
     * @since    3.0.0
     */
    private function create_seed_art_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_seed_artworks';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            filename varchar(255) NOT NULL,
            original_filename varchar(255) NOT NULL,
            s3_key varchar(500) NOT NULL,
            s3_url varchar(500) NOT NULL,
            file_size bigint(20) NOT NULL,
            file_type varchar(50) NOT NULL,
            image_width int(11) DEFAULT NULL,
            image_height int(11) DEFAULT NULL,
            metadata text DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY s3_key (s3_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Upload file to S3
     *
     * @since    3.0.0
     * @param    string    $file_path    Local file path
     * @param    string    $s3_key       S3 object key
     * @return   array                   Upload result
     */
    public function upload_to_s3($file_path, $s3_key) {
        if (!$this->is_s3_configured()) {
            return array(
                'success' => false,
                'error' => 'S3 not configured'
            );
        }

        try {
            // Initialize S3 client (mock implementation)
            // In production, this would use AWS SDK for PHP
            $s3_client = $this->get_s3_client();
            
            if (!$s3_client) {
                // Fallback to local storage for development
                return $this->upload_to_local_storage($file_path, $s3_key);
            }

            // Mock S3 upload for now
            $s3_url = $this->generate_s3_url($s3_key);
            
            // In production, this would be:
            // $result = $s3_client->putObject([
            //     'Bucket' => $this->bucket_name,
            //     'Key' => $s3_key,
            //     'SourceFile' => $file_path,
            //     'ContentType' => mime_content_type($file_path),
            //     'ACL' => 'public-read'
            // ]);

            return array(
                'success' => true,
                'url' => $s3_url,
                'key' => $s3_key
            );

        } catch (Exception $e) {
            error_log('VORTEX S3 Upload Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Upload file to local storage as fallback
     *
     * @since    3.0.0
     * @param    string    $file_path    Source file path
     * @param    string    $s3_key       Intended S3 key (used for local path)
     * @return   array                   Upload result
     */
    private function upload_to_local_storage($file_path, $s3_key) {
        $upload_dir = wp_upload_dir();
        $vortex_dir = $upload_dir['basedir'] . '/vortex-seed-art';
        
        if (!file_exists($vortex_dir)) {
            wp_mkdir_p($vortex_dir);
        }

        $local_path = $vortex_dir . '/' . basename($s3_key);
        
        if (copy($file_path, $local_path)) {
            $local_url = $upload_dir['baseurl'] . '/vortex-seed-art/' . basename($s3_key);
            
            return array(
                'success' => true,
                'url' => $local_url,
                'key' => $s3_key
            );
        }

        return array(
            'success' => false,
            'error' => 'Failed to copy to local storage'
        );
    }

    /**
     * Process and save uploaded seed art
     *
     * @since    3.0.0
     * @param    array    $file_data    Uploaded file data
     * @param    int      $user_id      User ID
     * @return   array                  Processing result
     */
    public function process_seed_upload($file_data, $user_id) {
        // Validate file
        $validation_result = $this->validate_uploaded_file($file_data);
        if (!$validation_result['valid']) {
            return array(
                'success' => false,
                'error' => $validation_result['error']
            );
        }

        // Generate unique filename
        $file_extension = pathinfo($file_data['name'], PATHINFO_EXTENSION);
        $unique_filename = $this->generate_unique_filename($user_id, $file_extension);
        $s3_key = "users/{$user_id}/seed/{$unique_filename}";

        // Upload to S3
        $upload_result = $this->upload_to_s3($file_data['tmp_name'], $s3_key);
        
        if (!$upload_result['success']) {
            return $upload_result;
        }

        // Get image dimensions
        $image_info = getimagesize($file_data['tmp_name']);
        
        // Save to database
        $artwork_id = $this->save_seed_artwork_record($user_id, array(
            'filename' => $unique_filename,
            'original_filename' => $file_data['name'],
            's3_key' => $s3_key,
            's3_url' => $upload_result['url'],
            'file_size' => $file_data['size'],
            'file_type' => $file_data['type'],
            'image_width' => $image_info ? $image_info[0] : null,
            'image_height' => $image_info ? $image_info[1] : null
        ));

        if (!$artwork_id) {
            return array(
                'success' => false,
                'error' => 'Failed to save artwork record'
            );
        }

        // Award gamification tokens
        $this->award_upload_tokens($user_id);

        // Generate thumbnail
        $thumbnail_url = $this->generate_thumbnail($upload_result['url'], $artwork_id);

        return array(
            'success' => true,
            'artwork_id' => $artwork_id,
            'filename' => $unique_filename,
            'url' => $upload_result['url'],
            'thumbnail' => $thumbnail_url,
            'size' => size_format($file_data['size'])
        );
    }

    /**
     * Validate uploaded file
     *
     * @since    3.0.0
     * @param    array    $file_data    File data from $_FILES
     * @return   array                  Validation result
     */
    private function validate_uploaded_file($file_data) {
        // Check for upload errors
        if ($file_data['error'] !== UPLOAD_ERR_OK) {
            return array(
                'valid' => false,
                'error' => 'File upload error: ' . $file_data['error']
            );
        }

        // Check file size (10MB limit)
        $max_size = 10 * 1024 * 1024;
        if ($file_data['size'] > $max_size) {
            return array(
                'valid' => false,
                'error' => 'File size exceeds 10MB limit'
            );
        }

        // Validate image type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file_data['type'], $allowed_types)) {
            return array(
                'valid' => false,
                'error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP allowed'
            );
        }

        // Verify it's actually an image
        if (!getimagesize($file_data['tmp_name'])) {
            return array(
                'valid' => false,
                'error' => 'File is not a valid image'
            );
        }

        return array('valid' => true);
    }

    /**
     * Generate unique filename
     *
     * @since    3.0.0
     * @param    int       $user_id         User ID
     * @param    string    $extension       File extension
     * @return   string                     Unique filename
     */
    private function generate_unique_filename($user_id, $extension) {
        $timestamp = time();
        $random = wp_generate_password(8, false, false);
        return "seed_{$user_id}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Save seed artwork record to database
     *
     * @since    3.0.0
     * @param    int      $user_id      User ID
     * @param    array    $artwork_data Artwork data
     * @return   int|false              Artwork ID or false
     */
    private function save_seed_artwork_record($user_id, $artwork_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_seed_artworks';
        
        $insert_data = array(
            'user_id' => $user_id,
            'filename' => $artwork_data['filename'],
            'original_filename' => $artwork_data['original_filename'],
            's3_key' => $artwork_data['s3_key'],
            's3_url' => $artwork_data['s3_url'],
            'file_size' => $artwork_data['file_size'],
            'file_type' => $artwork_data['file_type'],
            'image_width' => $artwork_data['image_width'],
            'image_height' => $artwork_data['image_height'],
            'status' => 'active'
        );

        $result = $wpdb->insert($table_name, $insert_data, array(
            '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s'
        ));

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Award tokens for seed upload
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     */
    private function award_upload_tokens($user_id) {
        // Check if this is first upload
        $previous_uploads = $this->get_user_seed_count($user_id);
        
        if ($previous_uploads <= 1) { // First upload (including current one)
            if (class_exists('Vortex_Gamification')) {
                $gamification = new Vortex_Gamification();
                $gamification->award_tokens($user_id, 10, 'seed_upload');
            }
            
            // Update milestone
            $completed_milestones = get_user_meta($user_id, 'vortex_completed_milestones', true) ?: array();
            if (!in_array('seed_upload_completed', $completed_milestones)) {
                $completed_milestones[] = 'seed_upload_completed';
                update_user_meta($user_id, 'vortex_completed_milestones', $completed_milestones);
            }
        }
    }

    /**
     * Get user's seed artwork count
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @return   int                Seed count
     */
    public function get_user_seed_count($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_seed_artworks';
        
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'active'",
                $user_id
            )
        );
    }

    /**
     * Get user's seed artworks
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @param    int    $limit      Limit results
     * @return   array              Seed artworks
     */
    public function get_user_seed_artworks($user_id, $limit = 50) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_seed_artworks';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE user_id = %d AND status = 'active' 
                 ORDER BY created_at DESC 
                 LIMIT %d",
                $user_id,
                $limit
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Generate thumbnail URL
     *
     * @since    3.0.0
     * @param    string    $image_url     Original image URL
     * @param    int       $artwork_id    Artwork ID
     * @return   string                   Thumbnail URL
     */
    private function generate_thumbnail($image_url, $artwork_id) {
        // For now, return the original image URL
        // In production, this would generate actual thumbnails
        return $image_url;
    }

    /**
     * Handle AJAX upload request
     *
     * @since    3.0.0
     */
    public function handle_ajax_upload() {
        check_ajax_referer('wp_rest', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }

        if (!isset($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
        }

        $result = $this->process_seed_upload($_FILES['file'], $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }

    /**
     * Handle AJAX delete request
     *
     * @since    3.0.0
     */
    public function handle_ajax_delete() {
        check_ajax_referer('wp_rest', 'nonce');

        $user_id = get_current_user_id();
        $artwork_id = intval($_POST['artwork_id']);

        if (!$user_id || !$artwork_id) {
            wp_send_json_error('Invalid request');
        }

        $result = $this->delete_seed_artwork($artwork_id, $user_id);
        
        if ($result) {
            wp_send_json_success('Artwork deleted');
        } else {
            wp_send_json_error('Failed to delete artwork');
        }
    }

    /**
     * Handle AJAX gallery request
     *
     * @since    3.0.0
     */
    public function handle_ajax_gallery() {
        check_ajax_referer('wp_rest', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }

        $artworks = $this->get_user_seed_artworks($user_id);
        
        wp_send_json_success(array(
            'artworks' => $artworks,
            'count' => count($artworks)
        ));
    }

    /**
     * Delete seed artwork
     *
     * @since    3.0.0
     * @param    int    $artwork_id    Artwork ID
     * @param    int    $user_id       User ID (for security)
     * @return   bool                  Success status
     */
    public function delete_seed_artwork($artwork_id, $user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_seed_artworks';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => 'deleted'),
            array('id' => $artwork_id, 'user_id' => $user_id),
            array('%s'),
            array('%d', '%d')
        );

        return $result !== false;
    }

    /**
     * Check if S3 is properly configured
     *
     * @since    3.0.0
     * @return   bool    Configuration status
     */
    private function is_s3_configured() {
        return !empty($this->bucket_name) && 
               !empty($this->aws_config['access_key']) && 
               !empty($this->aws_config['secret_key']);
    }

    /**
     * Get S3 client instance
     *
     * @since    3.0.0
     * @return   object|null    S3 client or null
     */
    private function get_s3_client() {
        if (!$this->is_s3_configured()) {
            return null;
        }

        // Mock S3 client for development
        // In production, this would return AWS SDK S3 client:
        // return new Aws\S3\S3Client([
        //     'version' => 'latest',
        //     'region' => $this->aws_config['region'],
        //     'credentials' => [
        //         'key' => $this->aws_config['access_key'],
        //         'secret' => $this->aws_config['secret_key']
        //     ]
        // ]);

        return (object) array('mock' => true);
    }

    /**
     * Generate S3 URL
     *
     * @since    3.0.0
     * @param    string    $s3_key    S3 object key
     * @return   string               S3 URL
     */
    private function generate_s3_url($s3_key) {
        if ($this->is_s3_configured()) {
            return "https://{$this->bucket_name}.s3.{$this->aws_config['region']}.amazonaws.com/{$s3_key}";
        }
        
        // Return mock URL for development
        return "https://mock-s3-bucket.s3.amazonaws.com/{$s3_key}";
    }

    /**
     * Get all seed artworks for TOLA-ART generation
     *
     * @since    3.0.0
     * @param    int    $limit    Limit results
     * @return   array            All active seed artworks
     */
    public function get_all_active_seed_artworks($limit = 100) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_seed_artworks';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE status = 'active' 
                 ORDER BY created_at DESC 
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return $results;
    }
} 