<?php
/**
 * Template for displaying collector purchases and followed artists
 *
 * @link       https://vortexai.io
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();

// Check if user is a collector
if (!in_array('vortex_collector', (array) $current_user->roles)) {
    echo '<div class="vortex-notice vortex-error">';
    esc_html_e('You must be a collector to view this page.', 'vortex-ai-marketplace');
    echo '</div>';
    return;
}

// Get show settings from shortcode attributes
$show_artwork = isset($atts['show_artwork']) ? filter_var($atts['show_artwork'], FILTER_VALIDATE_BOOLEAN) : true;
$show_following = isset($atts['show_following']) ? filter_var($atts['show_following'], FILTER_VALIDATE_BOOLEAN) : true;

// Get user ID
$user_id = get_current_user_id();

// Get purchased artworks
// TODO: Replace with actual database query
$purchased_artworks = array(
    array(
        'id' => 1,
        'title' => 'Cosmic Dreamer',
        'image' => plugin_dir_url(dirname(dirname(__FILE__))) . 'public/images/placeholder-1.jpg',
        'purchase_date' => '2023-10-15',
        'price' => 250.00,
        'artist' => array(
            'id' => 101,
            'name' => 'Maria Lorenzo',
            'profile_url' => '#artist-101'
        )
    ),
    array(
        'id' => 2,
        'title' => 'Digital Horizon',
        'image' => plugin_dir_url(dirname(dirname(__FILE__))) . 'public/images/placeholder-2.jpg',
        'purchase_date' => '2023-11-03',
        'price' => 175.50,
        'artist' => array(
            'id' => 102,
            'name' => 'Alex Chen',
            'profile_url' => '#artist-102'
        )
    ),
    array(
        'id' => 3,
        'title' => 'Neural Pathways',
        'image' => plugin_dir_url(dirname(dirname(__FILE__))) . 'public/images/placeholder-3.jpg',
        'purchase_date' => '2023-12-20',
        'price' => 310.00,
        'artist' => array(
            'id' => 103,
            'name' => 'Sophia Jenkins',
            'profile_url' => '#artist-103'
        )
    ),
);

// Get followed artists
// TODO: Replace with actual database query
$followed_artists = array(
    array(
        'id' => 101,
        'name' => 'Maria Lorenzo',
        'image' => plugin_dir_url(dirname(dirname(__FILE__))) . 'public/images/artist-1.jpg',
        'bio' => 'Contemporary digital artist specializing in abstract neural art and AI collaborations.',
        'profile_url' => '#artist-101'
    ),
    array(
        'id' => 104,
        'name' => 'James Peterson',
        'image' => plugin_dir_url(dirname(dirname(__FILE__))) . 'public/images/artist-2.jpg',
        'bio' => 'Traditional painter exploring the intersection of hand-crafted art and machine learning.',
        'profile_url' => '#artist-104'
    ),
    array(
        'id' => 105,
        'name' => 'Elena Rodriguez',
        'image' => plugin_dir_url(dirname(dirname(__FILE__))) . 'public/images/artist-3.jpg',
        'bio' => 'Mixed media artist using AI to enhance traditional sculpture and installation art.',
        'profile_url' => '#artist-105'
    ),
);
?>

<div class="vortex-collector-purchases-container">
    <h2 class="vortex-collector-heading"><?php esc_html_e('My Collection', 'vortex-ai-marketplace'); ?></h2>
    
    <div class="vortex-collector-tabs">
        <?php if ($show_artwork): ?>
        <button class="vortex-tab-button active" data-tab="purchases">
            <?php esc_html_e('Purchased Artwork', 'vortex-ai-marketplace'); ?>
            <span class="vortex-count"><?php echo count($purchased_artworks); ?></span>
        </button>
        <?php endif; ?>
        
        <?php if ($show_following): ?>
        <button class="vortex-tab-button <?php echo !$show_artwork ? 'active' : ''; ?>" data-tab="following">
            <?php esc_html_e('Following Artists', 'vortex-ai-marketplace'); ?>
            <span class="vortex-count"><?php echo count($followed_artists); ?></span>
        </button>
        <?php endif; ?>
    </div>
    
    <div class="vortex-tab-content">
        <?php if ($show_artwork): ?>
        <div id="vortex-purchases" class="vortex-tab-pane <?php echo $show_artwork ? 'active' : ''; ?>">
            <?php if (empty($purchased_artworks)): ?>
                <div class="vortex-empty-state">
                    <div class="vortex-empty-icon">🖼️</div>
                    <p><?php esc_html_e('You haven\'t purchased any artwork yet.', 'vortex-ai-marketplace'); ?></p>
                    <a href="<?php echo esc_url(home_url('/marketplace')); ?>" class="vortex-button">
                        <?php esc_html_e('Browse Marketplace', 'vortex-ai-marketplace'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="vortex-purchased-artworks">
                    <?php foreach ($purchased_artworks as $artwork): ?>
                        <div class="vortex-artwork-card">
                            <div class="vortex-artwork-image">
                                <img src="<?php echo esc_url($artwork['image']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>">
                            </div>
                            <div class="vortex-artwork-details">
                                <h3 class="vortex-artwork-title"><?php echo esc_html($artwork['title']); ?></h3>
                                <p class="vortex-artwork-artist">
                                    <?php esc_html_e('By', 'vortex-ai-marketplace'); ?> 
                                    <a href="<?php echo esc_url($artwork['artist']['profile_url']); ?>">
                                        <?php echo esc_html($artwork['artist']['name']); ?>
                                    </a>
                                </p>
                                <p class="vortex-artwork-meta">
                                    <span class="vortex-purchase-date">
                                        <?php esc_html_e('Purchased on', 'vortex-ai-marketplace'); ?> 
                                        <?php echo date_i18n(get_option('date_format'), strtotime($artwork['purchase_date'])); ?>
                                    </span>
                                </p>
                                <p class="vortex-artwork-price">
                                    <?php echo get_woocommerce_currency_symbol() . ' ' . number_format($artwork['price'], 2); ?>
                                </p>
                                <div class="vortex-artwork-actions">
                                    <a href="#" class="vortex-button vortex-button-secondary vortex-download-button">
                                        <?php esc_html_e('Download', 'vortex-ai-marketplace'); ?>
                                    </a>
                                    <a href="#" class="vortex-button vortex-message-button" data-artist-id="<?php echo esc_attr($artwork['artist']['id']); ?>">
                                        <?php esc_html_e('Message Artist', 'vortex-ai-marketplace'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($show_following): ?>
        <div id="vortex-following" class="vortex-tab-pane <?php echo !$show_artwork ? 'active' : ''; ?>">
            <?php if (empty($followed_artists)): ?>
                <div class="vortex-empty-state">
                    <div class="vortex-empty-icon">👩‍🎨</div>
                    <p><?php esc_html_e('You\'re not following any artists yet.', 'vortex-ai-marketplace'); ?></p>
                    <a href="<?php echo esc_url(home_url('/artists')); ?>" class="vortex-button">
                        <?php esc_html_e('Discover Artists', 'vortex-ai-marketplace'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="vortex-followed-artists">
                    <?php foreach ($followed_artists as $artist): ?>
                        <div class="vortex-artist-card">
                            <div class="vortex-artist-image">
                                <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                            </div>
                            <div class="vortex-artist-details">
                                <h3 class="vortex-artist-name"><?php echo esc_html($artist['name']); ?></h3>
                                <p class="vortex-artist-bio"><?php echo esc_html($artist['bio']); ?></p>
                                <div class="vortex-artist-actions">
                                    <a href="<?php echo esc_url($artist['profile_url']); ?>" class="vortex-button vortex-button-secondary">
                                        <?php esc_html_e('View Gallery', 'vortex-ai-marketplace'); ?>
                                    </a>
                                    <a href="#" class="vortex-button vortex-message-button" data-artist-id="<?php echo esc_attr($artist['id']); ?>">
                                        <?php esc_html_e('Message', 'vortex-ai-marketplace'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.vortex-collector-purchases-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.vortex-collector-heading {
    font-size: 32px;
    margin-bottom: 24px;
    font-weight: 600;
    color: #333;
}

.vortex-collector-tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.vortex-tab-button {
    padding: 12px 24px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    color: #666;
    transition: all 0.3s ease;
    position: relative;
}

.vortex-tab-button:after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 3px;
    background: transparent;
    transition: all 0.3s ease;
}

.vortex-tab-button.active {
    color: #6200ea;
}

.vortex-tab-button.active:after {
    background: #6200ea;
}

.vortex-count {
    display: inline-block;
    background: #f0f0f0;
    color: #666;
    font-size: 12px;
    border-radius: 12px;
    padding: 2px 8px;
    margin-left: 6px;
}

.vortex-tab-pane {
    display: none;
}

.vortex-tab-pane.active {
    display: block;
}

.vortex-purchased-artworks,
.vortex-followed-artists {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.vortex-artwork-card,
.vortex-artist-card {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background: #fff;
}

.vortex-artwork-card:hover,
.vortex-artist-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.vortex-artwork-image,
.vortex-artist-image {
    height: 200px;
    overflow: hidden;
}

.vortex-artwork-image img,
.vortex-artist-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.vortex-artwork-card:hover .vortex-artwork-image img,
.vortex-artist-card:hover .vortex-artist-image img {
    transform: scale(1.05);
}

.vortex-artwork-details,
.vortex-artist-details {
    padding: 16px;
}

.vortex-artwork-title,
.vortex-artist-name {
    font-size: 18px;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.vortex-artwork-artist,
.vortex-artwork-meta {
    font-size: 14px;
    color: #666;
    margin-bottom: 6px;
}

.vortex-artwork-artist a {
    color: #6200ea;
    text-decoration: none;
}

.vortex-artwork-price {
    font-size: 18px;
    font-weight: 600;
    color: #6200ea;
    margin-bottom: 12px;
}

.vortex-artist-bio {
    font-size: 14px;
    line-height: 1.5;
    color: #666;
    margin-bottom: 12px;
    height: 63px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.vortex-artwork-actions,
.vortex-artist-actions {
    display: flex;
    gap: 8px;
}

.vortex-button {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    background-color: #6200ea;
    color: white;
    border: none;
    flex: 1;
}

.vortex-button-secondary {
    background-color: #f0f0f0;
    color: #333;
}

.vortex-button:hover {
    opacity: 0.9;
}

.vortex-empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.vortex-empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.vortex-empty-state p {
    font-size: 16px;
    color: #666;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .vortex-purchased-artworks,
    .vortex-followed-artists {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

@media (max-width: 576px) {
    .vortex-purchased-artworks,
    .vortex-followed-artists {
        grid-template-columns: 1fr;
    }
    
    .vortex-collector-tabs {
        flex-direction: column;
        border-bottom: none;
    }
    
    .vortex-tab-button {
        border-bottom: 1px solid #e0e0e0;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.vortex-tab-button').on('click', function() {
        const tabId = $(this).data('tab');
        
        // Update active tab button
        $('.vortex-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Show selected tab content
        $('.vortex-tab-pane').removeClass('active');
        $('#vortex-' + tabId).addClass('active');
    });
    
    // Message button handling
    $('.vortex-message-button').on('click', function(e) {
        e.preventDefault();
        const artistId = $(this).data('artist-id');
        const artistName = $(this).closest('.vortex-artwork-card, .vortex-artist-card')
                               .find('.vortex-artwork-artist a, .vortex-artist-name')
                               .first()
                               .text().trim();
        
        alert('Messaging feature for ' + artistName + ' (ID: ' + artistId + ') coming soon!');
    });
});
</script> 