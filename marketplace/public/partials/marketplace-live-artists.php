<?php
/**
 * Template for displaying live artists in the marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current user role
$user = wp_get_current_user();
$user_roles = $user->roles;
$is_collector = in_array('vortex_collector', (array) $user_roles);
$is_artist = in_array('vortex_artist', (array) $user_roles);

// Check if user has an active subscription if they're a collector
$subscription_active = false;
if ($is_collector) {
    $subscription_status = get_user_meta($user->ID, '_vortex_collector_subscription_status', true);
    $subscription_active = ($subscription_status === 'active');
}

// Simulate getting live artists - in a real implementation, this would query for artists who are currently online
// For demo purposes, we'll create some sample data
$live_artists = array(
    array(
        'id' => 101,
        'name' => 'Sarah Johnson',
        'avatar' => 'https://i.pravatar.cc/150?img=1',
        'style' => 'Abstract Expressionism',
        'online_since' => '2 hours ago',
        'artworks' => array(
            array('id' => 1001, 'thumbnail' => 'https://source.unsplash.com/random/300x300/?abstract&sig=1', 'title' => 'Vibrant Emotions'),
            array('id' => 1002, 'thumbnail' => 'https://source.unsplash.com/random/300x300/?abstract&sig=2', 'title' => 'Cosmic Journey'),
        )
    ),
    array(
        'id' => 102,
        'name' => 'Michael Chen',
        'avatar' => 'https://i.pravatar.cc/150?img=11',
        'style' => 'Digital Surrealism',
        'online_since' => '45 minutes ago',
        'artworks' => array(
            array('id' => 1003, 'thumbnail' => 'https://source.unsplash.com/random/300x300/?surreal&sig=3', 'title' => 'Dream Sequence'),
            array('id' => 1004, 'thumbnail' => 'https://source.unsplash.com/random/300x300/?surreal&sig=4', 'title' => 'Parallel Realities'),
        )
    ),
    array(
        'id' => 103,
        'name' => 'Elena Rodriguez',
        'avatar' => 'https://i.pravatar.cc/150?img=5',
        'style' => 'Modern Portraiture',
        'online_since' => '1 hour ago',
        'artworks' => array(
            array('id' => 1005, 'thumbnail' => 'https://source.unsplash.com/random/300x300/?portrait&sig=5', 'title' => 'Silent Gaze'),
            array('id' => 1006, 'thumbnail' => 'https://source.unsplash.com/random/300x300/?portrait&sig=6', 'title' => 'Inner Reflection'),
        )
    ),
    array(
        'id' => 104,
        'name' => 'David Okafor',
        'avatar' => 'https://i.pravatar.cc/150?img=15',
        'style' => 'Afrofuturism',
        'online_since' => '30 minutes ago',
        'artworks' => array(
            array('id' => 1007, 'thumbnail' => 'https://source.unsplash.com/random/300x300/?futuristic&sig=7', 'title' => 'Cosmic Ancestry'),
            array('id' => 1008, 'thumbnail' => 'https://source.unsplash.com/random/300x300/?futuristic&sig=8', 'title' => 'Technological Spirits'),
        )
    ),
);

// Check if subscription is required and not active
if ($is_collector && !$subscription_active) {
    ?>
    <div class="vortex-subscription-required">
        <div class="vortex-notice vortex-warning">
            <h3><?php esc_html_e('Subscription Required', 'vortex-ai-marketplace'); ?></h3>
            <p><?php esc_html_e('You need an active collector subscription to interact with artists in the marketplace.', 'vortex-ai-marketplace'); ?></p>
            <a href="<?php echo esc_url(home_url('/collector-subscription/')); ?>" class="vortex-button"><?php esc_html_e('Subscribe Now', 'vortex-ai-marketplace'); ?></a>
        </div>
    </div>
    <?php
}

// If user is not logged in or doesn't have required roles, show login/register notice
if (!is_user_logged_in() || (!$is_collector && !$is_artist)) {
    ?>
    <div class="vortex-login-required">
        <div class="vortex-notice">
            <h3><?php esc_html_e('Login Required', 'vortex-ai-marketplace'); ?></h3>
            <p><?php esc_html_e('You need to be logged in as a collector or artist to interact with the marketplace.', 'vortex-ai-marketplace'); ?></p>
            
            <div class="vortex-login-options">
                <?php if (!is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-button"><?php esc_html_e('Log In', 'vortex-ai-marketplace'); ?></a>
                    <a href="<?php echo esc_url(home_url('/collector-registration/')); ?>" class="vortex-button vortex-button-secondary"><?php esc_html_e('Register as Collector', 'vortex-ai-marketplace'); ?></a>
                <?php else: ?>
                    <p><?php esc_html_e('Your current account does not have collector or artist privileges.', 'vortex-ai-marketplace'); ?></p>
                    <a href="<?php echo esc_url(home_url('/collector-registration/')); ?>" class="vortex-button"><?php esc_html_e('Upgrade to Collector', 'vortex-ai-marketplace'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?>

<div class="vortex-marketplace-container">
    <div class="vortex-marketplace-header">
        <h2><?php esc_html_e('Live Artists', 'vortex-ai-marketplace'); ?></h2>
        <p class="vortex-marketplace-description"><?php esc_html_e('These artists are currently live and available for interaction.', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-live-artists-grid">
        <?php foreach ($live_artists as $artist): ?>
            <div class="vortex-artist-frame">
                <div class="vortex-artist-header">
                    <div class="vortex-artist-avatar">
                        <img src="<?php echo esc_url($artist['avatar']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                        <span class="vortex-online-indicator"></span>
                    </div>
                    <div class="vortex-artist-info">
                        <h3 class="vortex-artist-name"><?php echo esc_html($artist['name']); ?></h3>
                        <div class="vortex-artist-style"><?php echo esc_html($artist['style']); ?></div>
                        <div class="vortex-online-since"><?php echo esc_html($artist['online_since']); ?></div>
                    </div>
                </div>
                
                <div class="vortex-artist-showcase">
                    <?php foreach ($artist['artworks'] as $artwork): ?>
                        <div class="vortex-artwork-thumbnail">
                            <img src="<?php echo esc_url($artwork['thumbnail']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>">
                            <div class="vortex-artwork-title"><?php echo esc_html($artwork['title']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="vortex-artist-actions">
                    <a href="<?php echo esc_url(home_url('/artist/' . $artist['id'])); ?>" class="vortex-button vortex-button-sm"><?php esc_html_e('View Artwork', 'vortex-ai-marketplace'); ?></a>
                    
                    <?php if (is_user_logged_in() && $is_collector && $subscription_active): ?>
                        <a href="#" class="vortex-button vortex-button-sm vortex-message-btn" data-artist-id="<?php echo esc_attr($artist['id']); ?>"><?php esc_html_e('Message', 'vortex-ai-marketplace'); ?></a>
                        <a href="#" class="vortex-button vortex-button-sm vortex-video-btn" data-artist-id="<?php echo esc_attr($artist['id']); ?>"><?php esc_html_e('Video Call', 'vortex-ai-marketplace'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.vortex-marketplace-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.vortex-marketplace-header {
    margin-bottom: 30px;
    text-align: center;
}

.vortex-marketplace-header h2 {
    font-size: 32px;
    color: #333;
    margin-bottom: 10px;
}

.vortex-marketplace-description {
    color: #666;
    font-size: 16px;
}

.vortex-live-artists-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.vortex-artist-frame {
    border-radius: 8px;
    background-color: #fff;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.vortex-artist-frame:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.vortex-artist-header {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #f1f1f1;
}

.vortex-artist-avatar {
    position: relative;
    margin-right: 15px;
}

.vortex-artist-avatar img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.vortex-online-indicator {
    position: absolute;
    bottom: 3px;
    right: 3px;
    width: 12px;
    height: 12px;
    background-color: #4caf50;
    border-radius: 50%;
    border: 2px solid #fff;
}

.vortex-artist-info {
    flex: 1;
}

.vortex-artist-name {
    margin: 0 0 5px;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.vortex-artist-style {
    font-size: 14px;
    color: #555;
    margin-bottom: 5px;
}

.vortex-online-since {
    font-size: 12px;
    color: #888;
}

.vortex-artist-showcase {
    display: flex;
    gap: 10px;
    padding: 15px;
    border-bottom: 1px solid #f1f1f1;
}

.vortex-artwork-thumbnail {
    flex: 1;
    position: relative;
    border-radius: 4px;
    overflow: hidden;
}

.vortex-artwork-thumbnail img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}

.vortex-artwork-title {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 5px 8px;
    font-size: 12px;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

.vortex-artist-actions {
    display: flex;
    gap: 8px;
    padding: 15px;
}

.vortex-button {
    display: inline-block;
    background-color: #4e73df;
    color: #fff;
    padding: 10px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.3s;
    border: none;
    cursor: pointer;
    text-align: center;
}

.vortex-button:hover {
    background-color: #2e59d9;
    color: #fff;
}

.vortex-button-secondary {
    background-color: #f8f9fc;
    color: #4e73df;
    border: 1px solid #4e73df;
}

.vortex-button-secondary:hover {
    background-color: #eaecf4;
    color: #2e59d9;
}

.vortex-button-sm {
    padding: 8px 12px;
    font-size: 13px;
    flex: 1;
}

.vortex-notice {
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 30px;
    background-color: #f8f9fc;
    border-left: 5px solid #4e73df;
}

.vortex-notice h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 20px;
}

.vortex-warning {
    background-color: #fff8e1;
    border-left-color: #ffc107;
}

.vortex-warning h3 {
    color: #856404;
}

.vortex-login-options {
    margin-top: 20px;
    display: flex;
    gap: 15px;
}

@media (max-width: 768px) {
    .vortex-live-artists-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .vortex-login-options {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle message button click
    $('.vortex-message-btn').on('click', function(e) {
        e.preventDefault();
        const artistId = $(this).data('artist-id');
        
        // In a real implementation, this would open a chat interface
        alert('Opening chat with artist ID: ' + artistId);
    });
    
    // Handle video call button click
    $('.vortex-video-btn').on('click', function(e) {
        e.preventDefault();
        const artistId = $(this).data('artist-id');
        
        // In a real implementation, this would initiate a video call
        alert('Initiating video call with artist ID: ' + artistId);
    });
});
</script> 