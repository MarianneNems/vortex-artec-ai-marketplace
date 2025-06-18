<?php
/**
 * Forum list template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/templates/collector-forum
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get post types for tabs
$post_types = Vortex_Collector_Forum::get_post_types();
$post_statuses = Vortex_Collector_Forum::get_post_statuses();

// Get active tab from shortcode attributes or default to 'all'
$active_tab = isset($atts['post_type']) ? $atts['post_type'] : 'all';
?>

<div class="vortex-forum-container">
    <!-- Forum tabs -->
    <div class="vortex-forum-tabs">
        <div class="vortex-forum-tab <?php echo $active_tab === 'all' ? 'active' : ''; ?>" data-tab="all">
            <?php _e('All Posts', 'vortex-ai-marketplace'); ?>
        </div>
        
        <?php foreach ($post_types as $type => $label) : ?>
            <div class="vortex-forum-tab <?php echo $active_tab === $type ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($type); ?>">
                <?php echo esc_html($label . 's'); ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Forum filters -->
    <div class="vortex-forum-filters">
        <div class="vortex-forum-search">
            <input type="text" id="vortex-forum-search" placeholder="<?php _e('Search in forum...', 'vortex-ai-marketplace'); ?>">
            <button type="button">
                <i class="dashicons dashicons-search"></i>
            </button>
        </div>
        
        <div class="vortex-forum-filters-right">
            <select id="vortex-forum-status-filter" class="vortex-forum-filter-select">
                <option value="all"><?php _e('All Status', 'vortex-ai-marketplace'); ?></option>
                <?php foreach ($post_statuses as $status => $label) : ?>
                    <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            
            <a href="<?php echo esc_url(add_query_arg('create_post', '', remove_query_arg('view_post'))); ?>" class="vortex-create-post-btn">
                <i class="dashicons dashicons-plus"></i> <?php _e('Create Post', 'vortex-ai-marketplace'); ?>
            </a>
        </div>
    </div>
    
    <!-- Forum posts will be loaded via AJAX -->
    <div class="vortex-forum-posts"></div>
    
    <!-- Pagination -->
    <div class="vortex-forum-pagination"></div>
</div>

<!-- Post details container, initially hidden -->
<div class="vortex-forum-post-detail-container" style="display: none;"></div>

<!-- Create post container, initially hidden -->
<div class="vortex-create-post-container" style="display: none;">
    <div class="vortex-forum-actions" style="margin-bottom: 20px;">
        <a href="<?php echo esc_url(remove_query_arg(array('create_post', 'view_post'))); ?>" class="vortex-forum-action-btn secondary">
            <i class="dashicons dashicons-arrow-left-alt"></i> <?php _e('Back to Forum', 'vortex-ai-marketplace'); ?>
        </a>
    </div>
    
    <div class="vortex-create-post-form">
        <div class="vortex-create-post-header">
            <h2 class="vortex-create-post-title"><?php _e('Create New Post', 'vortex-ai-marketplace'); ?></h2>
            <p class="vortex-create-post-subtitle"><?php _e('Share your project, offer, or event with the community', 'vortex-ai-marketplace'); ?></p>
        </div>
        
        <form id="vortex-create-post-form" enctype="multipart/form-data">
            <div class="vortex-form-group">
                <label for="post_type" class="vortex-form-label"><?php _e('Post Type', 'vortex-ai-marketplace'); ?> <span class="vortex-form-required">*</span></label>
                <select id="post_type" name="post_type" class="vortex-form-select" required>
                    <option value="" disabled selected><?php _e('Select post type', 'vortex-ai-marketplace'); ?></option>
                    <?php foreach ($post_types as $type => $label) : ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="vortex-form-help">
                    <?php _e('Select the type of post you want to create.', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
            
            <div class="vortex-form-group">
                <label for="title" class="vortex-form-label"><?php _e('Title', 'vortex-ai-marketplace'); ?> <span class="vortex-form-required">*</span></label>
                <input type="text" id="title" name="title" class="vortex-form-input" required>
                <div class="vortex-form-help">
                    <?php _e('Give your post a clear and descriptive title.', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
            
            <div class="vortex-form-group">
                <label for="description" class="vortex-form-label"><?php _e('Description', 'vortex-ai-marketplace'); ?> <span class="vortex-form-required">*</span></label>
                <textarea id="description" name="description" class="vortex-form-textarea" required></textarea>
                <div class="vortex-form-help">
                    <?php _e('Provide a detailed description.', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
            
            <div class="vortex-form-group field-budget" style="display: none;">
                <label for="budget" class="vortex-form-label"><?php _e('Budget', 'vortex-ai-marketplace'); ?></label>
                <input type="number" id="budget" name="budget" class="vortex-form-input" step="0.01" min="0">
                <div class="vortex-form-help">
                    <?php _e('Enter your budget in USD.', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
            
            <div class="vortex-form-group field-deadline" style="display: none;">
                <label for="deadline" class="vortex-form-label"><?php _e('Deadline', 'vortex-ai-marketplace'); ?></label>
                <input type="date" id="deadline" name="deadline" class="vortex-form-input">
                <div class="vortex-form-help">
                    <?php _e('Select the deadline date.', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
            
            <div class="vortex-form-group field-skills" style="display: none;">
                <label for="skills_required" class="vortex-form-label"><?php _e('Skills Required', 'vortex-ai-marketplace'); ?></label>
                <textarea id="skills_required" name="skills_required" class="vortex-form-textarea"></textarea>
                <div class="vortex-form-help">
                    <?php _e('List the skills required for this project.', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
            
            <div class="vortex-form-group">
                <label for="attachments" class="vortex-form-label"><?php _e('Attachments', 'vortex-ai-marketplace'); ?></label>
                <input type="file" id="attachments" name="attachments[]" multiple>
                <div class="vortex-form-help">
                    <?php _e('You can upload multiple files (max 5MB each).', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
            
            <div class="vortex-form-submit">
                <button type="submit" class="vortex-form-submit-btn"><?php _e('Create Post', 'vortex-ai-marketplace'); ?></button>
            </div>
        </form>
    </div>
</div> 