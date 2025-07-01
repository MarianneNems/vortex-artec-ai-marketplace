<?php
/**
 * RunPod Settings Page
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get RunPod configuration instance
$runpod_config = Vortex_RunPod_Config::get_instance();
$server_status = $runpod_config->get_server_status();
?>

<div class="wrap">
    <h1><?php _e('RunPod AI Server Settings', 'vortex-ai-marketplace'); ?></h1>
    
    <div class="vortex-settings-header">
        <p><?php _e('Configure your RunPod AUTOMATIC1111 WebUI server for AI image generation.', 'vortex-ai-marketplace'); ?></p>
    </div>

    <!-- Server Status Dashboard -->
    <div class="vortex-server-status-card">
        <h2><?php _e('Server Status', 'vortex-ai-marketplace'); ?></h2>
        
        <div class="server-status-grid">
            <div class="status-item primary-server">
                <h3><?php _e('Primary Server', 'vortex-ai-marketplace'); ?></h3>
                <div class="status-indicator <?php echo $server_status['primary']['status']['success'] ? 'online' : 'offline'; ?>">
                    <span class="status-dot"></span>
                    <?php echo $server_status['primary']['status']['success'] ? __('Online', 'vortex-ai-marketplace') : __('Offline', 'vortex-ai-marketplace'); ?>
                </div>
                <p class="server-url"><?php echo esc_html($server_status['primary']['url']); ?></p>
                <?php if ($server_status['primary']['status']['success'] && isset($server_status['primary']['status']['server_info'])): ?>
                    <p class="server-model"><?php printf(__('Model: %s', 'vortex-ai-marketplace'), esc_html($server_status['primary']['status']['server_info']['model'])); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="status-item">
                <h3><?php _e('Generation Stats', 'vortex-ai-marketplace'); ?></h3>
                <div class="stats-grid">
                    <div class="stat">
                        <span class="stat-number"><?php echo get_option('vortex_runpod_total_generations', 0); ?></span>
                        <span class="stat-label"><?php _e('Total Generated', 'vortex-ai-marketplace'); ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo get_option('vortex_runpod_today_generations', 0); ?></span>
                        <span class="stat-label"><?php _e('Today', 'vortex-ai-marketplace'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="button" id="test-connection" class="button button-secondary">
            <?php _e('Test Connection', 'vortex-ai-marketplace'); ?>
        </button>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields('vortex_runpod_settings'); ?>
        
        <!-- Server Configuration -->
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Primary Server URL', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <input type="url" 
                               name="vortex_runpod_primary_url" 
                               value="<?php echo esc_attr($runpod_config->get('primary_url')); ?>"
                               class="regular-text" 
                               placeholder="https://your-runpod-server.gradio.live"
                               required />
                        <p class="description">
                            <?php _e('Your RunPod AUTOMATIC1111 WebUI server URL (e.g., https://4416007023f09466f6.gradio.live)', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Request Timeout (seconds)', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <input type="number" 
                               name="vortex_runpod_timeout" 
                               value="<?php echo esc_attr($runpod_config->get('api_timeout')); ?>"
                               min="30" 
                               max="300" 
                               class="small-text" />
                        <p class="description">
                            <?php _e('How long to wait for generation requests (recommended: 120 seconds)', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Max Retries', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <input type="number" 
                               name="vortex_runpod_max_retries" 
                               value="<?php echo esc_attr($runpod_config->get('max_retries')); ?>"
                               min="1" 
                               max="10" 
                               class="small-text" />
                        <p class="description">
                            <?php _e('Number of times to retry failed requests', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Generation Settings -->
        <h2><?php _e('Generation Settings', 'vortex-ai-marketplace'); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Default Model', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <select name="vortex_runpod_model" class="regular-text">
                            <option value="sd_xl_base_1.0.safetensors" <?php selected($runpod_config->get('model_name'), 'sd_xl_base_1.0.safetensors'); ?>>
                                SDXL Base 1.0
                            </option>
                            <option value="v1-5-pruned-emaonly.safetensors" <?php selected($runpod_config->get('model_name'), 'v1-5-pruned-emaonly.safetensors'); ?>>
                                Stable Diffusion 1.5
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('Default AI model to use for generation', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Default Steps', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <input type="number" 
                               name="vortex_runpod_steps" 
                               value="<?php echo esc_attr($runpod_config->get('default_steps')); ?>"
                               min="10" 
                               max="100" 
                               class="small-text" />
                        <p class="description">
                            <?php _e('Number of generation steps (more steps = higher quality, longer time)', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('CFG Scale', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <input type="number" 
                               name="vortex_runpod_cfg_scale" 
                               value="<?php echo esc_attr($runpod_config->get('default_cfg_scale')); ?>"
                               min="1" 
                               max="20" 
                               step="0.5"
                               class="small-text" />
                        <p class="description">
                            <?php _e('How closely to follow the prompt (7.5 is recommended)', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Default Sampler', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <select name="vortex_runpod_sampler" class="regular-text">
                            <option value="DPM++ 2M Karras" <?php selected($runpod_config->get('default_sampler'), 'DPM++ 2M Karras'); ?>>
                                DPM++ 2M Karras (Recommended)
                            </option>
                            <option value="Euler a" <?php selected($runpod_config->get('default_sampler'), 'Euler a'); ?>>
                                Euler a
                            </option>
                            <option value="Heun" <?php selected($runpod_config->get('default_sampler'), 'Heun'); ?>>
                                Heun
                            </option>
                            <option value="DDIM" <?php selected($runpod_config->get('default_sampler'), 'DDIM'); ?>>
                                DDIM
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('Sampling method for generation', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- AWS S3 Integration -->
        <h2><?php _e('AWS S3 Backup Settings', 'vortex-ai-marketplace'); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Enable S3 Backup', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_runpod_s3_backup" 
                                   value="1"
                                   <?php checked($runpod_config->get('aws_s3_backup'), true); ?> />
                            <?php _e('Automatically backup generated images to AWS S3', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('S3 Bucket', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <input type="text" 
                               name="vortex_runpod_s3_bucket" 
                               value="<?php echo esc_attr($runpod_config->get('s3_bucket')); ?>"
                               class="regular-text" 
                               placeholder="vortexartec.com-client-art" />
                        <p class="description">
                            <?php _e('AWS S3 bucket name for storing generated images', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('S3 Region', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <select name="vortex_runpod_s3_region" class="regular-text">
                            <option value="us-east-1" <?php selected($runpod_config->get('s3_region'), 'us-east-1'); ?>>US East (N. Virginia)</option>
                            <option value="us-east-2" <?php selected($runpod_config->get('s3_region'), 'us-east-2'); ?>>US East (Ohio)</option>
                            <option value="us-west-1" <?php selected($runpod_config->get('s3_region'), 'us-west-1'); ?>>US West (N. California)</option>
                            <option value="us-west-2" <?php selected($runpod_config->get('s3_region'), 'us-west-2'); ?>>US West (Oregon)</option>
                            <option value="eu-west-1" <?php selected($runpod_config->get('s3_region'), 'eu-west-1'); ?>>Europe (Ireland)</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Advanced Settings -->
        <h2><?php _e('Advanced Settings', 'vortex-ai-marketplace'); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Enable Logging', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_runpod_logging" 
                                   value="1"
                                   <?php checked($runpod_config->get('enable_logging'), true); ?> />
                            <?php _e('Log RunPod server interactions for debugging', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Auto Failover', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_runpod_auto_failover" 
                                   value="1"
                                   <?php checked($runpod_config->get('auto_failover'), true); ?> />
                            <?php _e('Automatically switch to backup servers if primary fails', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(__('Save RunPod Settings', 'vortex-ai-marketplace')); ?>
    </form>
</div>

<!-- Connection Test Modal -->
<div id="connection-test-modal" class="vortex-modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2><?php _e('Connection Test Results', 'vortex-ai-marketplace'); ?></h2>
        <div id="test-results">
            <div class="test-loading">
                <span class="spinner"></span>
                <?php _e('Testing connection...', 'vortex-ai-marketplace'); ?>
            </div>
        </div>
    </div>
</div>

<style>
.vortex-settings-header {
    background: #f9f9f9;
    padding: 15px;
    border-left: 4px solid #4A26AB;
    margin: 20px 0;
}

.vortex-server-status-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.server-status-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.status-item {
    padding: 15px;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
    background: #fafafa;
}

.status-indicator {
    display: flex;
    align-items: center;
    font-weight: 600;
    margin: 10px 0;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
    display: inline-block;
}

.status-indicator.online .status-dot {
    background: #46b450;
    box-shadow: 0 0 8px rgba(70, 180, 80, 0.4);
}

.status-indicator.offline .status-dot {
    background: #dc3232;
    box-shadow: 0 0 8px rgba(220, 50, 50, 0.4);
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 10px;
}

.stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #4A26AB;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.vortex-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: none;
    width: 80%;
    max-width: 500px;
    border-radius: 4px;
    position: relative;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    right: 15px;
    top: 10px;
}

.close:hover {
    color: black;
}

.test-loading {
    text-align: center;
    padding: 20px;
}

.spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #4A26AB;
    border-radius: 50%;
    animation: spin 2s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test connection button
    $('#test-connection').on('click', function() {
        $('#connection-test-modal').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_test_runpod_connection',
                nonce: '<?php echo wp_create_nonce('vortex_runpod_test'); ?>'
            },
            success: function(response) {
                let html = '';
                if (response.success) {
                    html = '<div class="notice notice-success"><p>' + response.data.message + '</p></div>';
                    if (response.data.server_info) {
                        html += '<p><strong>Model:</strong> ' + response.data.server_info.model + '</p>';
                    }
                } else {
                    html = '<div class="notice notice-error"><p>' + response.data.message + '</p></div>';
                }
                $('#test-results').html(html);
            },
            error: function() {
                $('#test-results').html('<div class="notice notice-error"><p>Connection test failed</p></div>');
            }
        });
    });
    
    // Close modal
    $('.close').on('click', function() {
        $('#connection-test-modal').hide();
    });
    
    $(window).on('click', function(event) {
        if (event.target === document.getElementById('connection-test-modal')) {
            $('#connection-test-modal').hide();
        }
    });
});
</script> 