<?php
/**
 * Admin settings for CLOE Shuffle Integration
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Admin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register CLOE Shuffle settings page
 */
function vortex_register_cloe_shuffle_settings() {
    add_submenu_page(
        'vortex_marketplace',
        __('CLOE Shuffle Settings', 'vortex-ai-marketplace'),
        __('CLOE Shuffle', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex_cloe_shuffle_settings',
        'vortex_render_cloe_shuffle_settings'
    );
}
add_action('admin_menu', 'vortex_register_cloe_shuffle_settings');

/**
 * Register CLOE Shuffle settings
 */
function vortex_register_cloe_shuffle_settings_fields() {
    register_setting('vortex_cloe_shuffle_settings', 'vortex_cloe_shuffle_settings');
    
    add_settings_section(
        'vortex_cloe_shuffle_section',
        __('CLOE AI Gallery Shuffle Settings', 'vortex-ai-marketplace'),
        'vortex_cloe_shuffle_section_callback',
        'vortex_cloe_shuffle_settings'
    );
    
    add_settings_field(
        'enable_cloe_shuffle',
        __('Enable CLOE Shuffle', 'vortex-ai-marketplace'),
        'vortex_cloe_shuffle_enable_callback',
        'vortex_cloe_shuffle_settings',
        'vortex_cloe_shuffle_section'
    );
    
    add_settings_field(
        'cloe_shuffle_factor_weights',
        __('Shuffle Factor Weights', 'vortex-ai-marketplace'),
        'vortex_cloe_shuffle_weights_callback',
        'vortex_cloe_shuffle_settings',
        'vortex_cloe_shuffle_section'
    );
    
    add_settings_field(
        'cloe_shuffle_randomization',
        __('Randomization Percentage', 'vortex-ai-marketplace'),
        'vortex_cloe_shuffle_randomization_callback',
        'vortex_cloe_shuffle_settings',
        'vortex_cloe_shuffle_section'
    );
    
    add_settings_section(
        'vortex_cloe_winners_section',
        __('CLOE AI Daily Winners Settings', 'vortex-ai-marketplace'),
        'vortex_cloe_winners_section_callback',
        'vortex_cloe_shuffle_settings'
    );
    
    add_settings_field(
        'enable_cloe_winners',
        __('Enable CLOE Winners Selection', 'vortex-ai-marketplace'),
        'vortex_cloe_winners_enable_callback',
        'vortex_cloe_shuffle_settings',
        'vortex_cloe_winners_section'
    );
    
    add_settings_field(
        'cloe_winners_count',
        __('Number of Daily Winners', 'vortex-ai-marketplace'),
        'vortex_cloe_winners_count_callback',
        'vortex_cloe_shuffle_settings',
        'vortex_cloe_winners_section'
    );
    
    add_settings_field(
        'cloe_winners_diversity',
        __('Artist Diversity Factor', 'vortex-ai-marketplace'),
        'vortex_cloe_winners_diversity_callback',
        'vortex_cloe_shuffle_settings',
        'vortex_cloe_winners_section'
    );
}
add_action('admin_init', 'vortex_register_cloe_shuffle_settings_fields');

/**
 * CLOE Shuffle section description
 */
function vortex_cloe_shuffle_section_callback() {
    echo '<p>' . __('Configure how CLOE AI influences the gallery shuffle algorithm.', 'vortex-ai-marketplace') . '</p>';
}

/**
 * CLOE Winners section description
 */
function vortex_cloe_winners_section_callback() {
    echo '<p>' . __('Configure how CLOE AI selects and curates the daily winners.', 'vortex-ai-marketplace') . '</p>';
}

/**
 * Enable CLOE Shuffle field callback
 */
function vortex_cloe_shuffle_enable_callback() {
    $options = get_option('vortex_cloe_shuffle_settings', array());
    $enabled = isset($options['enable_cloe_shuffle']) ? $options['enable_cloe_shuffle'] : 'yes';
    ?>
    <select name="vortex_cloe_shuffle_settings[enable_cloe_shuffle]">
        <option value="yes" <?php selected($enabled, 'yes'); ?>><?php _e('Yes', 'vortex-ai-marketplace'); ?></option>
        <option value="no" <?php selected($enabled, 'no'); ?>><?php _e('No', 'vortex-ai-marketplace'); ?></option>
    </select>
    <p class="description"><?php _e('Allow CLOE AI to intelligently influence the gallery shuffle algorithm.', 'vortex-ai-marketplace'); ?></p>
    <?php
}

/**
 * Shuffle factor weights field callback
 */
function vortex_cloe_shuffle_weights_callback() {
    $options = get_option('vortex_cloe_shuffle_settings', array());
    $weights = isset($options['shuffle_factor_weights']) ? $options['shuffle_factor_weights'] : array(
        'trending' => 70,
        'random' => 30
    );
    
    $trending = isset($weights['trending']) ? $weights['trending'] : 70;
    $random = isset($weights['random']) ? $weights['random'] : 30;
    ?>
    <div class="shuffle-weights-container">
        <label>
            <span><?php _e('Trending/AI Factor:', 'vortex-ai-marketplace'); ?></span>
            <input type="number" name="vortex_cloe_shuffle_settings[shuffle_factor_weights][trending]" 
                value="<?php echo esc_attr($trending); ?>" min="0" max="100" step="1"> %
        </label>
        <br>
        <label>
            <span><?php _e('Random Factor:', 'vortex-ai-marketplace'); ?></span>
            <input type="number" name="vortex_cloe_shuffle_settings[shuffle_factor_weights][random]" 
                value="<?php echo esc_attr($random); ?>" min="0" max="100" step="1"> %
        </label>
        <p class="description"><?php _e('Balance between CLOE\'s intelligence (Trending) and randomness in the shuffle algorithm. Must add up to 100%.', 'vortex-ai-marketplace'); ?></p>
    </div>
    <?php
}

/**
 * Randomization percentage field callback
 */
function vortex_cloe_shuffle_randomization_callback() {
    $options = get_option('vortex_cloe_shuffle_settings', array());
    $randomization = isset($options['shuffle_randomization']) ? $options['shuffle_randomization'] : 15;
    ?>
    <input type="number" name="vortex_cloe_shuffle_settings[shuffle_randomization]" 
        value="<?php echo esc_attr($randomization); ?>" min="0" max="50" step="1"> %
    <p class="description"><?php _e('Maximum percentage variation in scoring during shuffle (recommended: 10-20%).', 'vortex-ai-marketplace'); ?></p>
    <?php
}

/**
 * Enable CLOE Winners Selection field callback
 */
function vortex_cloe_winners_enable_callback() {
    $options = get_option('vortex_cloe_shuffle_settings', array());
    $enabled = isset($options['enable_cloe_winners']) ? $options['enable_cloe_winners'] : 'yes';
    ?>
    <select name="vortex_cloe_shuffle_settings[enable_cloe_winners]">
        <option value="yes" <?php selected($enabled, 'yes'); ?>><?php _e('Yes', 'vortex-ai-marketplace'); ?></option>
        <option value="no" <?php selected($enabled, 'no'); ?>><?php _e('No', 'vortex-ai-marketplace'); ?></option>
    </select>
    <p class="description"><?php _e('Allow CLOE AI to intelligently select and curate daily winners.', 'vortex-ai-marketplace'); ?></p>
    <?php
}

/**
 * Winners count field callback
 */
function vortex_cloe_winners_count_callback() {
    $options = get_option('vortex_cloe_shuffle_settings', array());
    $count = isset($options['winners_count']) ? $options['winners_count'] : 10;
    ?>
    <input type="number" name="vortex_cloe_shuffle_settings[winners_count]" 
        value="<?php echo esc_attr($count); ?>" min="1" max="50" step="1">
    <p class="description"><?php _e('Number of daily winners to select (recommended: 10).', 'vortex-ai-marketplace'); ?></p>
    <?php
}

/**
 * Winners diversity factor field callback
 */
function vortex_cloe_winners_diversity_callback() {
    $options = get_option('vortex_cloe_shuffle_settings', array());
    $diversity = isset($options['winners_diversity']) ? $options['winners_diversity'] : 70;
    ?>
    <input type="range" name="vortex_cloe_shuffle_settings[winners_diversity]" 
        value="<?php echo esc_attr($diversity); ?>" min="0" max="100" step="5"
        oninput="this.nextElementSibling.value = this.value">
    <output><?php echo esc_html($diversity); ?></output> %
    <p class="description"><?php _e('How strongly CLOE should prioritize artist diversity in winner selection (higher = more diverse).', 'vortex-ai-marketplace'); ?></p>
    <?php
}

/**
 * Render CLOE Shuffle settings page
 */
function vortex_render_cloe_shuffle_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if CLOE is available
    $cloe_active = class_exists('VORTEX_CLOE') && method_exists(VORTEX_CLOE::get_instance(), 'is_active') && VORTEX_CLOE::get_instance()->is_active();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if (!$cloe_active) : ?>
            <div class="notice notice-warning">
                <p><?php _e('CLOE AI Agent is not active or not properly configured. Some advanced features may not work.', 'vortex-ai-marketplace'); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="vortex-admin-header">
            <div class="vortex-admin-logo">
                <img src="<?php echo esc_url(plugin_dir_url(VORTEX_PLUGIN_FILE) . 'assets/images/cloe-logo.png'); ?>" alt="CLOE AI">
            </div>
            <div class="vortex-admin-intro">
                <h2><?php _e('CLOE AI Gallery Management', 'vortex-ai-marketplace'); ?></h2>
                <p><?php _e('CLOE\'s Intelligence creates a dynamic and engaging marketplace gallery experience through advanced algorithms and user behavior analysis.', 'vortex-ai-marketplace'); ?></p>
            </div>
        </div>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('vortex_cloe_shuffle_settings');
            do_settings_sections('vortex_cloe_shuffle_settings');
            submit_button();
            ?>
        </form>
        
        <div class="vortex-cloe-stats">
            <h3><?php _e('CLOE Shuffle Statistics', 'vortex-ai-marketplace'); ?></h3>
            <?php
            $last_shuffle = get_option('vortex_last_gallery_shuffle', '');
            $last_shuffle_stats = get_option('vortex_cloe_last_shuffle_stats', array());
            
            if (!empty($last_shuffle)) {
                echo '<p><strong>' . __('Last Shuffle:', 'vortex-ai-marketplace') . '</strong> ' . esc_html($last_shuffle) . '</p>';
            }
            
            if (!empty($last_shuffle_stats)) {
                echo '<p><strong>' . __('Artworks Processed:', 'vortex-ai-marketplace') . '</strong> ' . 
                    esc_html(isset($last_shuffle_stats['artwork_count']) ? $last_shuffle_stats['artwork_count'] : 0) . '</p>';
                
                echo '<p><strong>' . __('Adjustments Applied:', 'vortex-ai-marketplace') . '</strong> ' . 
                    esc_html(isset($last_shuffle_stats['adjustments_applied']) ? $last_shuffle_stats['adjustments_applied'] : 0) . '</p>';
            }
            ?>
            
            <h3><?php _e('CLOE Daily Winners Statistics', 'vortex-ai-marketplace'); ?></h3>
            <?php
            global $wpdb;
            $winners_table = $wpdb->prefix . 'vortex_daily_winners';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$winners_table'") === $winners_table;
            
            if ($table_exists) {
                // Get winner stats
                $total_winners = $wpdb->get_var("SELECT COUNT(*) FROM $winners_table");
                $unique_artists = $wpdb->get_var("SELECT COUNT(DISTINCT artist_id) FROM $winners_table");
                $unique_artworks = $wpdb->get_var("SELECT COUNT(DISTINCT artwork_id) FROM $winners_table");
                
                echo '<p><strong>' . __('Total Winners Selected:', 'vortex-ai-marketplace') . '</strong> ' . esc_html($total_winners) . '</p>';
                echo '<p><strong>' . __('Unique Artists Featured:', 'vortex-ai-marketplace') . '</strong> ' . esc_html($unique_artists) . '</p>';
                echo '<p><strong>' . __('Unique Artworks Featured:', 'vortex-ai-marketplace') . '</strong> ' . esc_html($unique_artworks) . '</p>';
            } else {
                echo '<p>' . __('No winner statistics available yet.', 'vortex-ai-marketplace') . '</p>';
            }
            ?>
        </div>
    </div>
    
    <style>
        .vortex-admin-header {
            display: flex;
            margin-bottom: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            padding: 20px;
            align-items: center;
        }
        
        .vortex-admin-logo {
            margin-right: 20px;
            flex: 0 0 80px;
        }
        
        .vortex-admin-logo img {
            max-width: 100%;
            height: auto;
        }
        
        .vortex-admin-intro {
            flex: 1;
        }
        
        .vortex-admin-intro h2 {
            margin-top: 0;
        }
        
        .shuffle-weights-container label {
            display: block;
            margin-bottom: 10px;
        }
        
        .shuffle-weights-container label span {
            display: inline-block;
            width: 150px;
        }
        
        .vortex-cloe-stats {
            margin-top: 30px;
            background: #f0f7ff;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #3a86ff;
        }
    </style>
    <?php
} 