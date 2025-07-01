<?php
/**
 * AJAX handlers for Vortex AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_AJAX_Handlers {
    
    /**
     * Initialize the AJAX handlers
     */
    public static function init() {
        // Event handlers
        add_action('wp_ajax_vortex_create_event', array(__CLASS__, 'handle_create_event'));
        add_action('wp_ajax_vortex_register_event', array(__CLASS__, 'handle_register_event'));
        add_action('wp_ajax_vortex_cancel_event_registration', array(__CLASS__, 'handle_cancel_event_registration'));
        
        // Offer handlers
        add_action('wp_ajax_vortex_create_offer', array(__CLASS__, 'handle_create_offer'));
        add_action('wp_ajax_vortex_respond_to_offer', array(__CLASS__, 'handle_respond_to_offer'));
        add_action('wp_ajax_vortex_cancel_offer', array(__CLASS__, 'handle_cancel_offer'));
        
        // Collaboration handlers
        add_action('wp_ajax_vortex_create_collaboration', array(__CLASS__, 'handle_create_collaboration'));
        add_action('wp_ajax_vortex_join_collaboration', array(__CLASS__, 'handle_join_collaboration'));
        add_action('wp_ajax_vortex_leave_collaboration', array(__CLASS__, 'handle_leave_collaboration'));
        
        // Swiping handler
        add_action('wp_ajax_vortex_handle_swipe', array(__CLASS__, 'handle_swipe'));
        
        // User actions
        add_action('wp_ajax_vortex_artist_verification', array(__CLASS__, 'artist_verification'));
        add_action('wp_ajax_vortex_submit_artwork', array(__CLASS__, 'submit_artwork'));
        add_action('wp_ajax_vortex_like_artwork', array(__CLASS__, 'like_artwork'));
        add_action('wp_ajax_vortex_share_artwork', array(__CLASS__, 'share_artwork'));
        add_action('wp_ajax_vortex_follow_artist', array(__CLASS__, 'follow_artist'));
        
        // Marketplace actions
        add_action('wp_ajax_vortex_get_artwork', array(__CLASS__, 'get_artwork'));
        add_action('wp_ajax_nopriv_vortex_get_artwork', array(__CLASS__, 'get_artwork'));
        add_action('wp_ajax_vortex_purchase_artwork', array(__CLASS__, 'purchase_artwork'));
        add_action('wp_ajax_vortex_auction_bid', array(__CLASS__, 'auction_bid'));
        
        // Admin actions
        add_action('wp_ajax_vortex_admin_metrics', array(__CLASS__, 'admin_metrics'));
        add_action('wp_ajax_vortex_admin_user_management', array(__CLASS__, 'admin_user_management'));
        add_action('wp_ajax_vortex_admin_artwork_approval', array(__CLASS__, 'admin_artwork_approval'));
        add_action('wp_ajax_vortex_update_database', array(__CLASS__, 'update_database'));
        
        // TOLA token actions
        add_action('wp_ajax_vortex_get_token_balance', array(__CLASS__, 'get_token_balance'));
        add_action('wp_ajax_vortex_transfer_tokens', array(__CLASS__, 'transfer_tokens'));

        // Search handler
        add_action('wp_ajax_vortex_search', array(__CLASS__, 'handle_search'));
        add_action('wp_ajax_nopriv_vortex_search', array(__CLASS__, 'handle_search'));

        // Artwork theme handlers
        add_action('wp_ajax_vortex_artwork_theme_association', array(__CLASS__, 'handle_artwork_theme_association'));
        add_action('wp_ajax_vortex_get_available_themes', array(__CLASS__, 'get_available_themes'));
        add_action('wp_ajax_nopriv_vortex_get_available_themes', array(__CLASS__, 'get_available_themes'));

        // Business idea handlers
        add_action('wp_ajax_vortex_process_business_idea', array(__CLASS__, 'process_business_idea'));
        
        // Business plan PDF download handler
        add_action('wp_ajax_vortex_download_business_plan', array(__CLASS__, 'download_business_plan'));
        add_action('wp_ajax_nopriv_vortex_download_business_plan', array(__CLASS__, 'download_business_plan'));
        
        // Milestone reminders handler
        add_action('wp_ajax_vortex_enable_milestone_reminders', array(__CLASS__, 'enable_milestone_reminders'));
        
        // AI agent handlers
        add_action('wp_ajax_vortex_ai_agent_message', array(__CLASS__, 'handle_ai_agent_message'));
        add_action('wp_ajax_nopriv_vortex_ai_agent_message', array(__CLASS__, 'handle_ai_agent_message'));
        
        // Artist qualification quiz handler
        add_action('wp_ajax_vortex_analyze_quiz_responses', array(__CLASS__, 'analyze_quiz_responses'));
        add_action('wp_ajax_nopriv_vortex_analyze_quiz_responses', array(__CLASS__, 'analyze_quiz_responses'));
    }
    
    /**
     * Handle event creation
     */
    public static function handle_create_event() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['event_nonce'] ?? '', 'vortex_create_event')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to create an event.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $required_fields = array(
            'event_title',
            'event_description',
            'event_date',
            'event_location',
            'event_capacity',
            'event_price'
        );
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Please fill in all required fields. Missing: %s', 'vortex-ai-marketplace'), $field)
                ));
            }
        }
        
        try {
            // Create event post
            $event_data = array(
                'post_title' => sanitize_text_field($_POST['event_title']),
                'post_content' => wp_kses_post($_POST['event_description']),
                'post_type' => 'vortex_event',
                'post_status' => 'publish',
                'post_author' => $user_id
            );
            
            $event_id = wp_insert_post($event_data);
            if (is_wp_error($event_id)) {
                throw new Exception($event_id->get_error_message());
            }
            
            // Save event meta
            $meta_fields = array(
                'event_date' => sanitize_text_field($_POST['event_date']),
                'event_location' => sanitize_text_field($_POST['event_location']),
                'event_capacity' => intval($_POST['event_capacity']),
                'event_price' => floatval($_POST['event_price'])
            );
            
            foreach ($meta_fields as $key => $value) {
                update_post_meta($event_id, $key, $value);
            }
            
            // Handle event image
            if (!empty($_FILES['event_image'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_id = media_handle_upload('event_image', $event_id);
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($event_id, $attachment_id);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Event created successfully.', 'vortex-ai-marketplace'),
                'redirect_url' => get_permalink($event_id)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle event registration
     */
    public static function handle_register_event() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_register_event')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to register for an event.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $event_id = intval($_POST['event_id'] ?? 0);
        if (!$event_id) {
            wp_send_json_error(array(
                'message' => __('Invalid event ID.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Check if event exists and is published
            $event = get_post($event_id);
            if (!$event || $event->post_type !== 'vortex_event' || $event->post_status !== 'publish') {
                throw new Exception(__('Event not found.', 'vortex-ai-marketplace'));
            }
            
            // Check if event is full
            $current_registrations = self::get_event_registrations_count($event_id);
            $capacity = intval(get_post_meta($event_id, 'event_capacity', true));
            
            if ($current_registrations >= $capacity) {
                throw new Exception(__('This event is full.', 'vortex-ai-marketplace'));
            }
            
            // Check if user is already registered
            if (self::is_user_registered_for_event($event_id, $user_id)) {
                throw new Exception(__('You are already registered for this event.', 'vortex-ai-marketplace'));
            }
            
            // Get event price
            $price = floatval(get_post_meta($event_id, 'event_price', true));
            
            // Check user's TOLA balance
            $user_balance = vortex_get_user_tola_balance($user_id);
            if ($user_balance < $price) {
                throw new Exception(__('Insufficient TOLA balance.', 'vortex-ai-marketplace'));
            }
            
            // Process payment
            $payment_result = vortex_process_tola_payment($user_id, $price);
            if (!$payment_result['success']) {
                throw new Exception($payment_result['message']);
            }
            
            // Register user for event
            global $wpdb;
            $result = $wpdb->insert(
                $wpdb->prefix . 'vortex_event_registrations',
                array(
                    'event_id' => $event_id,
                    'user_id' => $user_id,
                    'registration_date' => current_time('mysql'),
                    'status' => 'confirmed',
                    'payment_status' => 'completed',
                    'payment_amount' => $price,
                    'payment_currency' => 'TOLA',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                throw new Exception(__('Failed to register for event.', 'vortex-ai-marketplace'));
            }
            
            wp_send_json_success(array(
                'message' => __('Successfully registered for event.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle offer creation
     */
    public static function handle_create_offer() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['offer_nonce'] ?? '', 'vortex_create_offer')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to create an offer.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $required_fields = array(
            'offer_title',
            'offer_description',
            'offer_type',
            'offer_amount',
            'offer_deadline',
            'offer_terms'
        );
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Please fill in all required fields. Missing: %s', 'vortex-ai-marketplace'), $field)
                ));
            }
        }
        
        try {
            // Create offer post
            $offer_data = array(
                'post_title' => sanitize_text_field($_POST['offer_title']),
                'post_content' => wp_kses_post($_POST['offer_description']),
                'post_type' => 'vortex_offer',
                'post_status' => 'publish',
                'post_author' => $user_id
            );
            
            $offer_id = wp_insert_post($offer_data);
            if (is_wp_error($offer_id)) {
                throw new Exception($offer_id->get_error_message());
            }
            
            // Save offer meta
            $meta_fields = array(
                'offer_type' => sanitize_text_field($_POST['offer_type']),
                'offer_amount' => floatval($_POST['offer_amount']),
                'offer_deadline' => sanitize_text_field($_POST['offer_deadline']),
                'offer_terms' => wp_kses_post($_POST['offer_terms'])
            );
            
            foreach ($meta_fields as $key => $value) {
                update_post_meta($offer_id, $key, $value);
            }
            
            // Handle offer attachments
            if (!empty($_FILES['offer_attachments'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_ids = array();
                foreach ($_FILES['offer_attachments']['name'] as $key => $value) {
                    if ($_FILES['offer_attachments']['error'][$key] === 0) {
                        $file = array(
                            'name' => $_FILES['offer_attachments']['name'][$key],
                            'type' => $_FILES['offer_attachments']['type'][$key],
                            'tmp_name' => $_FILES['offer_attachments']['tmp_name'][$key],
                            'error' => $_FILES['offer_attachments']['error'][$key],
                            'size' => $_FILES['offer_attachments']['size'][$key]
                        );
                        
                        $attachment_id = media_handle_sideload($file, $offer_id);
                        if (!is_wp_error($attachment_id)) {
                            $attachment_ids[] = $attachment_id;
                        }
                    }
                }
                
                if (!empty($attachment_ids)) {
                    update_post_meta($offer_id, 'offer_attachments', $attachment_ids);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Offer created successfully.', 'vortex-ai-marketplace'),
                'redirect_url' => get_permalink($offer_id)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle collaboration creation
     */
    public static function handle_create_collaboration() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['collaboration_nonce'] ?? '', 'vortex_create_collaboration')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to create a collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $required_fields = array(
            'collaboration_title',
            'collaboration_description',
            'collaboration_type',
            'collaboration_budget',
            'collaboration_deadline',
            'collaboration_requirements'
        );
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Please fill in all required fields. Missing: %s', 'vortex-ai-marketplace'), $field)
                ));
            }
        }
        
        if (empty($_POST['collaboration_roles'])) {
            wp_send_json_error(array(
                'message' => __('Please select at least one required role.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Create collaboration post
            $collaboration_data = array(
                'post_title' => sanitize_text_field($_POST['collaboration_title']),
                'post_content' => wp_kses_post($_POST['collaboration_description']),
                'post_type' => 'vortex_collaboration',
                'post_status' => 'publish',
                'post_author' => $user_id
            );
            
            $collaboration_id = wp_insert_post($collaboration_data);
            if (is_wp_error($collaboration_id)) {
                throw new Exception($collaboration_id->get_error_message());
            }
            
            // Save collaboration meta
            $meta_fields = array(
                'collaboration_type' => sanitize_text_field($_POST['collaboration_type']),
                'collaboration_budget' => floatval($_POST['collaboration_budget']),
                'collaboration_deadline' => sanitize_text_field($_POST['collaboration_deadline']),
                'collaboration_requirements' => wp_kses_post($_POST['collaboration_requirements']),
                'collaboration_roles' => array_map('sanitize_text_field', $_POST['collaboration_roles'])
            );
            
            foreach ($meta_fields as $key => $value) {
                update_post_meta($collaboration_id, $key, $value);
            }
            
            // Handle collaboration attachments
            if (!empty($_FILES['collaboration_attachments'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_ids = array();
                foreach ($_FILES['collaboration_attachments']['name'] as $key => $value) {
                    if ($_FILES['collaboration_attachments']['error'][$key] === 0) {
                        $file = array(
                            'name' => $_FILES['collaboration_attachments']['name'][$key],
                            'type' => $_FILES['collaboration_attachments']['type'][$key],
                            'tmp_name' => $_FILES['collaboration_attachments']['tmp_name'][$key],
                            'error' => $_FILES['collaboration_attachments']['error'][$key],
                            'size' => $_FILES['collaboration_attachments']['size'][$key]
                        );
                        
                        $attachment_id = media_handle_sideload($file, $collaboration_id);
                        if (!is_wp_error($attachment_id)) {
                            $attachment_ids[] = $attachment_id;
                        }
                    }
                }
                
                if (!empty($attachment_ids)) {
                    update_post_meta($collaboration_id, 'collaboration_attachments', $attachment_ids);
                }
            }
            
            // Add creator as first member
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'vortex_collaboration_members',
                array(
                    'collaboration_id' => $collaboration_id,
                    'user_id' => $user_id,
                    'role' => 'creator',
                    'join_date' => current_time('mysql'),
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            wp_send_json_success(array(
                'message' => __('Collaboration created successfully.', 'vortex-ai-marketplace'),
                'redirect_url' => get_permalink($collaboration_id)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Get event registrations count
     */
    private static function get_event_registrations_count($event_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_event_registrations 
            WHERE event_id = %d AND status = 'confirmed'",
            $event_id
        ));
    }
    
    /**
     * Check if user is registered for event
     */
    private static function is_user_registered_for_event($event_id, $user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_event_registrations 
            WHERE event_id = %d AND user_id = %d AND status = 'confirmed'",
            $event_id,
            $user_id
        )) > 0;
    }
    
    /**
     * Handle swipe action
     */
    public static function handle_swipe() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_handle_swipe')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to perform this action.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $item_id = intval($_POST['item_id'] ?? 0);
        $swipe_action = sanitize_text_field($_POST['swipe_action'] ?? '');
        
        if (!$item_id) {
            wp_send_json_error(array(
                'message' => __('Invalid item ID.', 'vortex-ai-marketplace')
            ));
        }
        
        if (!in_array($swipe_action, array('accept', 'reject'))) {
            wp_send_json_error(array(
                'message' => __('Invalid swipe action.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Check if item exists and is published
            $item = get_post($item_id);
            if (!$item || $item->post_type !== 'vortex_item' || $item->post_status !== 'publish') {
                throw new Exception(__('Item not found.', 'vortex-ai-marketplace'));
            }
            
            // Track swipe statistics
            self::track_swipe_statistics($item_id, $swipe_action);
            
            // Process swipe action
            if ($swipe_action === 'accept') {
                // Add item to user's collection
                $user_collection = get_user_meta($user_id, 'vortex_collection', true);
                if (!is_array($user_collection)) {
                    $user_collection = array();
                }
                
                if (!in_array($item_id, $user_collection)) {
                    $user_collection[] = $item_id;
                    update_user_meta($user_id, 'vortex_collection', $user_collection);
                    
                    // Increment collection count
                    $collection_count = get_post_meta($item_id, 'vortex_collection_count', true);
                    update_post_meta($item_id, 'vortex_collection_count', intval($collection_count) + 1);
                    
                    // Increment total collections
                    $total_collections = get_option('vortex_total_collections', 0);
                    update_option('vortex_total_collections', intval($total_collections) + 1);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Swipe action processed successfully.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Track swipe statistics
     */
    private static function track_swipe_statistics($item_id, $swipe_action) {
        // Increment swipe count for item
        $swipe_count = get_post_meta($item_id, 'vortex_swipe_count', true);
        update_post_meta($item_id, 'vortex_swipe_count', intval($swipe_count) + 1);
        
        // Increment total swipes
        $total_swipes = get_option('vortex_total_swipes', 0);
        update_option('vortex_total_swipes', intval($total_swipes) + 1);
        
        // Track action-specific counts
        if ($swipe_action === 'accept') {
            $accept_count = get_post_meta($item_id, 'vortex_accept_count', true);
            update_post_meta($item_id, 'vortex_accept_count', intval($accept_count) + 1);
        } else {
            $reject_count = get_post_meta($item_id, 'vortex_reject_count', true);
            update_post_meta($item_id, 'vortex_reject_count', intval($reject_count) + 1);
        }
        
        // Calculate and update acceptance rate
        $accept_count = intval(get_post_meta($item_id, 'vortex_accept_count', true));
        $swipe_count = intval(get_post_meta($item_id, 'vortex_swipe_count', true));
        if ($swipe_count > 0) {
            $acceptance_rate = round(($accept_count / $swipe_count) * 100, 2);
            update_post_meta($item_id, 'vortex_acceptance_rate', $acceptance_rate);
        }
    }
    
    /**
     * Handle join collaboration request
     */
    public static function handle_join_collaboration() {
        // Verify nonce
        check_ajax_referer('vortex_career_project_nonce', 'nonce');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to join a collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Validate required fields
        $collaboration_id = isset($_POST['collaboration_id']) ? intval($_POST['collaboration_id']) : 0;
        $requested_role = isset($_POST['requested_role']) ? sanitize_text_field($_POST['requested_role']) : '';
        $request_message = isset($_POST['request_message']) ? sanitize_textarea_field($_POST['request_message']) : '';
        
        if (empty($collaboration_id) || empty($requested_role) || empty($request_message)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if collaboration exists
        $collaboration = get_post($collaboration_id);
        if (!$collaboration || $collaboration->post_type !== 'vortex_collaboration' || $collaboration->post_status !== 'publish') {
            wp_send_json_error(array(
                'message' => __('Collaboration not found.', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if user is already a member
        if (self::is_user_collaboration_member($collaboration_id, $user_id)) {
            wp_send_json_error(array(
                'message' => __('You are already a member of this collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if user already has a pending request
        if (self::has_user_pending_collaboration_request($collaboration_id, $user_id)) {
            wp_send_json_error(array(
                'message' => __('You already have a pending request for this collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Submit join request
            global $wpdb;
            $result = $wpdb->insert(
                $wpdb->prefix . 'vortex_collaboration_requests',
                array(
                    'collaboration_id' => $collaboration_id,
                    'user_id' => $user_id,
                    'request_date' => current_time('mysql'),
                    'requested_role' => $requested_role,
                    'request_status' => 'pending',
                    'request_message' => $request_message,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                throw new Exception(__('Failed to submit join request.', 'vortex-ai-marketplace'));
            }
            
            // Send notification to collaboration creator
            $creator_id = $collaboration->post_author;
            if ($creator_id != $user_id) {
                $notification_message = sprintf(
                    __('User %s has requested to join your collaboration "%s" as a %s.', 'vortex-ai-marketplace'),
                    get_the_author_meta('display_name', $user_id),
                    $collaboration->post_title,
                    $requested_role
                );
                
                // Add notification for the creator
                if (function_exists('vortex_add_notification')) {
                    vortex_add_notification($creator_id, 'collaboration_request', $notification_message, $collaboration_id);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Your request to join the collaboration has been submitted successfully.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Check if user is a member of a collaboration
     */
    public static function is_user_collaboration_member($collaboration_id, $user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_collaboration_members 
            WHERE collaboration_id = %d AND user_id = %d AND status = 'active'",
            $collaboration_id,
            $user_id
        )) > 0;
    }
    
    /**
     * Check if user has a pending request for a collaboration
     */
    public static function has_user_pending_collaboration_request($collaboration_id, $user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_collaboration_requests 
            WHERE collaboration_id = %d AND user_id = %d AND request_status = 'pending'",
            $collaboration_id,
            $user_id
        )) > 0;
    }
    
    /**
     * Handle leave collaboration
     */
    public static function handle_leave_collaboration() {
        // Verify nonce
        check_ajax_referer('vortex_career_project_nonce', 'nonce');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to leave a collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Validate input
        $collaboration_id = isset($_POST['collaboration_id']) ? intval($_POST['collaboration_id']) : 0;
        
        if (empty($collaboration_id)) {
            wp_send_json_error(array(
                'message' => __('Invalid collaboration ID.', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if user is a member
        if (!self::is_user_collaboration_member($collaboration_id, $user_id)) {
            wp_send_json_error(array(
                'message' => __('You are not a member of this collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Update user membership status to 'inactive'
            global $wpdb;
            $result = $wpdb->update(
                $wpdb->prefix . 'vortex_collaboration_members',
                array(
                    'status' => 'inactive',
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'collaboration_id' => $collaboration_id,
                    'user_id' => $user_id
                ),
                array('%s', '%s'),
                array('%d', '%d')
            );
            
            if ($result === false) {
                throw new Exception(__('Failed to leave collaboration.', 'vortex-ai-marketplace'));
            }
            
            // Get collaboration details for notification
            $collaboration = get_post($collaboration_id);
            $creator_id = $collaboration->post_author;
            
            // Send notification to collaboration creator
            if ($creator_id != $user_id) {
                $notification_message = sprintf(
                    __('User %s has left your collaboration "%s".', 'vortex-ai-marketplace'),
                    get_the_author_meta('display_name', $user_id),
                    $collaboration->post_title
                );
                
                // Add notification for the creator
                if (function_exists('vortex_add_notification')) {
                    vortex_add_notification($creator_id, 'collaboration_leave', $notification_message, $collaboration_id);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('You have successfully left the collaboration.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle database update AJAX request
     */
    public static function update_database() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_update_database_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'vortex-ai-marketplace')));
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
        }
        
        // Run the database update
        require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-migrations.php';
        $db_migration = new \Vortex_DB_Migrations();
        $db_migration->setup_database();
        
        // Update the database version
        update_option('vortex_ai_db_version', VORTEX_VERSION);
        
        wp_send_json_success(array('message' => __('Database tables have been created or updated successfully.', 'vortex-ai-marketplace')));
    }

    /**
     * Handle database errors
     *
     * @param string $error_message The error message
     * @param string $table_name The name of the table causing issues
     * @return boolean True if error was fixed, false otherwise
     */
    public static function handle_db_error($error_message, $table_name) {
        // First, check if this is a missing table error
        if (strpos($error_message, "Table") !== false && strpos($error_message, "doesn't exist") !== false) {
            // Try to fix the specific table
            $table_basename = str_replace($GLOBALS['wpdb']->prefix, '', $table_name);
            
            if ($table_basename === 'vortex_searches') {
                // Fix searches table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_searches_table();
            } elseif ($table_basename === 'vortex_transactions') {
                // Fix transactions table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_transactions_table();
            } elseif ($table_basename === 'vortex_tags') {
                // Fix tags table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_tags_table();
            } elseif ($table_basename === 'vortex_artwork_tags') {
                // Fix artwork tags table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_artwork_tags_table();
            } elseif ($table_basename === 'vortex_art_styles') {
                // Fix art styles table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_art_styles_table();
            } elseif ($table_basename === 'vortex_artwork_themes') {
                // Fix artwork themes table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_artwork_themes_table();
            } elseif ($table_basename === 'vortex_artwork_theme_mapping') {
                // Fix artwork theme mapping table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_artwork_theme_mapping_table();
            } else {
                // For other tables, try the general repair
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-repair.php';
                $db_repair = VORTEX_DB_Repair::get_instance();
                $repaired = $db_repair->repair_specific_table($table_basename);
                return !empty($repaired);
            }
        }
        
        // Log the error for debugging
        error_log("VORTEX DB Error: $error_message");
        
        return false;
    }

    /**
     * Handle AJAX search request
     */
    public static function handle_search() {
        check_ajax_referer('vortex_search_nonce', 'nonce');
        
        $search_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($search_query)) {
            wp_send_json_error(array('message' => __('Please enter a search query.', 'vortex-ai-marketplace')));
            return;
        }
        
        global $wpdb;
        $searches_table = $wpdb->prefix . 'vortex_searches';
        
        // Record search query
        try {
            $user_id = is_user_logged_in() ? get_current_user_id() : NULL;
            $session_id = isset($_COOKIE['vortex_session']) ? sanitize_text_field($_COOKIE['vortex_session']) : NULL;
            
            // Get search filters if any
            $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
            $filters_json = !empty($filters) ? json_encode($filters) : NULL;
            
            // Get user's IP and user agent
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
            
            // Run the actual search
            $results = self::perform_search($search_query, $filters);
            
            // Record search in database
            $insert_result = $wpdb->insert(
                $searches_table,
                array(
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'search_query' => $search_query,
                    'search_time' => current_time('mysql'),
                    'results_count' => count($results),
                    'search_filters' => $filters_json,
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent
                )
            );
            
            // If insertion failed, check if it's due to missing table
            if ($insert_result === false) {
                $db_error = $wpdb->last_error;
                if (strpos($db_error, "doesn't exist") !== false) {
                    // Try to fix the table
                    self::handle_db_error($db_error, $searches_table);
                    
                    // Try insertion again
                    $wpdb->insert(
                        $searches_table,
                        array(
                            'user_id' => $user_id,
                            'session_id' => $session_id,
                            'search_query' => $search_query,
                            'search_time' => current_time('mysql'),
                            'results_count' => count($results),
                            'search_filters' => $filters_json,
                            'ip_address' => $ip_address,
                            'user_agent' => $user_agent
                        )
                    );
                }
            }
            
            // Return search results to user
            wp_send_json_success(array(
                'results' => $results,
                'count' => count($results),
                'message' => sprintf(
                    _n('Found %d result for "%s"', 'Found %d results for "%s"', count($results), 'vortex-ai-marketplace'),
                    count($results),
                    $search_query
                )
            ));
            
        } catch (Exception $e) {
            error_log('VORTEX Search Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred while processing your search. Please try again.', 'vortex-ai-marketplace')));
        }
    }

    /**
     * Perform the actual search
     * 
     * @param string $query The search query
     * @param array $filters Any search filters
     * @return array Search results
     */
    private static function perform_search($query, $filters = array()) {
        // This is a simplified implementation - expand as needed
        $args = array(
            'post_type' => array('vortex_artwork', 'product'),
            's' => $query,
            'posts_per_page' => 20,
        );
        
        // Apply filters if provided
        if (!empty($filters)) {
            // Example: filter by category
            if (isset($filters['category']) && !empty($filters['category'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $filters['category']
                );
            }
            
            // Example: filter by price range
            if (isset($filters['min_price']) && isset($filters['max_price'])) {
                $args['meta_query'][] = array(
                    'key' => '_price',
                    'value' => array($filters['min_price'], $filters['max_price']),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                );
            }
        }
        
        $query = new WP_Query($args);
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $results[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                    'type' => get_post_type(),
                    'excerpt' => get_the_excerpt(),
                    'price' => get_post_type() === 'product' ? get_post_meta($post_id, '_price', true) : null
                );
            }
            
            wp_reset_postdata();
        }
        
        return $results;
    }

    /**
     * Handle artwork theme associations
     */
    public static function handle_artwork_theme_association() {
        check_ajax_referer('vortex_artwork_theme_nonce', 'nonce');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action.', 'vortex-ai-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get parameters
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $theme_ids = isset($_POST['theme_ids']) ? array_map('intval', (array)$_POST['theme_ids']) : array();
        $action = isset($_POST['theme_action']) ? sanitize_text_field($_POST['theme_action']) : 'add';
        
        if (!$artwork_id) {
            wp_send_json_error(array('message' => __('Invalid artwork ID.', 'vortex-ai-marketplace')));
            return;
        }
        
        // Initialize the HURAII library
        require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-huraii-library.php';
        $huraii_library = Vortex_HURAII_Library::get_instance();
        
        // Perform the action
        if ($action === 'add') {
            // Add themes to artwork
            if (empty($theme_ids)) {
                wp_send_json_error(array('message' => __('No themes specified.', 'vortex-ai-marketplace')));
                return;
            }
            
            $result = $huraii_library->associate_artwork_with_themes($artwork_id, $theme_ids, $user_id);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(__('%d themes associated with the artwork.', 'vortex-ai-marketplace'), $result['added']),
                    'added' => $result['added']
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to associate themes with the artwork.', 'vortex-ai-marketplace'),
                    'errors' => $result['errors']
                ));
            }
        } elseif ($action === 'remove') {
            // Remove theme from artwork
            if (empty($theme_ids) || count($theme_ids) !== 1) {
                wp_send_json_error(array('message' => __('Please specify exactly one theme to remove.', 'vortex-ai-marketplace')));
                return;
            }
            
            $theme_id = $theme_ids[0];
            $result = $huraii_library->remove_artwork_theme($artwork_id, $theme_id);
            
            if ($result) {
                wp_send_json_success(array('message' => __('Theme removed from artwork.', 'vortex-ai-marketplace')));
            } else {
                wp_send_json_error(array('message' => __('Failed to remove theme from artwork.', 'vortex-ai-marketplace')));
            }
        } elseif ($action === 'get') {
            // Get themes for artwork
            $themes = $huraii_library->get_artwork_themes($artwork_id);
            
            wp_send_json_success(array(
                'themes' => $themes,
                'count' => count($themes)
            ));
        } else {
            wp_send_json_error(array('message' => __('Invalid action.', 'vortex-ai-marketplace')));
        }
    }
    
    /**
     * Get available artwork themes
     */
    public static function get_available_themes() {
        check_ajax_referer('vortex_artwork_theme_nonce', 'nonce');
        
        global $wpdb;
        $themes_table = $wpdb->prefix . 'vortex_artwork_themes';
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$themes_table'") !== $themes_table) {
            try {
                // Try to create the table
                require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-migrations.php';
                Vortex_DB_Migrations::ensure_artwork_themes_table();
                
                // Check again
                if ($wpdb->get_var("SHOW TABLES LIKE '$themes_table'") !== $themes_table) {
                    wp_send_json_error(array('message' => __('Themes table does not exist.', 'vortex-ai-marketplace')));
                    return;
                }
            } catch (Exception $e) {
                wp_send_json_error(array('message' => $e->getMessage()));
                return;
            }
        }
        
        // Query parameters
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $featured_only = isset($_GET['featured']) && $_GET['featured'] === 'true';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        
        // Build query
        $sql = "SELECT theme_id, theme_name, theme_slug, theme_description, 
                       popularity_score, trending_score, artwork_count, is_featured 
                FROM $themes_table";
        
        $where_clauses = array();
        
        if (!empty($search)) {
            $where_clauses[] = $wpdb->prepare(
                "(theme_name LIKE %s OR theme_description LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        if ($featured_only) {
            $where_clauses[] = "is_featured = 1";
        }
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " ORDER BY popularity_score DESC, artwork_count DESC";
        
        if ($limit > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d", $limit);
        }
        
        // Execute query
        $themes = $wpdb->get_results($sql);
        
        wp_send_json_success(array(
            'themes' => $themes,
            'count' => count($themes)
        ));
    }

    /**
     * Process business idea submission
     *
     * @since 1.0.0
     */
    public static function process_business_idea() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_business_strategist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get business idea
        $business_idea = isset($_POST['business_idea']) ? sanitize_textarea_field($_POST['business_idea']) : '';
        
        if (empty($business_idea)) {
            wp_send_json_error(array('message' => __('Please provide a business idea.', 'vortex-ai-marketplace')));
            return;
        }
        
        // Process the business idea and generate a plan
        if (class_exists('Vortex_Business_Strategist')) {
            $business_strategist = new Vortex_Business_Strategist();
            $business_plan = self::generate_ai_business_plan($business_idea);
            
            // Save the business plan if user is logged in
            $user_id = get_current_user_id();
            $email_sent = false;
            $pdf_url = '';
            
            if ($user_id) {
                // Store plan in user meta
                update_user_meta($user_id, '_vortex_business_idea', $business_idea);
                update_user_meta($user_id, '_vortex_business_plan', $business_plan);
                update_user_meta($user_id, '_vortex_business_plan_date', current_time('mysql'));
                
                // Generate PDF with VortexArtec branding
                $pdf_url = self::generate_business_plan_pdf($business_idea, $business_plan, $user_id);
                
                // Send email with PDF
                $user_info = get_userdata($user_id);
                if ($user_info && !empty($user_info->user_email)) {
                    $email_sent = self::send_business_plan_email($user_info->user_email, $business_plan, $pdf_url);
                }
            } else {
                // For non-logged in users, still generate a downloadable PDF
                $pdf_url = self::generate_business_plan_pdf($business_idea, $business_plan, 0);
            }
            
            // Add PDF URL and email status to the response
            $business_plan['pdf_url'] = $pdf_url;
            $business_plan['email_sent'] = $email_sent;
            
            // Return the business plan
            wp_send_json_success($business_plan);
        } else {
            wp_send_json_error(array('message' => __('Business Strategist module is not available.', 'vortex-ai-marketplace')));
        }
    }

    /**
     * Generate a business plan with AI analysis
     *
     * @since 1.0.0
     * @param string $business_idea The user's business idea
     * @return array The generated business plan
     */
    private static function generate_ai_business_plan($business_idea) {
        // In a real implementation, this would call an AI service
        // For now, create a template-based plan
        
        // Extract keywords from the business idea
        $keywords = self::extract_business_keywords($business_idea);
        
        // Generate a summary based on the idea and keywords
        $summary = self::generate_business_summary($business_idea, $keywords);
        
        // Create a 30-day roadmap
        $roadmap = self::generate_business_roadmap($keywords);
        
        // Create a weekly calendar
        $calendar = self::generate_business_calendar($keywords);
        
        return array(
            'summary' => $summary,
            'roadmap' => $roadmap,
            'calendar' => $calendar
        );
    }

    /**
     * Extract keywords from a business idea
     *
     * @since 1.0.0
     * @param string $business_idea The user's business idea
     * @return array Extracted keywords
     */
    private static function extract_business_keywords($business_idea) {
        // Common terms related to art business
        $common_terms = array(
            'art', 'gallery', 'artist', 'collector', 'museum', 'exhibition',
            'digital', 'painting', 'sculpture', 'photography', 'installation',
            'online', 'platform', 'marketplace', 'auction', 'sale', 'blockchain',
            'NFT', 'token', 'crypto', 'investment', 'commission', 'clients',
            'customers', 'audience', 'demographic', 'marketing', 'social media',
            'promotion', 'branding', 'unique', 'style', 'innovative', 'traditional',
            'contemporary', 'modern', 'classical', 'revenue', 'profit', 'growth'
        );
        
        // Check which terms appear in the business idea
        $keywords = array();
        $business_idea_lower = strtolower($business_idea);
        
        foreach ($common_terms as $term) {
            if (strpos($business_idea_lower, strtolower($term)) !== false) {
                $keywords[] = $term;
            }
        }
        
        // Add default keywords if none found
        if (empty($keywords)) {
            $keywords = array('art', 'business', 'online', 'platform');
        }
        
        return array_unique($keywords);
    }

    /**
     * Generate a business plan summary
     *
     * @since 1.0.0
     * @param string $business_idea The user's business idea
     * @param array $keywords Extracted keywords
     * @return string HTML formatted summary
     */
    private static function generate_business_summary($business_idea, $keywords) {
        // Create a personalized analysis
        $summary = '<p>After analyzing your business idea, I\'ve identified several key strengths and opportunities:</p>';
        
        $summary .= '<div class="vortex-business-analysis">';
        
        // Strengths section based on keywords
        $summary .= '<div class="vortex-analysis-section">';
        $summary .= '<h5>Key Strengths:</h5><ul>';
        
        // Generate 3-4 strengths based on keywords
        $strength_count = min(count($keywords), 4);
        $strengths = array(
            'art' => 'Strong focus on artistic quality that will appeal to discerning collectors',
            'gallery' => 'Physical gallery presence creates credibility in the art market',
            'artist' => 'Direct artist relationships ensure a steady supply of quality work',
            'collector' => 'Understanding of collector needs helps target the right audience',
            'museum' => 'Museum-quality focus elevates your brand positioning',
            'exhibition' => 'Exhibition strategy creates events that drive engagement and sales',
            'digital' => 'Digital focus allows for scalable growth and wider reach',
            'online' => 'Online presence enables access to global art markets',
            'platform' => 'Platform approach creates network effects as you grow',
            'marketplace' => 'Marketplace model allows for diverse revenue streams',
            'blockchain' => 'Blockchain integration provides authenticity verification',
            'NFT' => 'NFT strategy positions you at the forefront of digital art innovation',
            'unique' => 'Your unique approach differentiates you from competitors',
            'innovative' => 'Innovation focus will attract forward-thinking clients',
            'social media' => 'Social media strategy will boost visibility and community building'
        );
        
        $used_strengths = array();
        
        for ($i = 0; $i < $strength_count; $i++) {
            $keyword = $keywords[$i];
            if (isset($strengths[$keyword])) {
                $summary .= '<li>' . $strengths[$keyword] . '</li>';
                $used_strengths[] = $keyword;
            }
        }
        
        // If we didn't find enough strengths, add some generic ones
        if (count($used_strengths) < 3) {
            $generic_strengths = array(
                'Your passion for the art world shines through in your business concept',
                'Clear vision for creating value in the art market',
                'Focus on building relationships will help develop a loyal customer base'
            );
            
            foreach ($generic_strengths as $strength) {
                if (count($used_strengths) >= 3) break;
                $summary .= '<li>' . $strength . '</li>';
                $used_strengths[] = 'generic';
            }
        }
        
        $summary .= '</ul></div>';
        
        // Opportunities section
        $summary .= '<div class="vortex-analysis-section">';
        $summary .= '<h5>Growth Opportunities:</h5><ul>';
        $summary .= '<li>Expand your reach through strategic partnerships with complementary businesses</li>';
        $summary .= '<li>Develop a membership or subscription model for recurring revenue</li>';
        $summary .= '<li>Create educational content to establish thought leadership in your niche</li>';
        $summary .= '<li>Implement data analytics to better understand client preferences and optimize offerings</li>';
        $summary .= '</ul></div>';
        
        $summary .= '</div>';
        
        // Overall recommendation
        $summary .= '<p class="vortex-recommendation">My recommendation is to focus on establishing your unique value proposition, building a strong online presence, and developing relationships with key stakeholders in the art ecosystem. The 30-day roadmap below outlines specific steps to get started.</p>';
        
        return $summary;
    }

    /**
     * Generate a 30-day roadmap
     *
     * @since 1.0.0
     * @param array $keywords Extracted keywords
     * @return string HTML formatted roadmap
     */
    private static function generate_business_roadmap($keywords) {
        $roadmap = '<div class="vortex-roadmap">';
        
        // Days 1-7
        $roadmap .= '<div class="vortex-roadmap-phase">';
        $roadmap .= '<h5>Days 1-7: Foundation</h5>';
        $roadmap .= '<ul>';
        $roadmap .= '<li>Define your mission statement and core values</li>';
        $roadmap .= '<li>Research competitors and identify your unique selling proposition</li>';
        $roadmap .= '<li>Draft a basic business plan with revenue projections</li>';
        $roadmap .= '<li>Set up social media accounts and claim your business name</li>';
        $roadmap .= '<li>Create a simple landing page to capture interested contacts</li>';
        $roadmap .= '</ul>';
        $roadmap .= '</div>';
        
        // Days 8-14
        $roadmap .= '<div class="vortex-roadmap-phase">';
        $roadmap .= '<h5>Days 8-14: Building Relationships</h5>';
        $roadmap .= '<ul>';
        $roadmap .= '<li>Identify and reach out to 10 potential collaborators or partners</li>';
        
        // Customize based on keywords
        if (in_array('artist', $keywords)) {
            $roadmap .= '<li>Connect with 5-10 artists who align with your vision</li>';
        }
        if (in_array('collector', $keywords)) {
            $roadmap .= '<li>Research collector demographics and create ideal customer profiles</li>';
        }
        if (in_array('gallery', $keywords)) {
            $roadmap .= '<li>Visit competitor galleries and analyze their presentation strategies</li>';
        }
        
        $roadmap .= '<li>Join relevant online communities and begin participating</li>';
        $roadmap .= '<li>Schedule informational interviews with industry experts</li>';
        $roadmap .= '</ul>';
        $roadmap .= '</div>';
        
        // Days 15-22
        $roadmap .= '<div class="vortex-roadmap-phase">';
        $roadmap .= '<h5>Days 15-22: Creating Content & Products</h5>';
        $roadmap .= '<ul>';
        
        // Customize based on keywords
        if (in_array('online', $keywords) || in_array('platform', $keywords) || in_array('marketplace', $keywords)) {
            $roadmap .= '<li>Develop a detailed specification for your online platform</li>';
            $roadmap .= '<li>Research technology solutions and potential developers</li>';
        }
        if (in_array('NFT', $keywords) || in_array('blockchain', $keywords)) {
            $roadmap .= '<li>Research blockchain platforms and NFT marketplaces</li>';
            $roadmap .= '<li>Define your tokenomics model and technology approach</li>';
        }
        
        $roadmap .= '<li>Create a content calendar for the next month</li>';
        $roadmap .= '<li>Develop your brand identity (logo, colors, typography)</li>';
        $roadmap .= '<li>Draft key pages for your website</li>';
        $roadmap .= '</ul>';
        $roadmap .= '</div>';
        
        // Days 23-30
        $roadmap .= '<div class="vortex-roadmap-phase">';
        $roadmap .= '<h5>Days 23-30: Launch Preparations</h5>';
        $roadmap .= '<ul>';
        $roadmap .= '<li>Finalize your business plan and go-to-market strategy</li>';
        $roadmap .= '<li>Set up analytics to track key performance indicators</li>';
        $roadmap .= '<li>Create a 90-day action plan with specific milestones</li>';
        $roadmap .= '<li>Prepare a soft launch announcement for your network</li>';
        $roadmap .= '<li>Schedule your first promotional event or content release</li>';
        $roadmap .= '</ul>';
        $roadmap .= '</div>';
        
        $roadmap .= '</div>';
        
        return $roadmap;
    }

    /**
     * Generate a weekly calendar
     *
     * @since 1.0.0
     * @param array $keywords Extracted keywords
     * @return string HTML formatted calendar
     */
    private static function generate_business_calendar($keywords) {
        $calendar = '<div class="vortex-calendar">';
        
        // Week 1
        $calendar .= '<div class="vortex-calendar-week">';
        $calendar .= '<h5>Week 1 Focus: Strategy & Research</h5>';
        $calendar .= '<table class="vortex-schedule-table">';
        $calendar .= '<tr><th>Day</th><th>Morning</th><th>Afternoon</th></tr>';
        $calendar .= '<tr><td>Monday</td><td>Define business mission & vision</td><td>Market research: competitors</td></tr>';
        $calendar .= '<tr><td>Tuesday</td><td>Draft unique selling proposition</td><td>Research target audience</td></tr>';
        $calendar .= '<tr><td>Wednesday</td><td>Financial projection basics</td><td>Setup business social accounts</td></tr>';
        $calendar .= '<tr><td>Thursday</td><td>Build contact list of potential partners</td><td>Create basic landing page</td></tr>';
        $calendar .= '<tr><td>Friday</td><td>Review week\'s progress</td><td>Outline next week\'s goals</td></tr>';
        $calendar .= '</table>';
        $calendar .= '</div>';
        
        // Week 2
        $calendar .= '<div class="vortex-calendar-week">';
        $calendar .= '<h5>Week 2 Focus: Relationships & Networking</h5>';
        $calendar .= '<table class="vortex-schedule-table">';
        $calendar .= '<tr><th>Day</th><th>Morning</th><th>Afternoon</th></tr>';
        $calendar .= '<tr><td>Monday</td><td>Outreach to first 5 potential partners</td><td>Join online communities</td></tr>';
        
        // Customize based on keywords
        if (in_array('artist', $keywords)) {
            $calendar .= '<tr><td>Tuesday</td><td>Research artists for collaboration</td><td>Prepare artist outreach materials</td></tr>';
        } else {
            $calendar .= '<tr><td>Tuesday</td><td>Research industry influencers</td><td>Prepare outreach materials</td></tr>';
        }
        
        if (in_array('collector', $keywords) || in_array('audience', $keywords)) {
            $calendar .= '<tr><td>Wednesday</td><td>Create collector/audience profiles</td><td>Research collector behaviors</td></tr>';
        } else {
            $calendar .= '<tr><td>Wednesday</td><td>Create customer personas</td><td>Research customer behaviors</td></tr>';
        }
        
        $calendar .= '<tr><td>Thursday</td><td>Schedule informational interviews</td><td>Outreach to next 5 potential partners</td></tr>';
        $calendar .= '<tr><td>Friday</td><td>Follow up with contacts</td><td>Document learnings and plan week 3</td></tr>';
        $calendar .= '</table>';
        $calendar .= '</div>';
        
        // Week 3
        $calendar .= '<div class="vortex-calendar-week">';
        $calendar .= '<h5>Week 3 Focus: Content & Product Development</h5>';
        $calendar .= '<table class="vortex-schedule-table">';
        $calendar .= '<tr><th>Day</th><th>Morning</th><th>Afternoon</th></tr>';
        
        // Customize based on keywords
        if (in_array('platform', $keywords) || in_array('marketplace', $keywords) || in_array('online', $keywords)) {
            $calendar .= '<tr><td>Monday</td><td>Draft platform requirements</td><td>Research technology solutions</td></tr>';
            $calendar .= '<tr><td>Tuesday</td><td>Contact potential developers</td><td>Plan platform user experience</td></tr>';
        } else {
            $calendar .= '<tr><td>Monday</td><td>Draft product/service offering</td><td>Research production/delivery methods</td></tr>';
            $calendar .= '<tr><td>Tuesday</td><td>Create pricing strategy</td><td>Plan customer experience</td></tr>';
        }
        
        $calendar .= '<tr><td>Wednesday</td><td>Brand identity workshop</td><td>Begin logo design process</td></tr>';
        $calendar .= '<tr><td>Thursday</td><td>Create content calendar</td><td>Draft website main pages</td></tr>';
        $calendar .= '<tr><td>Friday</td><td>Review branding materials</td><td>Finalize week 4 plan</td></tr>';
        $calendar .= '</table>';
        $calendar .= '</div>';
        
        // Week 4
        $calendar .= '<div class="vortex-calendar-week">';
        $calendar .= '<h5>Week 4 Focus: Launch Preparation</h5>';
        $calendar .= '<table class="vortex-schedule-table">';
        $calendar .= '<tr><th>Day</th><th>Morning</th><th>Afternoon</th></tr>';
        $calendar .= '<tr><td>Monday</td><td>Finalize business plan</td><td>Setup analytics tracking</td></tr>';
        $calendar .= '<tr><td>Tuesday</td><td>Create 90-day milestone plan</td><td>Build KPI dashboard</td></tr>';
        $calendar .= '<tr><td>Wednesday</td><td>Draft launch announcement</td><td>Plan first promotional event</td></tr>';
        $calendar .= '<tr><td>Thursday</td><td>Prepare pitch materials</td><td>Practice your elevator pitch</td></tr>';
        $calendar .= '<tr><td>Friday</td><td>Review month\'s achievements</td><td>Schedule strategy check-in with Business Strategist</td></tr>';
        $calendar .= '</table>';
        $calendar .= '</div>';
        
        $calendar .= '</div>';
        
        return $calendar;
    }

    /**
     * Generate PDF of business plan with VortexArtec branding
     *
     * @since 1.0.0
     * @param string $business_idea The user's business idea
     * @param array $business_plan The generated business plan
     * @param int $user_id User ID (0 for anonymous users)
     * @return string URL to the generated PDF
     */
    private static function generate_business_plan_pdf($business_idea, $business_plan, $user_id) {
        // Create directory for PDFs if it doesn't exist
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/vortex-plans';
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        // Generate unique filename
        $timestamp = time();
        $filename = 'vortex-business-plan-' . ($user_id ? $user_id . '-' : '') . $timestamp . '.pdf';
        $pdf_path = $pdf_dir . '/' . $filename;
        $pdf_url = $upload_dir['baseurl'] . '/vortex-plans/' . $filename;
        
        // Check if we have a PDF generation library available
        if (class_exists('TCPDF') || class_exists('FPDF') || function_exists('imagepdf_output_pdf')) {
            // Use available library to generate PDF
            // For this implementation we'll create a simplified version using PHP's built-in functionality
            // In a real implementation, you would use a proper PDF library
            
            // HTML content for PDF
            $html = self::get_business_plan_html_for_pdf($business_idea, $business_plan);
            
            // Try to generate a simple PDF using PHP
            try {
                $pdf_content = '<html><head><title>Your VortexArtec Business Plan</title></head><body>' . $html . '</body></html>';
                file_put_contents($pdf_path, $pdf_content);
            } catch (Exception $e) {
                error_log('Failed to generate PDF: ' . $e->getMessage());
                // If PDF generation fails, create a fallback file
                $pdf_url = admin_url('admin-ajax.php') . '?action=vortex_download_business_plan&plan_id=' . $timestamp . '&nonce=' . wp_create_nonce('vortex_download_plan_' . $timestamp);
            }
        } else {
            // No PDF library available, provide a URL to generate the PDF on request
            $pdf_url = admin_url('admin-ajax.php') . '?action=vortex_download_business_plan&plan_id=' . $timestamp . '&user_id=' . $user_id . '&nonce=' . wp_create_nonce('vortex_download_plan_' . $timestamp);
        }
        
        return $pdf_url;
    }

    /**
     * Generate HTML content for PDF with VortexArtec branding
     *
     * @since 1.0.0
     * @param string $business_idea The user's business idea
     * @param array $business_plan The generated business plan
     * @return string HTML content
     */
    private static function get_business_plan_html_for_pdf($business_idea, $business_plan) {
        // Logo URL - VortexArtec.com branding
        $logo_url = VORTEX_PLUGIN_URL . 'images/vortexartec-logo.png';
        
        $html = '
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="' . $logo_url . '" alt="VortexArtec.com" style="max-width: 250px; height: auto;">
                <h1 style="color: #4A26AB; margin-top: 20px;">Your Business Plan</h1>
            </div>
            
            <div style="margin-bottom: 20px; background-color: #f9f9f9; padding: 15px; border-left: 4px solid #4A26AB;">
                <h2 style="color: #4A26AB; margin-top: 0;">Your Business Idea</h2>
                <p>' . nl2br(esc_html($business_idea)) . '</p>
            </div>
            
            <div style="margin-bottom: 30px;">
                <h2 style="color: #4A26AB; border-bottom: 2px solid #4A26AB; padding-bottom: 10px;">Business Analysis</h2>
                ' . $business_plan['summary'] . '
            </div>
            
            <div style="margin-bottom: 30px;">
                <h2 style="color: #4A26AB; border-bottom: 2px solid #4A26AB; padding-bottom: 10px;">30-Day Roadmap</h2>
                ' . $business_plan['roadmap'] . '
            </div>
            
            <div style="margin-bottom: 30px;">
                <h2 style="color: #4A26AB; border-bottom: 2px solid #4A26AB; padding-bottom: 10px;">Weekly Calendar</h2>
                ' . $business_plan['calendar'] . '
            </div>
            
            <div style="text-align: center; margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; font-size: 12px; color: #666;">
                <p>Generated by VortexArtec.com Business Strategist AI</p>
                <p><a href="https://vortexartec.com">https://vortexartec.com</a></p>
            </div>
        </div>';
        
        return $html;
    }

    /**
     * Send business plan email with PDF attachment
     *
     * @since 1.0.0
     * @param string $email Recipient email
     * @param array $business_plan The generated business plan
     * @param string $pdf_url URL to the PDF file
     * @return bool Whether the email was sent successfully
     */
    private static function send_business_plan_email($email, $business_plan, $pdf_url) {
        $subject = __('Your VortexArtec Business Plan', 'vortex-ai-marketplace');
        
        $logo_url = VORTEX_PLUGIN_URL . 'images/vortexartec-logo.png';
        $site_url = site_url();
        
        $message = '
        <html>
        <head>
            <title>' . $subject . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="' . $logo_url . '" alt="VortexArtec.com" style="max-width: 200px; height: auto;">
            </div>
            
            <div style="background-color: #f9f9f9; padding: 20px; border-radius: 5px;">
                <h2 style="color: #4A26AB;">Your Business Plan is Ready!</h2>
                <p>Thank you for using the VortexArtec Business Strategist AI. Your personalized business plan has been generated and is attached to this email.</p>
                
                <p>Your plan includes:</p>
                <ul>
                    <li>Detailed business analysis</li>
                    <li>30-day implementation roadmap</li>
                    <li>Weekly calendar with daily tasks</li>
                    <li>Growth opportunities and recommendations</li>
                </ul>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $pdf_url . '" style="background-color: #4A26AB; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Download Your Business Plan</a>
                </div>
                
                <p>If you have any questions about your plan, simply open the Business Strategist window on our website.</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
                <p>This email was sent from <a href="' . $site_url . '">' . $site_url . '</a></p>
                <p>&copy; ' . date('Y') . ' VortexArtec.com - All Rights Reserved</p>
            </div>
        </body>
        </html>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Attempt to attach the PDF if it's a local file
        $pdf_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $pdf_url);
        if (file_exists($pdf_path)) {
            // Add the PDF as an attachment
            $attachments = array($pdf_path);
            return wp_mail($email, $subject, $message, $headers, $attachments);
        } else {
            // Send without attachment but with download link
            return wp_mail($email, $subject, $message, $headers);
        }
    }

    /**
     * Handle business plan PDF download request
     *
     * @since 1.0.0
     */
    public static function download_business_plan() {
        // Check parameters
        $plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
        $nonce = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        // Verify nonce
        if (!wp_verify_nonce($nonce, 'vortex_download_plan_' . $plan_id)) {
            wp_die(__('Security check failed.', 'vortex-ai-marketplace'));
        }
        
        // Get business plan from user meta if user_id is provided
        $business_plan = array();
        $business_idea = '';
        
        if ($user_id > 0) {
            $business_plan = get_user_meta($user_id, '_vortex_business_plan', true);
            $business_idea = get_user_meta($user_id, '_vortex_business_idea', true);
        } else {
            // For non-logged in users, try to get from transient
            $business_plan = get_transient('vortex_business_plan_' . $plan_id);
            $business_idea = get_transient('vortex_business_idea_' . $plan_id);
        }
        
        // If plan not found, show error
        if (empty($business_plan)) {
            wp_die(__('Business plan not found or has expired.', 'vortex-ai-marketplace'));
        }
        
        // Generate HTML content
        $html = self::get_business_plan_html_for_pdf($business_idea, $business_plan);
        
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="vortex-business-plan.pdf"');
        header('Cache-Control: max-age=0');
        
        // If we have a PDF library, use it to generate PDF
        if (class_exists('TCPDF')) {
            // Use TCPDF to convert HTML to PDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('VortexArtec.com');
            $pdf->SetAuthor('VortexArtec Business Strategist');
            $pdf->SetTitle('Your Business Plan');
            $pdf->SetSubject('Business Plan');
            $pdf->SetKeywords('business plan, strategy, vortexartec');
            $pdf->SetHeaderData('', 0, 'VortexArtec Business Plan', 'Generated by VortexArtec.com');
            $pdf->setHeaderFont(Array('helvetica', '', 10));
            $pdf->setFooterFont(Array('helvetica', '', 8));
            $pdf->SetDefaultMonospacedFont('courier');
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output('vortex-business-plan.pdf', 'D');
            exit;
        } else if (class_exists('FPDF')) {
            // Use FPDF to generate a simple PDF
            require_once(FPDF_PATH);
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(40, 10, 'VortexArtec Business Plan');
            $pdf->Output('D', 'vortex-business-plan.pdf');
            exit;
        } else {
            // Fall back to outputting HTML
            echo '<!DOCTYPE html>
                <html>
                <head>
                    <title>Your VortexArtec Business Plan</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .print-button { display: block; margin: 20px auto; padding: 10px 20px; background: #4A26AB; color: white; border: none; cursor: pointer; }
                        @media print {
                            .print-button { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <button class="print-button" onclick="window.print()">Print Business Plan</button>
                    ' . $html . '
                    <script>
                        window.onload = function() {
                            // Automatically open print dialog
                            setTimeout(function() {
                                window.print();
                            }, 1000);
                        };
                    </script>
                </body>
                </html>';
            exit;
        }
    }

    /**
     * Handle enabling milestone reminders for business plan
     *
     * @since 1.0.0
     */
    public static function enable_milestone_reminders() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_business_strategist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            return;
        }
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to enable reminders.', 'vortex-ai-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Enable reminders for user
        update_user_meta($user_id, 'vortex_milestone_reminders_enabled', true);
        
        // Get business plan
        $business_plan = get_user_meta($user_id, '_vortex_business_plan', true);
        
        // Schedule reminders for business plan milestones
        if (!empty($business_plan)) {
            // Schedule weekly reminders for the next 30 days
            for ($week = 1; $week <= 4; $week++) {
                $timestamp = strtotime("+{$week} week");
                
                // Schedule weekly reminder
                wp_schedule_single_event($timestamp, 'vortex_send_milestone_reminder', array(
                    'user_id' => $user_id,
                    'week' => $week,
                    'plan_type' => 'business'
                ));
            }
        }
        
        // Log that reminders were enabled
        do_action('vortex_log_user_action', $user_id, 'enable_milestone_reminders', array(
            'timestamp' => current_time('timestamp'),
            'plan_type' => 'business'
        ));
        
        wp_send_json_success(array('message' => __('Milestone reminders enabled successfully.', 'vortex-ai-marketplace')));
    }
    
    /**
     * Handle AI agent message requests
     */
    public static function handle_ai_agent_message() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_ai_agent_security')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
        
        // Get agent id and message
        $agent_id = isset($_POST['agent_id']) ? sanitize_text_field($_POST['agent_id']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (empty($agent_id) || empty($message)) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
            exit;
        }
        
        // If Vortex_AI_Agents class exists, forward the request to it
        if (class_exists('Vortex_AI_Agents')) {
            $ai_agents = new Vortex_AI_Agents();
            $ai_agents->process_chat_message();
            exit; // The process_chat_message method will send the JSON response
        }
        
        // Fallback response if the AI Agents class is not available
        wp_send_json_error(array('message' => 'AI Agent system is not available'));
    }

    /**
     * Analyze artist qualification quiz responses
     */
    public static function analyze_quiz_responses() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['security'] ?? '', 'vortex_quiz_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Get quiz responses
        $education = sanitize_text_field($_POST['education'] ?? '');
        $self_taught_years = intval($_POST['self_taught_years'] ?? 0);
        $style = sanitize_text_field($_POST['style'] ?? '');
        $exhibitions = sanitize_text_field($_POST['exhibitions'] ?? '');
        $price_range = sanitize_text_field($_POST['price_range'] ?? '');
        $seed_commitment = isset($_POST['seed_art_commitment']) ? (bool)$_POST['seed_art_commitment'] : false;
        
        // Initialize score and feedback
        $score = 0;
        $feedback = '';
        $tier = 'standard';
        
        // Evaluate education
        if ($education === 'formal_degree') {
            $score += 3;
        } elseif ($education === 'formal_courses') {
            $score += 2;
        } elseif ($education === 'self_taught' && $self_taught_years >= 5) {
            $score += 2;
        } elseif ($education === 'self_taught' && $self_taught_years >= 2) {
            $score += 1;
        }
        
        // Evaluate exhibitions
        if ($exhibitions === 'gallery_featured') {
            $score += 3;
        } elseif ($exhibitions === 'group_exhibitions') {
            $score += 2;
        } elseif ($exhibitions === 'online_curated') {
            $score += 1;
        }
        
        // Evaluate price range
        if ($price_range === 'premium') {
            $score += 3;
        } elseif ($price_range === 'mid_range') {
            $score += 2;
        } elseif ($price_range === 'entry_level') {
            $score += 1;
        }
        
        // Seed art commitment is mandatory
        if (!$seed_commitment) {
            $feedback = __('Commitment to regular seed art uploads is required to participate as an artist in our marketplace. Please agree to this commitment.', 'vortex-ai-marketplace');
            wp_send_json_error(array(
                'message' => $feedback
            ));
        }
        
        // Determine tier and feedback based on score
        if ($score >= 7) {
            $tier = 'premium';
            $feedback = __('Your qualifications and experience suggest you would be an excellent addition to our premium artist tier. Your background in art education, exhibition history, and pricing strategy align well with our marketplace vision.', 'vortex-ai-marketplace');
        } elseif ($score >= 4) {
            $tier = 'advanced';
            $feedback = __('Your experience and background qualify you for our advanced artist tier. We appreciate your commitment to your craft and look forward to featuring your work in our marketplace.', 'vortex-ai-marketplace');
        } else {
            $tier = 'standard';
            $feedback = __('Welcome to our artist community! We\'re excited to help you grow your artistic career. Our standard tier is a great starting point for artists who are building their portfolio and market presence.', 'vortex-ai-marketplace');
        }
        
        // Add seed art commitment reminder
        $feedback .= ' ' . __('Remember, as part of your artist agreement, you\'ve committed to uploading at least two hand-crafted seed artworks weekly.', 'vortex-ai-marketplace');
        
        // Return the results
        wp_send_json_success(array(
            'tier' => $tier,
            'feedback' => $feedback,
            'score' => $score
        ));
    }
} 