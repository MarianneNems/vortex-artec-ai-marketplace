<?php
namespace Vortex;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems
 */
class Vortex_Activator {

    /**
     * Activate the plugin.
     *
     * Set up the default options and any necessary database tables.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create required database tables
        self::create_database_tables();
        
        // Set up initial options
        self::setup_options();
        
        // Set up initial schedules
        self::setup_schedules();
        
        // Flush rewrite rules to ensure our custom post types work
        flush_rewrite_rules();
    }

    /**
     * Create necessary directories.
     *
     * @since    1.0.0
     */
    private static function create_directories() {
        // Create the cache directory if it doesn't exist
        $cache_dir = WP_CONTENT_DIR . '/cache/vortex-ai-marketplace';
        if ( ! file_exists( $cache_dir ) ) {
            wp_mkdir_p( $cache_dir );
        }

        // Create an .htaccess file to protect the cache directory
        $htaccess_file = $cache_dir . '/.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            $htaccess_content = "# Disable directory browsing\n";
            $htaccess_content .= "Options -Indexes\n\n";
            $htaccess_content .= "# Deny access to all files\n";
            $htaccess_content .= "<FilesMatch \".*\">\n";
            $htaccess_content .= "    Order Allow,Deny\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</FilesMatch>\n";

            file_put_contents( $htaccess_file, $htaccess_content );
        }
    }

    /**
     * Create necessary database tables during plugin activation.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        self::create_database_tables();
        self::create_required_pages();
        self::set_user_roles();
        self::create_thorius_learning_tables();
    }

    /**
     * Create the plugin's database tables.
     *
     * @since    1.0.0
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Rankings table
        $rankings_table = $wpdb->prefix . 'vortex_rankings';
        
        $rankings_sql = "CREATE TABLE $rankings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL,
            item_id bigint(20) NOT NULL,
            related_id bigint(20) NOT NULL DEFAULT 0,
            rank int(11) NOT NULL,
            overall_score float NOT NULL DEFAULT 0,
            sales_score float NOT NULL DEFAULT 0,
            popularity_score float NOT NULL DEFAULT 0,
            timeframe varchar(20) NOT NULL DEFAULT 'all',
            category_id bigint(20) NOT NULL DEFAULT 0,
            metrics longtext,
            calculated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY item_id (item_id),
            KEY timeframe (timeframe),
            KEY rank (rank)
        ) $charset_collate;";
        
        // Daily winners table
        $winners_table = $wpdb->prefix . 'vortex_daily_winners';
        
        $winners_sql = "CREATE TABLE $winners_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) NOT NULL,
            artist_id bigint(20) NOT NULL,
            rank int(11) NOT NULL,
            score float NOT NULL DEFAULT 0,
            winner_date date NOT NULL,
            PRIMARY KEY  (id),
            KEY artwork_id (artwork_id),
            KEY artist_id (artist_id),
            KEY winner_date (winner_date),
            KEY rank (rank)
        ) $charset_collate;";
        
        // Metrics table
        $metrics_table = $wpdb->prefix . 'vortex_metrics';
        
        $metrics_sql = "CREATE TABLE $metrics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            object_type varchar(20) NOT NULL,
            object_id bigint(20) NOT NULL,
            metric_type varchar(50) NOT NULL,
            metric_value float NOT NULL DEFAULT 0,
            recorded_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY object_type (object_type),
            KEY object_id (object_id),
            KEY metric_type (metric_type),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";
        
        // Include WordPress database upgrade functions
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        // Create the tables
        dbDelta( $rankings_sql );
        dbDelta( $winners_sql );
        dbDelta( $metrics_sql );
    }

    /**
     * Create required pages if they don't exist.
     *
     * @since    1.0.0
     */
    private static function create_required_pages() {
        $pages = array(
            'marketplace' => array(
                'title' => 'AI Art Marketplace',
                'content' => '<!-- wp:shortcode -->[vortex_marketplace]<!-- /wp:shortcode -->',
            ),
            'huraii' => array(
                'title' => 'HURAII AI Creator',
                'content' => '<!-- wp:shortcode -->[vortex_huraii]<!-- /wp:shortcode -->',
            ),
            'artists' => array(
                'title' => 'VORTEX Artists',
                'content' => '<!-- wp:shortcode -->[vortex_artists]<!-- /wp:shortcode -->',
            ),
            'wallet' => array(
                'title' => 'TOLA Wallet',
                'content' => '<!-- wp:shortcode -->[vortex_tola_wallet]<!-- /wp:shortcode -->',
            ),
            'metrics' => array(
                'title' => 'Marketplace Metrics',
                'content' => '<!-- wp:shortcode -->[vortex_metrics]<!-- /wp:shortcode -->',
            ),
        );
        
        foreach ($pages as $slug => $page_data) {
            // Check if page exists
            $page_exists = get_page_by_path($slug);
            
            if (!$page_exists) {
                // Create page
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                ));
                
                // Save page ID in options
                update_option('vortex_page_' . $slug, $page_id);
            }
        }
    }

    /**
     * Set up user roles and capabilities.
     *
     * @since    1.0.0
     */
    private static function set_user_roles() {
        // Add Artist role
        add_role('vortex_artist', 'VORTEX Artist', array(
            'read' => true,
            'upload_files' => true,
            'publish_posts' => true,
            'edit_posts' => true,
            'delete_posts' => true,
        ));
        
        // Add Collector role
        add_role('vortex_collector', 'VORTEX Collector', array(
            'read' => true,
        ));
    }

    /**
     * Create Thorius agent learning tables
     */
    private static function create_thorius_learning_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Thorius agents table
        $agents_table = $wpdb->prefix . 'vortex_thorius_agents';
        $sql_agents = "CREATE TABLE IF NOT EXISTS $agents_table (
            agent_id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_name varchar(100) NOT NULL,
            agent_type varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            model_version varchar(50) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (agent_id),
            KEY status (status),
            KEY agent_type (agent_type)
        ) $charset_collate;";
        
        // Agent learning metrics table
        $metrics_table = $wpdb->prefix . 'vortex_agent_learning_metrics';
        $sql_metrics = "CREATE TABLE IF NOT EXISTS $metrics_table (
            metric_id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            accuracy decimal(5,2) DEFAULT NULL,
            learning_rate decimal(5,2) DEFAULT NULL,
            efficiency decimal(5,2) DEFAULT NULL,
            adaptation_id bigint(20) DEFAULT NULL,
            recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (metric_id),
            KEY agent_id (agent_id),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";
        
        // Agent adaptations table
        $adaptations_table = $wpdb->prefix . 'vortex_agent_adaptations';
        $sql_adaptations = "CREATE TABLE IF NOT EXISTS $adaptations_table (
            adaptation_id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            adaptation_type varchar(100) NOT NULL,
            impact varchar(20) DEFAULT 'medium',
            parameters longtext DEFAULT NULL,
            adaptation_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (adaptation_id),
            KEY agent_id (agent_id),
            KEY adaptation_time (adaptation_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_agents);
        dbDelta($sql_metrics);
        dbDelta($sql_adaptations);
    }

    /**
     * Set up initial options.
     *
     * @since    1.0.0
     */
    private static function setup_options() {
        // Rankings settings with default values
        if ( ! get_option( 'vortex_rankings_settings' ) ) {
            update_option( 'vortex_rankings_settings', array(
                'artist_ranking_factors' => array(
                    'sales_volume' => 35,
                    'artwork_quality' => 25,
                    'user_engagement' => 20,
                    'upload_frequency' => 10,
                    'marketplace_activity' => 10
                ),
                'artwork_ranking_factors' => array(
                    'sales_count' => 30,
                    'view_count' => 15,
                    'like_count' => 15,
                    'quality_score' => 25,
                    'recency' => 15
                ),
                'collector_ranking_factors' => array(
                    'purchase_volume' => 40,
                    'collection_diversity' => 20,
                    'marketplace_activity' => 25,
                    'community_engagement' => 15
                ),
                'enable_ai_boosting' => 'yes',
                'trending_calculation_period' => '7days',
                'rankings_refresh_interval' => 'daily',
                'minimum_data_points' => 5,
                'expose_ranking_factors' => 'no',
                'featured_artist_count' => 10,
                'featured_artwork_count' => 12,
                'enable_categorical_rankings' => 'yes',
                'popular_categories_count' => 8
            ));
        }
        
        // Shuffle settings
        if ( ! get_option( 'vortex_shuffle_settings' ) ) {
            update_option( 'vortex_shuffle_settings', array(
                'shuffle_interval' => 3, // Hours between shuffles
                'shuffle_randomization' => 15, // Percentage of randomization
                'winners_count' => 10, // Number of daily winners
                'enable_shuffle_notification' => 'yes',
                'enable_winners_notification' => 'yes',
                'shuffle_factor_weights' => array(
                    'trending' => 70,
                    'random' => 30
                )
            ));
        }
        
        // Set initial last shuffle time
        if ( ! get_option( 'vortex_last_gallery_shuffle' ) ) {
            update_option( 'vortex_last_gallery_shuffle', current_time( 'mysql' ) );
        }
    }
    
    /**
     * Set up initial schedules.
     *
     * @since    1.0.0
     */
    private static function setup_schedules() {
        // Clear any existing schedules to avoid duplicates
        wp_clear_scheduled_hook( 'vortex_shuffle_gallery' );
        wp_clear_scheduled_hook( 'vortex_determine_daily_winners' );
        
        // Schedule gallery shuffle every 3 hours
        wp_schedule_event( time(), 'three_hours', 'vortex_shuffle_gallery' );
        
        // Schedule daily winner determination at midnight
        $midnight = strtotime( 'tomorrow midnight' );
        wp_schedule_event( $midnight, 'daily', 'vortex_determine_daily_winners' );
    }
}
