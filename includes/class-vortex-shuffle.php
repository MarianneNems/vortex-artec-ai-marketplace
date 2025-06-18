<?php
/**
 * The Gallery Shuffle and Daily Winners functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The gallery shuffle and daily winners functionality.
 *
 * This class handles the scheduled shuffling of the marketplace gallery
 * and the determination of daily winners at midnight.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Shuffle {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      object    $logger    Logger instance.
     */
    private $logger;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     * @param    object    $logger            Optional. Logger instance.
     */
    public function __construct( $plugin_name, $version, $logger = null ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->logger = $logger;
        
        // Register hooks
        $this->register_hooks();
    }

    /**
     * Register hooks for shuffle functionality.
     *
     * @since    1.0.0
     */
    private function register_hooks() {
        // Schedule three-hour shuffle event if not already scheduled
        if ( ! wp_next_scheduled( 'vortex_shuffle_gallery' ) ) {
            wp_schedule_event( time(), 'three_hours', 'vortex_shuffle_gallery' );
        }
        
        // Schedule daily winner determination at midnight
        if ( ! wp_next_scheduled( 'vortex_determine_daily_winners' ) ) {
            // Schedule for midnight (00:00)
            $midnight = strtotime('tomorrow midnight');
            wp_schedule_event( $midnight, 'daily', 'vortex_determine_daily_winners' );
        }
        
        // Register custom cron schedule for three hours
        add_filter( 'cron_schedules', array( $this, 'add_three_hour_cron_schedule' ) );
        
        // Hook into the scheduled events
        add_action( 'vortex_shuffle_gallery', array( $this, 'perform_gallery_shuffle' ) );
        add_action( 'vortex_determine_daily_winners', array( $this, 'determine_daily_winners' ) );
        
        // Register shortcode for displaying winners
        add_shortcode( 'vortex_daily_winners', array( $this, 'daily_winners_shortcode' ) );
        
        // Register REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /**
     * Add custom cron schedule for three hours.
     *
     * @since    1.0.0
     * @param    array    $schedules    Existing cron schedules.
     * @return   array                  Modified cron schedules.
     */
    public function add_three_hour_cron_schedule( $schedules ) {
        $schedules['three_hours'] = array(
            'interval' => 3 * HOUR_IN_SECONDS,
            'display'  => __( 'Every Three Hours', 'vortex-ai-marketplace' ),
        );
        return $schedules;
    }

    /**
     * Register REST API routes for gallery shuffle functionality.
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route( 'vortex/v1', '/shuffle/gallery', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'api_manual_shuffle' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ));
        
        register_rest_route( 'vortex/v1', '/winners/daily', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_daily_winners' ),
            'permission_callback' => '__return_true',
            'args' => array(
                'date' => array(
                    'default' => current_time( 'Y-m-d' ),
                    'sanitize_callback' => function( $param ) {
                        return sanitize_text_field( $param );
                    },
                ),
            ),
        ));
    }

    /**
     * Perform the gallery shuffle.
     *
     * @since    1.0.0
     * @return   bool     Success or failure.
     */
    public function perform_gallery_shuffle() {
        $this->log( 'Starting gallery shuffle', 'info' );
        
        // Delegate the shuffle operation to CLOE if available
        if (class_exists('VORTEX_CLOE')) {
            $cloe = VORTEX_CLOE::get_instance();
            if (method_exists($cloe, 'handle_gallery_shuffle')) {
                // Let CLOE handle the shuffle
                do_action('vortex_shuffle_gallery');
                
                // Store shuffle timestamp
                update_option('vortex_last_gallery_shuffle', current_time('mysql'));
                
                $this->log('Delegated gallery shuffle to CLOE AI Agent', 'info');
                
                return true;
            }
        }
        
        // Fallback to standard shuffle if CLOE is not available
        
        // Get ranking instance
        $rankings = Vortex_Rankings::get_instance();
        
        // Get trending time window from settings
        $trending_days = get_option( 'vortex_rankings_trending_days', 7 );
        
        // Recalculate rankings with a randomization factor
        $this->recalculate_rankings_with_shuffle();
        
        // Store shuffle timestamp
        update_option( 'vortex_last_gallery_shuffle', current_time( 'mysql' ) );
        
        $this->log( 'Completed gallery shuffle (standard method)', 'info' );
        
        return true;
    }

    /**
     * Recalculate rankings with a randomization factor for shuffle effect.
     *
     * @since    1.0.0
     * @return   bool     Success or failure.
     */
    private function recalculate_rankings_with_shuffle() {
        global $wpdb;
        
        // Get all artwork rankings
        $rankings_query = "
            SELECT * FROM {$wpdb->prefix}vortex_rankings
            WHERE type = 'artwork'
            AND timeframe = 'weekly'
        ";
        
        $rankings = $wpdb->get_results( $rankings_query );
        
        if ( empty( $rankings ) ) {
            $this->log( 'No rankings found for shuffle', 'warning' );
            return false;
        }
        
        // Apply randomization factor to each ranking
        foreach ( $rankings as $ranking ) {
            // Generate a random factor between 0.85 and 1.15 (15% variation)
            $random_factor = mt_rand( 85, 115 ) / 100;
            
            // Apply CLOE's intelligence to adjust the randomization factor if available
            $random_factor = apply_filters('vortex_gallery_shuffle_randomization', $random_factor);
            
            // Allow artwork-specific adjustments from CLOE
            $random_factor = apply_filters('vortex_shuffle_artwork_adjustment', $random_factor, $ranking->item_id);
            
            // Apply random factor to score
            $new_score = $ranking->overall_score * $random_factor;
            
            // Update the score in the database
            $wpdb->update(
                $wpdb->prefix . 'vortex_rankings',
                array( 'overall_score' => $new_score ),
                array( 'id' => $ranking->id ),
                array( '%f' ),
                array( '%d' )
            );
            
            // Update the post meta as well
            update_post_meta( $ranking->item_id, '_vortex_artwork_score_weekly', $new_score );
        }
        
        // Re-rank all artworks based on new scores
        $this->recalculate_artwork_ranks( 'weekly' );
        
        return true;
    }

    /**
     * Recalculate artwork ranks after score shuffling.
     *
     * @since    1.0.0
     * @param    string    $timeframe    Timeframe for rankings.
     * @return   bool                    Success or failure.
     */
    private function recalculate_artwork_ranks( $timeframe ) {
        global $wpdb;
        
        // Get all artwork rankings ordered by new score
        $rankings_query = $wpdb->prepare("
            SELECT id, item_id, overall_score
            FROM {$wpdb->prefix}vortex_rankings
            WHERE type = 'artwork'
            AND timeframe = %s
            ORDER BY overall_score DESC
        ", $timeframe);
        
        $rankings = $wpdb->get_results( $rankings_query );
        
        if ( empty( $rankings ) ) {
            return false;
        }
        
        // Reassign ranks
        $rank = 1;
        foreach ( $rankings as $ranking ) {
            // Update rank in database
            $wpdb->update(
                $wpdb->prefix . 'vortex_rankings',
                array( 'rank' => $rank ),
                array( 'id' => $ranking->id ),
                array( '%d' ),
                array( '%d' )
            );
            
            // Update post meta
            update_post_meta( $ranking->item_id, '_vortex_artwork_rank_' . $timeframe, $rank );
            
            $rank++;
        }
        
        return true;
    }

    /**
     * Determine daily winners based on rankings.
     *
     * @since    1.0.0
     * @return   array    Array of winners.
     */
    public function determine_daily_winners() {
        $this->log( 'Determining daily winners', 'info' );
        
        // Delegate the winners determination to CLOE if available
        if (class_exists('VORTEX_CLOE')) {
            $cloe = VORTEX_CLOE::get_instance();
            if (method_exists($cloe, 'handle_daily_winners')) {
                // Let CLOE determine the winners
                $winners = array();
                
                // Use do_action to run the CLOE method but with a filter to capture the result
                add_filter('vortex_daily_winners_selection', function($existing_winners) use (&$winners) {
                    $winners = $existing_winners;
                    return $existing_winners;
                }, 999);
                
                do_action('vortex_determine_daily_winners');
                
                // If CLOE successfully determined winners, use them
                if (!empty($winners)) {
                    $this->log(sprintf('Delegated daily winners determination to CLOE, found %d winners', count($winners)), 'info');
                    
                    // Store winners in the database
                    if (!empty($winners[0]['date'])) {
                        $this->store_daily_winners($winners, $winners[0]['date']);
                    } else {
                        $this->store_daily_winners($winners, current_time('Y-m-d'));
                    }
                    
                    return $winners;
                }
            }
        }
        
        // Fallback to standard winners determination if CLOE is not available
        global $wpdb;
        
        // Get today's date
        $today = current_time( 'Y-m-d' );
        
        // Get top artworks from rankings
        $top_artworks_query = "
            SELECT r.*, p.post_title, u.display_name as artist_name
            FROM {$wpdb->prefix}vortex_rankings r
            JOIN {$wpdb->posts} p ON r.item_id = p.ID
            JOIN {$wpdb->users} u ON r.related_id = u.ID
            WHERE r.type = 'artwork'
            AND r.timeframe = 'weekly'
            AND r.rank <= 10
            ORDER BY r.rank ASC
        ";
        
        $top_artworks = $wpdb->get_results( $top_artworks_query );
        
        if ( empty( $top_artworks ) ) {
            $this->log( 'No artworks found for determining winners', 'warning' );
            return array();
        }
        
        // Format winners data
        $winners = array();
        foreach ( $top_artworks as $artwork ) {
            $winners[] = array(
                'rank' => $artwork->rank,
                'artwork_id' => $artwork->item_id,
                'artwork_title' => $artwork->post_title,
                'artist_id' => $artwork->related_id,
                'artist_name' => $artwork->artist_name,
                'score' => $artwork->overall_score,
                'date' => $today,
            );
        }
        
        // Store winners in the database
        $this->store_daily_winners( $winners, $today );
        
        $this->log( sprintf( 'Stored %d daily winners for %s (standard method)', count( $winners ), $today ), 'info' );
        
        // Execute any actions needed when winners are determined
        do_action( 'vortex_daily_winners_determined', $winners, $today );
        
        return $winners;
    }

    /**
     * Store daily winners in the database.
     *
     * @since    1.0.0
     * @param    array     $winners    Array of winner data.
     * @param    string    $date       Date string (Y-m-d).
     */
    private function store_daily_winners( $winners, $date ) {
        // Store as a serialized option
        update_option( 'vortex_daily_winners_' . $date, $winners );
        
        // Also store in a custom table if available
        global $wpdb;
        
        $winners_table = $wpdb->prefix . 'vortex_daily_winners';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$winners_table'" ) === $winners_table ) {
            // Delete any existing entries for this date
            $wpdb->delete(
                $winners_table,
                array( 'winner_date' => $date ),
                array( '%s' )
            );
            
            // Insert new entries
            foreach ( $winners as $winner ) {
                $wpdb->insert(
                    $winners_table,
                    array(
                        'artwork_id' => $winner['artwork_id'],
                        'artist_id' => $winner['artist_id'],
                        'rank' => $winner['rank'],
                        'score' => $winner['score'],
                        'winner_date' => $date,
                    ),
                    array( '%d', '%d', '%d', '%f', '%s' )
                );
            }
        }
    }

    /**
     * Get daily winners for a specific date.
     *
     * @since    1.0.0
     * @param    string    $date    Date string (Y-m-d).
     * @return   array              Array of winners.
     */
    public function get_daily_winners( $date = '' ) {
        if ( empty( $date ) ) {
            $date = current_time( 'Y-m-d' );
        }
        
        // Try to get from option first
        $winners = get_option( 'vortex_daily_winners_' . $date, array() );
        
        // If empty, try to get from database table
        if ( empty( $winners ) ) {
            global $wpdb;
            
            $winners_table = $wpdb->prefix . 'vortex_daily_winners';
            
            // Check if table exists
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$winners_table'" ) === $winners_table ) {
                $winners_query = $wpdb->prepare("
                    SELECT w.*, p.post_title as artwork_title, u.display_name as artist_name
                    FROM $winners_table w
                    JOIN {$wpdb->posts} p ON w.artwork_id = p.ID
                    JOIN {$wpdb->users} u ON w.artist_id = u.ID
                    WHERE w.winner_date = %s
                    ORDER BY w.rank ASC
                ", $date);
                
                $winners_data = $wpdb->get_results( $winners_query );
                
                if ( ! empty( $winners_data ) ) {
                    $winners = array();
                    foreach ( $winners_data as $winner ) {
                        $winners[] = array(
                            'rank' => $winner->rank,
                            'artwork_id' => $winner->artwork_id,
                            'artwork_title' => $winner->artwork_title,
                            'artist_id' => $winner->artist_id,
                            'artist_name' => $winner->artist_name,
                            'score' => $winner->score,
                            'date' => $winner->winner_date,
                        );
                    }
                }
            }
        }
        
        return $winners;
    }

    /**
     * Shortcode for displaying daily winners.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string             HTML content.
     */
    public function daily_winners_shortcode( $atts = array() ) {
        // Default attributes
        $default_atts = array(
            'date' => '',
            'count' => 5,
            'title' => __( 'Daily Winners', 'vortex-ai-marketplace' ),
            'show_rank' => true,
            'show_score' => false,
            'show_insights' => true,
            'columns' => 1,
            'class' => ''
        );
        
        // Parse attributes
        $atts = wp_parse_args( $atts, $default_atts );
        
        // Get winners for the specified date
        $winners = $this->get_daily_winners( $atts['date'] );
        
        // Limit to requested count
        $winners = array_slice( $winners, 0, $atts['count'] );
        
        // Classes for the container
        $classes = array(
            'vortex-daily-winners',
            'vortex-columns-' . $atts['columns']
        );
        
        if ( ! empty( $atts['class'] ) ) {
            $classes[] = $atts['class'];
        }
        
        // Start output buffer
        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h3 class="vortex-winners-title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>
            
            <?php if ( empty( $winners ) ) : ?>
                <p class="vortex-no-results"><?php _e( 'No winners found for this date.', 'vortex-ai-marketplace' ); ?></p>
            <?php else : ?>
                <div class="vortex-winners-grid">
                    <?php foreach ( $winners as $winner ) : ?>
                        <div class="vortex-winner-card">
                            <?php if ( $atts['show_rank'] ) : ?>
                                <div class="vortex-winner-rank"><?php echo esc_html( $winner['rank'] ); ?></div>
                            <?php endif; ?>
                            
                            <div class="vortex-winner-image">
                                <a href="<?php echo esc_url( get_permalink( $winner['artwork_id'] ) ); ?>">
                                    <?php echo get_the_post_thumbnail( $winner['artwork_id'], 'medium' ); ?>
                                </a>
                            </div>
                            
                            <div class="vortex-winner-info">
                                <h4 class="vortex-winner-title">
                                    <a href="<?php echo esc_url( get_permalink( $winner['artwork_id'] ) ); ?>">
                                        <?php echo esc_html( $winner['artwork_title'] ); ?>
                                    </a>
                                </h4>
                                
                                <div class="vortex-winner-artist">
                                    <span class="vortex-artist-label"><?php _e( 'By', 'vortex-ai-marketplace' ); ?></span>
                                    <a href="<?php echo esc_url( get_author_posts_url( $winner['artist_id'] ) ); ?>" class="vortex-artist-name">
                                        <?php echo esc_html( $winner['artist_name'] ); ?>
                                    </a>
                                </div>
                                
                                <?php if ( $atts['show_score'] ) : ?>
                                    <div class="vortex-winner-score">
                                        <span class="vortex-score-label"><?php _e( 'Score:', 'vortex-ai-marketplace' ); ?></span>
                                        <span class="vortex-score-value"><?php echo esc_html( number_format( $winner['score'], 2 ) ); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $atts['show_insights'] && !empty( $winner['cloe_insights'] ) ) : ?>
                                    <div class="vortex-winner-insights">
                                        <span class="vortex-ai-badge"><?php _e( 'CLOE Insights', 'vortex-ai-marketplace' ); ?></span>
                                        <?php if ( !empty( $winner['cloe_insights']['highlight'] ) ) : ?>
                                            <p class="vortex-insight-highlight"><?php echo esc_html( $winner['cloe_insights']['highlight'] ); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * REST API endpoint for manually triggering a gallery shuffle.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    REST API request.
     * @return   WP_REST_Response               REST API response.
     */
    public function api_manual_shuffle( $request ) {
        // Perform the shuffle
        $success = $this->perform_gallery_shuffle();
        
        if ( $success ) {
            return new WP_REST_Response( array(
                'success' => true,
                'message' => __( 'Gallery shuffle performed successfully.', 'vortex-ai-marketplace' ),
                'last_shuffle' => get_option( 'vortex_last_gallery_shuffle' ),
            ), 200 );
        } else {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => __( 'Failed to perform gallery shuffle.', 'vortex-ai-marketplace' ),
            ), 500 );
        }
    }

    /**
     * REST API endpoint for getting daily winners.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    REST API request.
     * @return   WP_REST_Response               REST API response.
     */
    public function api_get_daily_winners( $request ) {
        // Get date parameter
        $date = $request->get_param( 'date' );
        
        // Get winners for the specified date
        $winners = $this->get_daily_winners( $date );
        
        return new WP_REST_Response( array(
            'success' => true,
            'date' => $date,
            'winners' => $winners,
        ), 200 );
    }

    /**
     * Log a message if logger is available.
     *
     * @since    1.0.0
     * @param    string    $message    Message to log.
     * @param    string    $level      Log level.
     */
    private function log( $message, $level = 'info' ) {
        if ( $this->logger && method_exists( $this->logger, 'log' ) ) {
            $this->logger->log( $message, $level );
        }
    }
} 