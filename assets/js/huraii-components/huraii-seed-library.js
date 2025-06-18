/**
 * HURAII Seed Library Component
 * 
 * Manages Marianne Nems' curated Seed-Art collection
 * Provides analysis, categorization, and learning features
 */

(function(global, $) {
    'use strict';
    
    // Seed Library Module
    const SeedLibrary = {
        /**
         * Module name
         */
        name: 'seedLibrary',
        
        /**
         * Seed Library configuration
         */
        config: {
            categories: [
                { id: 'all', name: 'All Seeds', icon: 'seedling' },
                { id: 'sacred-geometry', name: 'Sacred Geometry', icon: 'shapes' },
                { id: 'color-harmony', name: 'Color Harmony', icon: 'palette' },
                { id: 'light-shadow', name: 'Light & Shadow', icon: 'adjust' },
                { id: 'texture', name: 'Texture', icon: 'th' },
                { id: 'perspective', name: 'Perspective', icon: 'cube' },
                { id: 'movement', name: 'Movement & Layering', icon: 'layer-group' }
            ],
            seedPrinciples: [
                'Sacred Geometry',
                'Color Weight', 
                'Light & Shadow',
                'Texture',
                'Perspective',
                'Movement & Layering'
            ],
            viewModes: ['grid', 'masonry', 'detailed'],
            itemsPerPage: 12
        },
        
        /**
         * Module state
         */
        state: {
            seeds: [],
            filteredSeeds: [],
            selectedCategory: 'all',
            viewMode: 'grid',
            searchQuery: '',
            selectedSeeds: [],
            loading: false,
            currentPage: 1,
            totalPages: 0,
            detailModal: null
        },
        
        /**
         * Initialize Seed Library module
         * @param {Object} core HURAII core instance
         */
        init: function(core) {
            this.core = core;
            
            // Load seed library
            this.loadSeedLibrary();
            
            // Register event handlers
            this._registerEventHandlers();
            
            // Register with core
            core.registerComponent(this.name, this);
            
            return this;
        },
        
        /**
         * Load seed library data
         */
        async loadSeedLibrary() {
            this.state.loading = true;
            this._updateLoadingState();
            
            try {
                const response = await this.core.apiCall('huraii_get_seed_library', {
                    category: this.state.selectedCategory,
                    search: this.state.searchQuery,
                    page: this.state.currentPage,
                    per_page: this.config.itemsPerPage
                });
                
                if (response.success) {
                    this.state.seeds = response.data.seeds;
                    this.state.totalPages = response.data.total_pages || 1;
                    this._filterSeeds();
                    this._updateDisplay();
                }
                
            } catch (error) {
                console.error('Failed to load seed library:', error);
                this._showError('Failed to load seed library. Please try again.');
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
            // Category selection
            $(document).on('click', '.category-btn', (e) => {
                const category = $(e.currentTarget).data('category');
                this.selectCategory(category);
            });
            
            // Search
            $(document).on('input', '#seed-search', this._debounce((e) => {
                this.setSearchQuery($(e.target).val());
            }, 300));
            
            // View mode toggle
            $(document).on('click', '.seed-view-btn', (e) => {
                const viewMode = $(e.currentTarget).data('view');
                this.setViewMode(viewMode);
            });
            
            // Seed actions
            $(document).on('click', '.seed-action-btn', (e) => {
                e.stopPropagation();
                const action = $(e.currentTarget).data('action');
                const seedId = $(e.currentTarget).closest('.seed-item').data('seed-id');
                this._handleSeedAction(action, seedId);
            });
            
            // Seed item click for detail view
            $(document).on('click', '.seed-item', (e) => {
                if (!$(e.target).hasClass('seed-action-btn')) {
                    const seedId = $(e.currentTarget).data('seed-id');
                    this.showSeedDetail(seedId);
                }
            });
            
            // Principle filter
            $(document).on('click', '.principle-filter', (e) => {
                const principle = $(e.currentTarget).data('principle');
                this.filterByPrinciple(principle);
            });
            
            // Modal close
            $(document).on('click', '.seed-modal-close, .seed-modal-overlay', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeSeedDetail();
                }
            });
            
            // Pagination
            $(document).on('click', '.seed-pagination-btn', (e) => {
                const page = $(e.currentTarget).data('page');
                this.goToPage(page);
            });
        },
        
        /**
         * Filter seeds based on current criteria
         * @private
         */
        _filterSeeds: function() {
            let filtered = [...this.state.seeds];
            
            // Filter by category
            if (this.state.selectedCategory && this.state.selectedCategory !== 'all') {
                filtered = filtered.filter(seed => 
                    seed.category === this.state.selectedCategory ||
                    (seed.categories && seed.categories.includes(this.state.selectedCategory))
                );
            }
            
            // Filter by search query
            if (this.state.searchQuery) {
                const query = this.state.searchQuery.toLowerCase();
                filtered = filtered.filter(seed =>
                    seed.title.toLowerCase().includes(query) ||
                    seed.description.toLowerCase().includes(query) ||
                    (seed.tags && seed.tags.some(tag => tag.toLowerCase().includes(query))) ||
                    (seed.principles && seed.principles.some(principle => principle.toLowerCase().includes(query)))
                );
            }
            
            this.state.filteredSeeds = filtered;
        },
        
        /**
         * Update display based on current state
         * @private
         */
        _updateDisplay: function() {
            const $container = $('#seed-grid');
            $container.empty();
            
            if (this.state.filteredSeeds.length === 0) {
                this._showEmptyState($container);
                return;
            }
            
            // Update container class for view mode
            $container.removeClass('grid-view masonry-view detailed-view')
                      .addClass(`${this.state.viewMode}-view`);
            
            // Render seeds
            this.state.filteredSeeds.forEach(seed => {
                const $item = this._createSeedItem(seed);
                $container.append($item);
            });
            
            // Initialize masonry if needed
            if (this.state.viewMode === 'masonry') {
                this._initMasonry($container);
            }
            
            // Update category buttons
            this._updateCategoryButtons();
            
            // Update pagination
            this._updatePagination();
        },
        
        /**
         * Create seed item element
         * @param {Object} seed Seed data
         * @returns {jQuery} Seed item element
         * @private
         */
        _createSeedItem: function(seed) {
            const principlesTags = seed.principles ? 
                seed.principles.map(p => `<span class="seed-principle">${p}</span>`).join('') : '';
            
            const $item = $(`
                <div class="seed-item" data-seed-id="${seed.id}">
                    <div class="seed-item-image">
                        <img src="${seed.image}" alt="${seed.title}" loading="lazy">
                        <div class="seed-overlay">
                            <div class="seed-principles">
                                ${principlesTags}
                            </div>
                        </div>
                        <div class="seed-rating">
                            <div class="seed-difficulty" title="Complexity Level">
                                ${this._renderDifficultyStars(seed.difficulty || 3)}
                            </div>
                        </div>
                    </div>
                    
                    <div class="seed-item-content">
                        <div class="seed-item-header">
                            <h3 class="seed-item-title">${seed.title}</h3>
                            <div class="seed-item-meta">
                                <span class="seed-category">${this._getCategoryName(seed.category)}</span>
                            </div>
                        </div>
                        
                        <p class="seed-item-description">${seed.description}</p>
                        
                        ${seed.technique ? `
                            <div class="seed-technique">
                                <strong>Technique:</strong> ${seed.technique}
                            </div>
                        ` : ''}
                        
                        <div class="seed-stats">
                            <div class="seed-stat">
                                <i class="fas fa-eye"></i>
                                <span>${seed.views || 0}</span>
                            </div>
                            <div class="seed-stat">
                                <i class="fas fa-heart"></i>
                                <span>${seed.likes || 0}</span>
                            </div>
                            <div class="seed-stat">
                                <i class="fas fa-magic"></i>
                                <span>${seed.uses || 0}</span>
                            </div>
                        </div>
                        
                        <div class="seed-actions">
                            <button class="seed-action-btn primary" data-action="analyze" title="Analyze Seed-Art Principles">
                                <i class="fas fa-search"></i>
                                <span>Analyze</span>
                            </button>
                            <button class="seed-action-btn" data-action="use" title="Use as Generation Seed">
                                <i class="fas fa-magic"></i>
                                <span>Use Seed</span>
                            </button>
                            <button class="seed-action-btn" data-action="learn" title="Learn Technique">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Learn</span>
                            </button>
                            <button class="seed-action-btn" data-action="favorite" title="Add to Favorites">
                                <i class="fas fa-heart${seed.is_favorite ? '' : '-o'}"></i>
                            </button>
                        </div>
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
            const message = this.state.searchQuery ? 
                'No seeds found matching your search criteria.' : 
                'No seeds available in this category.';
            
            const $emptyState = $(`
                <div class="seed-empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <h3>No Seeds Found</h3>
                    <p>${message}</p>
                    <button class="empty-action-btn" onclick="HURAII.getComponent('seedLibrary').selectCategory('all')">
                        <i class="fas fa-refresh"></i> View All Seeds
                    </button>
                </div>
            `);
            
            $container.append($emptyState);
        },
        
        /**
         * Update category buttons
         * @private
         */
        _updateCategoryButtons: function() {
            $('.category-btn').removeClass('active');
            $(`.category-btn[data-category="${this.state.selectedCategory}"]`).addClass('active');
        },
        
        /**
         * Select category
         * @param {string} category Category ID
         */
        selectCategory: function(category) {
            this.state.selectedCategory = category;
            this.state.currentPage = 1;
            this.loadSeedLibrary();
        },
        
        /**
         * Set search query
         * @param {string} query Search query
         */
        setSearchQuery: function(query) {
            this.state.searchQuery = query;
            this.state.currentPage = 1;
            this._filterSeeds();
            this._updateDisplay();
        },
        
        /**
         * Set view mode
         * @param {string} mode View mode
         */
        setViewMode: function(mode) {
            this.state.viewMode = mode;
            $('.seed-view-btn').removeClass('active');
            $(`.seed-view-btn[data-view="${mode}"]`).addClass('active');
            this._updateDisplay();
        },
        
        /**
         * Filter by principle
         * @param {string} principle Seed-Art principle
         */
        filterByPrinciple: function(principle) {
            // Update search to include principle
            this.setSearchQuery(principle);
        },
        
        /**
         * Go to specific page
         * @param {number} page Page number
         */
        goToPage: function(page) {
            this.state.currentPage = page;
            this.loadSeedLibrary();
        },
        
        /**
         * Show seed detail modal
         * @param {number} seedId Seed ID
         */
        showSeedDetail: function(seedId) {
            const seed = this.state.seeds.find(s => s.id === seedId);
            if (!seed) return;
            
            const $modal = this._createSeedDetailModal(seed);
            $('body').append($modal);
            
            // Animate in
            requestAnimationFrame(() => {
                $modal.addClass('active');
            });
            
            this.state.detailModal = $modal;
        },
        
        /**
         * Close seed detail modal
         */
        closeSeedDetail: function() {
            if (this.state.detailModal) {
                this.state.detailModal.removeClass('active');
                setTimeout(() => {
                    this.state.detailModal.remove();
                    this.state.detailModal = null;
                }, 300);
            }
        },
        
        /**
         * Handle seed action
         * @param {string} action Action type
         * @param {number} seedId Seed ID
         * @private
         */
        _handleSeedAction: function(action, seedId) {
            const seed = this.state.seeds.find(s => s.id === seedId);
            if (!seed) return;
            
            switch (action) {
                case 'analyze':
                    this._analyzeSeed(seed);
                    break;
                case 'use':
                    this._useAsSeed(seed);
                    break;
                case 'learn':
                    this._learnTechnique(seed);
                    break;
                case 'favorite':
                    this._toggleFavorite(seed);
                    break;
            }
        },
        
        /**
         * Analyze seed with visual descriptor
         * @param {Object} seed Seed data
         * @private
         */
        _analyzeSeed: function(seed) {
            const visualDescriptor = this.core.getComponent('visualDescriptor');
            if (visualDescriptor) {
                // Pre-populate with seed image
                visualDescriptor.analyzeSeedArt(seed);
            } else {
                this._showError('Visual descriptor component not available');
            }
        },
        
        /**
         * Use seed for generation
         * @param {Object} seed Seed data
         * @private
         */
        _useAsSeed: function(seed) {
            // Switch to studio tab and populate with seed
            this.core.switchTab('studio');
            
            // Get midjourney UI component
            const midjourneyUI = this.core.getComponent('midjourneyUI');
            if (midjourneyUI) {
                midjourneyUI.loadSeedArt(seed);
            }
        },
        
        /**
         * Learn technique
         * @param {Object} seed Seed data
         * @private
         */
        _learnTechnique: function(seed) {
            // Switch to learning tab with focus on this seed's techniques
            this.core.switchTab('learning');
            
            // Trigger learning content for this seed
            this.core.eventBus.emit('learning:focus', {
                seedId: seed.id,
                techniques: seed.principles
            });
        },
        
        /**
         * Toggle favorite status
         * @param {Object} seed Seed data
         * @private
         */
        async _toggleFavorite: function(seed) {
            try {
                const response = await this.core.apiCall('huraii_toggle_seed_favorite', {
                    seed_id: seed.id,
                    is_favorite: !seed.is_favorite
                });
                
                if (response.success) {
                    seed.is_favorite = !seed.is_favorite;
                    this._updateDisplay();
                }
                
            } catch (error) {
                console.error('Failed to toggle favorite:', error);
            }
        },
        
        /**
         * Utility methods
         */
        _getCategoryName: function(categoryId) {
            const category = this.config.categories.find(c => c.id === categoryId);
            return category ? category.name : 'Unknown';
        },
        
        _renderDifficultyStars: function(difficulty) {
            const stars = [];
            for (let i = 1; i <= 5; i++) {
                stars.push(`<i class="fas fa-star${i <= difficulty ? '' : '-o'}"></i>`);
            }
            return stars.join('');
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
            const $library = $('#seed-grid');
            if (this.state.loading) {
                $library.addClass('loading');
            } else {
                $library.removeClass('loading');
            }
        },
        
        _showError: function(message) {
            console.error(message);
            // Implementation for showing error messages
        },
        
        _initMasonry: function($container) {
            // Placeholder for masonry layout initialization
            // Would use a library like Masonry.js in production
        },
        
        _updatePagination: function() {
            // Similar to gallery pagination
            const $pagination = $('.seed-pagination');
            
            if (this.state.totalPages <= 1) {
                $pagination.hide();
                return;
            }
            
            $pagination.show().empty();
            
            // Previous button
            if (this.state.currentPage > 1) {
                $pagination.append(`
                    <button class="seed-pagination-btn" data-page="${this.state.currentPage - 1}">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                `);
            }
            
            // Page numbers
            const startPage = Math.max(1, this.state.currentPage - 2);
            const endPage = Math.min(this.state.totalPages, this.state.currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                $pagination.append(`
                    <button class="seed-pagination-btn ${i === this.state.currentPage ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `);
            }
            
            // Next button
            if (this.state.currentPage < this.state.totalPages) {
                $pagination.append(`
                    <button class="seed-pagination-btn" data-page="${this.state.currentPage + 1}">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                `);
            }
        },
        
        /**
         * Create seed detail modal
         * @param {Object} seed Seed data
         * @returns {jQuery} Modal element
         * @private
         */
        _createSeedDetailModal: function(seed) {
            const principlesTags = seed.principles ? 
                seed.principles.map(p => `<span class="principle-tag">${p}</span>`).join('') : '';
            
            const $modal = $(`
                <div class="seed-modal-overlay">
                    <div class="seed-modal">
                        <div class="seed-modal-header">
                            <h2>${seed.title}</h2>
                            <button class="seed-modal-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="seed-modal-content">
                            <div class="seed-modal-image">
                                <img src="${seed.image}" alt="${seed.title}">
                            </div>
                            
                            <div class="seed-modal-info">
                                <div class="seed-modal-description">
                                    <p>${seed.description}</p>
                                </div>
                                
                                <div class="seed-modal-principles">
                                    <h4>Seed-Art Principles</h4>
                                    <div class="principles-list">
                                        ${principlesTags}
                                    </div>
                                </div>
                                
                                ${seed.technique ? `
                                    <div class="seed-modal-technique">
                                        <h4>Technique</h4>
                                        <p>${seed.technique}</p>
                                    </div>
                                ` : ''}
                                
                                <div class="seed-modal-actions">
                                    <button class="seed-modal-action-btn primary" data-action="analyze">
                                        <i class="fas fa-search"></i> Deep Analysis
                                    </button>
                                    <button class="seed-modal-action-btn" data-action="use">
                                        <i class="fas fa-magic"></i> Use as Seed
                                    </button>
                                    <button class="seed-modal-action-btn" data-action="learn">
                                        <i class="fas fa-graduation-cap"></i> Learn Technique
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            return $modal;
        }
    };
    
    // Register with HURAII when loaded
    if (global.HURAII) {
        global.HURAII.registerComponent('seedLibrary', SeedLibrary);
    } else {
        // Wait for HURAII to be defined
        document.addEventListener('DOMContentLoaded', () => {
            if (global.HURAII) {
                global.HURAII.registerComponent('seedLibrary', SeedLibrary);
            }
        });
    }
    
})(window, jQuery); 