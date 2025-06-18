/**
 * Vortex Collector Forum JavaScript
 */
(function($) {
    'use strict';

    // Variables
    let currentTab = 'all';
    let currentStatus = 'all';
    let currentPage = 1;
    let postsPerPage = 10;
    let totalPosts = 0;
    let isLoading = false;
    let searchTimer = null;

    // DOM Ready
    $(document).ready(function() {
        initForumTabs();
        initForumFilters();
        initCreatePostForm();
        initPostDetails();
        
        // If view_post parameter is in URL, load post details
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('view_post')) {
            const postId = urlParams.get('view_post');
            loadPostDetails(postId);
        } else if (urlParams.has('create_post')) {
            // If create_post parameter is in URL, show create post form
            showCreatePostForm(urlParams.get('create_post'));
        } else {
            // Otherwise load posts
            loadPosts();
        }
    });

    /**
     * Initialize forum tabs
     */
    function initForumTabs() {
        $('.vortex-forum-tab').on('click', function() {
            const tab = $(this).data('tab');
            
            // Set active tab
            $('.vortex-forum-tab').removeClass('active');
            $(this).addClass('active');
            
            // Update current tab
            currentTab = tab;
            currentPage = 1;
            
            // Load posts for this tab
            loadPosts();
        });
    }

    /**
     * Initialize forum filters
     */
    function initForumFilters() {
        // Status filter
        $('#vortex-forum-status-filter').on('change', function() {
            currentStatus = $(this).val();
            currentPage = 1;
            loadPosts();
        });
        
        // Search
        $('#vortex-forum-search').on('input', function() {
            clearTimeout(searchTimer);
            
            searchTimer = setTimeout(function() {
                currentPage = 1;
                loadPosts();
            }, 500);
        });
        
        // Create button
        $('.vortex-create-post-btn').on('click', function(e) {
            e.preventDefault();
            
            const createType = $(this).data('type') || '';
            showCreatePostForm(createType);
        });
    }

    /**
     * Initialize create post form
     */
    function initCreatePostForm() {
        // Post type selection
        $('#post_type').on('change', function() {
            const postType = $(this).val();
            toggleFieldsByPostType(postType);
        });
        
        // Form submission
        $('#vortex-create-post-form').on('submit', function(e) {
            e.preventDefault();
            
            if (isLoading) return;
            
            isLoading = true;
            showLoader($('#vortex-form-submit'));
            
            const formData = new FormData(this);
            formData.append('action', 'vortex_create_forum_post');
            formData.append('security', vortexForum.security);
            
            $.ajax({
                url: vortexForum.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    isLoading = false;
                    hideLoader($('#vortex-form-submit'));
                    
                    if (response.success) {
                        showToast(response.data.message, 'success');
                        
                        // Redirect to the post
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        showToast(response.data.message, 'error');
                    }
                },
                error: function() {
                    isLoading = false;
                    hideLoader($('#vortex-form-submit'));
                    showToast(vortexForum.i18n.error_message, 'error');
                }
            });
        });
    }

    /**
     * Initialize post details view
     */
    function initPostDetails() {
        // Submit response form
        $(document).on('submit', '#vortex-submit-response-form', function(e) {
            e.preventDefault();
            
            if (isLoading) return;
            
            isLoading = true;
            showLoader($('#vortex-submit-response-btn'));
            
            const formData = new FormData(this);
            formData.append('action', 'vortex_submit_response');
            formData.append('security', vortexForum.security);
            
            $.ajax({
                url: vortexForum.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    isLoading = false;
                    hideLoader($('#vortex-submit-response-btn'));
                    
                    if (response.success) {
                        showToast(response.data.message, 'success');
                        
                        // Add the new response to the list
                        addNewResponse(response.data.response);
                        
                        // Clear the form
                        $('#vortex-submit-response-form textarea').val('');
                        $('#vortex-submit-response-form input[type="file"]').val('');
                    } else {
                        showToast(response.data.message, 'error');
                    }
                },
                error: function() {
                    isLoading = false;
                    hideLoader($('#vortex-submit-response-btn'));
                    showToast(vortexForum.i18n.error_message, 'error');
                }
            });
        });
        
        // Update post status
        $(document).on('click', '.vortex-update-status-btn', function() {
            const postId = $(this).data('post-id');
            const status = $(this).data('status');
            
            if (isLoading) return;
            
            isLoading = true;
            showLoader($(this));
            
            $.ajax({
                url: vortexForum.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_update_post_status',
                    security: vortexForum.security,
                    post_id: postId,
                    status: status
                },
                success: function(response) {
                    isLoading = false;
                    hideLoader($(this));
                    
                    if (response.success) {
                        showToast(response.data.message, 'success');
                        
                        // Update the status label
                        $('.vortex-forum-post-status').removeClass('open closed').addClass(response.data.status).text(
                            response.data.status === 'open' ? 'Open' : 'Closed'
                        );
                        
                        // Update the button text
                        $('.vortex-update-status-btn').data('status', response.data.status === 'open' ? 'closed' : 'open').text(
                            response.data.status === 'open' ? 'Close Post' : 'Reopen Post'
                        );
                    } else {
                        showToast(response.data.message, 'error');
                    }
                }.bind(this),
                error: function() {
                    isLoading = false;
                    hideLoader($(this));
                    showToast(vortexForum.i18n.error_message, 'error');
                }.bind(this)
            });
        });
    }

    /**
     * Load posts via AJAX
     */
    function loadPosts() {
        if (isLoading) return;
        
        isLoading = true;
        showLoader($('.vortex-forum-posts'));
        
        const search = $('#vortex-forum-search').val();
        
        $.ajax({
            url: vortexForum.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_load_forum_posts',
                security: vortexForum.security,
                post_type: currentTab,
                status: currentStatus,
                limit: postsPerPage,
                offset: (currentPage - 1) * postsPerPage,
                search: search
            },
            success: function(response) {
                isLoading = false;
                hideLoader($('.vortex-forum-posts'));
                
                if (response.success) {
                    renderPosts(response.data.posts);
                    totalPosts = response.data.total;
                    renderPagination();
                } else {
                    showToast(vortexForum.i18n.error_message, 'error');
                }
            },
            error: function() {
                isLoading = false;
                hideLoader($('.vortex-forum-posts'));
                showToast(vortexForum.i18n.error_message, 'error');
            }
        });
    }

    /**
     * Render posts to the DOM
     */
    function renderPosts(posts) {
        const $postsContainer = $('.vortex-forum-posts');
        
        // Empty container
        $postsContainer.empty();
        
        if (posts.length === 0) {
            $postsContainer.html('<div class="vortex-no-results">' + vortexForum.i18n.no_posts + '</div>');
            return;
        }
        
        // Loop through posts and create HTML
        $.each(posts, function(index, post) {
            const postHtml = `
                <div class="vortex-forum-post">
                    <div class="vortex-forum-post-header">
                        <div>
                            <a href="${post.post_url}" class="vortex-forum-post-title">${post.title}</a>
                            <div class="vortex-forum-post-meta">
                                <span>By ${post.author_name}</span>
                                <span>${post.created_at}</span>
                            </div>
                        </div>
                        <div>
                            <span class="vortex-forum-post-type ${post.post_type}">${capitalizeFirstLetter(post.post_type)}</span>
                            <span class="vortex-forum-post-status ${post.status}">${capitalizeFirstLetter(post.status)}</span>
                        </div>
                    </div>
                    <div class="vortex-forum-post-content">
                        ${post.description}
                    </div>
                    <div class="vortex-forum-post-footer">
                        <div class="vortex-forum-post-stats">
                            <span><i class="dashicons dashicons-visibility"></i> ${post.views}</span>
                            <span><i class="dashicons dashicons-admin-comments"></i> ${post.responses}</span>
                        </div>
                        <div>
                            ${post.budget ? '<span><i class="dashicons dashicons-money-alt"></i> $' + post.budget + '</span>' : ''}
                            ${post.deadline ? '<span><i class="dashicons dashicons-calendar-alt"></i> ' + post.deadline + '</span>' : ''}
                        </div>
                    </div>
                </div>
            `;
            
            $postsContainer.append(postHtml);
        });
    }

    /**
     * Load post details via AJAX
     */
    function loadPostDetails(postId) {
        if (isLoading) return;
        
        // Show the post details container
        $('.vortex-forum-container').hide();
        $('.vortex-forum-post-detail-container').show();
        
        isLoading = true;
        showLoader($('.vortex-forum-post-detail-container'));
        
        $.ajax({
            url: vortexForum.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_load_post_details',
                security: vortexForum.security,
                post_id: postId
            },
            success: function(response) {
                isLoading = false;
                hideLoader($('.vortex-forum-post-detail-container'));
                
                if (response.success) {
                    renderPostDetails(response.data.post);
                } else {
                    showToast(response.data.message, 'error');
                }
            },
            error: function() {
                isLoading = false;
                hideLoader($('.vortex-forum-post-detail-container'));
                showToast(vortexForum.i18n.error_message, 'error');
            }
        });
    }

    /**
     * Render post details to the DOM
     */
    function renderPostDetails(post) {
        const $container = $('.vortex-forum-post-detail-container');
        
        // Build HTML for post details
        let html = `
            <div class="vortex-forum-actions">
                <a href="${vortexForum.current_url}" class="vortex-forum-action-btn secondary">
                    <i class="dashicons dashicons-arrow-left-alt"></i> Back to Forum
                </a>
                ${post.is_author ? `
                    <button class="vortex-forum-action-btn ${post.status === 'open' ? 'danger' : 'primary'} vortex-update-status-btn" 
                        data-post-id="${post.id}" 
                        data-status="${post.status === 'open' ? 'closed' : 'open'}">
                        ${post.status === 'open' ? 'Close Post' : 'Reopen Post'}
                    </button>
                ` : ''}
            </div>
            
            <div class="vortex-forum-post-details">
                <div class="vortex-forum-post-header">
                    <div>
                        <h2>${post.title}</h2>
                        <div class="vortex-forum-post-meta">
                            <span class="vortex-forum-post-type ${post.post_type}">${capitalizeFirstLetter(post.post_type)}</span>
                            <span class="vortex-forum-post-status ${post.status}">${capitalizeFirstLetter(post.status)}</span>
                        </div>
                    </div>
                </div>
                
                <div class="vortex-forum-post-author">
                    <img src="${getAvatarUrl(post.author_id)}" alt="${post.author_name}" class="vortex-forum-post-author-avatar">
                    <div class="vortex-forum-post-author-info">
                        <h4 class="vortex-forum-post-author-name">${post.author_name}</h4>
                        <span class="vortex-forum-post-date">${post.created_at}</span>
                    </div>
                </div>
                
                <div class="vortex-forum-post-description">
                    ${post.description}
                </div>
                
                <div class="vortex-forum-post-info-grid">
                    ${post.budget ? `
                        <div class="vortex-forum-post-info-item">
                            <span class="vortex-forum-post-info-label">Budget</span>
                            <span class="vortex-forum-post-info-value">$${post.budget}</span>
                        </div>
                    ` : ''}
                    
                    ${post.deadline ? `
                        <div class="vortex-forum-post-info-item">
                            <span class="vortex-forum-post-info-label">Deadline</span>
                            <span class="vortex-forum-post-info-value">${post.deadline}</span>
                        </div>
                    ` : ''}
                    
                    ${post.skills_required ? `
                        <div class="vortex-forum-post-info-item">
                            <span class="vortex-forum-post-info-label">Skills Required</span>
                            <span class="vortex-forum-post-info-value">${post.skills_required}</span>
                        </div>
                    ` : ''}
                    
                    <div class="vortex-forum-post-info-item">
                        <span class="vortex-forum-post-info-label">Views</span>
                        <span class="vortex-forum-post-info-value">${post.views}</span>
                    </div>
                    
                    <div class="vortex-forum-post-info-item">
                        <span class="vortex-forum-post-info-label">Responses</span>
                        <span class="vortex-forum-post-info-value">${post.response_count}</span>
                    </div>
                </div>
                
                ${post.attachments && post.attachments.length > 0 ? `
                    <div class="vortex-forum-post-attachments">
                        <h4>Attachments</h4>
                        ${post.attachments.map(attachment => `
                            <div class="vortex-forum-post-attachment">
                                <span class="vortex-forum-post-attachment-icon"><i class="dashicons dashicons-media-default"></i></span>
                                <span class="vortex-forum-post-attachment-name">${getFilenameFromUrl(attachment)}</span>
                                <a href="${attachment}" target="_blank" class="vortex-forum-post-attachment-link">View</a>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
            
            <div class="vortex-forum-responses">
                <h3 class="vortex-forum-responses-title">Responses (${post.response_count})</h3>
                
                <div class="vortex-forum-responses-list">
                    ${renderResponses(post.responses)}
                </div>
                
                ${post.status === 'open' && vortexForum.is_logged_in ? `
                    <div class="vortex-submit-response-form">
                        <h4 class="vortex-submit-response-title">Submit Your Response</h4>
                        
                        <form id="vortex-submit-response-form">
                            <input type="hidden" name="post_id" value="${post.id}">
                            
                            <div class="vortex-form-group">
                                <label for="message" class="vortex-form-label">Your Message <span class="vortex-form-required">*</span></label>
                                <textarea id="message" name="message" class="vortex-form-textarea" required></textarea>
                            </div>
                            
                            <div class="vortex-form-group">
                                <label for="attachments" class="vortex-form-label">Attachments</label>
                                <input type="file" id="attachments" name="attachments[]" multiple>
                                <div class="vortex-form-help">You can upload multiple files (max 5MB each).</div>
                            </div>
                            
                            <div class="vortex-form-submit">
                                <button type="submit" class="vortex-form-submit-btn" id="vortex-submit-response-btn">Submit Response</button>
                            </div>
                        </form>
                    </div>
                ` : post.status === 'closed' ? `
                    <div class="vortex-message info">
                        This post is closed to new responses.
                    </div>
                ` : `
                    <div class="vortex-forum-login-required">
                        <p>You must be logged in to submit a response.</p>
                        <a href="${vortexForum.login_url}" class="vortex-button">Login</a>
                    </div>
                `}
            </div>
        `;
        
        $container.html(html);
    }

    /**
     * Show create post form
     */
    function showCreatePostForm(type) {
        // Hide the forum container and show the create post form
        $('.vortex-forum-container').hide();
        $('.vortex-create-post-container').show();
        
        // If type is specified, set the select value
        if (type && ['project', 'offer', 'event'].includes(type)) {
            $('#post_type').val(type).trigger('change');
        }
    }

    // Helper functions
    
    /**
     * Show loading indicator
     */
    function showLoader($container) {
        $container.append('<div class="vortex-loader"><span class="vortex-loader-spinner"></span> ' + vortexForum.i18n.loading + '</div>');
    }
    
    /**
     * Hide loading indicator
     */
    function hideLoader($container) {
        $container.find('.vortex-loader').remove();
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type) {
        // Remove any existing toast
        $('.vortex-toast').remove();
        
        // Create new toast
        const $toast = $('<div class="vortex-toast ' + type + '">' + message + '</div>');
        $('body').append($toast);
        
        // Show the toast
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        // Hide the toast after 3 seconds
        setTimeout(function() {
            $toast.removeClass('show');
            
            // Remove the toast after the transition
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
    /**
     * Toggle form fields based on post type
     */
    function toggleFieldsByPostType(postType) {
        // Hide all optional fields first
        $('.field-budget, .field-deadline, .field-skills').hide();
        
        // Show fields based on post type
        switch (postType) {
            case 'project':
                $('.field-budget, .field-deadline, .field-skills').show();
                break;
                
            case 'offer':
                $('.field-budget').show();
                break;
                
            case 'event':
                $('.field-deadline').show();
                break;
        }
    }
    
    /**
     * Render pagination
     */
    function renderPagination() {
        const $pagination = $('.vortex-forum-pagination');
        $pagination.empty();
        
        const totalPages = Math.ceil(totalPosts / postsPerPage);
        
        if (totalPages <= 1) {
            return;
        }
        
        // Previous button
        $pagination.append(`
            <button class="vortex-pagination-button prev" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="dashicons dashicons-arrow-left-alt2"></i> Prev
            </button>
        `);
        
        // Page numbers
        const maxPages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
        let endPage = Math.min(totalPages, startPage + maxPages - 1);
        
        if (endPage - startPage + 1 < maxPages) {
            startPage = Math.max(1, endPage - maxPages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            $pagination.append(`
                <button class="vortex-pagination-button page ${i === currentPage ? 'active' : ''}" data-page="${i}">
                    ${i}
                </button>
            `);
        }
        
        // Next button
        $pagination.append(`
            <button class="vortex-pagination-button next" ${currentPage === totalPages ? 'disabled' : ''}>
                Next <i class="dashicons dashicons-arrow-right-alt2"></i>
            </button>
        `);
        
        // Pagination event handlers
        $('.vortex-pagination-button.prev').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadPosts();
            }
        });
        
        $('.vortex-pagination-button.next').on('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                loadPosts();
            }
        });
        
        $('.vortex-pagination-button.page').on('click', function() {
            currentPage = parseInt($(this).data('page'));
            loadPosts();
        });
    }
    
    /**
     * Add new response to the list
     */
    function addNewResponse(response) {
        const $responsesList = $('.vortex-forum-responses-list');
        
        const responseHtml = `
            <div class="vortex-forum-response">
                <div class="vortex-forum-response-header">
                    <div class="vortex-forum-response-author">
                        <img src="${getAvatarUrl(response.author_id)}" alt="${response.author_name}" class="vortex-forum-response-author-avatar">
                        <div class="vortex-forum-response-author-info">
                            <h4 class="vortex-forum-response-author-name">${response.author_name}</h4>
                            <span class="vortex-forum-response-date">${response.created_at}</span>
                        </div>
                    </div>
                </div>
                
                <div class="vortex-forum-response-content">
                    ${response.message}
                </div>
                
                ${response.attachments && response.attachments.length > 0 ? `
                    <div class="vortex-forum-post-attachments">
                        <h4>Attachments</h4>
                        ${response.attachments.map(attachment => `
                            <div class="vortex-forum-post-attachment">
                                <span class="vortex-forum-post-attachment-icon"><i class="dashicons dashicons-media-default"></i></span>
                                <span class="vortex-forum-post-attachment-name">${getFilenameFromUrl(attachment)}</span>
                                <a href="${attachment}" target="_blank" class="vortex-forum-post-attachment-link">View</a>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;
        
        $responsesList.append(responseHtml);
        
        // Update response count
        const $responseCount = $('.vortex-forum-responses-title');
        const count = parseInt($responseCount.text().match(/\d+/)[0]) + 1;
        $responseCount.text(`Responses (${count})`);
    }
    
    /**
     * Render responses
     */
    function renderResponses(responses) {
        if (!responses || responses.length === 0) {
            return '<div class="vortex-no-results">No responses yet. Be the first to respond!</div>';
        }
        
        let html = '';
        
        for (let i = 0; i < responses.length; i++) {
            const response = responses[i];
            
            html += `
                <div class="vortex-forum-response ${response.is_selected ? 'selected' : ''}">
                    <div class="vortex-forum-response-header">
                        <div class="vortex-forum-response-author">
                            <img src="${getAvatarUrl(response.author_id)}" alt="${response.author_name}" class="vortex-forum-response-author-avatar">
                            <div class="vortex-forum-response-author-info">
                                <h4 class="vortex-forum-response-author-name">${response.author_name}</h4>
                                <span class="vortex-forum-response-date">${response.created_at}</span>
                            </div>
                        </div>
                        
                        ${response.is_selected ? `
                            <div class="vortex-forum-selected-badge">
                                Selected Response
                            </div>
                        ` : response.can_select ? `
                            <button class="vortex-forum-action-btn secondary vortex-select-response-btn" data-response-id="${response.id}">
                                Select Response
                            </button>
                        ` : ''}
                    </div>
                    
                    <div class="vortex-forum-response-content">
                        ${response.message}
                    </div>
                    
                    ${response.attachments && response.attachments.length > 0 ? `
                        <div class="vortex-forum-post-attachments">
                            <h4>Attachments</h4>
                            ${response.attachments.map(attachment => `
                                <div class="vortex-forum-post-attachment">
                                    <span class="vortex-forum-post-attachment-icon"><i class="dashicons dashicons-media-default"></i></span>
                                    <span class="vortex-forum-post-attachment-name">${getFilenameFromUrl(attachment)}</span>
                                    <a href="${attachment}" target="_blank" class="vortex-forum-post-attachment-link">View</a>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
        }
        
        return html;
    }
    
    /**
     * Get avatar URL for a user
     */
    function getAvatarUrl(userId) {
        return `https://www.gravatar.com/avatar/${userId}?s=50&d=mp`;
    }
    
    /**
     * Get filename from URL
     */
    function getFilenameFromUrl(url) {
        const parts = url.split('/');
        return parts[parts.length - 1];
    }
    
    /**
     * Capitalize first letter of a string
     */
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

})(jQuery); 