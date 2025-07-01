<?php
/**
 * Artist Categories Shortcode Handler
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Artist Categories Shortcode Handler
 */
class Vortex_Artist_Categories_Shortcode {

    /**
     * Initialize the class
     */
    public static function init() {
        add_shortcode('vortex_artist_categories', array(__CLASS__, 'render_artist_categories'));
    }

    /**
     * Render artist categories selection
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered output
     */
    public static function render_artist_categories($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => __('Select Your Artistic Categories', 'vortex-ai-marketplace'),
            'description' => __('Choose up to 3 categories that best describe your art. This helps us recommend the right AI agents and resources for you.', 'vortex-ai-marketplace'),
            'show_recommendations' => 'yes',
            'required' => 'no',
            'context' => 'default' // Can be: default, registration, profile, dashboard
        ), $atts, 'vortex_artist_categories');
        
        // If not logged in and required, show login message
        if (!is_user_logged_in() && $atts['required'] === 'yes') {
            return '<div class="vortex-login-required">' . 
                sprintf(
                    __('Please <a href="%s">log in</a> to select your artist categories.', 'vortex-ai-marketplace'),
                    esc_url(wp_login_url(get_permalink()))
                ) . 
                '</div>';
        }
        
        // Get current user info
        $user_id = get_current_user_id();
        $is_artist = false;
        
        if ($user_id) {
            $user = get_userdata($user_id);
            $is_artist = in_array('vortex_artist', (array)$user->roles) || in_array('author', (array)$user->roles);
        }

        // Get saved categories
        $saved_categories = array();
        if ($user_id) {
            $saved_categories = get_user_meta($user_id, 'vortex_artist_categories', true);
            if (!is_array($saved_categories)) {
                $saved_categories = array();
            }
        }
        
        // Get artist categories from handler if it exists
        $artist_categories = array();
        if (class_exists('Vortex_Artist_Categories')) {
            $handler = vortex_artist_categories();
            $artist_categories = $handler->get_artist_categories();
        } else {
            // Default categories if handler doesn't exist
            $artist_categories = array(
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
        
        // Enqueue required scripts and styles
        wp_enqueue_style('vortex-artist-categories', VORTEX_PLUGIN_URL . 'public/css/vortex-artist-categories.css', array(), VORTEX_VERSION);
        wp_enqueue_script('vortex-artist-categories', VORTEX_PLUGIN_URL . 'public/js/vortex-artist-categories.js', array('jquery'), VORTEX_VERSION, true);
        
        // Pass data to JavaScript
        wp_localize_script('vortex-artist-categories', 'vortexData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_artist_categories_nonce'),
            'userId' => $user_id,
            'maxCategories' => 3,
            'i18n' => array(
                'maxCategoriesError' => __('You can only select up to 3 categories', 'vortex-ai-marketplace'),
                'noCategories' => __('No categories selected yet', 'vortex-ai-marketplace'),
                'loading' => __('Loading...', 'vortex-ai-marketplace')
            )
        ));
        
        // Set container class based on context
        $container_class = 'vortex-artist-categories-container';
        if ($atts['context'] !== 'default') {
            $container_class .= ' vortex-categories-context-' . sanitize_html_class($atts['context']);
        }
        
        // Start output buffer
        ob_start();
        
        // Display categories interface
        ?>
        <div class="<?php echo esc_attr($container_class); ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <p class="vortex-categories-description">
                <?php echo esc_html($atts['description']); ?>
            </p>
            
            <div class="vortex-category-grid">
                <?php foreach ($artist_categories as $id => $category): ?>
                    <div class="vortex-category-item <?php echo in_array($id, $saved_categories) ? 'selected' : ''; ?>" data-category="<?php echo esc_attr($id); ?>">
                        <div class="vortex-category-icon">
                            <i class="fa-solid fa-<?php echo esc_attr($category['icon']); ?>"></i>
                        </div>
                        <div class="vortex-category-info">
                            <h4><?php echo esc_html($category['name']); ?></h4>
                            <p><?php echo esc_html($category['description']); ?></p>
                        </div>
                        <div class="vortex-category-checkbox">
                            <input type="checkbox" 
                                   id="category-<?php echo esc_attr($id); ?>" 
                                   name="vortex_artist_categories[]" 
                                   value="<?php echo esc_attr($id); ?>"
                                   <?php checked(in_array($id, $saved_categories)); ?>>
                            <span class="checkmark"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="vortex-selected-categories">
                <h3><?php echo esc_html__('Your Selected Categories', 'vortex-ai-marketplace'); ?></h3>
                <div class="vortex-category-chips">
                    <?php if (empty($saved_categories)): ?>
                        <p class="vortex-no-categories"><?php echo esc_html__('No categories selected yet', 'vortex-ai-marketplace'); ?></p>
                    <?php else: ?>
                        <?php foreach ($saved_categories as $id): ?>
                            <?php if (isset($artist_categories[$id])): ?>
                                <div class="vortex-category-chip" data-category="<?php echo esc_attr($id); ?>">
                                    <i class="fa-solid fa-<?php echo esc_attr($artist_categories[$id]['icon']); ?>"></i>
                                    <span><?php echo esc_html($artist_categories[$id]['name']); ?></span>
                                    <button type="button" class="vortex-remove-category">×</button>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p class="vortex-category-hint"><?php echo esc_html__('Select up to 3 categories that best describe your art', 'vortex-ai-marketplace'); ?></p>
            </div>
            
            <?php if ($atts['show_recommendations'] === 'yes'): ?>
                <div class="vortex-recommended-agents" <?php echo empty($saved_categories) ? 'style="display:none;"' : ''; ?>>
                    <h3><?php echo esc_html__('Recommended AI Agents', 'vortex-ai-marketplace'); ?></h3>
                    <p><?php echo esc_html__('Based on your categories, these AI agents can help you the most:', 'vortex-ai-marketplace'); ?></p>
                    <div class="vortex-agent-recommendations">
                        <?php if (!empty($saved_categories)): ?>
                            <p class="vortex-loading-recommendations"><?php echo esc_html__('Loading recommendations...', 'vortex-ai-marketplace'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Hidden field to store selected categories -->
            <input type="hidden" id="vortex-selected-categories" name="vortex_selected_categories" value="<?php echo esc_attr(json_encode($saved_categories)); ?>">
        </div>
        <?php
        
        // Return buffered content
        return ob_get_clean();
    }
}

// Initialize the shortcode
add_action('init', array('Vortex_Artist_Categories_Shortcode', 'init')); 