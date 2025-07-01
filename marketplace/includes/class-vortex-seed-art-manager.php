<?php
/**
 * The seed artwork management functionality.
 *
 * @link       https://vortexartec.com
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
 * The seed artwork management functionality.
 *
 * Handles seed artwork uploads, verification, and artist status management
 * based on compliance with the upload requirements.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Vortex AI Team
 */
class Vortex_Seed_Art_Manager {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Initialize hooks
        add_action('init', array($this, 'register_post_meta'));
        add_action('save_post', array($this, 'handle_artwork_save'), 10, 2);
        
        // Artist status checking
        add_action('vortex_daily_seed_art_check', array($this, 'check_artist_seed_uploads'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_mark_as_seed_art', array($this, 'mark_as_seed_art'));
        add_action('wp_ajax_run_seed_art_check', array($this, 'ajax_run_seed_art_check'));
        add_action('wp_ajax_reset_artist_status', array($this, 'ajax_reset_artist_status'));
        
        // Schedule daily check if not already scheduled
        if (!wp_next_scheduled('vortex_daily_seed_art_check')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_seed_art_check');
        }
    }

    /**
     * Register custom post meta for artwork.
     *
     * @since    1.0.0
     */
    public function register_post_meta() {
        register_post_meta('vortex_artwork', '_vortex_is_seed_artwork', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
    }

    /**
     * Handle artwork save to process seed art uploads.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @param    WP_Post   $post       The post object.
     */
    public function handle_artwork_save($post_id, $post) {
        // Bail early if this is not an artwork post
        if ($post->post_type !== 'vortex_artwork') {
            return;
        }
        
        // Don't process during autosave or revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check if this is a new post
        $is_new = get_post_meta($post_id, '_vortex_first_save', true) ? false : true;
        if ($is_new) {
            update_post_meta($post_id, '_vortex_first_save', true);
            
            // Check if user has the seed art commitment
            $user_id = $post->post_author;
            $seed_commitment = get_user_meta($user_id, '_vortex_artist_seed_commitment', true);
            
            if ($seed_commitment) {
                // Offer option to mark as seed art
                add_action('admin_notices', array($this, 'display_seed_art_notice'));
                
                // Store temporary post ID for the notice
                set_transient('vortex_new_artwork_' . get_current_user_id(), $post_id, 60 * 5); // 5 minutes
            }
        }
        
        // Check if explicitly marked as seed art
        $is_seed_art = isset($_POST['vortex_is_seed_artwork']) ? (bool) $_POST['vortex_is_seed_artwork'] : false;
        
        if ($is_seed_art) {
            $this->process_seed_artwork($post_id, $post->post_author);
        }
    }

    /**
     * Display notice for marking new artwork as seed art.
     *
     * @since    1.0.0
     */
    public function display_seed_art_notice() {
        // Get the post ID from transient
        $post_id = get_transient('vortex_new_artwork_' . get_current_user_id());
        
        if (!$post_id) {
            return;
        }
        
        // Clear the transient to avoid showing the notice multiple times
        delete_transient('vortex_new_artwork_' . get_current_user_id());
        
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <?php 
                printf(
                    __('Would you like to mark this artwork as one of your weekly seed artworks? <a href="%s" class="button button-primary">Yes, Mark as Seed Art</a>', 'vortex-ai-marketplace'),
                    esc_url(
                        add_query_arg(
                            array(
                                'action' => 'vortex_mark_as_seed_art',
                                'post_id' => $post_id,
                                'security' => wp_create_nonce('vortex_seed_art_nonce')
                            ),
                            admin_url('admin-ajax.php')
                        )
                    )
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX handler for marking artwork as seed art.
     *
     * @since    1.0.0
     */
    public function mark_as_seed_art() {
        // Check nonce
        if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'vortex_seed_art_nonce')) {
            wp_die(__('Security check failed.', 'vortex-ai-marketplace'));
        }
        
        // Get post ID
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        if (!$post_id) {
            wp_die(__('No artwork specified.', 'vortex-ai-marketplace'));
        }
        
        // Get post
        $post = get_post($post_id);
        
        // Check if user is the author
        if (get_current_user_id() !== (int) $post->post_author) {
            wp_die(__('You do not have permission to modify this artwork.', 'vortex-ai-marketplace'));
        }
        
        // Process as seed artwork
        $this->process_seed_artwork($post_id, $post->post_author);
        
        // Redirect back to the post
        wp_redirect(get_edit_post_link($post_id, 'redirect'));
        exit;
    }

    /**
     * Process artwork as seed art and update artist status.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     * @param    int    $user_id    The user ID.
     */
    public function process_seed_artwork($post_id, $user_id) {
        // Mark artwork as seed art
        update_post_meta($post_id, '_vortex_is_seed_artwork', true);
        update_post_meta($post_id, '_vortex_seed_date', current_time('mysql'));
        
        // Get user's seed art status
        $seed_status = get_user_meta($user_id, '_vortex_artist_seed_status', true);
        $uploads_due = intval(get_user_meta($user_id, '_vortex_artist_seed_uploads_due', true));
        
        // Update last upload date
        update_user_meta($user_id, '_vortex_artist_last_seed_upload', current_time('mysql'));
        
        // Decrement uploads due
        if ($uploads_due > 0) {
            $uploads_due--;
            update_user_meta($user_id, '_vortex_artist_seed_uploads_due', $uploads_due);
        }
        
        // If user was inactive but now caught up, restore status
        if ($seed_status === 'inactive' && $uploads_due <= 0) {
            update_user_meta($user_id, '_vortex_artist_seed_status', 'active');
            
            // Restore artist role
            $user = new WP_User($user_id);
            $user->set_role('vortex_artist');
            
            // Send notification
            $this->send_status_change_email($user_id, 'active');
        }
    }

    /**
     * Check all artists for seed art upload compliance.
     * 
     * This function is meant to be run daily via WP Cron.
     *
     * @since    1.0.0
     */
    public function check_artist_seed_uploads() {
        // Get all users with artist role or artists with inactive status
        $artist_users = get_users(array(
            'role' => 'vortex_artist',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_vortex_artist_seed_commitment',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        // Also get users who were artists but are now inactive
        $inactive_artists = get_users(array(
            'role' => 'subscriber',
            'meta_query' => array(
                array(
                    'key' => '_vortex_artist_seed_status',
                    'value' => 'inactive',
                    'compare' => '='
                )
            )
        ));
        
        // Combine the user arrays
        $all_users = array_merge($artist_users, $inactive_artists);
        
        $current_time = current_time('mysql');
        
        foreach ($all_users as $user) {
            $user_id = $user->ID;
            
            // Skip if user hasn't committed to seed uploads
            $seed_commitment = get_user_meta($user_id, '_vortex_artist_seed_commitment', true);
            if (empty($seed_commitment)) {
                continue;
            }
            
            // Get current status
            $seed_status = get_user_meta($user_id, '_vortex_artist_seed_status', true);
            if (empty($seed_status)) {
                $seed_status = 'active';
                update_user_meta($user_id, '_vortex_artist_seed_status', $seed_status);
            }
            
            $last_upload = get_user_meta($user_id, '_vortex_artist_last_seed_upload', true);
            $uploads_due = intval(get_user_meta($user_id, '_vortex_artist_seed_uploads_due', true));
            
            if (empty($last_upload)) {
                // For new artists, set initial last upload date as today minus 3 days (grace period)
                $last_upload = date('Y-m-d H:i:s', strtotime('-3 days'));
                update_user_meta($user_id, '_vortex_artist_last_seed_upload', $last_upload);
            }
            
            // Calculate days since last upload
            $days_since_upload = (strtotime($current_time) - strtotime($last_upload)) / DAY_IN_SECONDS;
            
            // Get grace period setting (default: 7 days)
            $grace_period = intval(get_option('vortex_seed_grace_period', 7));
            
            // Check if artist has missed uploads beyond the grace period
            if ($days_since_upload > $grace_period && $seed_status === 'active') {
                // Change status to inactive
                update_user_meta($user_id, '_vortex_artist_seed_status', 'inactive');
                
                // Change user role to regular member
                $user_obj = new WP_User($user_id);
                $user_obj->remove_role('vortex_artist');
                $user_obj->add_role('subscriber');
                
                // Calculate uploads due (2 per week)
                $weeks_missed = ceil($days_since_upload / 7);
                $weekly_requirement = intval(get_option('vortex_weekly_seed_requirement', 2));
                $new_uploads_due = $weeks_missed * $weekly_requirement;
                update_user_meta($user_id, '_vortex_artist_seed_uploads_due', $new_uploads_due);
                
                // Send notification email
                $this->send_status_change_email($user_id, 'inactive', $new_uploads_due);
                
            } elseif ($days_since_upload > $grace_period && $seed_status === 'inactive') {
                // Update uploads due counter for already inactive artists
                $weeks_missed = ceil($days_since_upload / 7);
                $weekly_requirement = intval(get_option('vortex_weekly_seed_requirement', 2));
                $new_uploads_due = $weeks_missed * $weekly_requirement;
                update_user_meta($user_id, '_vortex_artist_seed_uploads_due', $new_uploads_due);
            }
        }
    }

    /**
     * AJAX handler to run the seed art check manually.
     *
     * @since    1.0.0
     */
    public function ajax_run_seed_art_check() {
        // Check nonce
        check_ajax_referer('vortex_seed_art_check_nonce', 'security');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
        }
        
        // Run the check
        $this->check_artist_seed_uploads();
        
        wp_send_json_success(array('message' => __('Seed art check completed successfully.', 'vortex-ai-marketplace')));
    }

    /**
     * AJAX handler to reset an artist's status.
     *
     * @since    1.0.0
     */
    public function ajax_reset_artist_status() {
        // Check nonce
        check_ajax_referer('vortex_reset_artist_status_nonce', 'security');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
        }
        
        // Get user ID
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('No user specified.', 'vortex-ai-marketplace')));
        }
        
