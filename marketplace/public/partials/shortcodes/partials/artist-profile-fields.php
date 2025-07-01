<?php
/**
 * Template for artist profile fields
 * 
 * This template is included in both new artist registration and artist upgrade forms
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get values from post if set, or empty values otherwise
$bio = isset($_POST['vortex_artist_bio']) ? sanitize_textarea_field(wp_unslash($_POST['vortex_artist_bio'])) : '';
$specialties = isset($_POST['vortex_artist_specialties']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_specialties'])) : '';
$wallet_address = isset($_POST['vortex_artist_wallet_address']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_wallet_address'])) : '';
$website = isset($_POST['vortex_artist_website']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_website'])) : '';

// Social media links
$social_media = array(
    'twitter' => isset($_POST['vortex_artist_social_media']['twitter']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['twitter'])) : '',
    'instagram' => isset($_POST['vortex_artist_social_media']['instagram']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['instagram'])) : '',
    'facebook' => isset($_POST['vortex_artist_social_media']['facebook']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['facebook'])) : '',
    'deviantart' => isset($_POST['vortex_artist_social_media']['deviantart']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['deviantart'])) : '',
    'behance' => isset($_POST['vortex_artist_social_media']['behance']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['behance'])) : '',
);
?>

<div class="vortex-form-field">
    <label for="vortex_artist_bio"><?php esc_html_e('Artist Bio', 'vortex-ai-marketplace'); ?></label>
    <textarea id="vortex_artist_bio" 
              name="vortex_artist_bio" 
              placeholder="<?php esc_attr_e('Tell collectors about yourself and your artistic journey...', 'vortex-ai-marketplace'); ?>"><?php echo esc_textarea($bio); ?></textarea>
    <p class="description"><?php esc_html_e('Tell collectors about yourself, your background, and your artistic style.', 'vortex-ai-marketplace'); ?></p>
</div>

<div class="vortex-form-field">
    <label for="vortex_artist_specialties"><?php esc_html_e('Artistic Specialties', 'vortex-ai-marketplace'); ?> <span class="required">*</span></label>
    <input type="text" 
           id="vortex_artist_specialties" 
           name="vortex_artist_specialties" 
           value="<?php echo esc_attr($specialties); ?>" 
           placeholder="<?php esc_attr_e('e.g., Digital Art, AI Art, Photography, Illustration', 'vortex-ai-marketplace'); ?>"
           required>
    <p class="description"><?php esc_html_e('Enter your artistic specialties, separated by commas.', 'vortex-ai-marketplace'); ?></p>
</div>

<div class="vortex-form-field">
    <label for="vortex_artist_wallet_address"><?php esc_html_e('Wallet Address', 'vortex-ai-marketplace'); ?></label>
    <input type="text" 
           id="vortex_artist_wallet_address" 
           name="vortex_artist_wallet_address" 
           value="<?php echo esc_attr($wallet_address); ?>"
           placeholder="<?php esc_attr_e('Your cryptocurrency wallet address', 'vortex-ai-marketplace'); ?>">
    <p class="description"><?php esc_html_e('Your cryptocurrency wallet address for receiving payments. You can add this later if you don\'t have one yet.', 'vortex-ai-marketplace'); ?></p>
</div>

<div class="vortex-form-field">
    <label for="vortex_artist_website"><?php esc_html_e('Website', 'vortex-ai-marketplace'); ?></label>
    <input type="url" 
           id="vortex_artist_website" 
           name="vortex_artist_website" 
           value="<?php echo esc_url($website); ?>"
           placeholder="https://example.com">
    <p class="description"><?php esc_html_e('Your portfolio website or online presence.', 'vortex-ai-marketplace'); ?></p>
</div>

<h4><?php esc_html_e('Social Media Profiles', 'vortex-ai-marketplace'); ?></h4>
<p class="description"><?php esc_html_e('Add your social media profiles to help collectors find more of your work.', 'vortex-ai-marketplace'); ?></p>

<div class="vortex-social-fields">
    <div class="vortex-form-field">
        <label for="vortex_artist_social_twitter">
            <span class="dashicons dashicons-twitter"></span> <?php esc_html_e('Twitter', 'vortex-ai-marketplace'); ?>
        </label>
        <input type="url" 
               id="vortex_artist_social_twitter" 
               name="vortex_artist_social_media[twitter]" 
               value="<?php echo esc_url($social_media['twitter']); ?>"
               placeholder="https://twitter.com/username">
    </div>

    <div class="vortex-form-field">
        <label for="vortex_artist_social_instagram">
            <span class="dashicons dashicons-instagram"></span> <?php esc_html_e('Instagram', 'vortex-ai-marketplace'); ?>
        </label>
        <input type="url" 
               id="vortex_artist_social_instagram" 
               name="vortex_artist_social_media[instagram]" 
               value="<?php echo esc_url($social_media['instagram']); ?>"
               placeholder="https://instagram.com/username">
    </div>

    <div class="vortex-form-field">
        <label for="vortex_artist_social_facebook">
            <span class="dashicons dashicons-facebook"></span> <?php esc_html_e('Facebook', 'vortex-ai-marketplace'); ?>
        </label>
        <input type="url" 
               id="vortex_artist_social_facebook" 
               name="vortex_artist_social_media[facebook]" 
               value="<?php echo esc_url($social_media['facebook']); ?>"
               placeholder="https://facebook.com/username">
    </div>

    <div class="vortex-form-field">
        <label for="vortex_artist_social_deviantart">
            <span class="dashicons dashicons-art"></span> <?php esc_html_e('DeviantArt', 'vortex-ai-marketplace'); ?>
        </label>
        <input type="url" 
               id="vortex_artist_social_deviantart" 
               name="vortex_artist_social_media[deviantart]" 
               value="<?php echo esc_url($social_media['deviantart']); ?>"
               placeholder="https://username.deviantart.com">
    </div>

    <div class="vortex-form-field">
        <label for="vortex_artist_social_behance">
            <span class="dashicons dashicons-portfolio"></span> <?php esc_html_e('Behance', 'vortex-ai-marketplace'); ?>
        </label>
        <input type="url" 
               id="vortex_artist_social_behance" 
               name="vortex_artist_social_media[behance]" 
               value="<?php echo esc_url($social_media['behance']); ?>"
               placeholder="https://behance.net/username">
    </div>
</div> 