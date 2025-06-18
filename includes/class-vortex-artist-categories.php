<?php
/**
 * Artist Categories Handler
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * Artist Categories Handler
 *
 * Handles the functionality related to artist categories including:
 * - Saving and retrieving categories
 * - Displaying category selection interface
 * - Providing AI agent recommendations based on categories
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Vortex AI Team
 */
class Vortex_Artist_Categories {

    /**
     * The single instance of the class.
     *
     * @var Vortex_Artist_Categories
     */
    protected static $_instance = null;

    /**
     * Main instance.
     *
     * @return Vortex_Artist_Categories
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        // Register hooks
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_vortex_save_artist_categories', array($this, 'ajax_save_categories'));
        add_action('wp_ajax_vortex_get_agent_recommendations', array($this, 'ajax_get_agent_recommendations'));
        
        // Add profile field for artist categories
        add_action('show_user_profile', array($this, 'add_categories_profile_field'));
        add_action('edit_user_profile', array($this, 'add_categories_profile_field'));
        
        // Save profile field
        add_action('personal_options_update', array($this, 'save_categories_profile_field'));
        add_action('edit_user_profile_update', array($this, 'save_categories_profile_field'));
        
        // Register shortcode
        add_shortcode('vortex_artist_categories', array($this, 'render_categories_shortcode'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Filter the artist category data for AI agent context
        add_filter('vortex_ai_user_context', array($this, 'add_categories_to_ai_context'), 10, 2);
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on relevant pages
        if (is_page('artist-dashboard') || is_page('artist-registration') || is_page('my-account') || 
            has_shortcode(get_post()->post_content, 'vortex_artist_categories')) {
            
            wp_enqueue_style(
                'vortex-artist-categories',
                VORTEX_PLUGIN_URL . 'public/css/vortex-artist-categories.css',
                array(),
                VORTEX_VERSION
            );
            
            wp_enqueue_script(
                'vortex-artist-categories',
                VORTEX_PLUGIN_URL . 'public/js/vortex-artist-categories.js',
                array('jquery'),
                VORTEX_VERSION,
                true
            );
            
            wp_localize_script(
                'vortex-artist-categories',
                'vortexData',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('vortex_artist_categories_nonce'),
                    'userId' => get_current_user_id(),
                    'maxCategories' => 3,
                    'i18n' => array(
                        'maxCategoriesError' => __('You can only select up to 3 categories', 'vortex-ai-marketplace'),
                        'noCategories' => __('No categories selected yet', 'vortex-ai-marketplace'),
                        'loading' => __('Loading...', 'vortex-ai-marketplace')
                    )
                )
            );
        }
    }

    /**
     * AJAX handler for saving artist categories
     */
    public function ajax_save_categories() {
        check_ajax_referer('vortex_artist_categories_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to save categories', 'vortex-ai-marketplace')));
            return;
        }
        
        $categories = isset($_POST['categories']) ? (array)$_POST['categories'] : array();
        
        // Sanitize categories
        $clean_categories = array();
        foreach ($categories as $category) {
            $clean_categories[] = sanitize_text_field($category);
        }
        
        // Limit to maximum of 3 categories
        $clean_categories = array_slice($clean_categories, 0, 3);
        
        // Save to user meta
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'vortex_artist_categories', $clean_categories);
        
        // Update related AI personalization data
        $this->update_ai_personalization($user_id, $clean_categories);
        
        // Get AI agent recommendations
        $recommendations = $this->get_agent_recommendations($user_id);
        
        wp_send_json_success(array(
            'categories' => $clean_categories,
            'recommendations' => $recommendations
        ));
    }

    /**
     * AJAX handler for getting agent recommendations based on categories
     */
    public function ajax_get_agent_recommendations() {
        check_ajax_referer('vortex_artist_categories_nonce', 'nonce');
        
        // Default to current user if user_id not provided
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
        
        // Get recommendations
        $recommendations = $this->get_agent_recommendations($user_id);
        
        wp_send_json_success(array(
            'recommendations' => $recommendations
        ));
    }

    /**
     * Get agent recommendations based on user's categories
     *
     * @param int $user_id User ID
     * @return array Agent recommendations
     */
    public function get_agent_recommendations($user_id) {
        // Get user's categories
        $categories = get_user_meta($user_id, 'vortex_artist_categories', true);
        if (empty($categories) || !is_array($categories)) {
            return array();
        }
        
        // Map of categories to recommended agents
        $category_agent_map = array(
            'musician' => array('prompt_engineer', 'community_assistant'),
            'choreographer' => array('community_assistant', 'artwork_advisor'),
            'sculptor' => array('artwork_advisor', 'marketplace_guide'),
            'fine_artist' => array('marketplace_guide', 'artwork_advisor'),
            'digital_artist' => array('prompt_engineer', 'artwork_advisor'),
            'film' => array('prompt_engineer', 'community_assistant'),
            'graphic_designer' => array('marketplace_guide', 'prompt_engineer'),
            'fashion_designer' => array('artwork_advisor', 'community_assistant'),
            'architect' => array('marketplace_guide', 'prompt_engineer'),
            'interior_designer' => array('artwork_advisor', 'marketplace_guide'),
            'dancer' => array('community_assistant', 'artwork_advisor'),
            'other' => array('technical_support', 'community_assistant')
        );
        
        // Count recommendations for each agent
        $agent_counts = array();
        
        foreach ($categories as $category) {
            if (isset($category_agent_map[$category])) {
                foreach ($category_agent_map[$category] as $agent) {
                    if (!isset($agent_counts[$agent])) {
                        $agent_counts[$agent] = 0;
                    }
                    $agent_counts[$agent]++;
                }
            }
        }
        
        // Sort by recommendation count (highest first)
        arsort($agent_counts);
        
        // Get top 3 recommended agents
        $recommended_agents = array_keys(array_slice($agent_counts, 0, 3, true));
        
        // Get agent data
        $agent_data = array();
        
        // Agent definitions
        $agents = array(
            'artwork_advisor' => array(
                'name' => __('Artwork Advisor', 'vortex-ai-marketplace'),
                'icon' => 'palette',
                'description' => __('Get advice on improving your artwork and selling strategies', 'vortex-ai-marketplace')
            ),
            'marketplace_guide' => array(
                'name' => __('Marketplace Guide', 'vortex-ai-marketplace'),
                'icon' => 'shopping-cart',
                'description' => __('Learn how to navigate the marketplace effectively', 'vortex-ai-marketplace')
            ),
            'prompt_engineer' => array(
                'name' => __('Prompt Engineer', 'vortex-ai-marketplace'),
                'icon' => 'wand-magic-sparkles',
                'description' => __('Get help crafting effective prompts for AI art', 'vortex-ai-marketplace')
            ),
            'community_assistant' => array(
                'name' => __('Community Assistant', 'vortex-ai-marketplace'),
                'icon' => 'users',
                'description' => __('Connect with other artists and community events', 'vortex-ai-marketplace')
            ),
            'technical_support' => array(
                'name' => __('Technical Support', 'vortex-ai-marketplace'),
                'icon' => 'wrench',
                'description' => __('Get help with technical issues', 'vortex-ai-marketplace')
            )
        );
        
        foreach ($recommended_agents as $agent_id) {
            if (isset($agents[$agent_id])) {
                $agent_data[] = array_merge(
                    array('id' => $agent_id),
                    $agents[$agent_id]
                );
            }
        }
        
        return $agent_data;
    }

    /**
     * Update AI personalization data based on categories
     *
     * @param int $user_id User ID
     * @param array $categories Selected categories
     */
    private function update_ai_personalization($user_id, $categories) {
        // Get existing AI context
        $ai_context = get_user_meta($user_id, 'vortex_ai_context', true);
        
        if (!is_array($ai_context)) {
            $ai_context = array();
        }
        
        // Update with categories
        $ai_context['artist_categories'] = $categories;
        $ai_context['updated_at'] = time();
        
        // Save context
        update_user_meta($user_id, 'vortex_ai_context', $ai_context);
        
        // Log the update
        $this->log_category_update($user_id, $categories);
    }

    /**
     * Add categories to AI context for agent interactions
     *
     * @param array $context Current AI context
     * @param int $user_id User ID
     * @return array Updated context
     */
    public function add_categories_to_ai_context($context, $user_id) {
        // Get user's categories
        $categories = get_user_meta($user_id, 'vortex_artist_categories', true);
        
        if (!empty($categories) && is_array($categories)) {
            // Add to context
            $context['user_categories'] = $categories;
            
            // Also add primary category (first one) as artist type
            if (!empty($categories[0])) {
                $context['artist_type'] = $categories[0];
            }
        }
        
        return $context;
    }

    /**
     * Log category updates for analytics
     *
     * @param int $user_id User ID
     * @param array $categories Selected categories
     */
    private function log_category_update($user_id, $categories) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_events';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return;
        }
        
        // Log the update
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'event_type' => 'artist_categories_updated',
                'event_data' => json_encode(array('categories' => $categories)),
                'event_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }

    /**
     * Add categories field to user profile
     *
     * @param WP_User $user User object
     */
    public function add_categories_profile_field($user) {
        // Only show for users with the artist role
        if (!in_array('vortex_artist', (array)$user->roles) && !in_array('author', (array)$user->roles)) {
            return;
        }
        
        // Get saved categories
        $saved_categories = get_user_meta($user->ID, 'vortex_artist_categories', true);
        if (!is_array($saved_categories)) {
            $saved_categories = array();
        }
        
        // Artist categories
        $artist_categories = array(
            'musician' => __('Musician', 'vortex-ai-marketplace'),
            'choreographer' => __('Choreographer', 'vortex-ai-marketplace'),
            'sculptor' => __('Sculptor', 'vortex-ai-marketplace'),
            'fine_artist' => __('Fine Artist', 'vortex-ai-marketplace'),
            'digital_artist' => __('Digital Artist', 'vortex-ai-marketplace'),
            'film' => __('Film', 'vortex-ai-marketplace'),
            'graphic_designer' => __('Graphic Designer', 'vortex-ai-marketplace'),
            'fashion_designer' => __('Fashion Designer', 'vortex-ai-marketplace'),
            'architect' => __('Architect', 'vortex-ai-marketplace'),
            'interior_designer' => __('Interior Designer', 'vortex-ai-marketplace'),
            'dancer' => __('Dancer', 'vortex-ai-marketplace'),
            'other' => __('Other', 'vortex-ai-marketplace')
        );
        
        ?>
        <h3><?php _e('Artist Categories', 'vortex-ai-marketplace'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="vortex_artist_categories"><?php _e('Select up to 3 categories that best describe your art', 'vortex-ai-marketplace'); ?></label></th>
                <td>
                    <div class="vortex-category-checkboxes">
                        <?php foreach ($artist_categories as $value => $label): ?>
                            <label>
                                <input type="checkbox" 
                                       name="vortex_artist_categories[]" 
                                       value="<?php echo esc_attr($value); ?>" 
                                       <?php checked(in_array($value, $saved_categories)); ?>>
                                <?php echo esc_html($label); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="description"><?php _e('These categories help us recommend the right AI agents and resources for you.', 'vortex-ai-marketplace'); ?></p>
                </td>
            </tr>
        </table>
        <script>
            jQuery(document).ready(function($) {
                $('input[name="vortex_artist_categories[]"]').on('change', function() {
                    var checked = $('input[name="vortex_artist_categories[]"]:checked').length;
                    if (checked > 3) {
                        this.checked = false;
                        alert('<?php _e('You can only select up to 3 categories', 'vortex-ai-marketplace'); ?>');
                    }
                });
            });
        </script>
        <style>
            .vortex-category-checkboxes {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 8px;
            }
            .vortex-category-checkboxes label {
                display: block;
                margin-bottom: 5px;
            }
        </style>
        <?php
    }

    /**
     * Save categories profile field
     *
     * @param int $user_id User ID
     */
    public function save_categories_profile_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        // Get categories from form
        $categories = isset($_POST['vortex_artist_categories']) ? (array)$_POST['vortex_artist_categories'] : array();
        
        // Sanitize categories
        $clean_categories = array();
        foreach ($categories as $category) {
            $clean_categories[] = sanitize_text_field($category);
        }
        
        // Limit to maximum of 3 categories
        $clean_categories = array_slice($clean_categories, 0, 3);
        
        // Save to user meta
        update_user_meta($user_id, 'vortex_artist_categories', $clean_categories);
        
        // Update related AI personalization data
        $this->update_ai_personalization($user_id, $clean_categories);
    }

    /**
     * Render categories selection shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function render_categories_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Select Your Artistic Categories', 'vortex-ai-marketplace'),
            'description' => __('Choose up to 3 categories that best describe your art. This helps us recommend the right AI agents and resources for you.', 'vortex-ai-marketplace'),
            'show_recommendations' => 'yes'
        ), $atts, 'vortex_artist_categories');
        
        // Buffer output
        ob_start();
        
        // Include template
        include VORTEX_PLUGIN_DIR . 'public/partials/artist-categories.php';
        
        // Return buffered content
        return ob_get_clean();
    }
    
    /**
     * Get available artist categories
     *
     * @return array Categories array with name, icon, and description
     */
    public function get_artist_categories() {
        return array(
            'musician' => array(
                'name' => __('Musician', 'vortex-ai-marketplace'),
                'icon' => 'music',
                'description' => __('Music composition and sound art', 'vortex-ai-marketplace')
            ),
            'choreographer' => array(
                'name' => __('Choreographer', 'vortex-ai-marketplace'),
                'icon' => 'person-walking',
                'description' => __('Dance and movement art', 'vortex-ai-marketplace')
            ),
            'sculptor' => array(
                'name' => __('Sculptor', 'vortex-ai-marketplace'),
                'icon' => 'cubes',
                'description' => __('3D artwork and sculptures', 'vortex-ai-marketplace')
            ),
            'fine_artist' => array(
                'name' => __('Fine Artist', 'vortex-ai-marketplace'),
                'icon' => 'paint-brush',
                'description' => __('Traditional painting and drawing', 'vortex-ai-marketplace')
            ),
            'digital_artist' => array(
                'name' => __('Digital Artist', 'vortex-ai-marketplace'),
                'icon' => 'desktop',
                'description' => __('Digital artwork and designs', 'vortex-ai-marketplace')
            ),
            'film' => array(
                'name' => __('Film', 'vortex-ai-marketplace'),
                'icon' => 'film',
                'description' => __('Film and video art', 'vortex-ai-marketplace')
            ),
            'graphic_designer' => array(
                'name' => __('Graphic Designer', 'vortex-ai-marketplace'),
                'icon' => 'bezier-curve',
                'description' => __('Graphic design and illustration', 'vortex-ai-marketplace')
            ),
            'fashion_designer' => array(
                'name' => __('Fashion Designer', 'vortex-ai-marketplace'),
                'icon' => 'tshirt',
                'description' => __('Fashion and textile art', 'vortex-ai-marketplace')
            ),
            'architect' => array(
                'name' => __('Architect', 'vortex-ai-marketplace'),
                'icon' => 'building',
                'description' => __('Architectural design and concepts', 'vortex-ai-marketplace')
            ),
            'interior_designer' => array(
                'name' => __('Interior Designer', 'vortex-ai-marketplace'),
                'icon' => 'couch',
                'description' => __('Interior and spatial design', 'vortex-ai-marketplace')
            ),
            'dancer' => array(
                'name' => __('Dancer', 'vortex-ai-marketplace'),
                'icon' => 'user-ninja',
                'description' => __('Dance performance and choreography', 'vortex-ai-marketplace')
            ),
            'other' => array(
                'name' => __('Other', 'vortex-ai-marketplace'),
                'icon' => 'palette',
                'description' => __('Other art forms not listed', 'vortex-ai-marketplace')
            )
        );
    }
}

// Initialize the class
function vortex_artist_categories() {
    return Vortex_Artist_Categories::instance();
}

// Start the handler
vortex_artist_categories(); 