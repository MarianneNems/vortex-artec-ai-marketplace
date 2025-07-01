<?php
/**
 * Template for the artist education dashboard
 *
 * @link       https://vortexai.io
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/artist-education
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get TOLA currency symbol
$tola_symbol = 'TOLA';
?>

<div class="vortex-education-dashboard">
    <!-- Education Package Overview -->
    <div class="vortex-education-overview">
        <div class="vortex-education-header">
            <h2><?php esc_html_e('Vortex Academy - Artist Education Program', 'vortex-ai-marketplace'); ?></h2>
            <div class="vortex-education-status">
                <?php if ($is_certified): ?>
                    <div class="vortex-certification-badge">
                        <i class="dashicons dashicons-awards"></i>
                        <?php esc_html_e('Certified Pro Artist', 'vortex-ai-marketplace'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="vortex-package-info">
            <div class="vortex-package-details">
                <h3><?php echo esc_html(ucfirst($education_package ?: 'standard')); ?> <?php esc_html_e('Education Package', 'vortex-ai-marketplace'); ?></h3>
                <p class="vortex-package-description">
                    <?php esc_html_e('As a Vortex artist student, you are entitled to', 'vortex-ai-marketplace'); ?> 
                    <strong><?php echo esc_html($workshop_hours_total); ?> <?php esc_html_e('hours', 'vortex-ai-marketplace'); ?></strong> 
                    <?php esc_html_e('of workshops in one semester. Complete your workshop hours to receive your Vortex certification and become a Pro Artist.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
            
            <div class="vortex-hours-progress">
                <div class="vortex-progress-label">
                    <span><?php esc_html_e('Hours Used:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($workshop_hours_used); ?> / <?php echo esc_html($workshop_hours_total); ?></span>
                    <span><?php esc_html_e('Remaining:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($workshop_hours_remaining); ?> <?php esc_html_e('hours', 'vortex-ai-marketplace'); ?></span>
                </div>
                <div class="vortex-progress-bar">
                    <div class="vortex-progress-fill" style="width: <?php echo esc_attr(($workshop_hours_used / $workshop_hours_total) * 100); ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scheduled Workshops -->
    <div class="vortex-workshop-schedule">
        <h3><?php esc_html_e('My Scheduled Workshops', 'vortex-ai-marketplace'); ?></h3>
        
        <?php if (empty($scheduled_workshops)): ?>
            <div class="vortex-empty-schedule">
                <p><?php esc_html_e('You have no workshops scheduled. Schedule a workshop to start your education journey.', 'vortex-ai-marketplace'); ?></p>
            </div>
        <?php else: ?>
            <div class="vortex-schedule-list">
                <?php foreach ($scheduled_workshops as $workshop): ?>
                    <div class="vortex-schedule-item">
                        <div class="vortex-schedule-date">
                            <div class="vortex-date-month"><?php echo esc_html(date_i18n('M', strtotime($workshop['date']))); ?></div>
                            <div class="vortex-date-day"><?php echo esc_html(date_i18n('d', strtotime($workshop['date']))); ?></div>
                        </div>
                        <div class="vortex-schedule-info">
                            <h4><?php echo esc_html($workshop['title']); ?></h4>
                            <div class="vortex-schedule-details">
                                <span><i class="dashicons dashicons-clock"></i> <?php echo esc_html($workshop['time_slot']); ?></span>
                                <span><i class="dashicons dashicons-businessman"></i> <?php echo esc_html($workshop['instructor']); ?></span>
                                <span><i class="dashicons dashicons-hourglass"></i> <?php echo esc_html($workshop['duration']); ?> <?php esc_html_e('hours', 'vortex-ai-marketplace'); ?></span>
                            </div>
                            <div class="vortex-schedule-status <?php echo esc_attr($workshop['status']); ?>">
                                <?php echo esc_html(ucfirst($workshop['status'])); ?>
                            </div>
                        </div>
                        <div class="vortex-schedule-actions">
                            <?php if ($workshop['status'] === 'scheduled' && strtotime($workshop['date']) > time()): ?>
                                <button class="vortex-button vortex-button-outline-danger vortex-cancel-workshop" data-schedule-id="<?php echo esc_attr($workshop['id']); ?>">
                                    <?php esc_html_e('Cancel', 'vortex-ai-marketplace'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if (strtotime($workshop['date']) >= time()): ?>
                                <a href="#" class="vortex-button vortex-button-outline" data-schedule-id="<?php echo esc_attr($workshop['id']); ?>">
                                    <?php esc_html_e('Add to Calendar', 'vortex-ai-marketplace'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($workshop['status'] === 'completed'): ?>
                                <a href="#" class="vortex-button vortex-button-outline" data-schedule-id="<?php echo esc_attr($workshop['id']); ?>">
                                    <?php esc_html_e('View Materials', 'vortex-ai-marketplace'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Schedule Workshop Button -->
    <div class="vortex-workshop-actions">
        <button id="vortex-schedule-workshop-btn" class="vortex-button vortex-button-primary" <?php echo $workshop_hours_remaining <= 0 ? 'disabled' : ''; ?>>
            <i class="dashicons dashicons-calendar-alt"></i> <?php esc_html_e('Schedule a Workshop', 'vortex-ai-marketplace'); ?>
        </button>
        
        <?php if ($workshop_hours_remaining <= 0 && !$is_certified): ?>
            <div class="vortex-package-upgrade">
                <p><?php esc_html_e('You have used all your workshop hours. Upgrade your package for more hours.', 'vortex-ai-marketplace'); ?></p>
                <a href="#" class="vortex-button vortex-button-secondary vortex-upgrade-package-btn">
                    <?php esc_html_e('Upgrade Package', 'vortex-ai-marketplace'); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($is_certified): ?>
            <div class="vortex-certification-info">
                <p>
                    <i class="dashicons dashicons-awards"></i>
                    <?php esc_html_e('Congratulations! You are a certified Pro Artist. Your certification will appear on your artist profile.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Workshop Scheduling Modal -->
    <div id="vortex-workshop-modal" class="vortex-modal">
        <div class="vortex-modal-content">
            <span class="vortex-modal-close">&times;</span>
            <h3><?php esc_html_e('Schedule a Workshop', 'vortex-ai-marketplace'); ?></h3>
            
            <div class="vortex-modal-body">
                <div class="vortex-modal-loading">
                    <div class="vortex-spinner"></div>
                    <p><?php esc_html_e('Loading available workshops...', 'vortex-ai-marketplace'); ?></p>
                </div>
                
                <div class="vortex-workshop-form" style="display: none;">
                    <div class="vortex-date-picker-container">
                        <label for="vortex-workshop-date"><?php esc_html_e('Select Date:', 'vortex-ai-marketplace'); ?></label>
                        <input type="date" id="vortex-workshop-date" min="<?php echo esc_attr(date('Y-m-d')); ?>">
                    </div>
                    
                    <div class="vortex-workshop-list"></div>
                </div>
                
                <div class="vortex-workshop-confirmation" style="display: none;">
                    <div class="vortex-confirmation-content">
                        <div class="vortex-confirmation-icon">
                            <i class="dashicons dashicons-yes-alt"></i>
                        </div>
                        <h4><?php esc_html_e('Workshop Scheduled!', 'vortex-ai-marketplace'); ?></h4>
                        <div class="vortex-confirmation-details"></div>
                    </div>
                </div>
            </div>
            
            <div class="vortex-modal-footer">
                <div class="vortex-modal-hours-info">
                    <span><?php esc_html_e('Available Hours:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($workshop_hours_remaining); ?></span>
                </div>
                <button class="vortex-button vortex-button-outline vortex-modal-cancel"><?php esc_html_e('Cancel', 'vortex-ai-marketplace'); ?></button>
                <button class="vortex-button vortex-button-primary vortex-modal-schedule" disabled><?php esc_html_e('Schedule', 'vortex-ai-marketplace'); ?></button>
                <button class="vortex-button vortex-button-primary vortex-modal-done" style="display: none;"><?php esc_html_e('Done', 'vortex-ai-marketplace'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
/* Education Dashboard Styles */
.vortex-education-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.vortex-education-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.vortex-education-header h2 {
    font-size: 28px;
    margin: 0;
    color: #333;
}

