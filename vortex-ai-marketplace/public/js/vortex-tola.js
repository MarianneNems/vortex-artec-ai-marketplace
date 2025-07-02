/**
 * VORTEX AI Marketplace Frontend JavaScript
 *
 * Handles all AJAX interactions with the REST API endpoints
 * for the Artist Journey implementation.
 *
 * @since 2.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        VortexAPI.init();
    });

    /**
     * Main VORTEX API object
     */
    window.VortexAPI = {
        
        /**
         * Initialize the API handlers
         */
        init: function() {
            this.bindEvents();
            this.loadInitialData();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Artist Journey Events
            $(document).on('click', '#startRoleQuiz', this.startRoleQuiz);
            $(document).on('click', '#connectWallet', this.connectWallet);
            $(document).on('change', '#seedArtFile', this.uploadSeedArt);
            $(document).on('click', '#completeSignup', this.completeSignup);

            // AI Generation Events
            $(document).on('click', '#generateArt', this.generateArtwork);
            $(document).on('change', '#galleryFilter', this.filterGallery);
            $(document).on('click', '#createCollection', this.createCollection);

            // Plan Management Events
            $(document).on('click', '.vortex-plan-select', this.selectPlan);
            
            // Milestone Events
            $(document).on('click', '.vortex-milestone-complete', this.completeMilestone);
        },

        /**
         * Load initial data on page load
         */
        loadInitialData: function() {
            if ($('#vortexPlansGrid').length) {
                this.loadPlans();
            }
            if ($('#generationLimits').length) {
                this.loadGenerationLimits();
            }
            if ($('#milestonesTimeline').length) {
                this.loadMilestones();
            }
            if ($('#galleryGrid').length) {
                this.loadGallery();
            }
        },

        /**
         * Make API request with proper authentication
         */
        apiRequest: function(endpoint, method, data, callback) {
            $.ajax({
                url: vortexAjax.restUrl + endpoint,
                method: method || 'GET',
                data: data,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', vortexAjax.nonce);
                },
                success: function(response) {
                    if (callback) callback(null, response);
                },
                error: function(xhr, status, error) {
                    const errorData = xhr.responseJSON || { message: error };
                    if (callback) callback(errorData, null);
                }
            });
        },

        /**
         * Load subscription plans
         */
        loadPlans: function() {
            this.apiRequest('plans', 'GET', null, function(error, response) {
                if (error) {
                    $('#vortexPlansGrid').html('<div class="vortex-error">Failed to load plans</div>');
                    return;
                }

                let html = '';
                response.plans.forEach(function(plan) {
                    html += `
                        <div class="vortex-plan ${plan.popular ? 'vortex-plan-popular' : ''}" data-plan="${plan.id}">
                            ${plan.popular ? '<div class="vortex-plan-badge">Popular</div>' : ''}
                            <h3>${plan.name}</h3>
                            <div class="vortex-plan-price">$${plan.price}<span>/month</span></div>
                            <ul class="vortex-plan-features">
                                ${plan.features.map(feature => `<li>${feature}</li>`).join('')}
                            </ul>
                            <button class="vortex-btn vortex-btn-primary vortex-plan-select" data-plan="${plan.id}">
                                Choose ${plan.name}
                            </button>
                        </div>
                    `;
                });

                $('#vortexPlansGrid').html(html);
            });
        },

        /**
         * Select a subscription plan
         */
        selectPlan: function(e) {
            e.preventDefault();
            const planId = $(this).data('plan');
            const userId = vortexAjax.currentUserId;

            if (!userId) {
                alert('Please log in to select a plan.');
            return;
        }
        
            VortexAPI.apiRequest(`users/${userId}/plan`, 'POST', { plan: planId }, function(error, response) {
                if (error) {
                    alert('Failed to select plan: ' + error.message);
            return;
        }
        
                alert('Plan selected successfully!');
                $('.vortex-plan').removeClass('vortex-plan-selected');
                $(`.vortex-plan[data-plan="${planId}"]`).addClass('vortex-plan-selected');
            });
        },

        /**
         * Start the role quiz
         */
        startRoleQuiz: function(e) {
            e.preventDefault();
            
            // Mock quiz data - in real implementation, this would be dynamic
            const quizQuestions = [
                {
                    question: "What describes your primary interest in art?",
                    options: [
                        { text: "Creating original artworks", value: "artist" },
                        { text: "Collecting and investing", value: "collector" },
                        { text: "Curating and organizing", value: "curator" },
                        { text: "Trading and investing", value: "investor" }
                    ]
                }
            ];

            VortexAPI.showQuizModal(quizQuestions, function(answers) {
                const userId = vortexAjax.currentUserId;
                VortexAPI.apiRequest(`users/${userId}/role-quiz`, 'POST', { answers: answers }, function(error, response) {
                    if (error) {
                        alert('Quiz submission failed: ' + error.message);
                return;
            }
            
                    alert(`Quiz completed! Your recommended role: ${response.recommended_role}`);
                    $('.vortex-step[data-step="role-quiz"]').addClass('vortex-step-completed');
                    VortexAPI.checkSignupCompletion();
                });
            });
        },

        /**
         * Connect wallet
         */
        connectWallet: function(e) {
            e.preventDefault();

            // Check if Phantom wallet is available
            if (typeof window.solana === 'undefined') {
                alert('Please install Phantom wallet to continue.');
                return;
            }
            
            window.solana.connect().then(function(response) {
                const walletAddress = response.publicKey.toString();
                
                VortexAPI.apiRequest('wallet/connect', 'POST', {
                    wallet_address: walletAddress,
                    wallet_type: 'phantom'
                }, function(error, response) {
                    if (error) {
                        alert('Wallet connection failed: ' + error.message);
                        return;
                    }

                    alert('Wallet connected successfully!');
                    $('.vortex-step[data-step="wallet-connect"]').addClass('vortex-step-completed');
                    VortexAPI.checkSignupCompletion();
                });
            }).catch(function(error) {
                alert('Wallet connection cancelled.');
            });
        },

        /**
         * Upload seed art
         */
        uploadSeedArt: function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('seed_art', file);

            const userId = vortexAjax.currentUserId;
            
            $.ajax({
                url: vortexAjax.restUrl + `users/${userId}/seed-art/upload`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', vortexAjax.nonce);
                },
                success: function(response) {
                    alert('Seed art uploaded successfully!');
                    $('.vortex-step[data-step="seed-art"]').addClass('vortex-step-completed');
                    VortexAPI.checkSignupCompletion();
                },
                error: function(xhr) {
                    const error = xhr.responseJSON || { message: 'Upload failed' };
                    alert('Upload failed: ' + error.message);
                }
            });
        },

        /**
         * Check if signup is complete
         */
        checkSignupCompletion: function() {
            const completedSteps = $('.vortex-step-completed').length;
            const totalSteps = $('.vortex-step').length;

            if (completedSteps >= totalSteps) {
                $('#completeSignup').prop('disabled', false);
            }
        },

        /**
         * Complete signup process
         */
        completeSignup: function(e) {
            e.preventDefault();
            
            const userId = vortexAjax.currentUserId;
            VortexAPI.apiRequest(`users/${userId}/accept-tos`, 'POST', { tos_version: '1.0' }, function(error, response) {
                if (error) {
                    alert('Signup completion failed: ' + error.message);
                    return;
                }

                alert('Congratulations! Your Artist Journey setup is complete.');
                window.location.reload();
            });
        },

        /**
         * Generate artwork
         */
        generateArtwork: function(e) {
            e.preventDefault();

            const prompt = $('#artPrompt').val();
            const style = $('#artStyle').val();
            const dimensions = $('#artDimensions').val();

            if (!prompt.trim()) {
                alert('Please enter a prompt for artwork generation.');
                return;
            }
            
            $('#generateArt').prop('disabled', true).text('Generating...');

            VortexAPI.apiRequest('api/generate', 'POST', {
                prompt: prompt,
                style: style,
                dimensions: dimensions
            }, function(error, response) {
                $('#generateArt').prop('disabled', false).text('Generate Artwork');

                if (error) {
                    alert('Generation failed: ' + error.message);
                    return;
                }

                VortexAPI.pollGenerationStatus(response.job_id);
            });
        },

        /**
         * Poll generation status
         */
        pollGenerationStatus: function(jobId) {
            const pollInterval = setInterval(function() {
                VortexAPI.apiRequest(`api/generate/status/${jobId}`, 'GET', null, function(error, response) {
                    if (error) {
                        clearInterval(pollInterval);
                        alert('Failed to check generation status');
                return;
            }
            
                    if (response.status === 'completed') {
                        clearInterval(pollInterval);
                        VortexAPI.displayGenerationResults(response);
                    } else if (response.status === 'failed') {
                        clearInterval(pollInterval);
                        alert('Generation failed');
                    }
                });
            }, 2000);
        },

        /**
         * Display generation results
         */
        displayGenerationResults: function(generation) {
            let html = '<div class="vortex-generation-result">';
            html += '<h3>Generation Complete!</h3>';
            html += '<div class="vortex-generated-images">';
            
            generation.result_urls.forEach(function(url, index) {
                html += `
                    <div class="vortex-generated-image">
                        <img src="${url}" alt="Generated artwork ${index + 1}">
                        <div class="vortex-image-actions">
                            <button class="vortex-btn vortex-btn-sm" onclick="VortexAPI.downloadImage('${url}')">Download</button>
                            <button class="vortex-btn vortex-btn-sm" onclick="VortexAPI.mintNFT('${url}')">Mint NFT</button>
                        </div>
                    </div>
                `;
            });

            html += '</div></div>';
            $('#generationResults').html(html);
            VortexAPI.loadGenerationLimits(); // Refresh limits
        },

        /**
         * Load generation limits
         */
        loadGenerationLimits: function() {
            const userId = vortexAjax.currentUserId;
            if (!userId) return;

            VortexAPI.apiRequest(`users/${userId}/plan`, 'GET', null, function(error, response) {
                if (error || !response.plan_details) return;

                const limits = response.plan_details.limits;
                const remaining = limits.monthly_generations === -1 ? 'Unlimited' : '50'; // Mock remaining
                $('#remainingGens').text(remaining);
            });
        },

        /**
         * Load user gallery
         */
        loadGallery: function() {
            const userId = vortexAjax.currentUserId;
            VortexAPI.apiRequest(`users/${userId}/collections`, 'GET', null, function(error, response) {
                if (error) {
                    $('#galleryGrid').html('<div class="vortex-error">Failed to load gallery</div>');
                    return;
                }

                // Mock gallery items
                const items = [
                    { id: 1, title: 'Digital Sunset', type: 'generated', url: 'https://via.placeholder.com/300x300' },
                    { id: 2, title: 'Abstract Dreams', type: 'uploaded', url: 'https://via.placeholder.com/300x300' }
                ];

                let html = '';
                items.forEach(function(item) {
                    html += `
                        <div class="vortex-gallery-item" data-type="${item.type}">
                            <img src="${item.url}" alt="${item.title}">
                            <div class="vortex-item-overlay">
                                <h4>${item.title}</h4>
                                <div class="vortex-item-actions">
                                    <button class="vortex-btn vortex-btn-sm" onclick="VortexAPI.viewItem(${item.id})">View</button>
                                    <button class="vortex-btn vortex-btn-sm" onclick="VortexAPI.editItem(${item.id})">Edit</button>
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#galleryGrid').html(html);
            });
        },

        /**
         * Load user milestones
         */
        loadMilestones: function() {
            const userId = vortexAjax.currentUserId;
            VortexAPI.apiRequest(`users/${userId}/milestones`, 'GET', null, function(error, response) {
                if (error) {
                    $('#milestonesTimeline').html('<div class="vortex-error">Failed to load milestones</div>');
                    return;
                }

                // Mock milestones
                const milestones = [
                    { 
                        id: 1, 
                        title: 'Complete Profile Setup', 
                        status: 'completed', 
                        progress: 100,
                        reward: '10 TOLA tokens'
                    },
                    { 
                        id: 2, 
                        title: 'Upload First Artwork', 
                        status: 'in_progress', 
                        progress: 75,
                        reward: '25 TOLA tokens'
                    },
                    { 
                        id: 3, 
                        title: 'Make First Sale', 
                        status: 'pending', 
                        progress: 0,
                        reward: '50 TOLA tokens'
                    }
                ];

                let html = '';
                milestones.forEach(function(milestone) {
                    html += `
                        <div class="vortex-milestone vortex-milestone-${milestone.status}">
                            <div class="vortex-milestone-icon">
                                ${milestone.status === 'completed' ? '✓' : milestone.status === 'in_progress' ? '⏳' : '○'}
                            </div>
                            <div class="vortex-milestone-content">
                                <h4>${milestone.title}</h4>
                                <div class="vortex-milestone-progress">
                                    <div class="vortex-progress-bar">
                                        <div class="vortex-progress-fill" style="width: ${milestone.progress}%"></div>
                                    </div>
                                    <span>${milestone.progress}%</span>
                                </div>
                                <div class="vortex-milestone-reward">Reward: ${milestone.reward}</div>
                            </div>
                        </div>
                    `;
                });

                $('#milestonesTimeline').html(html);

                // Update overall progress
                const overallProgress = Math.round(milestones.reduce((sum, m) => sum + m.progress, 0) / milestones.length);
                $('#overallProgress').css('width', overallProgress + '%');
                $('#overallProgressText').text(overallProgress + '%');
            });
        },

        /**
         * Show quiz modal
         */
        showQuizModal: function(questions, callback) {
            // Create modal HTML
            let modalHtml = `
                <div class="vortex-modal" id="vortexQuizModal">
                    <div class="vortex-modal-content">
                        <div class="vortex-modal-header">
                            <h3>Role Discovery Quiz</h3>
                            <button class="vortex-modal-close">&times;</button>
                        </div>
                        <div class="vortex-modal-body">
                            <form id="roleQuizForm">
            `;

            questions.forEach(function(q, index) {
                modalHtml += `
                    <div class="vortex-quiz-question">
                        <h4>${q.question}</h4>
                        <div class="vortex-quiz-options">
                `;
                q.options.forEach(function(option) {
                    modalHtml += `
                        <label>
                            <input type="radio" name="question_${index}" value="${option.value}">
                            ${option.text}
                        </label>
                    `;
                });
                modalHtml += '</div></div>';
            });

            modalHtml += `
                            </form>
                        </div>
                        <div class="vortex-modal-footer">
                            <button class="vortex-btn vortex-btn-primary" id="submitQuiz">Submit Quiz</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('#vortexQuizModal').show();

            // Handle quiz submission
            $('#submitQuiz').on('click', function() {
                const formData = new FormData(document.getElementById('roleQuizForm'));
                const answers = [];
                for (let [key, value] of formData.entries()) {
                    answers.push({ [key]: value, role_preference: value });
                }

                $('#vortexQuizModal').remove();
                callback(answers);
            });

            // Handle modal close
            $('.vortex-modal-close').on('click', function() {
                $('#vortexQuizModal').remove();
            });
        },

        /**
         * Utility functions
         */
        downloadImage: function(url) {
            const link = document.createElement('a');
            link.href = url;
            link.download = 'generated-artwork.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        mintNFT: function(imageUrl) {
            VortexAPI.apiRequest('nft/mint', 'POST', { image_url: imageUrl }, function(error, response) {
                if (error) {
                    alert('NFT minting failed: ' + error.message);
                    return;
                }
                alert('NFT minting initiated! Transaction ID: ' + response.transaction_id);
            });
        },

        viewItem: function(itemId) {
            // Open item in modal or navigate to detail page
            console.log('Viewing item:', itemId);
        },

        editItem: function(itemId) {
            // Open edit modal
            console.log('Editing item:', itemId);
        }
    };

})(jQuery); 