<?php
/**
 * Template for Artist Categories
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get user ID
$user_id = get_current_user_id();

// Get saved categories if user is logged in
$saved_categories = array();
if ($user_id) {
    $saved_categories = get_user_meta($user_id, 'vortex_artist_categories', true);
    if (!is_array($saved_categories)) {
        $saved_categories = array();
    }
}

// Artist categories
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

?>

<div class="vortex-artist-categories-container">
    <h3><?php echo esc_html__('Select Your Artistic Categories', 'vortex-ai-marketplace'); ?></h3>
    <p class="vortex-categories-description">
        <?php echo esc_html__('Choose up to 3 categories that best describe your art. This helps us recommend the right AI agents and resources for you.', 'vortex-ai-marketplace'); ?>
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
    
    <div class="vortex-recommended-agents" <?php echo empty($saved_categories) ? 'style="display:none;"' : ''; ?>>
        <h3><?php echo esc_html__('Recommended AI Agents', 'vortex-ai-marketplace'); ?></h3>
        <p><?php echo esc_html__('Based on your categories, these AI agents can help you the most:', 'vortex-ai-marketplace'); ?></p>
        <div class="vortex-agent-recommendations">
            <!-- Agent recommendations will be populated via JavaScript -->
            <?php if (!empty($saved_categories)): ?>
                <p class="vortex-loading-recommendations"><?php echo esc_html__('Loading recommendations...', 'vortex-ai-marketplace'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Hidden field to store selected categories -->
    <input type="hidden" id="vortex-selected-categories" name="vortex_selected_categories" value="<?php echo esc_attr(json_encode($saved_categories)); ?>">
</div>

<?php
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
?> 