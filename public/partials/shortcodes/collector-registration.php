<?php
/**
 * Template for collector registration shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Default shortcode attributes
$atts = isset($atts) ? $atts : array(
    'redirect' => '',
    'class' => ''
);

// Check if user is already logged in
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $is_collector = in_array('vortex_collector', (array) $user->roles);
    
    if ($is_collector) {
        ?>
        <div class="vortex-already-registered">
            <p><?php printf(esc_html__('You are already registered as a collector: %s.', 'vortex-ai-marketplace'), esc_html($user->display_name)); ?></p>
            
            <div class="vortex-user-actions">
                <a href="<?php echo esc_url(home_url('/collector-dashboard/')); ?>" class="vortex-button">
                    <?php esc_html_e('Go to Collector Dashboard', 'vortex-ai-marketplace'); ?>
                </a>
                
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="vortex-button vortex-button-secondary">
                    <?php esc_html_e('Log Out', 'vortex-ai-marketplace'); ?>
                </a>
            </div>
        </div>
        <?php
    } else {
        // User is logged in but not a collector, show form to upgrade to collector
        ?>
        <div class="vortex-collector-upgrade">
            <h2><?php esc_html_e('Become a Collector', 'vortex-ai-marketplace'); ?></h2>
            <p><?php printf(esc_html__('You are currently logged in as %s. Complete the form below to register as a collector.', 'vortex-ai-marketplace'), esc_html($user->display_name)); ?></p>
            <?php
            // Process collector registration form submission for existing user
            collector_upgrade_form($user, $atts);
            ?>
        </div>
        <?php
    }
    return;
}

// Process registration form submission
$registration_error = '';
$registration_success = false;

if (isset($_POST['vortex_register_collector']) && isset($_POST['vortex_collector_registration_nonce']) && 
    wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vortex_collector_registration_nonce'])), 'vortex_collector_registration')) {
    
    $username = isset($_POST['vortex_username']) ? sanitize_user(wp_unslash($_POST['vortex_username'])) : '';
    $email = isset($_POST['vortex_email']) ? sanitize_email(wp_unslash($_POST['vortex_email'])) : '';
    $password = isset($_POST['vortex_password']) ? $_POST['vortex_password'] : '';
    $confirm_password = isset($_POST['vortex_confirm_password']) ? $_POST['vortex_confirm_password'] : '';
    $terms = isset($_POST['vortex_terms']) ? $_POST['vortex_terms'] : '';
    
    // Collector specific fields
    $bio = isset($_POST['vortex_collector_bio']) ? sanitize_textarea_field(wp_unslash($_POST['vortex_collector_bio'])) : '';
    $interests = isset($_POST['vortex_collector_interests']) ? sanitize_text_field(wp_unslash($_POST['vortex_collector_interests'])) : '';
    $wallet_address = isset($_POST['vortex_collector_wallet_address']) ? sanitize_text_field(wp_unslash($_POST['vortex_collector_wallet_address'])) : '';
    
    // Social media links
    $social_media = array(
        'twitter' => isset($_POST['vortex_collector_social_media']['twitter']) ? esc_url_raw(wp_unslash($_POST['vortex_collector_social_media']['twitter'])) : '',
        'instagram' => isset($_POST['vortex_collector_social_media']['instagram']) ? esc_url_raw(wp_unslash($_POST['vortex_collector_social_media']['instagram'])) : '',
        'facebook' => isset($_POST['vortex_collector_social_media']['facebook']) ? esc_url_raw(wp_unslash($_POST['vortex_collector_social_media']['facebook'])) : '',
    );
    
    // Validate inputs
    if (empty($username)) {
        $registration_error = __('Username is required.', 'vortex-ai-marketplace');
    } elseif (empty($email)) {
        $registration_error = __('Email address is required.', 'vortex-ai-marketplace');
    } elseif (!is_email($email)) {
        $registration_error = __('Invalid email address.', 'vortex-ai-marketplace');
    } elseif (empty($password)) {
        $registration_error = __('Password is required.', 'vortex-ai-marketplace');
    } elseif ($password !== $confirm_password) {
        $registration_error = __('Passwords do not match.', 'vortex-ai-marketplace');
    } elseif (empty($terms)) {
        $registration_error = __('You must agree to the terms and conditions.', 'vortex-ai-marketplace');
    } else {
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            $registration_error = $user_id->get_error_message();
        } else {
            // Set user role to collector
            $user = new WP_User($user_id);
            $user->set_role('vortex_collector');
            
            // Save collector profile data
            update_user_meta($user_id, '_vortex_collector_bio', $bio);
            update_user_meta($user_id, '_vortex_collector_interests', $interests);
            update_user_meta($user_id, '_vortex_collector_wallet_address', $wallet_address);
            update_user_meta($user_id, '_vortex_collector_social_media', $social_media);
            update_user_meta($user_id, 'vortex_registered_as_collector', true);
            update_user_meta($user_id, '_vortex_collector_subscription_status', 'pending');
            
            // Auto login
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            // Create collector profile
            if (class_exists('Vortex_Collectors')) {
                $collectors = new Vortex_Collectors(VORTEX_PLUGIN_NAME, VORTEX_VERSION);
                $profile_id = $collectors->create_or_update_collector_profile($user_id);
                
                if ($profile_id) {
                    // Update the profile with additional details
                    if (!empty($bio)) {
                        wp_update_post(array(
                            'ID' => $profile_id,
                            'post_content' => $bio
                        ));
                    }
                    
                    if (!empty($interests)) {
                        update_post_meta($profile_id, '_vortex_collector_interests', $interests);
                    }
                    
                    if (!empty($wallet_address)) {
                        update_post_meta($profile_id, '_vortex_collector_wallet_address', $wallet_address);
                    }
                    
                    // Set social media links
                    update_post_meta($profile_id, '_vortex_collector_social_links', $social_media);
                }
            }
            
            $registration_success = true;
            
            // Redirect after registration if specified
            if (!empty($atts['redirect'])) {
                wp_redirect(esc_url($atts['redirect']));
                exit;
            }
        }
    }
}

/**
 * Display the form for upgrading an existing user to a collector
 *
 * @param WP_User $user The current user object
 * @param array $atts Shortcode attributes
 */
