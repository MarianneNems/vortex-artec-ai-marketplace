/**
 * JavaScript for Vortex Marketplace Shuffle and Winners Display
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize shuffle feature if available
        initShuffleFeature();
        
        // Initialize winners display if available
        initWinnersDisplay();
    });

    /**
     * Initialize gallery shuffle feature
     */
    function initShuffleFeature() {
        // Check for admin controls
        if ($('.vortex-shuffle-controls').length) {
            $('.vortex-manual-shuffle-btn').on('click', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const originalText = $btn.text();
                
                $btn.text('Shuffling...').prop('disabled', true);
                
                // Make API request to trigger shuffle
                $.ajax({
                    url: vortexShuffleData.restUrl + 'vortex/v1/shuffle/gallery',
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', vortexShuffleData.restNonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showMessage('success', 'Gallery shuffled successfully!');
                            
                            // Update last shuffle time
                            if (response.last_shuffle) {
                                $('.vortex-last-shuffle-time').text(response.last_shuffle);
                            }
                            
                            // Reload gallery if on gallery page
                            if ($('.vortex-artwork-gallery').length) {
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
                            }
                        } else {
                            showMessage('error', 'Failed to shuffle gallery.');
                        }
                    },
                    error: function() {
                        showMessage('error', 'An error occurred while shuffling gallery.');
                    },
                    complete: function() {
                        $btn.text(originalText).prop('disabled', false);
                    }
                });
            });
        }
        
        // Check for shuffle countdown
        if ($('.vortex-shuffle-countdown').length) {
            updateShuffleCountdown();
        }
    }

    /**
     * Update shuffle countdown timer
     */
    function updateShuffleCountdown() {
        const lastShuffleTime = new Date($('.vortex-shuffle-countdown').data('last-shuffle'));
        const intervalHours = parseInt($('.vortex-shuffle-countdown').data('interval-hours')) || 3;
        
        // Set next shuffle time (last shuffle + interval hours)
        const nextShuffleTime = new Date(lastShuffleTime);
        nextShuffleTime.setHours(nextShuffleTime.getHours() + intervalHours);
        
        // Update countdown every second
        setInterval(function() {
            const now = new Date();
            const timeDiff = nextShuffleTime - now;
            
            if (timeDiff <= 0) {
                // Time expired, show 00:00:00
                $('.vortex-shuffle-countdown-timer').text('00:00:00');
                
                // Check if we should refresh the page
                setTimeout(function() {
                    window.location.reload();
                }, 5000);
            } else {
                // Calculate hours, minutes, seconds
                const hours = Math.floor(timeDiff / (1000 * 60 * 60));
                const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);
                
                // Format with leading zeros
                const formattedHours = hours.toString().padStart(2, '0');
                const formattedMinutes = minutes.toString().padStart(2, '0');
                const formattedSeconds = seconds.toString().padStart(2, '0');
                
                // Update countdown display
                $('.vortex-shuffle-countdown-timer').text(
                    `${formattedHours}:${formattedMinutes}:${formattedSeconds}`
                );
            }
        }, 1000);
    }

    /**
     * Initialize winners display
     */
    function initWinnersDisplay() {
        // Check for winners display
        if ($('.vortex-daily-winners').length) {
            // Add animation class to winners if they're newly announced
            const announcementTime = $('.vortex-daily-winners').data('announcement-time');
            
            if (announcementTime) {
                const announcementDate = new Date(announcementTime);
                const now = new Date();
                
                // If announced in the last hour, add animation class
                if ((now - announcementDate) / (1000 * 60 * 60) < 1) {
                    $('.vortex-winner-card').addClass('vortex-winner-new');
                }
            }
            
            // Initialize date picker if available
            if ($('.vortex-winners-date-picker').length) {
                $('.vortex-winners-date-picker').on('change', function() {
                    const selectedDate = $(this).val();
                    
                    if (selectedDate) {
                        window.location.href = vortexShuffleData.winnersUrl + '?date=' + selectedDate;
                    }
                });
            }
        }
    }

    /**
     * Show message to user
     * 
     * @param {string} type Message type ('success', 'error', 'info')
     * @param {string} message Message text
     */
    function showMessage(type, message) {
        // Check if notification container exists, create if not
        let $notifyContainer = $('.vortex-notifications');
        
        if (!$notifyContainer.length) {
            $notifyContainer = $('<div class="vortex-notifications"></div>');
            $('body').append($notifyContainer);
        }
        
        // Create notification
        const $notification = $(
            `<div class="vortex-notification vortex-notification-${type}">
                <span class="vortex-notification-message">${message}</span>
                <button class="vortex-notification-close">&times;</button>
            </div>`
        );
        
        // Add to container
        $notifyContainer.append($notification);
        
        // Add close handler
        $notification.find('.vortex-notification-close').on('click', function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery); 