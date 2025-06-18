<?php
/**
 * Collaboration Session Form Template
 *
 * Displays the forms for creating or joining a collaboration session
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/templates/collaboration
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <div class="vortex-login-required">
        <p><?php _e('You need to be logged in to use the collaboration features.', 'vortex-ai-marketplace'); ?></p>
        <p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button">
                <?php _e('Log In', 'vortex-ai-marketplace'); ?>
            </a>
        </p>
    </div>
    <?php
    return;
}
?>

<div class="vortex-session-forms" id="vortex-session-forms">
    <h2><?php _e('Collaborative Canvas', 'vortex-ai-marketplace'); ?></h2>
    
    <div class="vortex-form-tabs">
        <div class="vortex-form-tab active" data-tab="create">
            <?php _e('Create New Session', 'vortex-ai-marketplace'); ?>
        </div>
        <div class="vortex-form-tab" data-tab="join">
            <?php _e('Join Existing Session', 'vortex-ai-marketplace'); ?>
        </div>
    </div>
    
    <div class="vortex-form-content active" data-content="create">
        <form id="vortex-create-session-form">
            <div class="vortex-form-group">
                <label for="session-title"><?php _e('Session Title', 'vortex-ai-marketplace'); ?> *</label>
                <input type="text" id="session-title" name="session-title" required>
            </div>
            
            <div class="vortex-form-group">
                <label for="session-description"><?php _e('Session Description', 'vortex-ai-marketplace'); ?></label>
                <textarea id="session-description" name="session-description"></textarea>
            </div>
            
            <?php if (current_user_can('vortex_artist')): ?>
            <div class="vortex-form-group">
                <label for="ai-assistance">
                    <input type="checkbox" id="ai-assistance" name="ai-assistance" checked>
                    <?php _e('Enable AI assistance', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="vortex-form-help"><?php _e('AI assistants will analyze your work and provide suggestions during the collaboration session.', 'vortex-ai-marketplace'); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="vortex-form-actions">
                <button type="submit" class="vortex-create-button">
                    <?php _e('Create Session', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <div class="vortex-form-content" data-content="join">
        <form id="vortex-join-session-form">
            <div class="vortex-form-group">
                <label for="session-id"><?php _e('Session ID', 'vortex-ai-marketplace'); ?> *</label>
                <input type="text" id="session-id" name="session-id" required>
            </div>
            
            <div class="vortex-form-actions">
                <button type="submit" class="vortex-join-button">
                    <?php _e('Join Session', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.vortex-form-tab').on('click', function() {
        $('.vortex-form-tab').removeClass('active');
        $(this).addClass('active');
        
        var tabId = $(this).data('tab');
        $('.vortex-form-content').removeClass('active');
        $('.vortex-form-content[data-content="' + tabId + '"]').addClass('active');
    });
    
    // Check for session ID in URL
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('session_id')) {
        var sessionId = urlParams.get('session_id');
        $('#session-id').val(sessionId);
        $('.vortex-form-tab[data-tab="join"]').click();
    }
});
</script> 