<?php
/**
 * Collaboration Canvas Template
 *
 * Displays the real-time collaboration canvas
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/templates/collaboration
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-collaboration-workspace" id="vortex-collaboration-workspace">
    <div class="vortex-collaboration-header">
        <div class="vortex-session-info">
            <h2 class="vortex-session-title"><?php echo esc_html($session['title']); ?></h2>
            <div class="vortex-session-id"><?php _e('Session ID:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($session_id); ?></div>
        </div>
        <div class="vortex-session-actions">
            <button type="button" id="vortex-invite-user" class="vortex-invite-user">
                <?php _e('Invite User', 'vortex-ai-marketplace'); ?>
            </button>
            <button type="button" id="vortex-leave-session" class="vortex-leave-session">
                <?php _e('Leave Session', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </div>
    
    <div class="vortex-collaboration-toolbar">
        <div class="vortex-tool-group">
            <div class="vortex-tool vortex-tool-brush active" data-tool="brush" title="<?php esc_attr_e('Brush', 'vortex-ai-marketplace'); ?>"></div>
            <div class="vortex-tool vortex-tool-eraser" data-tool="eraser" title="<?php esc_attr_e('Eraser', 'vortex-ai-marketplace'); ?>"></div>
            <div class="vortex-tool vortex-tool-text" data-tool="text" title="<?php esc_attr_e('Text', 'vortex-ai-marketplace'); ?>"></div>
            <div class="vortex-tool vortex-tool-line" data-tool="line" title="<?php esc_attr_e('Line', 'vortex-ai-marketplace'); ?>"></div>
        </div>
        
        <div class="vortex-color-picker-container">
            <span class="vortex-color-picker-label"><?php _e('Color:', 'vortex-ai-marketplace'); ?></span>
            <input type="color" id="vortex-color-picker" value="#000000">
        </div>
        
        <div class="vortex-line-width-container">
            <span class="vortex-line-width-label"><?php _e('Size:', 'vortex-ai-marketplace'); ?></span>
            <input type="range" id="vortex-line-width" min="1" max="20" value="5">
        </div>
        
        <div class="vortex-tool-group">
            <button type="button" id="vortex-undo" class="vortex-tool vortex-tool-undo" title="<?php esc_attr_e('Undo', 'vortex-ai-marketplace'); ?>">
                <?php _e('Undo', 'vortex-ai-marketplace'); ?>
            </button>
            <button type="button" id="vortex-redo" class="vortex-tool vortex-tool-redo" title="<?php esc_attr_e('Redo', 'vortex-ai-marketplace'); ?>">
                <?php _e('Redo', 'vortex-ai-marketplace'); ?>
            </button>
            <button type="button" id="vortex-clear" class="vortex-tool vortex-tool-clear" title="<?php esc_attr_e('Clear', 'vortex-ai-marketplace'); ?>">
                <?php _e('Clear', 'vortex-ai-marketplace'); ?>
            </button>
            <button type="button" id="vortex-save" class="vortex-tool vortex-tool-save" title="<?php esc_attr_e('Save', 'vortex-ai-marketplace'); ?>">
                <?php _e('Save', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </div>
    
    <div class="vortex-collaboration-main">
        <div class="vortex-collaboration-canvas-container">
            <canvas id="vortex-collaboration-canvas" 
                data-session-id="<?php echo esc_attr($session_id); ?>"
                data-width="<?php echo esc_attr($atts['width']); ?>"
                data-height="<?php echo esc_attr($atts['height']); ?>"
                width="<?php echo esc_attr($atts['width']); ?>"
                height="<?php echo esc_attr($atts['height']); ?>">
                <?php _e('Your browser does not support the HTML5 canvas element.', 'vortex-ai-marketplace'); ?>
            </canvas>
        </div>
        
        <?php if ($atts['show_chat'] === 'true'): ?>
        <div class="vortex-collaboration-sidebar">
            <div class="vortex-participants">
                <h3><?php _e('Participants', 'vortex-ai-marketplace'); ?></h3>
                <div class="vortex-participants-list">
                    <?php foreach ($session['participants'] as $user_id => $participant): ?>
                        <?php if ($participant['active']): ?>
                        <div class="vortex-participant" data-user-id="<?php echo esc_attr($user_id); ?>">
                            <span class="vortex-participant-name"><?php echo esc_html($participant['name']); ?></span>
                            <span class="vortex-participant-role vortex-participant-role-<?php echo esc_attr($participant['role']); ?>"><?php echo esc_html($participant['role']); ?></span>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="vortex-chat">
                <div class="vortex-chat-messages">
                    <?php if (isset($session['chat_history'])): ?>
                        <?php foreach ($session['chat_history'] as $message): ?>
                            <?php if ($message['type'] === 'system'): ?>
                                <div class="vortex-chat-system-message">
                                    <?php echo esc_html($message['message']); ?>
                                </div>
                            <?php else: ?>
                                <?php 
                                $is_current_user = isset($message['user_id']) && $message['user_id'] == get_current_user_id();
                                $class = $is_current_user ? 'vortex-chat-message-self' : 'vortex-chat-message-other';
                                ?>
                                <div class="vortex-chat-message <?php echo esc_attr($class); ?>">
                                    <div class="vortex-chat-message-header">
                                        <span class="vortex-chat-message-name"><?php echo esc_html($message['user_name']); ?></span>
                                        <span class="vortex-chat-message-time"><?php echo esc_html(date('H:i', strtotime($message['timestamp']))); ?></span>
                                    </div>
                                    <div class="vortex-chat-message-content">
                                        <?php echo esc_html($message['message']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <form class="vortex-chat-form" id="vortex-chat-form">
                    <input type="text" id="vortex-chat-message" placeholder="<?php esc_attr_e('Type a message...', 'vortex-ai-marketplace'); ?>" autocomplete="off">
                    <button type="submit">
                        <span class="screen-reader-text"><?php _e('Send', 'vortex-ai-marketplace'); ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- AI Integration Module -->
<div id="vortex-ai-collaboration-assistant" class="vortex-ai-collaboration-assistant">
    <h3><?php _e('AI Assistant', 'vortex-ai-marketplace'); ?></h3>
    <div class="vortex-ai-insights">
        <?php if (class_exists('VORTEX_HURAII') || class_exists('VORTEX_CLOE')): ?>
            <div class="vortex-ai-insight">
                <div class="vortex-ai-suggestion">
                    <?php _e('AI assistants are analyzing your work and will provide suggestions...', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="vortex-ai-insight">
                <div class="vortex-ai-suggestion">
                    <?php _e('AI assistants are not available for this session.', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/template" id="vortex-participant-template">
    <div class="vortex-participant" data-user-id="{{user_id}}">
        <span class="vortex-participant-name">{{name}}</span>
        <span class="vortex-participant-role vortex-participant-role-{{role}}">{{role}}</span>
    </div>
</script>

<script type="text/template" id="vortex-chat-message-template">
    <div class="vortex-chat-message {{class}}">
        <div class="vortex-chat-message-header">
            <span class="vortex-chat-message-name">{{name}}</span>
            <span class="vortex-chat-message-time">{{time}}</span>
        </div>
        <div class="vortex-chat-message-content">{{message}}</div>
    </div>
</script>

<script type="text/template" id="vortex-chat-system-message-template">
    <div class="vortex-chat-system-message">{{message}}</div>
</script>

<script type="text/template" id="vortex-ai-insight-template">
    <div class="vortex-ai-insight">
        <div class="vortex-ai-agent">{{agent_name}}</div>
        <div class="vortex-ai-suggestion">{{suggestion}}</div>
    </div>
</script>

<div id="vortex-invite-dialog" class="vortex-dialog" style="display: none;">
    <div class="vortex-dialog-content">
        <div class="vortex-dialog-header">
            <h3><?php _e('Invite to Collaboration', 'vortex-ai-marketplace'); ?></h3>
            <button type="button" class="vortex-dialog-close">&times;</button>
        </div>
        <div class="vortex-dialog-body">
            <p><?php _e('Share this session ID with others to invite them:', 'vortex-ai-marketplace'); ?></p>
            <div class="vortex-invite-session-id"><?php echo esc_html($session_id); ?></div>
            <button type="button" id="vortex-copy-session-id" class="vortex-copy-button">
                <?php _e('Copy Session ID', 'vortex-ai-marketplace'); ?>
            </button>
            
            <p><?php _e('Or share this direct link:', 'vortex-ai-marketplace'); ?></p>
            <div class="vortex-invite-link">
                <?php echo esc_url(add_query_arg('session_id', $session_id, get_permalink())); ?>
            </div>
            <button type="button" id="vortex-copy-link" class="vortex-copy-button">
                <?php _e('Copy Link', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </div>
</div> 