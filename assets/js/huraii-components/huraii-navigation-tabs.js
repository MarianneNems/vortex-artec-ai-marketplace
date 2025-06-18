/**
 * HURAII Navigation Tabs System
 * 
 * Comprehensive tab navigation for the HURAII AI art platform
 * Inspired by Midjourney but enhanced with Seed-Art technique features
 */

(function(global, $) {
    'use strict';
    
    // Navigation Tabs Module
    const NavigationTabs = {
        /**
         * Module name
         */
        name: 'navigationTabs',
        
        /**
         * Tab configuration
         */
        config: {
            tabs: [
                {
                    id: 'studio',
                    name: 'Studio',
                    icon: 'magic',
                    description: 'Create AI artwork with Seed-Art technique',
                    component: 'midjourneyUI',
                    badge: null,
                    permissions: ['create']
                },
                {
                    id: 'gallery',
                    name: 'Gallery',
                    icon: 'images',
                    description: 'View and manage your artwork collection',
                    component: 'gallery',
                    badge: null,
                    permissions: ['view_gallery']
                },
                {
                    id: 'seed-library',
                    name: 'Seed Library',
                    icon: 'seedling',
                    description: 'Explore Marianne\'s Seed-Art collection',
                    component: 'seedLibrary',
                    badge: 'NEW',
                    permissions: ['view_seeds']
                },
                {
                    id: 'marketplace',
                    name: 'Marketplace',
                    icon: 'store',
                    description: 'Browse and purchase AI artwork',
                    component: 'marketplace',
                    badge: null,
                    permissions: ['view_marketplace']
                },
                {
                    id: 'analytics',
                    name: 'Analytics',
                    icon: 'chart-line',
                    description: 'Performance insights and statistics',
                    component: 'analytics',
                    badge: null,
                    permissions: ['view_analytics']
                },
                {
                    id: 'community',
                    name: 'Community',
                    icon: 'users',
                    description: 'Connect with other artists',
                    component: 'community',
                    badge: null,
                    permissions: ['view_community']
                },
                {
                    id: 'learning',
                    name: 'Learning',
                    icon: 'graduation-cap',
                    description: 'Learn Seed-Art technique and AI art',
                    component: 'learning',
                    badge: null,
                    permissions: ['view_learning']
                },
                {
                    id: 'profile',
                    name: 'Profile',
                    icon: 'user-circle',
                    description: 'Manage your profile and achievements',
                    component: 'profile',
                    badge: null,
                    permissions: ['view_profile']
                },
                {
                    id: 'settings',
                    name: 'Settings',
                    icon: 'cog',
                    description: 'Configure your preferences',
                    component: 'settings',
                    badge: null,
                    permissions: ['manage_settings']
                }
            ],
            defaultTab: 'studio',
            tabContainer: '#huraii-tab-container',
            contentContainer: '#huraii-content-container'
        },
        
        /**
         * Module state
         */
        state: {
            activeTab: null,
            loadedComponents: {},
            userPermissions: [],
            tabHistory: [],
            notifications: {}
        },
        
        /**
         * Initialize Navigation Tabs module
         * @param {Object} core HURAII core instance
         */
        init: function(core) {
            this.core = core;
            
            // Load user permissions
            this._loadUserPermissions();
            
            // Initialize UI
            this._initUI();
            
            // Register event handlers
            this._registerEventHandlers();
            
            // Load default tab
            this._loadTab(this.config.defaultTab);
            
            // Register with core
            core.registerComponent(this.name, this);
            
            return this;
        },
        
        /**
         * Initialize UI components
         * @private
         */
        _initUI: function() {
            // Create main navigation container if it doesn't exist
            if (!$(this.config.tabContainer).length) {
                this._createNavigationContainer();
            }
            
            // Create tab navigation
            this._createTabNavigation();
            
            // Create content areas
            this._createContentAreas();
            
            // Apply responsive behavior
            this._initResponsiveNavigation();
        },
        
        /**
         * Create main navigation container
         * @private
         */
        _createNavigationContainer: function() {
            const $container = $(`
                <div id="huraii-navigation-wrapper" class="huraii-navigation-wrapper">
                    <!-- Top Header -->
                    <header class="huraii-header">
                        <div class="huraii-header-left">
                            <div class="huraii-logo">
                                <img src="/assets/images/VORTEX_ROUND_BLACK.png" alt="VORTEX" class="logo-image">
                                <span class="logo-text">HURAII</span>
                            </div>
                        </div>
                        
                        <div class="huraii-header-center">
                            <nav id="huraii-tab-container" class="huraii-tab-navigation">
                                <!-- Tabs will be generated here -->
                            </nav>
                        </div>
                        
                        <div class="huraii-header-right">
                            <div class="huraii-header-actions">
                                <button class="huraii-action-btn" id="notifications-btn" title="Notifications">
                                    <i class="fas fa-bell"></i>
                                    <span class="notification-badge" style="display: none;">0</span>
                                </button>
                                <button class="huraii-action-btn" id="search-btn" title="Search">
                                    <i class="fas fa-search"></i>
                                </button>
                                <div class="huraii-user-menu">
                                    <button class="huraii-user-btn" id="user-menu-btn">
                                        <img src="/assets/images/marianne-nems.png" alt="User" class="user-avatar">
                                        <span class="user-name">Artist</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </header>
                    
                    <!-- Main Content Area -->
                    <main id="huraii-content-container" class="huraii-content-container">
                        <!-- Tab content will be loaded here -->
                    </main>
                    
                    <!-- Mobile Navigation -->
                    <nav class="huraii-mobile-nav" id="huraii-mobile-nav">
                        <!-- Mobile tabs will be generated here -->
                    </nav>
                </div>
            `);
            
            // Append to body or main interface
            const $interface = $('.vortex-huraii-interface');
            if ($interface.length) {
                $interface.html($container);
            } else {
                $('body').append($container);
            }
        },
        
        /**
         * Create tab navigation elements
         * @private
         */
        _createTabNavigation: function() {
            const $tabContainer = $(this.config.tabContainer);
            const $mobileNav = $('#huraii-mobile-nav');
            
            // Clear existing tabs
            $tabContainer.empty();
            $mobileNav.empty();
            
            // Generate tabs based on user permissions
            this.config.tabs.forEach(tab => {
                if (this._hasPermission(tab.permissions)) {
                    // Desktop tab
                    const $desktopTab = this._createTabElement(tab, 'desktop');
                    $tabContainer.append($desktopTab);
                    
                    // Mobile tab
                    const $mobileTab = this._createTabElement(tab, 'mobile');
                    $mobileNav.append($mobileTab);
                }
            });
        },
        
        /**
         * Create individual tab element
         * @param {Object} tab Tab configuration
         * @param {string} type 'desktop' or 'mobile'
         * @returns {jQuery} Tab element
         * @private
         */
        _createTabElement: function(tab, type) {
            const isActive = tab.id === this.config.defaultTab;
            const badgeHtml = tab.badge ? `<span class="tab-badge">${tab.badge}</span>` : '';
            const notificationCount = this.state.notifications[tab.id] || 0;
            const notificationHtml = notificationCount > 0 ? 
                `<span class="tab-notification">${notificationCount}</span>` : '';
            
            if (type === 'mobile') {
                return $(`
                    <button class="huraii-mobile-tab${isActive ? ' active' : ''}" 
                            data-tab="${tab.id}" 
                            title="${tab.description}">
                        <i class="fas fa-${tab.icon}"></i>
                        <span class="tab-label">${tab.name}</span>
                        ${badgeHtml}
                        ${notificationHtml}
                    </button>
                `);
            } else {
                return $(`
                    <button class="huraii-tab${isActive ? ' active' : ''}" 
                            data-tab="${tab.id}" 
                            title="${tab.description}">
                        <i class="fas fa-${tab.icon}"></i>
                        <span class="tab-label">${tab.name}</span>
                        ${badgeHtml}
                        ${notificationHtml}
                    </button>
                `);
            }
        },
        
        /**
         * Create content areas for each tab
         * @private
         */
        _createContentAreas: function() {
            const $contentContainer = $(this.config.contentContainer);
            
            this.config.tabs.forEach(tab => {
                if (this._hasPermission(tab.permissions)) {
                    const $contentArea = $(`
                        <div id="huraii-content-${tab.id}" 
                             class="huraii-tab-content${tab.id === this.config.defaultTab ? ' active' : ''}"
                             data-tab="${tab.id}">
                            <div class="content-loading">
                                <div class="loading-spinner"></div>
                                <p>Loading ${tab.name}...</p>
                            </div>
                        </div>
                    `);
                    
                    $contentContainer.append($contentArea);
                }
            });
        },
        
        /**
         * Initialize responsive navigation behavior
         * @private
         */
        _initResponsiveNavigation: function() {
            // Handle window resize
            $(window).on('resize', this._handleResize.bind(this));
            
            // Initial responsive check
            this._handleResize();
            
            // Mobile menu toggle
            $(document).on('click', '.huraii-mobile-menu-toggle', () => {
                $('#huraii-mobile-nav').toggleClass('open');
            });
        },
        
        /**
         * Register event handlers
         * @private
         */
        _registerEventHandlers: function() {
            // Tab click handlers
            $(document).on('click', '.huraii-tab, .huraii-mobile-tab', (e) => {
                const tabId = $(e.currentTarget).data('tab');
                this.switchTab(tabId);
            });
            
            // User menu toggle
            $(document).on('click', '#user-menu-btn', (e) => {
                e.stopPropagation();
                this._toggleUserMenu();
            });
            
            // Notifications button
            $(document).on('click', '#notifications-btn', () => {
                this._showNotifications();
            });
            
            // Search button
            $(document).on('click', '#search-btn', () => {
                this._openSearch();
            });
            
            // Close dropdowns when clicking outside
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.huraii-user-menu').length) {
                    $('.huraii-user-menu').removeClass('open');
                }
            });
            
            // Handle keyboard navigation
            $(document).on('keydown', this._handleKeyboardNavigation.bind(this));
        },
        
        /**
         * Switch to a specific tab
         * @param {string} tabId Tab identifier
         */
        switchTab: function(tabId) {
            const tab = this.config.tabs.find(t => t.id === tabId);
            
            if (!tab || !this._hasPermission(tab.permissions)) {
                console.warn(`Tab ${tabId} not found or no permission`);
                return;
            }
            
            // Update state
            if (this.state.activeTab) {
                this.state.tabHistory.push(this.state.activeTab);
            }
            this.state.activeTab = tabId;
            
            // Update UI
            this._updateActiveTab(tabId);
            
            // Load tab content
            this._loadTab(tabId);
            
            // Trigger event
            $(document).trigger('huraii:tab:switched', { tabId, tab });
        },
        
        /**
         * Update active tab visual state
         * @param {string} tabId Tab identifier
         * @private
         */
        _updateActiveTab: function(tabId) {
            // Remove active state from all tabs
            $('.huraii-tab, .huraii-mobile-tab').removeClass('active');
            $('.huraii-tab-content').removeClass('active');
            
            // Add active state to current tab
            $(`.huraii-tab[data-tab="${tabId}"], .huraii-mobile-tab[data-tab="${tabId}"]`).addClass('active');
            $(`#huraii-content-${tabId}`).addClass('active');
        },
        
        /**
         * Load tab content
         * @param {string} tabId Tab identifier
         * @private
         */
        _loadTab: function(tabId) {
            const tab = this.config.tabs.find(t => t.id === tabId);
            if (!tab) return;
            
            const $contentArea = $(`#huraii-content-${tabId}`);
            
            // If already loaded, just show it
            if (this.state.loadedComponents[tabId]) {
                $contentArea.removeClass('loading');
                return;
            }
            
            // Show loading state
            $contentArea.addClass('loading');
            
            // Load component based on tab configuration
            this._loadTabComponent(tab, $contentArea);
        },
        
        /**
         * Load specific tab component
         * @param {Object} tab Tab configuration
         * @param {jQuery} $container Content container
         * @private
         */
        _loadTabComponent: function(tab, $container) {
            switch (tab.id) {
                case 'studio':
                    this._loadStudioComponent($container);
                    break;
                case 'gallery':
                    this._loadGalleryComponent($container);
                    break;
                case 'seed-library':
                    this._loadSeedLibraryComponent($container);
                    break;
                case 'marketplace':
                    this._loadMarketplaceComponent($container);
                    break;
                case 'analytics':
                    this._loadAnalyticsComponent($container);
                    break;
                case 'community':
                    this._loadCommunityComponent($container);
                    break;
                case 'learning':
                    this._loadLearningComponent($container);
                    break;
                case 'profile':
                    this._loadProfileComponent($container);
                    break;
                case 'settings':
                    this._loadSettingsComponent($container);
                    break;
                default:
                    this._loadDefaultComponent($container, tab);
            }
        },
        
        /**
         * Load Studio component (main creation interface)
         * @param {jQuery} $container Content container
         * @private
         */
        _loadStudioComponent: function($container) {
            const content = `
                <div class="huraii-studio">
                    <div class="studio-header">
                        <h1>HURAII Studio</h1>
                        <p>Create AI artwork using Marianne Nems' Seed-Art Technique</p>
                    </div>
                    
                    <!-- Midjourney-style interface will be loaded here -->
                    <div id="vortex-midjourney-container" class="studio-interface">
                        <!-- This will be populated by the midjourneyUI component -->
                    </div>
                    
                    <div class="studio-sidebar">
                        <div class="quick-actions">
                            <h3>Quick Actions</h3>
                            <button class="action-btn" data-action="new-project">
                                <i class="fas fa-plus"></i> New Project
                            </button>
                            <button class="action-btn" data-action="load-seed">
                                <i class="fas fa-seedling"></i> Use Seed Art
                            </button>
                            <button class="action-btn" data-action="describe-image">
                                <i class="fas fa-comment-alt"></i> Describe Image
                            </button>
                        </div>
                        
                        <div class="recent-projects">
                            <h3>Recent Projects</h3>
                            <div class="project-list" id="recent-projects">
                                <!-- Recent projects will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $container.html(content);
            
            // Initialize Midjourney UI component
            if (this.core.getComponent('midjourneyUI')) {
                this.core.getComponent('midjourneyUI').init(this.core);
            }
            
            this.state.loadedComponents['studio'] = true;
            $container.removeClass('loading');
        },
        
        /**
         * Load Gallery component
         * @param {jQuery} $container Content container
         * @private
         */
        _loadGalleryComponent: function($container) {
            const content = `
                <div class="huraii-gallery">
                    <div class="gallery-header">
                        <h1>My Gallery</h1>
                        <div class="gallery-controls">
                            <div class="view-controls">
                                <button class="view-btn active" data-view="grid">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button class="view-btn" data-view="list">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                            <div class="filter-controls">
                                <select class="filter-select" id="gallery-filter">
                                    <option value="all">All Artworks</option>
                                    <option value="recent">Recent</option>
                                    <option value="favorites">Favorites</option>
                                    <option value="seed-art">Seed Art</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="gallery-stats">
                        <div class="stat-card">
                            <i class="fas fa-images"></i>
                            <div class="stat-info">
                                <span class="stat-number" id="total-artworks">0</span>
                                <span class="stat-label">Total Artworks</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-heart"></i>
                            <div class="stat-info">
                                <span class="stat-number" id="total-likes">0</span>
                                <span class="stat-label">Total Likes</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-eye"></i>
                            <div class="stat-info">
                                <span class="stat-number" id="total-views">0</span>
                                <span class="stat-label">Total Views</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="gallery-grid" id="gallery-grid">
                        <!-- Gallery items will be loaded here -->
                    </div>
                </div>
            `;
            
            $container.html(content);
            this._loadGalleryContent();
            this.state.loadedComponents['gallery'] = true;
            $container.removeClass('loading');
        },
        
        /**
         * Load Seed Library component
         * @param {jQuery} $container Content container
         * @private
         */
        _loadSeedLibraryComponent: function($container) {
            const content = `
                <div class="huraii-seed-library">
                    <div class="seed-library-header">
                        <h1>Seed Art Library</h1>
                        <p>Explore Marianne Nems' curated collection of Seed-Art masterpieces</p>
                    </div>
                    
                    <div class="seed-categories">
                        <button class="category-btn active" data-category="all">All Seeds</button>
                        <button class="category-btn" data-category="sacred-geometry">Sacred Geometry</button>
                        <button class="category-btn" data-category="color-harmony">Color Harmony</button>
                        <button class="category-btn" data-category="light-shadow">Light & Shadow</button>
                        <button class="category-btn" data-category="texture">Texture</button>
                        <button class="category-btn" data-category="perspective">Perspective</button>
                        <button class="category-btn" data-category="movement">Movement & Layering</button>
                    </div>
                    
                    <div class="seed-grid" id="seed-grid">
                        <!-- Seed artworks will be loaded here -->
                    </div>
                </div>
            `;
            
            $container.html(content);
            this._loadSeedLibraryContent();
            this.state.loadedComponents['seed-library'] = true;
            $container.removeClass('loading');
        },
        
        /**
         * Load additional components (simplified for now)
         * @private
         */
        _loadMarketplaceComponent: function($container) {
            this._loadComponentPlaceholder($container, 'marketplace', 'Marketplace', 'Browse and purchase AI artwork');
        },
        
        _loadAnalyticsComponent: function($container) {
            this._loadComponentPlaceholder($container, 'analytics', 'Analytics', 'View your performance insights and statistics');
        },
        
        _loadCommunityComponent: function($container) {
            this._loadComponentPlaceholder($container, 'community', 'Community', 'Connect with other artists and share your work');
        },
        
        _loadLearningComponent: function($container) {
            this._loadComponentPlaceholder($container, 'learning', 'Learning Center', 'Master the Seed-Art technique and AI art creation');
        },
        
        _loadProfileComponent: function($container) {
            this._loadComponentPlaceholder($container, 'profile', 'Profile', 'Manage your profile and view achievements');
        },
        
        _loadSettingsComponent: function($container) {
            this._loadComponentPlaceholder($container, 'settings', 'Settings', 'Configure your HURAII preferences');
        },
        
        /**
         * Load placeholder component (for development)
         * @param {jQuery} $container Content container
         * @param {string} id Component ID
         * @param {string} title Component title
         * @param {string} description Component description
         * @private
         */
        _loadComponentPlaceholder: function($container, id, title, description) {
            const content = `
                <div class="huraii-component-placeholder">
                    <div class="placeholder-icon">
                        <i class="fas fa-construction"></i>
                    </div>
                    <h2>${title}</h2>
                    <p>${description}</p>
                    <p class="placeholder-note">This component is coming soon!</p>
                </div>
            `;
            
            $container.html(content);
            this.state.loadedComponents[id] = true;
            $container.removeClass('loading');
        },
        
        /**
         * Load user permissions
         * @private
         */
        _loadUserPermissions: function() {
            // In a real implementation, this would load from the server
            this.state.userPermissions = [
                'create', 'view_gallery', 'view_seeds', 'view_marketplace',
                'view_analytics', 'view_community', 'view_learning',
                'view_profile', 'manage_settings'
            ];
        },
        
        /**
         * Check if user has permission
         * @param {Array} permissions Required permissions
         * @returns {boolean} Has permission
         * @private
         */
        _hasPermission: function(permissions) {
            return permissions.every(perm => this.state.userPermissions.includes(perm));
        },
        
        /**
         * Handle window resize for responsive behavior
         * @private
         */
        _handleResize: function() {
            const width = $(window).width();
            
            if (width < 768) {
                // Mobile view
                $('.huraii-navigation-wrapper').addClass('mobile-view');
            } else {
                // Desktop view
                $('.huraii-navigation-wrapper').removeClass('mobile-view');
            }
        },
        
        /**
         * Handle keyboard navigation
         * @param {Event} e Keyboard event
         * @private
         */
        _handleKeyboardNavigation: function(e) {
            // Ctrl/Cmd + number keys for tab switching
            if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '9') {
                e.preventDefault();
                const tabIndex = parseInt(e.key) - 1;
                const availableTabs = this.config.tabs.filter(tab => this._hasPermission(tab.permissions));
                
                if (availableTabs[tabIndex]) {
                    this.switchTab(availableTabs[tabIndex].id);
                }
            }
        },
        
        /**
         * Toggle user menu
         * @private
         */
        _toggleUserMenu: function() {
            $('.huraii-user-menu').toggleClass('open');
        },
        
        /**
         * Show notifications panel
         * @private
         */
        _showNotifications: function() {
            // Placeholder for notifications functionality
            console.log('Showing notifications...');
        },
        
        /**
         * Open search interface
         * @private
         */
        _openSearch: function() {
            // Placeholder for search functionality
            console.log('Opening search...');
        },
        
        /**
         * Load gallery content
         * @private
         */
        _loadGalleryContent: function() {
            // Placeholder for gallery loading
            setTimeout(() => {
                $('#total-artworks').text('24');
                $('#total-likes').text('156');
                $('#total-views').text('1.2k');
            }, 500);
        },
        
        /**
         * Load seed library content
         * @private
         */
        _loadSeedLibraryContent: function() {
            // Placeholder for seed library loading
            setTimeout(() => {
                console.log('Seed library content loaded');
            }, 500);
        },
        
        /**
         * Update tab notification count
         * @param {string} tabId Tab identifier
         * @param {number} count Notification count
         */
        updateNotificationCount: function(tabId, count) {
            this.state.notifications[tabId] = count;
            
            const $notification = $(`.huraii-tab[data-tab="${tabId}"] .tab-notification, .huraii-mobile-tab[data-tab="${tabId}"] .tab-notification`);
            
            if (count > 0) {
                $notification.text(count).show();
            } else {
                $notification.hide();
            }
        },
        
        /**
         * Get current active tab
         * @returns {string} Active tab ID
         */
        getActiveTab: function() {
            return this.state.activeTab;
        },
        
        /**
         * Get tab history
         * @returns {Array} Tab history
         */
        getTabHistory: function() {
            return this.state.tabHistory;
        }
    };
    
    // Register with HURAII when loaded
    if (global.HURAII) {
        global.HURAII.registerComponent('navigationTabs', NavigationTabs);
    } else {
        // Wait for HURAII to be defined
        document.addEventListener('DOMContentLoaded', () => {
            if (global.HURAII) {
                global.HURAII.registerComponent('navigationTabs', NavigationTabs);
            } else {
                console.warn('HURAII core module not found. Navigation Tabs module initialization failed.');
            }
        });
    }
    
})(window, jQuery); 