.vortex-certification-badge {
    background-color: #4A26AB;
    color: white;
    border-radius: 50px;
    padding: 6px 16px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.vortex-certification-badge i {
    margin-right: 8px;
    font-size: 18px;
}

.vortex-package-info {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 32px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.vortex-package-details h3 {
    font-size: 22px;
    margin-top: 0;
    margin-bottom: 12px;
    color: #4A26AB;
}

.vortex-package-description {
    font-size: 16px;
    line-height: 1.5;
    color: #555;
    margin-bottom: 24px;
}

.vortex-hours-progress {
    margin-top: 20px;
}

.vortex-progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
    color: #555;
}

.vortex-progress-bar {
    height: 10px;
    background-color: #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
}

.vortex-progress-fill {
    height: 100%;
    background-color: #4A26AB;
    transition: width 0.3s ease;
}

.vortex-workshop-schedule {
    margin-bottom: 32px;
}

.vortex-workshop-schedule h3 {
    font-size: 20px;
    margin-bottom: 16px;
    color: #333;
}

.vortex-empty-schedule {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 32px;
    text-align: center;
    color: #666;
}

.vortex-schedule-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.vortex-schedule-item {
    display: flex;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.vortex-schedule-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.vortex-schedule-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #4A26AB;
    color: white;
    padding: 16px;
    min-width: 80px;
}

.vortex-date-month {
    font-size: 14px;
    text-transform: uppercase;
    font-weight: 600;
}

.vortex-date-day {
    font-size: 24px;
    font-weight: 700;
}

.vortex-schedule-info {
    flex: 1;
    padding: 16px;
    position: relative;
}

.vortex-schedule-info h4 {
    margin-top: 0;
    margin-bottom: 8px;
    font-size: 18px;
    color: #333;
}

.vortex-schedule-details {
    display: flex;
    gap: 16px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #666;
}

.vortex-schedule-details span {
    display: flex;
    align-items: center;
}

.vortex-schedule-details i {
    margin-right: 4px;
}

.vortex-schedule-status {
    display: inline-block;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    background: #f0f0f0;
    color: #666;
}

.vortex-schedule-status.scheduled {
    background: #e3f2fd;
    color: #1976d2;
}

.vortex-schedule-status.completed {
    background: #e8f5e9;
    color: #388e3c;
}

.vortex-schedule-status.cancelled {
    background: #ffebee;
    color: #d32f2f;
}

.vortex-schedule-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 16px;
    border-left: 1px solid #e0e0e0;
    justify-content: center;
}

