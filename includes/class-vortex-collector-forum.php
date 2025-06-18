<?php
/**
 * Collector Forum Functionality
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle collector forum functionality
 */
class Vortex_Collector_Forum {
    
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode('vortex_forum', array($this, 'render_forum_shortcode'));
        add_shortcode('vortex_create_post', array($this, 'render_create_post_shortcode'));
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_create_forum_post', array($this, 'ajax_create_forum_post'));
        add_action('wp_ajax_vortex_load_forum_posts', array($this, 'ajax_load_forum_posts'));
        add_action('wp_ajax_vortex_load_post_details', array($this, 'ajax_load_post_details'));
        add_action('wp_ajax_vortex_submit_response', array($this, 'ajax_submit_response'));
        add_action('wp_ajax_vortex_update_post_status', array($this, 'ajax_update_post_status'));
        
        // Non-logged in users can view forum posts
        add_action('wp_ajax_nopriv_vortex_load_forum_posts', array($this, 'ajax_load_forum_posts'));
        add_action('wp_ajax_nopriv_vortex_load_post_details', array($this, 'ajax_load_post_details'));
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Register scripts and styles
     */
    public function enqueue_scripts() {
        wp_register_style(
            'vortex-collector-forum',
            plugin_dir_url(VORTEX_PLUGIN_FILE) . 'css/vortex-collector-forum.css',
            array(),
            VORTEX_VERSION
        );
        
        wp_register_script(
            'vortex-collector-forum',
            plugin_dir_url(VORTEX_PLUGIN_FILE) . 'js/vortex-collector-forum.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-collector-forum', 'vortexForum', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_forum_nonce'),
            'security' => wp_create_nonce('vortex_forum_security'),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in(),
            'login_url' => wp_login_url(get_permalink()),
            'current_url' => get_permalink(),
            'i18n' => array(
                'confirm_delete' => __('Are you sure you want to delete this post?', 'vortex-ai-marketplace'),
                'error_message' => __('An error occurred. Please try again.', 'vortex-ai-marketplace'),
                'success_create' => __('Your post has been created successfully!', 'vortex-ai-marketplace'),
                'success_response' => __('Your response has been submitted successfully!', 'vortex-ai-marketplace'),
                'loading' => __('Loading...', 'vortex-ai-marketplace'),
                'no_posts' => __('No posts found.', 'vortex-ai-marketplace'),
                'login_required' => __('You must be logged in to perform this action.', 'vortex-ai-marketplace')
            )
        ));
    }
    
    /**
     * Render the forum shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_forum_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_type' => 'all', // all, project, offer, event
            'limit' => 10,
            'status' => 'all', // all, open, closed
        ), $atts, 'vortex_forum');
        
        // Enqueue required assets
        wp_enqueue_style('vortex-collector-forum');
        wp_enqueue_script('vortex-collector-forum');
        
        ob_start();
        
        // Include template
        include(plugin_dir_path(VORTEX_PLUGIN_FILE) . 'templates/collector-forum/forum-list.php');
        
        return ob_get_clean();
    }
    
    /**
     * Render the create post shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_create_post_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => '', // project, offer, event (empty for all types)
        ), $atts, 'vortex_create_post');
        
        // Enqueue required assets
        wp_enqueue_style('vortex-collector-forum');
        wp_enqueue_script('vortex-collector-forum');
        
        ob_start();
        
        if (!is_user_logged_in()) {
            // Show login message if user is not logged in
            ?>
            <div class="vortex-forum-login-required">
                <p><?php _e('You must be logged in to create a post.', 'vortex-ai-marketplace'); ?></p>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="vortex-button"><?php _e('Login', 'vortex-ai-marketplace'); ?></a>
            </div>
            <?php
        } else {
            // Include template
            include(plugin_dir_path(VORTEX_PLUGIN_FILE) . 'templates/collector-forum/create-post.php');
        }
        
        return ob_get_clean();
    }
    
    /**
     * Ajax handler for creating a forum post
     */
    public function ajax_create_forum_post() {
        // Verify nonce
        check_ajax_referer('vortex_forum_security', 'security');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to create a post.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Validate post data
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        
        if (empty($post_type) || empty($title) || empty($description)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Validate post type
        $allowed_types = array('project', 'offer', 'event');
        if (!in_array($post_type, $allowed_types)) {
            wp_send_json_error(array(
                'message' => __('Invalid post type.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Prepare additional fields
        $budget = isset($_POST['budget']) ? floatval($_POST['budget']) : null;
        $deadline = isset($_POST['deadline']) ? sanitize_text_field($_POST['deadline']) : null;
        $skills_required = isset($_POST['skills_required']) ? sanitize_textarea_field($_POST['skills_required']) : null;
        
        // Handle attachments
        $attachments = array();
        if (!empty($_FILES['attachments'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $files = $_FILES['attachments'];
            $upload_overrides = array('test_form' => false);
            
            // Check if we have multiple files
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $file = array(
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    );
                    
                    if ($file['error'] == 0) {
                        $movefile = wp_handle_upload($file, $upload_overrides);
                        if (!isset($movefile['error'])) {
                            $attachments[] = $movefile['url'];
                        }
                    }
                }
            } else {
                // Single file
                if ($files['error'] == 0) {
                    $movefile = wp_handle_upload($files, $upload_overrides);
                    if (!isset($movefile['error'])) {
                        $attachments[] = $movefile['url'];
                    }
                }
            }
        }
        
        // Insert post into database
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_forum_posts',
            array(
                'user_id' => get_current_user_id(),
                'post_type' => $post_type,
                'title' => $title,
                'description' => $description,
                'budget' => $budget,
                'deadline' => $deadline,
                'skills_required' => $skills_required,
                'attachments' => !empty($attachments) ? json_encode($attachments) : null,
                'status' => 'open',
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        $post_id = $wpdb->insert_id;
        
        if (!$post_id) {
            wp_send_json_error(array(
                'message' => __('Failed to create post. Please try again.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Success response
        wp_send_json_success(array(
            'message' => __('Your post has been created successfully!', 'vortex-ai-marketplace'),
            'post_id' => $post_id,
            'redirect_url' => add_query_arg('view_post', $post_id, remove_query_arg('create_post'))
        ));
    }
    
    /**
     * Ajax handler for loading forum posts
     */
    public function ajax_load_forum_posts() {
        // Verify nonce
        check_ajax_referer('vortex_forum_security', 'security');
        
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'all';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'all';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Get posts from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_forum_posts';
        
        $sql = "SELECT p.*, u.display_name as author_name 
                FROM $table_name p 
                LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                WHERE 1=1";
        
        $sql_args = array();
        
        if ($post_type !== 'all') {
            $sql .= " AND p.post_type = %s";
            $sql_args[] = $post_type;
        }
        
        if ($status !== 'all') {
            $sql .= " AND p.status = %s";
            $sql_args[] = $status;
        }
        
        if (!empty($search)) {
            $sql .= " AND (p.title LIKE %s OR p.description LIKE %s)";
            $sql_args[] = '%' . $wpdb->esc_like($search) . '%';
            $sql_args[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT %d OFFSET %d";
        $sql_args[] = $limit;
        $sql_args[] = $offset;
        
        $posts = $wpdb->get_results($wpdb->prepare($sql, $sql_args));
        
        // Count total posts for pagination
        $count_sql = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
        $count_args = array();
        
        if ($post_type !== 'all') {
            $count_sql .= " AND post_type = %s";
            $count_args[] = $post_type;
        }
        
        if ($status !== 'all') {
            $count_sql .= " AND status = %s";
            $count_args[] = $status;
        }
        
        if (!empty($search)) {
            $count_sql .= " AND (title LIKE %s OR description LIKE %s)";
            $count_args[] = '%' . $wpdb->esc_like($search) . '%';
            $count_args[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        $total_posts = $wpdb->get_var($wpdb->prepare($count_sql, $count_args));
        
        // Format data for display
        $formatted_posts = array();
        foreach ($posts as $post) {
            $formatted_posts[] = array(
                'id' => $post->id,
                'title' => $post->title,
                'description' => wp_trim_words($post->description, 20, '...'),
                'post_type' => $post->post_type,
                'author_name' => $post->author_name,
                'author_id' => $post->user_id,
                'created_at' => date_i18n(get_option('date_format'), strtotime($post->created_at)),
                'status' => $post->status,
                'responses' => $post->responses,
                'views' => $post->views,
                'is_author' => (get_current_user_id() == $post->user_id),
                'budget' => $post->budget,
                'deadline' => $post->deadline ? date_i18n(get_option('date_format'), strtotime($post->deadline)) : null,
                'post_url' => add_query_arg('view_post', $post->id, remove_query_arg('create_post'))
            );
        }
        
        // Update view count for all loaded posts
        if (!empty($posts)) {
            $post_ids = wp_list_pluck($posts, 'id');
            $post_ids_str = implode(',', array_map('intval', $post_ids));
            $wpdb->query("UPDATE $table_name SET views = views + 1 WHERE id IN ($post_ids_str)");
        }
        
        wp_send_json_success(array(
            'posts' => $formatted_posts,
            'total' => intval($total_posts),
            'has_more' => ($offset + $limit) < intval($total_posts)
        ));
    }
    
    /**
     * Ajax handler for loading post details
     */
    public function ajax_load_post_details() {
        // Verify nonce
        check_ajax_referer('vortex_forum_security', 'security');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (empty($post_id)) {
            wp_send_json_error(array(
                'message' => __('Invalid post ID.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Get post details
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_forum_posts';
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, u.display_name as author_name 
            FROM $table_name p 
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
            WHERE p.id = %d",
            $post_id
        ));
        
        if (!$post) {
            wp_send_json_error(array(
                'message' => __('Post not found.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Get responses
        $responses_table = $wpdb->prefix . 'vortex_forum_responses';
        $responses = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name as author_name 
            FROM $responses_table r 
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
            WHERE r.post_id = %d 
            ORDER BY r.created_at ASC",
            $post_id
        ));
        
        // Format responses
        $formatted_responses = array();
        foreach ($responses as $response) {
            $formatted_responses[] = array(
                'id' => $response->id,
                'author_name' => $response->author_name,
                'author_id' => $response->user_id,
                'message' => $response->message,
                'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($response->created_at)),
                'attachments' => $response->attachments ? json_decode($response->attachments) : array(),
                'is_selected' => (bool) $response->is_selected,
                'is_author' => (get_current_user_id() == $response->user_id),
                'can_select' => (get_current_user_id() == $post->user_id) && $post->status === 'open'
            );
        }
        
        // Format post for display
        $formatted_post = array(
            'id' => $post->id,
            'title' => $post->title,
            'description' => $post->description,
            'post_type' => $post->post_type,
            'author_name' => $post->author_name,
            'author_id' => $post->user_id,
            'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($post->created_at)),
            'status' => $post->status,
            'responses' => $formatted_responses,
            'response_count' => count($formatted_responses),
            'views' => $post->views + 1, // Increment view count
            'is_author' => (get_current_user_id() == $post->user_id),
            'budget' => $post->budget,
            'deadline' => $post->deadline ? date_i18n(get_option('date_format'), strtotime($post->deadline)) : null,
            'skills_required' => $post->skills_required,
            'attachments' => $post->attachments ? json_decode($post->attachments) : array()
        );
        
        // Update view count
        $wpdb->update(
            $table_name,
            array('views' => $post->views + 1),
            array('id' => $post_id),
            array('%d'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'post' => $formatted_post
        ));
    }
    
    /**
     * Ajax handler for submitting a response to a post
     */
    public function ajax_submit_response() {
        // Verify nonce
        check_ajax_referer('vortex_forum_security', 'security');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to submit a response.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $message = isset($_POST['message']) ? wp_kses_post($_POST['message']) : '';
        
        if (empty($post_id) || empty($message)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Verify the post exists and is open
        global $wpdb;
        $posts_table = $wpdb->prefix . 'vortex_forum_posts';
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $posts_table WHERE id = %d",
            $post_id
        ));
        
        if (!$post) {
            wp_send_json_error(array(
                'message' => __('Post not found.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        if ($post->status !== 'open') {
            wp_send_json_error(array(
                'message' => __('This post is closed to new responses.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Handle attachments
        $attachments = array();
        if (!empty($_FILES['attachments'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $files = $_FILES['attachments'];
            $upload_overrides = array('test_form' => false);
            
            // Check if we have multiple files
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $file = array(
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    );
                    
                    if ($file['error'] == 0) {
                        $movefile = wp_handle_upload($file, $upload_overrides);
                        if (!isset($movefile['error'])) {
                            $attachments[] = $movefile['url'];
                        }
                    }
                }
            } else {
                // Single file
                if ($files['error'] == 0) {
                    $movefile = wp_handle_upload($files, $upload_overrides);
                    if (!isset($movefile['error'])) {
                        $attachments[] = $movefile['url'];
                    }
                }
            }
        }
        
        // Insert response
        $responses_table = $wpdb->prefix . 'vortex_forum_responses';
        $wpdb->insert(
            $responses_table,
            array(
                'post_id' => $post_id,
                'user_id' => get_current_user_id(),
                'message' => $message,
                'attachments' => !empty($attachments) ? json_encode($attachments) : null,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        $response_id = $wpdb->insert_id;
        
        if (!$response_id) {
            wp_send_json_error(array(
                'message' => __('Failed to submit response. Please try again.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Update response count for the post
        $wpdb->update(
            $posts_table,
            array('responses' => $post->responses + 1),
            array('id' => $post_id),
            array('%d'),
            array('%d')
        );
        
        // Get response details
        $response = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, u.display_name as author_name 
            FROM $responses_table r 
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
            WHERE r.id = %d",
            $response_id
        ));
        
        // Format response for display
        $formatted_response = array(
            'id' => $response->id,
            'author_name' => $response->author_name,
            'author_id' => $response->user_id,
            'message' => $response->message,
            'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($response->created_at)),
            'attachments' => $response->attachments ? json_decode($response->attachments) : array(),
            'is_selected' => (bool) $response->is_selected,
            'is_author' => true, // Since the current user is submitting the response
            'can_select' => false
        );
        
        wp_send_json_success(array(
            'message' => __('Your response has been submitted successfully!', 'vortex-ai-marketplace'),
            'response' => $formatted_response
        ));
    }
    
    /**
     * Ajax handler for updating post status
     */
    public function ajax_update_post_status() {
        // Verify nonce
        check_ajax_referer('vortex_forum_security', 'security');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to update post status.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (empty($post_id) || empty($status)) {
            wp_send_json_error(array(
                'message' => __('Invalid request.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Verify the post exists and the current user is the author
        global $wpdb;
        $posts_table = $wpdb->prefix . 'vortex_forum_posts';
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $posts_table WHERE id = %d",
            $post_id
        ));
        
        if (!$post) {
            wp_send_json_error(array(
                'message' => __('Post not found.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        if ($post->user_id != get_current_user_id()) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to update this post.', 'vortex-ai-marketplace')
            ));
            return;
        }
        
        // Update post status
        $wpdb->update(
            $posts_table,
            array('status' => $status),
            array('id' => $post_id),
            array('%s'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'message' => __('Post status updated successfully!', 'vortex-ai-marketplace'),
            'status' => $status
        ));
    }
    
    /**
     * Get post types for forum
     * 
     * @return array Post types
     */
    public static function get_post_types() {
        return array(
            'project' => __('Project', 'vortex-ai-marketplace'),
            'offer' => __('Offer', 'vortex-ai-marketplace'),
            'event' => __('Event', 'vortex-ai-marketplace')
        );
    }
    
    /**
     * Get post statuses for forum
     * 
     * @return array Post statuses
     */
    public static function get_post_statuses() {
        return array(
            'open' => __('Open', 'vortex-ai-marketplace'),
            'closed' => __('Closed', 'vortex-ai-marketplace')
        );
    }
}

// Initialize the class
new Vortex_Collector_Forum(); 