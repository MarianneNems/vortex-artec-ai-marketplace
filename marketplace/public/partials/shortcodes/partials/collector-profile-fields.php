<?php
/**
 * Partial template for collector profile fields
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-profile-section">
    <h3><?php esc_html_e('Collector Profile', 'vortex-ai-marketplace'); ?></h3>
    
    <div class="vortex-form-field">
        <label for="vortex_collector_interests"><?php esc_html_e('Art Interests', 'vortex-ai-marketplace'); ?></label>
        <input type="text" 
               id="vortex_collector_interests" 
               name="vortex_collector_interests" 
               placeholder="<?php esc_attr_e('e.g., Abstract, Digital Art, Portraits', 'vortex-ai-marketplace'); ?>"
               value="<?php echo isset($_POST['vortex_collector_interests']) ? esc_attr(wp_unslash($_POST['vortex_collector_interests'])) : (isset($user) ? esc_attr(get_user_meta($user->ID, '_vortex_collector_interests', true)) : ''); ?>">
    </div>
    
    <div class="vortex-form-field">
        <label for="vortex_collector_bio"><?php esc_html_e('About You', 'vortex-ai-marketplace'); ?></label>
        <textarea id="vortex_collector_bio" 
                  name="vortex_collector_bio" 
                  rows="4"
                  placeholder="<?php esc_attr_e('Share a bit about your art collecting interests and preferences', 'vortex-ai-marketplace'); ?>"><?php echo isset($_POST['vortex_collector_bio']) ? esc_textarea(wp_unslash($_POST['vortex_collector_bio'])) : (isset($user) ? esc_textarea(get_user_meta($user->ID, '_vortex_collector_bio', true)) : ''); ?></textarea>
    </div>
    
    <div class="vortex-form-field">
        <label for="vortex_collector_wallet_address"><?php esc_html_e('Wallet Address (Optional)', 'vortex-ai-marketplace'); ?></label>
        <input type="text" 
               id="vortex_collector_wallet_address" 
               name="vortex_collector_wallet_address"
               placeholder="<?php esc_attr_e('Your blockchain wallet address', 'vortex-ai-marketplace'); ?>"
               value="<?php echo isset($_POST['vortex_collector_wallet_address']) ? esc_attr(wp_unslash($_POST['vortex_collector_wallet_address'])) : (isset($user) ? esc_attr(get_user_meta($user->ID, '_vortex_collector_wallet_address', true)) : ''); ?>">
        <p class="form-field-note"><?php esc_html_e('You can also connect your wallet later through your collector dashboard.', 'vortex-ai-marketplace'); ?></p>
    </div>
</div>

<div class="vortex-profile-section">
    <h3><?php esc_html_e('Social Media (Optional)', 'vortex-ai-marketplace'); ?></h3>
    
    <div class="vortex-social-fields">
        <div class="vortex-form-field">
            <label for="vortex_collector_social_media_twitter">
                <i class="fab fa-twitter"></i> <?php esc_html_e('Twitter', 'vortex-ai-marketplace'); ?>
            </label>
            <input type="url" 
                   id="vortex_collector_social_media_twitter" 
                   name="vortex_collector_social_media[twitter]"
                   placeholder="https://twitter.com/yourusername"
                   value="<?php 
                   $social_media = isset($user) ? get_user_meta($user->ID, '_vortex_collector_social_media', true) : array();
                   if (isset($_POST['vortex_collector_social_media']['twitter'])) {
                       echo esc_attr(wp_unslash($_POST['vortex_collector_social_media']['twitter']));
                   } elseif (is_array($social_media) && isset($social_media['twitter'])) {
                       echo esc_attr($social_media['twitter']);
                   }
                   ?>">
        </div>
        
        <div class="vortex-form-field">
            <label for="vortex_collector_social_media_instagram">
                <i class="fab fa-instagram"></i> <?php esc_html_e('Instagram', 'vortex-ai-marketplace'); ?>
            </label>
            <input type="url" 
                   id="vortex_collector_social_media_instagram" 
                   name="vortex_collector_social_media[instagram]"
                   placeholder="https://instagram.com/yourusername"
                   value="<?php 
                   if (isset($_POST['vortex_collector_social_media']['instagram'])) {
                       echo esc_attr(wp_unslash($_POST['vortex_collector_social_media']['instagram']));
                   } elseif (is_array($social_media) && isset($social_media['instagram'])) {
                       echo esc_attr($social_media['instagram']);
                   }
                   ?>">
        </div>
        
        <div class="vortex-form-field">
            <label for="vortex_collector_social_media_facebook">
                <i class="fab fa-facebook"></i> <?php esc_html_e('Facebook', 'vortex-ai-marketplace'); ?>
            </label>
            <input type="url" 
                   id="vortex_collector_social_media_facebook" 
                   name="vortex_collector_social_media[facebook]"
                   placeholder="https://facebook.com/yourusername"
                   value="<?php 
                   if (isset($_POST['vortex_collector_social_media']['facebook'])) {
                       echo esc_attr(wp_unslash($_POST['vortex_collector_social_media']['facebook']));
                   } elseif (is_array($social_media) && isset($social_media['facebook'])) {
                       echo esc_attr($social_media['facebook']);
                   }
                   ?>">
        </div>
    </div>
</div>

<style>
.vortex-profile-section {
    margin-bottom: 30px;
}

.vortex-social-fields {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.form-field-note {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .vortex-social-fields {
        grid-template-columns: 1fr;
    }
}
</style> 