        // Update user meta
        update_user_meta($user_id, '_vortex_artist_seed_status', 'active');
        update_user_meta($user_id, '_vortex_artist_uploads_due', 0);
        update_user_meta($user_id, '_vortex_artist_last_seed_upload', current_time('mysql'));
        
        // Update user role
        $user = new WP_User($user_id);
        $user->remove_role('subscriber');
        $user->add_role('vortex_artist');
        
        // Send notification
        $this->send_status_change_email($user_id, 'active');
        
        wp_send_json_success(array('message' => __('Artist status reset successfully.', 'vortex-ai-marketplace')));
    }

    /**
     * Send email notification when artist status changes.
     *
     * @since    1.0.0
     * @param    int      $user_id      User ID
     * @param    string   $new_status   New status (active/inactive)
     * @param    int      $uploads_due  Number of uploads due (for inactive status)
     */
    private function send_status_change_email($user_id, $new_status, $uploads_due = 0) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        
        if ($new_status === 'inactive') {
            $subject = sprintf(__('[%s] Artist Status Update: Action Required', 'vortex-ai-marketplace'), $site_name);
            $message = sprintf(__('Hello %s,', 'vortex-ai-marketplace'), $user->display_name) . "\n\n";
            $message .= __('Your artist status on the Vortex AI Marketplace has been changed to inactive due to missed seed artwork uploads.', 'vortex-ai-marketplace') . "\n\n";
            $message .= sprintf(__('You currently have %d seed artwork uploads due. To restore your artist status, please upload these artworks.', 'vortex-ai-marketplace'), $uploads_due) . "\n\n";
            $message .= __('While your status is inactive, you will have limited access to artist features, but you can still access your account and artwork library.', 'vortex-ai-marketplace') . "\n\n";
            $message .= __('To upload seed artworks, please visit:', 'vortex-ai-marketplace') . "\n";
            $message .= home_url('/submit-artwork/') . "\n\n";
            $message .= __('Thank you for your understanding.', 'vortex-ai-marketplace') . "\n\n";
            $message .= sprintf(__('The %s Team', 'vortex-ai-marketplace'), $site_name);
            
        } else {
            $subject = sprintf(__('[%s] Artist Status Restored', 'vortex-ai-marketplace'), $site_name);
            $message = sprintf(__('Hello %s,', 'vortex-ai-marketplace'), $user->display_name) . "\n\n";
            $message .= __('Great news! Your artist status on the Vortex AI Marketplace has been restored.', 'vortex-ai-marketplace') . "\n\n";
            $message .= __('You now have full access to all artist features again. Remember to continue uploading your weekly seed artworks to maintain your status.', 'vortex-ai-marketplace') . "\n\n";
            $message .= __('Thank you for your continued participation in our creative community.', 'vortex-ai-marketplace') . "\n\n";
            $message .= sprintf(__('The %s Team', 'vortex-ai-marketplace'), $site_name);
        }
        
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Get all artists with their seed art status.
     *
     * @since    1.0.0
     * @return   array    Array of artists with status data
     */
    public function get_artists_seed_status() {
        // Get active artists
        $active_artists = get_users(array(
            'role' => 'vortex_artist',
            'meta_query' => array(
                array(
                    'key' => '_vortex_artist_seed_commitment',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        // Get inactive artists
        $inactive_artists = get_users(array(
            'role' => 'subscriber',
            'meta_query' => array(
                array(
                    'key' => '_vortex_artist_seed_status',
                    'value' => 'inactive',
                    'compare' => '='
                )
            )
        ));
        
        // Combine user arrays
        $all_users = array_merge($active_artists, $inactive_artists);
        
        $artists_data = array();
        
        foreach ($all_users as $user) {
            $user_id = $user->ID;
            
            // Get status data
            $status = get_user_meta($user_id, '_vortex_artist_seed_status', true);
            $last_upload = get_user_meta($user_id, '_vortex_artist_last_seed_upload', true);
            $uploads_due = intval(get_user_meta($user_id, '_vortex_artist_seed_uploads_due', true));
            
            if (empty($status)) {
                $status = 'active';
            }
            
            // Get artist profile
            $artist_profile_id = get_user_meta($user_id, '_vortex_artist_profile_id', true);
            
            $artists_data[] = array(
                'user_id' => $user_id,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'status' => $status,
                'last_upload' => $last_upload,
                'uploads_due' => $uploads_due,
                'profile_id' => $artist_profile_id
            );
        }
        
        return $artists_data;
    }

    /**
     * Get the count of seed artworks.
     *
     * @since    1.0.0
     * @return   int    Number of seed artworks
     */
    public function get_seed_artwork_count() {
        $args = array(
            'post_type' => 'vortex_artwork',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_vortex_is_seed_artwork',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get the count of active artists.
     *
     * @since    1.0.0
     * @return   int    Number of active artists
     */
    public function get_active_artists_count() {
        $args = array(
            'role' => 'vortex_artist',
            'meta_query' => array(
                array(
                    'key' => '_vortex_artist_seed_commitment',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => '_vortex_artist_seed_status',
                    'value' => 'active',
                    'compare' => '='
                )
            ),
            'count_total' => true
        );
        
        $users = new WP_User_Query($args);
        return $users->get_total();
    }

    /**
     * Get the count of inactive artists.
     *
     * @since    1.0.0
     * @return   int    Number of inactive artists
     */
    public function get_inactive_artists_count() {
        $args = array(
            'role' => 'subscriber',
            'meta_query' => array(
                array(
                    'key' => '_vortex_artist_seed_status',
                    'value' => 'inactive',
                    'compare' => '='
                )
            ),
            'count_total' => true
        );
        
        $users = new WP_User_Query($args);
        return $users->get_total();
    }

    /**
     * Get the count of new artists in a given period.
     *
     * @since    1.0.0
     * @param    int    $days    Number of days
     * @return   int    Number of new artists
     */
    public function get_new_artists_count($days = 30) {
        $date = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        
        $args = array(
            'role' => 'vortex_artist',
            'meta_query' => array(
                array(
                    'key' => '_vortex_artist_registration_date',
                    'value' => $date,
                    'compare' => '>=',
                    'type' => 'DATETIME'
                )
            ),
            'count_total' => true
        );
        
        $users = new WP_User_Query($args);
        return $users->get_total();
    }
}

// Initialize the class
new Vortex_Seed_Art_Manager(); 