.vortex-workshop-actions {
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-items: center;
    margin-top: 32px;
}

.vortex-package-upgrade {
    text-align: center;
    margin-top: 16px;
}

.vortex-package-upgrade p {
    margin-bottom: 12px;
    color: #666;
}

.vortex-certification-info {
    background: #f8f4ff;
    border-radius: 8px;
    padding: 16px;
    border-left: 4px solid #4A26AB;
    margin-top: 16px;
}

.vortex-certification-info p {
    margin: 0;
    display: flex;
    align-items: center;
    color: #4A26AB;
}

.vortex-certification-info i {
    margin-right: 8px;
    font-size: 20px;
}

/* Button Styles */
.vortex-button {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    font-size: 14px;
}

.vortex-button-primary {
    background-color: #4A26AB;
    color: white;
}

.vortex-button-primary:hover {
    background-color: #3b1e89;
}

.vortex-button-secondary {
    background-color: #6c757d;
    color: white;
}

.vortex-button-secondary:hover {
    background-color: #5a6268;
}

.vortex-button-outline {
    background-color: transparent;
    color: #4A26AB;
    border: 1px solid #4A26AB;
}

.vortex-button-outline:hover {
    background-color: #f0ebff;
}

.vortex-button-outline-danger {
    background-color: transparent;
    color: #d32f2f;
    border: 1px solid #d32f2f;
}