function collector_upgrade_form($user, $atts = array()) {
    // Process form submission for upgrade
    $upgrade_error = '';
    $upgrade_success = false;
    
    if (isset($_POST['vortex_upgrade_collector']) && isset($_POST['vortex_collector_upgrade_nonce']) && 
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vortex_collector_upgrade_nonce'])), 'vortex_collector_upgrade')) {
        
        // Collector specific fields
        $bio = isset($_POST['vortex_collector_bio']) ? sanitize_textarea_field(wp_unslash($_POST['vortex_collector_bio'])) : '';
        $interests = isset($_POST['vortex_collector_interests']) ? sanitize_text_field(wp_unslash($_POST['vortex_collector_interests'])) : '';
        $wallet_address = isset($_POST['vortex_collector_wallet_address']) ? sanitize_text_field(wp_unslash($_POST['vortex_collector_wallet_address'])) : '';
        
        // Social media links
        $social_media = array(
            'twitter' => isset($_POST['vortex_collector_social_media']['twitter']) ? esc_url_raw(wp_unslash($_POST['vortex_collector_social_media']['twitter'])) : '',
            'instagram' => isset($_POST['vortex_collector_social_media']['instagram']) ? esc_url_raw(wp_unslash($_POST['vortex_collector_social_media']['instagram'])) : '',
            'facebook' => isset($_POST['vortex_collector_social_media']['facebook']) ? esc_url_raw(wp_unslash($_POST['vortex_collector_social_media']['facebook'])) : '',
        );
        
        // Update user role to collector
        $user->set_role('vortex_collector');
        
        // Save collector profile data
        update_user_meta($user->ID, '_vortex_collector_bio', $bio);
        update_user_meta($user->ID, '_vortex_collector_interests', $interests);
        update_user_meta($user->ID, '_vortex_collector_wallet_address', $wallet_address);
        update_user_meta($user->ID, '_vortex_collector_social_media', $social_media);
        update_user_meta($user->ID, 'vortex_registered_as_collector', true);
        update_user_meta($user->ID, '_vortex_collector_subscription_status', 'pending');
        
        // Create collector profile
        if (class_exists('Vortex_Collectors')) {
            $collectors = new Vortex_Collectors(VORTEX_PLUGIN_NAME, VORTEX_VERSION);
            $profile_id = $collectors->create_or_update_collector_profile($user->ID);
            
            if ($profile_id) {
                // Update the profile with additional details
                if (!empty($bio)) {
                    wp_update_post(array(
                        'ID' => $profile_id,
                        'post_content' => $bio
                    ));
                }
                
                if (!empty($interests)) {
                    update_post_meta($profile_id, '_vortex_collector_interests', $interests);
                }
                
                if (!empty($wallet_address)) {
                    update_post_meta($profile_id, '_vortex_collector_wallet_address', $wallet_address);
                }
                
                // Set social media links
                update_post_meta($profile_id, '_vortex_collector_social_links', $social_media);
            }
        }
        
        $upgrade_success = true;
        
        // Redirect after upgrade if specified
        if (!empty($atts['redirect'])) {
            wp_redirect(esc_url($atts['redirect']));
            exit;
        }
    }
    
    // Display upgrade form
    ?>
    <div class="vortex-registration-form-container vortex-collector-form">
        <?php if ($upgrade_success): ?>
            <div class="vortex-registration-success">
                <h3><?php esc_html_e('Upgrade Successful!', 'vortex-ai-marketplace'); ?></h3>
                <p><?php esc_html_e('Your account has been upgraded to a collector account.', 'vortex-ai-marketplace'); ?></p>
                
                <div class="vortex-next-steps">
                    <p><?php esc_html_e('Next Steps:', 'vortex-ai-marketplace'); ?></p>
                    <ol>
                        <li>
                            <a href="<?php echo esc_url(home_url('/collector-dashboard/')); ?>">
                                <?php esc_html_e('Go to your Collector Dashboard', 'vortex-ai-marketplace'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/collector-subscription/')); ?>">
                                <?php esc_html_e('Subscribe to Collector Plan', 'vortex-ai-marketplace'); ?>
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($upgrade_error)): ?>
                <div class="vortex-registration-error">
                    <?php echo esc_html($upgrade_error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="vortex-registration-form">
                <?php wp_nonce_field('vortex_collector_upgrade', 'vortex_collector_upgrade_nonce'); ?>
                
                <?php // Display collector fields ?>
                <?php include(dirname(__FILE__) . '/partials/collector-profile-fields.php'); ?>
                
                <div class="vortex-form-submit">
                    <button type="submit" name="vortex_upgrade_collector" class="vortex-button vortex-button-primary">
                        <?php esc_html_e('Upgrade to Collector', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
?>

<div class="vortex-registration-form-container vortex-collector-form">
    <?php if ($registration_success): ?>
        <div class="vortex-registration-success">
            <h3><?php esc_html_e('Collector Registration Successful!', 'vortex-ai-marketplace'); ?></h3>
            <p><?php esc_html_e('Welcome to Vortex AI Marketplace! Your collector account has been created.', 'vortex-ai-marketplace'); ?></p>
            
            <div class="vortex-next-steps">
                <p><?php esc_html_e('Next Steps:', 'vortex-ai-marketplace'); ?></p>
                <ol>
                    <li>
                        <a href="<?php echo esc_url(home_url('/collector-dashboard/')); ?>">
                            <?php esc_html_e('Go to your Collector Dashboard', 'vortex-ai-marketplace'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(home_url('/collector-subscription/')); ?>">
                            <?php esc_html_e('Subscribe to Collector Plan', 'vortex-ai-marketplace'); ?>
                        </a>
                    </li>
                </ol>
            </div>
        </div>
    <?php else: ?>
        <h2><?php esc_html_e('Register as a Collector', 'vortex-ai-marketplace'); ?></h2>
        <p class="vortex-registration-intro">
            <?php esc_html_e('Join Vortex AI Marketplace as a collector to discover and purchase unique AI-generated artwork.', 'vortex-ai-marketplace'); ?>
        </p>
        
        <?php if (!empty($registration_error)): ?>
            <div class="vortex-registration-error">
                <?php echo esc_html($registration_error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="vortex-registration-form vortex-fast-form">
            <?php wp_nonce_field('vortex_collector_registration', 'vortex_collector_registration_nonce'); ?>
            
            <div class="vortex-form-row">
                <div class="vortex-form-field">
                    <label for="vortex_username"><?php esc_html_e('Username', 'vortex-ai-marketplace'); ?> <span class="required">*</span></label>
                    <input type="text" 
                           id="vortex_username" 
                           name="vortex_username" 
                           value="<?php echo isset($_POST['vortex_username']) ? esc_attr(wp_unslash($_POST['vortex_username'])) : ''; ?>" 
                           required>
                </div>
                
                <div class="vortex-form-field">
                    <label for="vortex_email"><?php esc_html_e('Email Address', 'vortex-ai-marketplace'); ?> <span class="required">*</span></label>
                    <input type="email" 
                           id="vortex_email" 
                           name="vortex_email" 
                           value="<?php echo isset($_POST['vortex_email']) ? esc_attr(wp_unslash($_POST['vortex_email'])) : ''; ?>" 
                           required>
                </div>
            </div>
            
            <div class="vortex-form-row">
                <div class="vortex-form-field">
                    <label for="vortex_password"><?php esc_html_e('Password', 'vortex-ai-marketplace'); ?> <span class="required">*</span></label>
                    <input type="password" 
                           id="vortex_password" 
                           name="vortex_password" 
                           required>
                </div>
                
                <div class="vortex-form-field">
                    <label for="vortex_confirm_password"><?php esc_html_e('Confirm Password', 'vortex-ai-marketplace'); ?> <span class="required">*</span></label>
                    <input type="password" 
                           id="vortex_confirm_password" 
                           name="vortex_confirm_password" 
                           required>
                </div>
            </div>
            
            <div class="vortex-form-field">
                <label for="vortex_collector_interests"><?php esc_html_e('Art Interests', 'vortex-ai-marketplace'); ?></label>
                <input type="text" 
                       id="vortex_collector_interests" 
                       name="vortex_collector_interests" 
                       placeholder="<?php esc_attr_e('e.g., Abstract, Digital Art, Portraits', 'vortex-ai-marketplace'); ?>"
                       value="<?php echo isset($_POST['vortex_collector_interests']) ? esc_attr(wp_unslash($_POST['vortex_collector_interests'])) : ''; ?>">
            </div>
            
            <div class="vortex-form-field">
                <label for="vortex_collector_bio"><?php esc_html_e('About You (Optional)', 'vortex-ai-marketplace'); ?></label>
                <textarea id="vortex_collector_bio" 
                          name="vortex_collector_bio" 
                          rows="3"
                          placeholder="<?php esc_attr_e('Share a bit about your art collecting interests and preferences', 'vortex-ai-marketplace'); ?>"><?php echo isset($_POST['vortex_collector_bio']) ? esc_textarea(wp_unslash($_POST['vortex_collector_bio'])) : ''; ?></textarea>
            </div>
            
            <div class="vortex-form-field vortex-terms-field">
                <input type="checkbox" 
                       id="vortex_terms" 
                       name="vortex_terms" 
                       value="1" 
                       required>
                <label for="vortex_terms">
                    <?php 
                    printf(
                        esc_html__('I agree to the %1$sCollector Terms of Service%2$s and %3$sPrivacy Policy%4$s', 'vortex-ai-marketplace'),
                        '<a href="' . esc_url(home_url('/collector-terms-of-service/')) . '" target="_blank">',
                        '</a>',
                        '<a href="' . esc_url(home_url('/privacy-policy/')) . '" target="_blank">',
                        '</a>'
                    ); 
                    ?> <span class="required">*</span>
                </label>
            </div>
            
            <div class="vortex-collector-notice">
                <p class="vortex-notice">
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php esc_html_e('After registration, you\'ll need to connect your wallet and purchase a collector subscription ($399/year) to access the marketplace.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
            
            <div class="vortex-form-submit">
                <button type="submit" name="vortex_register_collector" class="vortex-button vortex-button-primary">
                    <?php esc_html_e('Register as Collector', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
        </form>
        
        <div class="vortex-login-link">
            <p>
                <?php 
                printf(
                    esc_html__('Already have an account? %sLog In%s', 'vortex-ai-marketplace'),
                    '<a href="' . esc_url(wp_login_url()) . '">',
                    '</a>'
                ); 
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.vortex-registration-form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.vortex-collector-form h3 {
    margin-top: 30px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #333;
}

.vortex-registration-form-container h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.vortex-registration-intro {
    margin-bottom: 25px;
    color: #666;
}

.vortex-registration-error {
    padding: 10px 15px;
    margin-bottom: 20px;
    background-color: #ffebee;
    border-left: 4px solid #f44336;
    color: #d32f2f;
}

.vortex-registration-success {
    padding: 20px;
    margin-bottom: 20px;
    background-color: #e8f5e9;
    border-left: 4px solid #4caf50;
    color: #2e7d32;
}

.vortex-form-field {
    margin-bottom: 20px;
}

.vortex-form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 10px;
}

.vortex-form-row .vortex-form-field {
    flex: 1;
}

.vortex-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.vortex-form-field input[type="text"],
.vortex-form-field input[type="email"],
.vortex-form-field input[type="password"],
.vortex-form-field input[type="url"],
.vortex-form-field textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.vortex-form-field textarea {
    min-height: 80px;
}

.vortex-terms-field {
    display: flex;
    align-items: flex-start;
}

.vortex-terms-field input {
    margin-top: 5px;
    margin-right: 10px;
}

.vortex-button {
    display: inline-block;
    padding: 12px 25px;
    background-color: #2271b1;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    font-size: 16px;
}

.vortex-button:hover {
    background-color: #135e96;
    color: white;
}

.vortex-button-secondary {
    background-color: #f0f0f1;
    color: #2c3338;
}

.vortex-button-secondary:hover {
    background-color: #e5e5e5;
    color: #2c3338;
}

.vortex-login-link {
    margin-top: 20px;
    text-align: center;
}

.required {
    color: #f44336;
}

.vortex-next-steps {
    margin-top: 20px;
}

.vortex-next-steps ol {
    margin-left: 20px;
}

.vortex-next-steps li {
    margin-bottom: 10px;
}

.vortex-user-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.vortex-collector-notice {
    margin: 20px 0;
}

.vortex-notice {
    padding: 15px;
    background-color: #fff8e1;
    border-left: 4px solid #ffc107;
    font-size: 0.9em;
}

.vortex-notice .dashicons {
    color: #ffc107;
    margin-right: 8px;
    vertical-align: middle;
}

.vortex-fast-form {
    max-width: 100%;
}
</style>

<script type="text/javascript">
(function($) {
    $(document).ready(function() {
        // Simple form validation and submission
        $('.vortex-fast-form').on('submit', function() {
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var originalText = $submit.text();
            
            $submit.text('<?php echo esc_js(__('Processing...', 'vortex-ai-marketplace')); ?>').prop('disabled', true);
            
            // Add a small delay to simulate fast processing
            setTimeout(function() {
                // Form will be submitted normally
                $form.off('submit').submit();
            }, 500);
            
            return false;
        });
    });
})(jQuery);
</script> 