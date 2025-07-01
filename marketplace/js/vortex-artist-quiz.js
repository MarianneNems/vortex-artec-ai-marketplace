/**
 * Handle artist qualification quiz submissions and feedback
 */
(function($) {
    $(document).ready(function() {
        // Handle quiz form submission
        $('#vortex-artist-qualification-quiz').on('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            var education = $('#education').val();
            var selfTaughtYears = $('#self_taught_years').val() || 0;
            var style = $('#style').val();
            var exhibitions = $('#exhibitions').val();
            var priceRange = $('#price_range').val();
            var seedArtCommitment = $('#seed_art_commitment').is(':checked') ? 1 : 0;
            
            // Get nonce
            var nonce = $('#quiz_security').val();
            
            if (!nonce) {
                console.error('Security nonce is missing');
                return;
            }
            
            // Show loading indicator
            var $submitButton = $(this).find('button[type="submit"]');
            var originalText = $submitButton.text();
            $submitButton.text('Analyzing...').prop('disabled', true);
            
            // AJAX request
            $.ajax({
                url: vortex_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_analyze_quiz_responses',
                    security: nonce,
                    education: education,
                    self_taught_years: selfTaughtYears,
                    style: style,
                    exhibitions: exhibitions,
                    price_range: priceRange,
                    seed_art_commitment: seedArtCommitment
                },
                success: function(response) {
                    // Reset button
                    $submitButton.text(originalText).prop('disabled', false);
                    
                    if (response.success) {
                        // Show feedback
                        var feedbackHtml = '<div class="vortex-quiz-feedback '+ response.data.tier +'">' +
                                          '<h3>Your Artist Assessment</h3>' +
                                          '<p>' + response.data.feedback + '</p>' +
                                          '</div>';
                        
                        // Check if feedback container exists, otherwise create it
                        var $feedbackContainer = $('.vortex-quiz-feedback-container');
                        if ($feedbackContainer.length === 0) {
                            $feedbackContainer = $('<div class="vortex-quiz-feedback-container"></div>');
                            $(feedbackHtml).appendTo($feedbackContainer);
                            $feedbackContainer.insertAfter('#vortex-artist-qualification-quiz');
                        } else {
                            $feedbackContainer.html(feedbackHtml);
                        }
                        
                        // Scroll to feedback
                        $('html, body').animate({
                            scrollTop: $feedbackContainer.offset().top - 50
                        }, 500);
                        
                        // Store feedback in local storage for post-registration display
                        localStorage.setItem('vortex_artist_quiz_feedback', JSON.stringify({
                            feedback: response.data.feedback,
                            tier: response.data.tier,
                            score: response.data.score
                        }));
                    } else {
                        // Show error
                        alert(response.data.message || 'There was an error processing your quiz. Please try again.');
                    }
                },
                error: function() {
                    $submitButton.text(originalText).prop('disabled', false);
                    alert('Connection error. Please try again later.');
                }
            });
        });
        
        // Check for stored feedback after registration redirect
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('registered') === 'true') {
            var storedFeedback = localStorage.getItem('vortex_artist_quiz_feedback');
            if (storedFeedback) {
                try {
                    var feedbackData = JSON.parse(storedFeedback);
                    var $successMessage = $('.vortex-registration-success');
                    
                    if ($successMessage.length > 0) {
                        // Append feedback to success message
                        $successMessage.append(
                            '<div class="vortex-quiz-feedback '+ feedbackData.tier +'">' +
                            '<h4>Your Artist Assessment</h4>' +
                            '<p>' + feedbackData.feedback + '</p>' +
                            '</div>'
                        );
                        
                        // Clear stored feedback
                        localStorage.removeItem('vortex_artist_quiz_feedback');
                    }
                } catch (e) {
                    console.error('Error parsing stored feedback', e);
                }
            }
        }
    });
})(jQuery); 