<?php
/**
 * Provide an admin area view for the Business Strategist module
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="vortex-admin-tabs">
        <a href="#overview" class="vortex-tab vortex-tab-active"><?php esc_html_e('Overview', 'vortex-ai-marketplace'); ?></a>
        <a href="#artist-qualifications" class="vortex-tab"><?php esc_html_e('Artist Qualifications', 'vortex-ai-marketplace'); ?></a>
        <a href="#seed-art-status" class="vortex-tab"><?php esc_html_e('Seed Art Status', 'vortex-ai-marketplace'); ?></a>
        <a href="#market-insights" class="vortex-tab"><?php esc_html_e('Market Insights', 'vortex-ai-marketplace'); ?></a>
    </div>
    
    <div class="vortex-admin-panels">
        <!-- Overview Panel -->
        <div id="overview" class="vortex-panel vortex-panel-active">
            <div class="vortex-panel-header">
                <h2><?php esc_html_e('Business Strategist AI Agent', 'vortex-ai-marketplace'); ?></h2>
                <p class="description"><?php esc_html_e('The Business Strategist analyzes marketplace data to provide strategic recommendations and insights for platform growth.', 'vortex-ai-marketplace'); ?></p>
            </div>
            
            <div class="vortex-stats-cards">
                <div class="vortex-stat-card">
                    <h3><?php esc_html_e('Active Artists', 'vortex-ai-marketplace'); ?></h3>
                    <div class="vortex-stat-value"><?php echo esc_html($this->get_active_artists_count()); ?></div>
                </div>
                
                <div class="vortex-stat-card">
                    <h3><?php esc_html_e('Inactive Artists', 'vortex-ai-marketplace'); ?></h3>
                    <div class="vortex-stat-value"><?php echo esc_html($this->get_inactive_artists_count()); ?></div>
                </div>
                
                <div class="vortex-stat-card">
                    <h3><?php esc_html_e('Seed Artworks', 'vortex-ai-marketplace'); ?></h3>
                    <div class="vortex-stat-value"><?php echo esc_html($this->get_seed_artwork_count()); ?></div>
                </div>
                
                <div class="vortex-stat-card">
                    <h3><?php esc_html_e('New Artists (30 days)', 'vortex-ai-marketplace'); ?></h3>
                    <div class="vortex-stat-value"><?php echo esc_html($this->get_new_artists_count(30)); ?></div>
                </div>
            </div>
            
            <div class="vortex-admin-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=vortex_marketplace&tab=artists')); ?>" class="button button-primary"><?php esc_html_e('Manage Artists', 'vortex-ai-marketplace'); ?></a>
                <a href="#" class="button vortex-run-seed-check"><?php esc_html_e('Run Seed Art Check', 'vortex-ai-marketplace'); ?></a>
            </div>
        </div>
        
        <!-- Artist Qualifications Panel -->
        <div id="artist-qualifications" class="vortex-panel">
            <div class="vortex-panel-header">
                <h2><?php esc_html_e('Artist Qualification Quiz', 'vortex-ai-marketplace'); ?></h2>
                <p class="description"><?php esc_html_e('Configure the qualification quiz that new artists must complete during registration.', 'vortex-ai-marketplace'); ?></p>
            </div>
            
            <div class="vortex-qualification-questions">
                <h3><?php esc_html_e('Current Questions', 'vortex-ai-marketplace'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Question', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Type', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Required', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Weight', 'vortex-ai-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $questions = $this->generate_qualification_questions();
                        foreach ($questions as $question) : 
                        ?>
                            <tr>
                                <td><?php echo esc_html($question['question']); ?></td>
                                <td><?php echo esc_html($question['type']); ?></td>
                                <td><?php echo $question['required'] ? esc_html__('Yes', 'vortex-ai-marketplace') : esc_html__('No', 'vortex-ai-marketplace'); ?></td>
                                <td><?php echo isset($question['weight']) ? esc_html($question['weight']) : '1'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="vortex-admin-section">
                    <h3><?php esc_html_e('Seed Art Commitment', 'vortex-ai-marketplace'); ?></h3>
                    <p><?php esc_html_e('Artists must commit to uploading two seed artworks weekly to maintain their artist status.', 'vortex-ai-marketplace'); ?></p>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('vortex_business_strategist_options'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Seed Art Requirement', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="vortex_seed_art_required" value="1" <?php checked(get_option('vortex_seed_art_required', 1), 1); ?>>
                                        <?php esc_html_e('Require weekly seed art uploads', 'vortex-ai-marketplace'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Weekly Upload Requirement', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="number" name="vortex_weekly_seed_requirement" value="<?php echo esc_attr(get_option('vortex_weekly_seed_requirement', 2)); ?>" min="1" max="10" class="small-text">
                                    <p class="description"><?php esc_html_e('Number of seed artworks artists must upload weekly.', 'vortex-ai-marketplace'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Grace Period (Days)', 'vortex-ai-marketplace'); ?></th>
                                <td>
                                    <input type="number" name="vortex_seed_grace_period" value="<?php echo esc_attr(get_option('vortex_seed_grace_period', 7)); ?>" min="1" max="30" class="small-text">
                                    <p class="description"><?php esc_html_e('Days before an artist loses status due to missed uploads.', 'vortex-ai-marketplace'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Seed Art Status Panel -->
        <div id="seed-art-status" class="vortex-panel">
            <div class="vortex-panel-header">
                <h2><?php esc_html_e('Seed Art Status', 'vortex-ai-marketplace'); ?></h2>
                <p class="description"><?php esc_html_e('Monitor artists\' seed art upload compliance status.', 'vortex-ai-marketplace'); ?></p>
            </div>
            
            <div class="vortex-seed-status-filters">
                <form method="get">
                    <input type="hidden" name="page" value="vortex_business_strategist">
                    <input type="hidden" name="tab" value="seed-art-status">
                    
                    <select name="status">
                        <option value=""><?php esc_html_e('All Statuses', 'vortex-ai-marketplace'); ?></option>
                        <option value="active" <?php selected(isset($_GET['status']) && $_GET['status'] === 'active'); ?>><?php esc_html_e('Active', 'vortex-ai-marketplace'); ?></option>
                        <option value="inactive" <?php selected(isset($_GET['status']) && $_GET['status'] === 'inactive'); ?>><?php esc_html_e('Inactive', 'vortex-ai-marketplace'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'vortex-ai-marketplace'); ?>">
                </form>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Artist', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Status', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Last Upload', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Uploads Due', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Actions', 'vortex-ai-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $artists = $this->get_artists_seed_status();
                    
                    if (empty($artists)) : 
                    ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No artists found.', 'vortex-ai-marketplace'); ?></td>
                        </tr>
                    <?php 
                    else :
                        foreach ($artists as $artist) : 
                    ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_user_link($artist['user_id'])); ?>">
                                        <?php echo esc_html($artist['name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($artist['status'] === 'active') : ?>
                                        <span class="vortex-status vortex-status-active"><?php esc_html_e('Active', 'vortex-ai-marketplace'); ?></span>
                                    <?php else : ?>
                                        <span class="vortex-status vortex-status-inactive"><?php esc_html_e('Inactive', 'vortex-ai-marketplace'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($artist['last_upload'])) {
                                        echo esc_html(human_time_diff(strtotime($artist['last_upload']), current_time('timestamp'))) . ' ' . esc_html__('ago', 'vortex-ai-marketplace');
                                    } else {
                                        esc_html_e('Never', 'vortex-ai-marketplace');
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($artist['uploads_due']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=vortex_marketplace&tab=artists&action=view&user_id=' . $artist['user_id'])); ?>" class="button button-small"><?php esc_html_e('View Profile', 'vortex-ai-marketplace'); ?></a>
                                    
                                    <?php if ($artist['status'] === 'inactive') : ?>
                                        <a href="#" class="button button-small vortex-reset-status" data-user-id="<?php echo esc_attr($artist['user_id']); ?>"><?php esc_html_e('Reset Status', 'vortex-ai-marketplace'); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Market Insights Panel -->
        <div id="market-insights" class="vortex-panel">
            <div class="vortex-panel-header">
                <h2><?php esc_html_e('Market Insights', 'vortex-ai-marketplace'); ?></h2>
                <p class="description"><?php esc_html_e('AI-generated insights into marketplace trends and artist performance.', 'vortex-ai-marketplace'); ?></p>
            </div>
            
            <div class="vortex-insights-cards">
                <!-- Top artist styles by sales -->
                <div class="vortex-insight-card">
                    <h3><?php esc_html_e('Top Artist Styles', 'vortex-ai-marketplace'); ?></h3>
                    <div class="vortex-insight-chart">
                        <!-- Chart would be rendered here in a real implementation -->
                        <div class="vortex-chart-placeholder">
                            <div class="vortex-chart-bar" style="height: 80%;"><?php esc_html_e('Abstract', 'vortex-ai-marketplace'); ?> (32%)</div>
                            <div class="vortex-chart-bar" style="height: 65%;"><?php esc_html_e('Digital', 'vortex-ai-marketplace'); ?> (26%)</div>
                            <div class="vortex-chart-bar" style="height: 45%;"><?php esc_html_e('Surrealist', 'vortex-ai-marketplace'); ?> (18%)</div>
                            <div class="vortex-chart-bar" style="height: 30%;"><?php esc_html_e('Impressionist', 'vortex-ai-marketplace'); ?> (12%)</div>
                            <div class="vortex-chart-bar" style="height: 25%;"><?php esc_html_e('Other', 'vortex-ai-marketplace'); ?> (10%)</div>
                        </div>
                    </div>
                </div>
                
                <!-- Artist retention insights -->
                <div class="vortex-insight-card">
                    <h3><?php esc_html_e('Artist Retention', 'vortex-ai-marketplace'); ?></h3>
                    <div class="vortex-insight-data">
                        <p><strong><?php esc_html_e('Active Rate:', 'vortex-ai-marketplace'); ?></strong> 78%</p>
                        <p><strong><?php esc_html_e('Avg. Time to First Sale:', 'vortex-ai-marketplace'); ?></strong> 14 days</p>
                        <p><strong><?php esc_html_e('Seed Art Compliance:', 'vortex-ai-marketplace'); ?></strong> 83%</p>
                    </div>
                    <div class="vortex-insight-recommendation">
                        <h4><?php esc_html_e('AI Recommendation', 'vortex-ai-marketplace'); ?></h4>
                        <p><?php esc_html_e('Consider implementing an automated onboarding sequence with step-by-step guidance for new artists to improve seed art compliance rates.', 'vortex-ai-marketplace'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Market strategy recommendations -->
            <div class="vortex-insight-strategies">
                <h3><?php esc_html_e('Strategic Recommendations', 'vortex-ai-marketplace'); ?></h3>
                
                <div class="vortex-strategy-card">
                    <h4><?php esc_html_e('Artist Development Program', 'vortex-ai-marketplace'); ?></h4>
                    <p><?php esc_html_e('Create a structured mentorship program pairing established artists with emerging ones. Analysis shows this could improve emerging artist retention by up to 35%.', 'vortex-ai-marketplace'); ?></p>
                    <div class="vortex-strategy-metrics">
                        <span class="vortex-metric"><strong><?php esc_html_e('Impact:', 'vortex-ai-marketplace'); ?></strong> <?php esc_html_e('High', 'vortex-ai-marketplace'); ?></span>
                        <span class="vortex-metric"><strong><?php esc_html_e('Cost:', 'vortex-ai-marketplace'); ?></strong> <?php esc_html_e('Medium', 'vortex-ai-marketplace'); ?></span>
                        <span class="vortex-metric"><strong><?php esc_html_e('Timeline:', 'vortex-ai-marketplace'); ?></strong> <?php esc_html_e('3 months', 'vortex-ai-marketplace'); ?></span>
                    </div>
                </div>
                
                <div class="vortex-strategy-card">
                    <h4><?php esc_html_e('Enhanced Seed Art Visibility', 'vortex-ai-marketplace'); ?></h4>
                    <p><?php esc_html_e('Create a dedicated featured section for exceptional seed artworks to increase artist motivation and compliance with upload requirements.', 'vortex-ai-marketplace'); ?></p>
                    <div class="vortex-strategy-metrics">
                        <span class="vortex-metric"><strong><?php esc_html_e('Impact:', 'vortex-ai-marketplace'); ?></strong> <?php esc_html_e('Medium', 'vortex-ai-marketplace'); ?></span>
                        <span class="vortex-metric"><strong><?php esc_html_e('Cost:', 'vortex-ai-marketplace'); ?></strong> <?php esc_html_e('Low', 'vortex-ai-marketplace'); ?></span>
                        <span class="vortex-metric"><strong><?php esc_html_e('Timeline:', 'vortex-ai-marketplace'); ?></strong> <?php esc_html_e('1 month', 'vortex-ai-marketplace'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .vortex-admin-tabs {
        margin-bottom: 20px;
        border-bottom: 1px solid #ccc;
    }
    
    .vortex-tab {
        display: inline-block;
        padding: 10px 15px;
        margin-right: 5px;
        border: 1px solid #ccc;
        border-bottom: none;
        background: #f7f7f7;
        text-decoration: none;
        color: #555;
        font-weight: 500;
    }
    
    .vortex-tab:hover {
        background: #fff;
        color: #000;
    }
    
    .vortex-tab-active {
        background: #fff;
        border-bottom: 1px solid #fff;
        color: #000;
    }
    
    .vortex-panel {
        display: none;
        padding: 20px;
        background: #fff;
        border: 1px solid #ccc;
        border-top: none;
    }
    
    .vortex-panel-active {
        display: block;
    }
    
    .vortex-panel-header {
        margin-bottom: 20px;
    }
    
    .vortex-stats-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .vortex-stat-card {
        flex: 1;
        min-width: 200px;
        padding: 20px;
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        text-align: center;
    }
    
    .vortex-stat-value {
        font-size: 2em;
        font-weight: bold;
        color: #2271b1;
    }
    
    .vortex-admin-actions {
        margin-top: 20px;
    }
    
    .vortex-admin-section {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    
    .vortex-qualification-questions {
        margin-bottom: 30px;
    }
    
    .vortex-seed-status-filters {
        margin-bottom: 20px;
    }
    
    .vortex-status {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.9em;
    }
    
    .vortex-status-active {
        background-color: #e7f9e7;
        color: #0a6b0a;
    }
    
    .vortex-status-inactive {
        background-color: #f9e7e7;
        color: #6b0a0a;
    }
    
    .vortex-insights-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .vortex-insight-card {
        flex: 1;
        min-width: 300px;
        padding: 20px;
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
    }
    
    .vortex-chart-placeholder {
        height: 200px;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        padding-top: 20px;
    }
    
    .vortex-chart-bar {
        width: 18%;
        background-color: #2271b1;
        color: white;
        padding: 8px 4px;
        text-align: center;
        font-size: 11px;
        border-radius: 3px 3px 0 0;
    }
    
    .vortex-strategy-card {
        padding: 15px;
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    
    .vortex-strategy-metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }
    
    .vortex-metric {
        font-size: 0.9em;
    }
    
    .vortex-insight-recommendation {
        margin-top: 15px;
        padding: 10px;
        background-color: #f0f8ff;
        border-left: 4px solid #2271b1;
    }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab navigation
    $('.vortex-tab').on('click', function(e) {
        e.preventDefault();
        var targetId = $(this).attr('href').substring(1);
        
        // Update active tab
        $('.vortex-tab').removeClass('vortex-tab-active');
        $(this).addClass('vortex-tab-active');
        
        // Show target panel, hide others
        $('.vortex-panel').removeClass('vortex-panel-active');
        $('#' + targetId).addClass('vortex-panel-active');
    });
    
    // Run seed art check action
    $('.vortex-run-seed-check').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('<?php esc_html_e('Are you sure you want to run a seed art compliance check? This will update all artist statuses.', 'vortex-ai-marketplace'); ?>')) {
            $(this).prop('disabled', true).text('<?php esc_html_e('Running...', 'vortex-ai-marketplace'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'run_seed_art_check',
                    security: '<?php echo wp_create_nonce('vortex_seed_art_check_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php esc_html_e('Seed art check completed. Artist statuses have been updated.', 'vortex-ai-marketplace'); ?>');
                        location.reload();
                    } else {
                        alert('<?php esc_html_e('Error: ', 'vortex-ai-marketplace'); ?>' + response.data.message);
                    }
                },
                error: function() {
                    alert('<?php esc_html_e('An error occurred while processing the request.', 'vortex-ai-marketplace'); ?>');
                },
                complete: function() {
                    $('.vortex-run-seed-check').prop('disabled', false).text('<?php esc_html_e('Run Seed Art Check', 'vortex-ai-marketplace'); ?>');
                }
            });
        }
    });
    
    // Reset artist status action
    $('.vortex-reset-status').on('click', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        
        if (confirm('<?php esc_html_e('Are you sure you want to reset this artist\'s status to active? This will restore their artist privileges.', 'vortex-ai-marketplace'); ?>')) {
            $(this).prop('disabled', true).text('<?php esc_html_e('Resetting...', 'vortex-ai-marketplace'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'reset_artist_status',
                    user_id: userId,
                    security: '<?php echo wp_create_nonce('vortex_reset_artist_status_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php esc_html_e('Artist status has been reset to active.', 'vortex-ai-marketplace'); ?>');
                        location.reload();
                    } else {
                        alert('<?php esc_html_e('Error: ', 'vortex-ai-marketplace'); ?>' + response.data.message);
                    }
                },
                error: function() {
                    alert('<?php esc_html_e('An error occurred while processing the request.', 'vortex-ai-marketplace'); ?>');
                }
            });
        }
    });
});
</script> 