.vortex-button-outline-danger:hover {
    background-color: #ffebee;
}

.vortex-button[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}

.vortex-button i {
    margin-right: 8px;
}

/* Modal Styles */
.vortex-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.vortex-modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 24px;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    position: relative;
}

.vortex-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #aaa;
}

.vortex-modal-close:hover {
    color: #555;
}

.vortex-modal h3 {
    margin-top: 0;
    margin-bottom: 24px;
    font-size: 24px;
    color: #333;
}

.vortex-modal-body {
    max-height: 60vh;
    overflow-y: auto;
    margin-bottom: 24px;
}

.vortex-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 1px solid #e0e0e0;
}

.vortex-modal-hours-info {
    font-size: 14px;
    color: #666;
}

.vortex-modal-loading {
    text-align: center;
    padding: 40px 0;
}

.vortex-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 3px solid #f0f0f0;
    border-top-color: #4A26AB;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.vortex-date-picker-container {
    margin-bottom: 20px;
}

.vortex-date-picker-container label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.vortex-date-picker-container input[type="date"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.vortex-workshop-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
}

.vortex-workshop-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.2s ease;
    cursor: pointer;
}

.vortex-workshop-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.vortex-workshop-card.selected {
    border-color: #4A26AB;
    box-shadow: 0 0 0 2px rgba(74, 38, 171, 0.2);
}

.vortex-workshop-image {
    height: 120px;
    overflow: hidden;
}

.vortex-workshop-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.vortex-workshop-info {
    padding: 16px;
}

.vortex-workshop-title {
    font-size: 16px;
    font-weight: 600;
    margin-top: 0;
    margin-bottom: 8px;
    color: #333;
}

.vortex-workshop-details {
    font-size: 14px;
    color: #666;
    margin-bottom: 12px;
}

.vortex-workshop-times {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.vortex-time-slot {
    padding: 8px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-size: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.vortex-time-slot:hover:not(.unavailable) {
    border-color: #4A26AB;
    background-color: #f8f4ff;
}

.vortex-time-slot.selected {
    border-color: #4A26AB;
    background-color: #4A26AB;
    color: white;
}

.vortex-time-slot.unavailable {
    opacity: 0.5;
    background-color: #f0f0f0;
    cursor: not-allowed;
    text-decoration: line-through;
}

.vortex-confirmation-content {
    text-align: center;
    padding: 32px 0;
}

.vortex-confirmation-icon {
    font-size: 48px;
    color: #4caf50;
    margin-bottom: 16px;
}

.vortex-confirmation-icon i {
    font-size: 64px;
}

.vortex-confirmation-details {
    margin-top: 24px;
    text-align: left;
    background: #f9f9f9;
    padding: 16px;
    border-radius: 8px;
    font-size: 14px;
}

.vortex-confirmation-details p {
    margin: 8px 0;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .vortex-education-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .vortex-schedule-item {
        flex-direction: column;
    }
    
    .vortex-schedule-date {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        min-width: auto;
    }
    
    .vortex-date-month,
    .vortex-date-day {
        font-size: 16px;
        margin: 0 4px;
    }
    
    .vortex-schedule-actions {
        flex-direction: row;
        border-left: none;
        border-top: 1px solid #e0e0e0;
        padding: 12px 16px;
    }
    
    .vortex-workshop-list {
        grid-template-columns: 1fr;
    }
    
    .vortex-modal-content {
        width: 95%;
        margin: 5% auto;
        padding: 16px;
    }
    
    .vortex-modal-footer {
        flex-direction: column;
        gap: 16px;
    }
    
    .vortex-modal-footer button {
        width: 100%;
    }
    
    .vortex-modal-hours-info {
        width: 100%;
        text-align: left;
        margin-bottom: 8px;
    }
}
</style> 