<?php
/**
 * HURAII Admin Page Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap huraii-admin-wrap">
    <h1><?php _e('HURAII Image Processing', 'vortex-huraii'); ?></h1>
    
    <div class="huraii-admin-header">
        <div class="huraii-admin-logo">
            <img src="<?php echo VORTEX_HURAII_ASSETS_URL; ?>images/huraii-logo.png" alt="HURAII Logo">
        </div>
        <div class="huraii-admin-version">
            <span><?php _e('Version', 'vortex-huraii'); ?>: <?php echo VORTEX_HURAII_VERSION; ?></span>
        </div>
    </div>
    
    <div class="huraii-admin-content">
        <div class="huraii-admin-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#dashboard" class="nav-tab nav-tab-active"><?php _e('Dashboard', 'vortex-huraii'); ?></a>
                <a href="#settings" class="nav-tab"><?php _e('Settings', 'vortex-huraii'); ?></a>
                <a href="#statistics" class="nav-tab"><?php _e('Statistics', 'vortex-huraii'); ?></a>
                <a href="#help" class="nav-tab"><?php _e('Help', 'vortex-huraii'); ?></a>
            </nav>
        </div>
        
        <div class="huraii-admin-tab-content">
            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-pane active">
                <div class="huraii-admin-dashboard">
                    <div class="huraii-admin-cards">
                        <div class="huraii-admin-card">
                            <div class="card-header">
                                <h3><?php _e('System Status', 'vortex-huraii'); ?></h3>
                            </div>
                            <div class="card-body">
                                <div class="status-item">
                                    <span class="status-label"><?php _e('Connection Status', 'vortex-huraii'); ?>:</span>
                                    <span class="status-value status-connected"><?php _e('Connected', 'vortex-huraii'); ?></span>
                                </div>
                                <div class="status-item">
                                    <span class="status-label"><?php _e('Image Processing Server', 'vortex-huraii'); ?>:</span>
                                    <span class="status-value status-active"><?php _e('Active', 'vortex-huraii'); ?></span>
                                </div>
                                <div class="status-item">
                                    <span class="status-label"><?php _e('Cache Status', 'vortex-huraii'); ?>:</span>
                                    <span class="status-value"><?php _e('Optimized', 'vortex-huraii'); ?></span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button id="huraii-test-connection" class="button"><?php _e('Test Connection', 'vortex-huraii'); ?></button>
                                <button id="huraii-clear-cache" class="button button-primary"><?php _e('Clear Cache', 'vortex-huraii'); ?></button>
                            </div>
                        </div>
                        
                        <div class="huraii-admin-card">
                            <div class="card-header">
                                <h3><?php _e('Processing Statistics', 'vortex-huraii'); ?></h3>
                            </div>
                            <div class="card-body">
                                <div class="stat-item">
                                    <span class="stat-value">0</span>
                                    <span class="stat-label"><?php _e('Images Processed', 'vortex-huraii'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">0</span>
                                    <span class="stat-label"><?php _e('Variations Generated', 'vortex-huraii'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">0%</span>
                                    <span class="stat-label"><?php _e('Cache Hit Rate', 'vortex-huraii'); ?></span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button class="button button-secondary"><?php _e('View Detailed Stats', 'vortex-huraii'); ?></button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="huraii-admin-recent">
                        <h3><?php _e('Recent Artworks', 'vortex-huraii'); ?></h3>
                        <div class="recent-artworks-empty">
                            <p><?php _e('No recent artworks found.', 'vortex-huraii'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Tab -->
            <div id="settings" class="tab-pane">
                <form class="huraii-settings-form" method="post" action="">
                    <h3><?php _e('General Settings', 'vortex-huraii'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="huraii-cache-enabled"><?php _e('Enable Caching', 'vortex-huraii'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="huraii-cache-enabled" name="huraii_cache_enabled" value="1" checked>
                                <p class="description"><?php _e('Enable caching of generated images for better performance.', 'vortex-huraii'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="huraii-cache-ttl"><?php _e('Cache TTL (days)', 'vortex-huraii'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="huraii-cache-ttl" name="huraii_cache_ttl" value="7" min="1" max="30">
                                <p class="description"><?php _e('Time to live for cached images in days.', 'vortex-huraii'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="huraii-image-quality"><?php _e('Image Quality', 'vortex-huraii'); ?></label>
                            </th>
                            <td>
                                <select id="huraii-image-quality" name="huraii_image_quality">
                                    <option value="high" selected><?php _e('High (90%)', 'vortex-huraii'); ?></option>
                                    <option value="medium"><?php _e('Medium (75%)', 'vortex-huraii'); ?></option>
                                    <option value="low"><?php _e('Low (60%)', 'vortex-huraii'); ?></option>
                                </select>
                                <p class="description"><?php _e('Quality setting for generated images.', 'vortex-huraii'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Processing Settings', 'vortex-huraii'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="huraii-default-width"><?php _e('Default Width', 'vortex-huraii'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="huraii-default-width" name="huraii_default_width" value="1024" min="256" max="2048" step="64">
                                <p class="description"><?php _e('Default width for generated images.', 'vortex-huraii'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="huraii-default-height"><?php _e('Default Height', 'vortex-huraii'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="huraii-default-height" name="huraii_default_height" value="1024" min="256" max="2048" step="64">
                                <p class="description"><?php _e('Default height for generated images.', 'vortex-huraii'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="huraii-variation-count"><?php _e('Default Variation Count', 'vortex-huraii'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="huraii-variation-count" name="huraii_variation_count" value="4" min="1" max="10">
                                <p class="description"><?php _e('Default number of variations to generate.', 'vortex-huraii'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Save Settings', 'vortex-huraii'); ?></button>
                        <button type="button" class="button button-secondary"><?php _e('Reset to Defaults', 'vortex-huraii'); ?></button>
                    </p>
                </form>
            </div>
            
            <!-- Statistics Tab -->
            <div id="statistics" class="tab-pane">
                <div class="huraii-statistics">
                    <h3><?php _e('Usage Statistics', 'vortex-huraii'); ?></h3>
                    <div class="huraii-statistics-empty">
                        <p><?php _e('No statistics available yet.', 'vortex-huraii'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Help Tab -->
            <div id="help" class="tab-pane">
                <div class="huraii-help">
                    <h3><?php _e('How to Use HURAII', 'vortex-huraii'); ?></h3>
                    
                    <div class="huraii-help-section">
                        <h4><?php _e('Using the Shortcode', 'vortex-huraii'); ?></h4>
                        <p><?php _e('You can add the HURAII interface to any page or post using the shortcode:', 'vortex-huraii'); ?></p>
                        <code>[vortex_huraii]</code>
                        
                        <p><?php _e('You can also specify additional parameters:', 'vortex-huraii'); ?></p>
                        <code>[vortex_huraii mode="full" width="100%" height="auto"]</code>
                        
                        <h5><?php _e('Available parameters:', 'vortex-huraii'); ?></h5>
                        <ul>
                            <li><strong>mode</strong>: <?php _e('Display mode (full, text-only, or image-only)', 'vortex-huraii'); ?></li>
                            <li><strong>width</strong>: <?php _e('Width of the interface', 'vortex-huraii'); ?></li>
                            <li><strong>height</strong>: <?php _e('Height of the interface', 'vortex-huraii'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="huraii-help-section">
                        <h4><?php _e('Image Upload and Variations', 'vortex-huraii'); ?></h4>
                        <p><?php _e('HURAII supports both text-to-image generation and image-to-image generation with variations:', 'vortex-huraii'); ?></p>
                        <ol>
                            <li><?php _e('Switch to the "Image Upload" tab in the HURAII interface', 'vortex-huraii'); ?></li>
                            <li><?php _e('Upload your image by dragging and dropping or clicking the upload area', 'vortex-huraii'); ?></li>
                            <li><?php _e('Adjust the settings for variation strength if needed', 'vortex-huraii'); ?></li>
                            <li><?php _e('Click "Generate Variations" to create multiple variations based on your uploaded image', 'vortex-huraii'); ?></li>
                        </ol>
                    </div>
                    
                    <div class="huraii-help-section">
                        <h4><?php _e('Troubleshooting', 'vortex-huraii'); ?></h4>
                        <p><?php _e('If you encounter any issues with HURAII:', 'vortex-huraii'); ?></p>
                        <ol>
                            <li><?php _e('Make sure your server meets the minimum requirements', 'vortex-huraii'); ?></li>
                            <li><?php _e('Check that the upload directories are writable', 'vortex-huraii'); ?></li>
                            <li><?php _e('Try clearing the cache using the button in the dashboard', 'vortex-huraii'); ?></li>
                            <li><?php _e('Verify that your server has enough memory and CPU resources for image processing', 'vortex-huraii'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple tab functionality
    jQuery(document).ready(function($) {
        // Tab switching
        $('.huraii-admin-tabs a').on('click', function(e) {
            e.preventDefault();
            
            // Get target tab
            var target = $(this).attr('href');
            
            // Remove active class from all tabs
            $('.huraii-admin-tabs a').removeClass('nav-tab-active');
            $('.tab-pane').removeClass('active');
            
            // Add active class to current tab
            $(this).addClass('nav-tab-active');
            $(target).addClass('active');
        });
        
        // Test connection button
        $('#huraii-test-connection').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true).text('<?php _e('Testing...', 'vortex-huraii'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_huraii_test_connection',
                    nonce: vortexHURAII.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php _e('Connection successful!', 'vortex-huraii'); ?>');
                    } else {
                        alert('<?php _e('Connection failed: ', 'vortex-huraii'); ?>' + response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('Connection test failed due to a network error.', 'vortex-huraii'); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('<?php _e('Test Connection', 'vortex-huraii'); ?>');
                }
            });
        });
        
        // Clear cache button
        $('#huraii-clear-cache').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true).text('<?php _e('Clearing...', 'vortex-huraii'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_huraii_clear_cache',
                    nonce: vortexHURAII.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php _e('Cache cleared successfully!', 'vortex-huraii'); ?>');
                    } else {
                        alert('<?php _e('Failed to clear cache: ', 'vortex-huraii'); ?>' + response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('Cache clearing failed due to a network error.', 'vortex-huraii'); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('<?php _e('Clear Cache', 'vortex-huraii'); ?>');
                }
            });
        });
    });
</script> 