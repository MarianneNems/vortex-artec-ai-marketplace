/**
 * HURAII Gallery Component
 * 
 * Manages user artwork collections with filtering, sorting, and organization features
 */

(function(global, $) {
    'use strict';
    
    // Gallery Module
    const Gallery = {
        /**
         * Module name
         */
        name: 'gallery',
        
        /**
         * Gallery configuration
         */
        config: {
            itemsPerPage: 20,
            viewModes: ['grid', 'list', 'masonry'],
            defaultView: 'grid',
            sortOptions: [
                { value: 'created_desc', label: 'Newest First' },
                { value: 'created_asc', label: 'Oldest First' },
                { value: 'name_asc', label: 'Name A-Z' },
                { value: 'name_desc', label: 'Name Z-A' },
                { value: 'likes_desc', label: 'Most Liked' },
                { value: 'views_desc', label: 'Most Viewed' }
            ],
            filterOptions: [
                { value: 'all', label: 'All Artworks' },
                { value: 'recent', label: 'Recent (7 days)' },
                { value: 'favorites', label: 'Favorites' },
                { value: 'seed-art', label: 'Seed Art' },
                { value: 'generated', label: 'AI Generated' },
                { value: 'uploaded', label: 'Uploaded' }
            ]
        },
        
        /**
         * Module state
         */
        state: {
            artworks: [],
            filteredArtworks: [],
            currentPage: 1,
            totalPages: 0,
            viewMode: 'grid',
            sortBy: 'created_desc',
            filterBy: 'all',
            searchQuery: '',
            selectedArtworks: [],
            loading: false,
            stats: {
                total: 0,
                favorites: 0,
                views: 0,
                likes: 0
            }
        },
        
        /**
         * Initialize Gallery module
         * @param {Object} core HURAII core instance
         */
        init: function(core) {
            this.core = core;
            
            // Load gallery data
            this.loadGallery();
            
            // Register event handlers
            this._registerEventHandlers();
            
            // Register with core
            core.registerComponent(this.name, this);
            
            return this;
        },
        
        /**
         * Load gallery data from server
         */
        async loadGallery() {
            this.state.loading = true;
            this._updateLoadingState();
            
            try {
                const response = await this.core.apiCall('huraii_get_gallery', {
                    page: this.state.currentPage,
                    per_page: this.config.itemsPerPage,
                    sort: this.state.sortBy,
                    filter: this.state.filterBy,
                    search: this.state.searchQuery
                });
                
                if (response.success) {
                    this.state.artworks = response.data.artworks;
                    this.state.stats = response.data.stats;
                    this.state.totalPages = response.data.total_pages;
                    
                    this._updateGalleryDisplay();
                    this._updateStats();
                }
                
            } catch (error) {
                console.error('Failed to load gallery:', error);
                this._showError('Failed to load gallery. Please try again.');
            } finally {
                this.state.loading = false;
                this._updateLoadingState();
            }
        },
        
        /**
         * Register event handlers
         * @private
         */
        _registerEventHandlers: function() {
            // View mode toggle
            $(document).on('click', '.view-btn', (e) => {
                const viewMode = $(e.target).data('view');
                this.setViewMode(viewMode);
            });
            
            // Filter change
            $(document).on('change', '#gallery-filter', (e) => {
                this.setFilter($(e.target).val());
            });
            
            // Sort change
            $(document).on('change', '#gallery-sort', (e) => {
                this.setSortBy($(e.target).val());
            });
            
            // Search
            $(document).on('input', '#gallery-search', this._debounce((e) => {
                this.setSearchQuery($(e.target).val());
            }, 300));
            
            // Artwork selection
            $(document).on('click', '.artwork-item', (e) => {
                if (!$(e.target).hasClass('artwork-action')) {
                    this._selectArtwork($(e.currentTarget).data('artwork-id'));
                }
            });
            
            // Artwork actions
            $(document).on('click', '.artwork-action', (e) => {
                e.stopPropagation();
                const action = $(e.currentTarget).data('action');
                const artworkId = $(e.currentTarget).closest('.artwork-item').data('artwork-id');
                this._handleArtworkAction(action, artworkId);
            });
            
            // Pagination
            $(document).on('click', '.pagination-btn', (e) => {
                const page = $(e.currentTarget).data('page');
                this.goToPage(page);
            });
            
            // Bulk actions
            $(document).on('click', '#select-all', (e) => {
                this._toggleSelectAll($(e.target).is(':checked'));
            });
            
            $(document).on('click', '.bulk-action-btn', (e) => {
                const action = $(e.currentTarget).data('action');
                this._handleBulkAction(action);
            });
        },
        
        /**
         * Update gallery display
         * @private
         */
        _updateGalleryDisplay: function() {
            const $container = $('#gallery-grid');
            $container.empty();
            
            if (this.state.artworks.length === 0) {
                this._showEmptyState($container);
                return;
            }
            
            // Update container class for view mode
            $container.removeClass('grid-view list-view masonry-view')
                      .addClass(`${this.state.viewMode}-view`);
            
            // Render artworks
            this.state.artworks.forEach(artwork => {
                const $item = this._createArtworkItem(artwork);
                $container.append($item);
            });
            
            // Initialize masonry if needed
            if (this.state.viewMode === 'masonry') {
                this._initMasonry($container);
            }
            
            // Update pagination
            this._updatePagination();
        },
        
        /**
         * Create artwork item element
         * @param {Object} artwork Artwork data
         * @returns {jQuery} Artwork item element
         * @private
         */
        _createArtworkItem: function(artwork) {
            const $item = $(`
                <div class="artwork-item" data-artwork-id="${artwork.id}">
                    <div class="artwork-image">
                        <img src="${artwork.thumbnail}" alt="${artwork.title}" loading="lazy">
                        <div class="artwork-overlay">
                            <div class="artwork-actions">
                                <button class="artwork-action" data-action="view" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="artwork-action" data-action="edit" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="artwork-action" data-action="download" title="Download">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="artwork-action" data-action="share" title="Share">
                                    <i class="fas fa-share"></i>
                                </button>
                                <button class="artwork-action" data-action="favorite" title="${artwork.is_favorite ? 'Remove from Favorites' : 'Add to Favorites'}">
                                    <i class="fas fa-heart${artwork.is_favorite ? '' : '-o'}"></i>
                                </button>
                            </div>
                        </div>
                        <div class="artwork-checkbox">
                            <input type="checkbox" class="artwork-select" value="${artwork.id}">
                        </div>
                    </div>
                    
                    <div class="artwork-info">
                        <h3 class="artwork-title">${artwork.title}</h3>
                        <div class="artwork-meta">
                            <span class="artwork-date">${this._formatDate(artwork.created)}</span>
                            <div class="artwork-stats">
                                <span class="stat-item">
                                    <i class="fas fa-eye"></i> ${artwork.views || 0}
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-heart"></i> ${artwork.likes || 0}
                                </span>
                            </div>
                        </div>
                        
                        ${artwork.tags && artwork.tags.length > 0 ? `
                            <div class="artwork-tags">
                                ${artwork.tags.map(tag => `<span class="artwork-tag">${tag}</span>`).join('')}
                            </div>
                        ` : ''}
                        
                        ${artwork.seed_art_analysis ? `
                            <div class="seed-art-indicator">
                                <i class="fas fa-seedling"></i>
                                <span>Seed Art Analysis Available</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `);
            
            return $item;
        },
        
        /**
         * Show empty state
         * @param {jQuery} $container Container element
         * @private
         */
        _showEmptyState: function($container) {
            const emptyMessage = this.state.searchQuery ? 
                'No artworks found matching your search.' : 
                'You haven\'t created any artworks yet.';
            
            const $emptyState = $(`
                <div class="gallery-empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <h3>No Artworks Found</h3>
                    <p>${emptyMessage}</p>
                    ${!this.state.searchQuery ? `
                        <button class="create-artwork-btn" onclick="HURAII.switchTab('studio')">
                            <i class="fas fa-plus"></i> Create Your First Artwork
                        </button>
                    ` : ''}
                </div>
            `);
            
            $container.append($emptyState);
        },
        
        /**
         * Update stats display
         * @private
         */
        _updateStats: function() {
            $('#total-artworks').text(this.state.stats.total || 0);
            $('#total-likes').text(this.state.stats.likes || 0);
            $('#total-views').text(this._formatNumber(this.state.stats.views || 0));
        },
        
        /**
         * Update pagination
         * @private
         */
        _updatePagination: function() {
            const $pagination = $('.gallery-pagination');
            
            if (this.state.totalPages <= 1) {
                $pagination.hide();
                return;
            }
            
            $pagination.show().empty();
            
            // Previous button
            if (this.state.currentPage > 1) {
                $pagination.append(`
                    <button class="pagination-btn" data-page="${this.state.currentPage - 1}">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                `);
            }
            
            // Page numbers
            const startPage = Math.max(1, this.state.currentPage - 2);
            const endPage = Math.min(this.state.totalPages, this.state.currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                $pagination.append(`
                    <button class="pagination-btn ${i === this.state.currentPage ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `);
            }
            
            // Next button
            if (this.state.currentPage < this.state.totalPages) {
                $pagination.append(`
                    <button class="pagination-btn" data-page="${this.state.currentPage + 1}">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                `);
            }
        },
        
        /**
         * Set view mode
         * @param {string} mode View mode (grid, list, masonry)
         */
        setViewMode: function(mode) {
            this.state.viewMode = mode;
            $('.view-btn').removeClass('active');
            $(`.view-btn[data-view="${mode}"]`).addClass('active');
            this._updateGalleryDisplay();
        },
        
        /**
         * Set filter
         * @param {string} filter Filter option
         */
        setFilter: function(filter) {
            this.state.filterBy = filter;
            this.state.currentPage = 1;
            this.loadGallery();
        },
        
        /**
         * Set sort option
         * @param {string} sortBy Sort option
         */
        setSortBy: function(sortBy) {
            this.state.sortBy = sortBy;
            this.state.currentPage = 1;
            this.loadGallery();
        },
        
        /**
         * Set search query
         * @param {string} query Search query
         */
        setSearchQuery: function(query) {
            this.state.searchQuery = query;
            this.state.currentPage = 1;
            this.loadGallery();
        },
        
        /**
         * Go to specific page
         * @param {number} page Page number
         */
        goToPage: function(page) {
            this.state.currentPage = page;
            this.loadGallery();
        },
        
        /**
         * Handle artwork action
         * @param {string} action Action type
         * @param {number} artworkId Artwork ID
         * @private
         */
        _handleArtworkAction: function(action, artworkId) {
            switch (action) {
                case 'view':
                    this._viewArtwork(artworkId);
                    break;
                case 'edit':
                    this._editArtwork(artworkId);
                    break;
                case 'download':
                    this._downloadArtwork(artworkId);
                    break;
                case 'share':
                    this._shareArtwork(artworkId);
                    break;
                case 'favorite':
                    this._toggleFavorite(artworkId);
                    break;
                case 'delete':
                    this._deleteArtwork(artworkId);
                    break;
            }
        },
        
        /**
         * Utility methods
         */
        _formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString();
        },
        
        _formatNumber: function(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
            return num.toString();
        },
        
        _debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        _updateLoadingState: function() {
            const $gallery = $('#gallery-grid');
            if (this.state.loading) {
                $gallery.addClass('loading');
            } else {
                $gallery.removeClass('loading');
            }
        },
        
        _showError: function(message) {
            // Implementation for showing error messages
            console.error(message);
        }
    };
    
    // Register with HURAII when loaded
    if (global.HURAII) {
        global.HURAII.registerComponent('gallery', Gallery);
    } else {
        // Wait for HURAII to be defined
        document.addEventListener('DOMContentLoaded', () => {
            if (global.HURAII) {
                global.HURAII.registerComponent('gallery', Gallery);
            }
        });
    }
    
})(window, jQuery); 