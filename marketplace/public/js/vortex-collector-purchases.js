/**
 * JavaScript for Collector Purchases functionality
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_Art_Marketplace
 * @subpackage Vortex_Art_Marketplace/public/js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab switching functionality
        $('.vortex-tab-button').on('click', function(e) {
            e.preventDefault();
            const targetId = $(this).data('target');
            
            // Update active tab
            $('.vortex-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Show target content
            $('.vortex-tab-pane').removeClass('active');
            $('#' + targetId).addClass('active');
        });

        // Message artist functionality
        $('.vortex-message-artist').on('click', function(e) {
            e.preventDefault();
            const artistId = $(this).data('artist-id');
            const artistName = $(this).data('artist-name');
            
            if (typeof vortexChat !== 'undefined' && vortexChat.openChat) {
                // If chat system is available
                vortexChat.openChat(artistId);
            } else {
                // Fallback alert
                alert('Messaging feature coming soon! You will be able to message ' + artistName + ' directly.');
            }
        });

        // View artwork functionality
        $('.vortex-view-artwork').on('click', function(e) {
            const artworkUrl = $(this).data('artwork-url');
            if (artworkUrl) {
                window.location.href = artworkUrl;
            }
        });

        // Artist profile link functionality
        $('.vortex-view-artist').on('click', function(e) {
            const artistUrl = $(this).data('artist-url');
            if (artistUrl) {
                window.location.href = artistUrl;
            }
        });

        // Unfollow artist functionality
        $('.vortex-unfollow-artist').on('click', function(e) {
            e.preventDefault();
            const artistId = $(this).data('artist-id');
            const artistName = $(this).data('artist-name');
            const $button = $(this);
            
            if (confirm('Are you sure you want to unfollow ' + artistName + '?')) {
                // If AJAX endpoint is available
                if (typeof vortex_collector_data !== 'undefined' && vortex_collector_data.ajax_url) {
                    $.ajax({
                        url: vortex_collector_data.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'vortex_unfollow_artist',
                            artist_id: artistId,
                            security: vortex_collector_data.security
                        },
                        beforeSend: function() {
                            $button.prop('disabled', true).text('Unfollowing...');
                        },
                        success: function(response) {
                            if (response.success) {
                                // Remove the artist card with a fade out effect
                                $button.closest('.vortex-artist-card').fadeOut(300, function() {
                                    $(this).remove();
                                    
                                    // Update the counter
                                    let count = parseInt($('.vortex-following-count').text());
                                    $('.vortex-following-count').text(count - 1);
                                    
                                    // Show empty state if no more artists
                                    if (count - 1 === 0) {
                                        $('#vortex-following-tab').html(
                                            '<div class="vortex-empty-state">' +
                                            '<div class="vortex-empty-icon">👥</div>' +
                                            '<p>You are not following any artists yet.</p>' +
                                            '<a href="#" class="vortex-button">Discover Artists</a>' +
                                            '</div>'
                                        );
                                    }
                                });
                            } else {
                                alert('Error: ' + (response.data || 'Could not unfollow artist.'));
                                $button.prop('disabled', false).text('Unfollow');
                            }
                        },
                        error: function() {
                            alert('Error: Could not connect to the server.');
                            $button.prop('disabled', false).text('Unfollow');
                        }
                    });
                } else {
                    // Fallback for development/testing
                    $button.closest('.vortex-artist-card').fadeOut(300, function() {
                        $(this).remove();
                        
                        // Update the counter
                        let count = parseInt($('.vortex-following-count').text());
                        $('.vortex-following-count').text(count - 1);
                        
                        // Show empty state if no more artists
                        if (count - 1 === 0) {
                            $('#vortex-following-tab').html(
                                '<div class="vortex-empty-state">' +
                                '<div class="vortex-empty-icon">👥</div>' +
                                '<p>You are not following any artists yet.</p>' +
                                '<a href="#" class="vortex-button">Discover Artists</a>' +
                                '</div>'
                            );
                        }
                    });
                }
            }
        });
    });

})(jQuery); 