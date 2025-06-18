/**
 * HURAII Core System
 * 
 * Main orchestrator for all HURAII AI components
 * Provides centralized component management, event handling, and API integration
 */

(function(global) {
    'use strict';
    
    /**
     * HURAII Core Class
     */
    class HuraiiCore {
        constructor() {
            this.components = new Map();
            this.config = this.getDefaultConfig();
            this.state = {
                initialized: false,
                user: null,
                permissions: [],
                activeSession: null,
                debug: false
            };
            this.eventBus = this.createEventBus();
        }
        
        /**
         * Get default configuration
         */
        getDefaultConfig() {
            return {
                // API Configuration
                api: {
                    baseUrl: '/wp-admin/admin-ajax.php',
                    timeout: 30000,
                    retryAttempts: 3
                },
                
                // Component Configuration
                components: {
                    navigationTabs: {
                        enabled: true,
                        defaultTab: 'studio'
                    },
                    midjourneyUI: {
                        enabled: true,
                        gridLayout: true,
                        maxImages: 100
                    },
                    visualDescriptor: {
                        enabled: true,
                        maxFileSize: 10 * 1024 * 1024, // 10MB
                        supportedFormats: ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
                    },
                    seedArtAnalyzer: {
                        enabled: true,
                        analysisDepth: 'comprehensive'
                    }
                },
                
                // UI Configuration
                ui: {
                    theme: 'dark',
                    animations: true,
                    responsiveBreakpoints: {
                        mobile: 768,
                        tablet: 1024,
                        desktop: 1200
                    }
                },
                
                // User Experience
                ux: {
                    autoSave: true,
                    autoSaveInterval: 30000, // 30 seconds
                    showTooltips: true,
                    enableKeyboardShortcuts: true
                }
            };
        }
        
        /**
         * Create event bus for component communication
         */
        createEventBus() {
            const events = {};
            
            return {
                on: (event, callback) => {
                    if (!events[event]) {
                        events[event] = [];
                    }
                    events[event].push(callback);
                },
                
                off: (event, callback) => {
                    if (events[event]) {
                        events[event] = events[event].filter(cb => cb !== callback);
                    }
                },
                
                emit: (event, data) => {
                    if (events[event]) {
                        events[event].forEach(callback => {
                            try {
                                callback(data);
                            } catch (error) {
                                console.error(`Error in event handler for ${event}:`, error);
                            }
                        });
                    }
                    
                    // Also trigger jQuery events for compatibility
                    if (global.jQuery) {
                        global.jQuery(document).trigger(`huraii:${event}`, data);
                    }
                }
            };
        }
        
        /**
         * Initialize HURAII system
         */
        async init(customConfig = {}) {
            try {
                // Merge custom configuration
                this.config = this.deepMerge(this.config, customConfig);
                
                // Set debug mode
                this.state.debug = this.config.debug || global.HURAII_DEBUG || false;
                
                this.log('Initializing HURAII Core System...');
                
                // Load user data and permissions
                await this.loadUserData();
                
                // Initialize UI framework
                this.initializeUI();
                
                // Initialize core components
                await this.initializeComponents();
                
                // Setup global event handlers
                this.setupGlobalEvents();
                
                // Setup keyboard shortcuts
                if (this.config.ux.enableKeyboardShortcuts) {
                    this.setupKeyboardShortcuts();
                }
                
                // Setup auto-save if enabled
                if (this.config.ux.autoSave) {
                    this.setupAutoSave();
                }
                
                // Mark as initialized
                this.state.initialized = true;
                this.eventBus.emit('core:initialized', this.state);
                
                this.log('HURAII Core System initialized successfully');
                
                return this;
                
            } catch (error) {
                console.error('Failed to initialize HURAII Core:', error);
                this.eventBus.emit('core:error', error);
                throw error;
            }
        }
        
        /**
         * Register a component with the core system
         */
        registerComponent(name, component) {
            if (this.components.has(name)) {
                console.warn(`Component ${name} is already registered`);
                return false;
            }
            
            this.components.set(name, component);
            this.log(`Component registered: ${name}`);
            this.eventBus.emit('component:registered', { name, component });
            
            return true;
        }
        
        /**
         * Get a registered component
         */
        getComponent(name) {
            return this.components.get(name);
        }
        
        /**
         * Initialize UI framework
         */
        initializeUI() {
            // Add CSS custom properties for theming
            this.setCSSProperties();
            
            // Add main HURAII classes to body
            if (global.jQuery) {
                global.jQuery('body').addClass('huraii-initialized huraii-theme-dark');
            }
            
            // Setup responsive behavior
            this.setupResponsiveBehavior();
        }
        
        /**
         * Initialize core components based on configuration
         */
        async initializeComponents() {
            const $ = global.jQuery;
            if (!$) {
                throw new Error('jQuery is required for HURAII components');
            }
            
            // Initialize components in order of dependency
            const initOrder = [
                'navigationTabs',
                'midjourneyUI', 
                'visualDescriptor',
                'seedArtAnalyzer'
            ];
            
            for (const componentName of initOrder) {
                if (this.config.components[componentName]?.enabled) {
                    await this.initializeComponent(componentName);
                }
            }
        }
        
        /**
         * Initialize a specific component
         */
        async initializeComponent(componentName) {
            try {
                this.log(`Initializing component: ${componentName}`);
                
                // Wait for component script to be available
                await this.waitForComponent(componentName);
                
                // Initialize the component
                const component = this.getComponent(componentName);
                if (component && typeof component.init === 'function') {
                    await component.init(this);
                    this.log(`Component initialized: ${componentName}`);
                } else {
                    console.warn(`Component ${componentName} not found or has no init method`);
                }
                
            } catch (error) {
                console.error(`Failed to initialize component ${componentName}:`, error);
            }
        }
        
        /**
         * Wait for a component to be available
         */
        waitForComponent(componentName, timeout = 5000) {
            return new Promise((resolve, reject) => {
                const startTime = Date.now();
                
                const checkComponent = () => {
                    if (this.components.has(componentName)) {
                        resolve();
                        return;
                    }
                    
                    if (Date.now() - startTime > timeout) {
                        reject(new Error(`Component ${componentName} not loaded within timeout`));
                        return;
                    }
                    
                    setTimeout(checkComponent, 100);
                };
                
                checkComponent();
            });
        }
        
        /**
         * Load user data and permissions
         */
        async loadUserData() {
            try {
                const response = await this.apiCall('huraii_get_user_data');
                
                if (response.success) {
                    this.state.user = response.data.user;
                    this.state.permissions = response.data.permissions || [];
                    this.eventBus.emit('user:loaded', this.state.user);
                }
                
            } catch (error) {
                console.warn('Failed to load user data:', error);
                // Continue with guest permissions
                this.state.permissions = ['view_public'];
            }
        }
        
        /**
         * Setup global event handlers
         */
        setupGlobalEvents() {
            const $ = global.jQuery;
            if (!$) return;
            
            // Global error handling
            global.addEventListener('error', (error) => {
                this.handleGlobalError(error);
            });
            
            // Handle window resize
            $(global).on('resize', this.debounce(() => {
                this.eventBus.emit('window:resize', {
                    width: $(global).width(),
                    height: $(global).height()
                });
            }, 250));
            
            // Handle visibility changes
            document.addEventListener('visibilitychange', () => {
                this.eventBus.emit('visibility:change', {
                    hidden: document.hidden
                });
            });
            
            // Handle network status
            global.addEventListener('online', () => {
                this.eventBus.emit('network:online');
            });
            
            global.addEventListener('offline', () => {
                this.eventBus.emit('network:offline');
            });
        }
        
        /**
         * Setup keyboard shortcuts
         */
        setupKeyboardShortcuts() {
            const $ = global.jQuery;
            if (!$) return;
            
            const shortcuts = {
                // Tab navigation
                'ctrl+1,cmd+1': () => this.switchTab('studio'),
                'ctrl+2,cmd+2': () => this.switchTab('gallery'),
                'ctrl+3,cmd+3': () => this.switchTab('seed-library'),
                'ctrl+4,cmd+4': () => this.switchTab('marketplace'),
                
                // Common actions
                'ctrl+n,cmd+n': () => this.createNew(),
                'ctrl+s,cmd+s': () => this.save(),
                'ctrl+z,cmd+z': () => this.undo(),
                'ctrl+y,cmd+y': () => this.redo(),
                
                // Search
                'ctrl+f,cmd+f': () => this.openSearch(),
                
                // Help
                'f1': () => this.openHelp(),
                '?': () => this.showShortcuts()
            };
            
            // Bind shortcuts
            Object.entries(shortcuts).forEach(([keys, action]) => {
                $(document).on('keydown', null, keys, (e) => {
                    e.preventDefault();
                    action();
                });
            });
        }
        
        /**
         * Setup auto-save functionality
         */
        setupAutoSave() {
            if (!this.config.ux.autoSave) return;
            
            this.autoSaveInterval = setInterval(() => {
                this.eventBus.emit('autosave:trigger');
            }, this.config.ux.autoSaveInterval);
        }
        
        /**
         * Set CSS custom properties for theming
         */
        setCSSProperties() {
            const root = document.documentElement;
            
            // Color scheme
            const colors = {
                '--midnight-blue': '#13141c',
                '--dark-blue': '#1e1f2b',
                '--medium-blue': '#2b2d3b',
                '--light-blue': '#34364a',
                '--highlight-blue': '#4c5efd',
                '--success-green': '#30c85e',
                '--warning-yellow': '#ffcf5c',
                '--error-red': '#f04a5d',
                '--text-primary': '#ffffff',
                '--text-secondary': '#9a9ebc',
                '--text-tertiary': '#636784',
                '--border-color': 'rgba(255, 255, 255, 0.1)'
            };
            
            Object.entries(colors).forEach(([property, value]) => {
                root.style.setProperty(property, value);
            });
        }
        
        /**
         * Setup responsive behavior
         */
        setupResponsiveBehavior() {
            const $ = global.jQuery;
            if (!$) return;
            
            const updateBreakpoint = () => {
                const width = $(global).width();
                const { mobile, tablet, desktop } = this.config.ui.responsiveBreakpoints;
                
                let breakpoint = 'desktop';
                if (width < mobile) breakpoint = 'mobile';
                else if (width < tablet) breakpoint = 'tablet';
                
                $('body').removeClass('huraii-mobile huraii-tablet huraii-desktop')
                         .addClass(`huraii-${breakpoint}`);
                
                this.eventBus.emit('breakpoint:change', breakpoint);
            };
            
            $(global).on('resize', this.debounce(updateBreakpoint, 250));
            updateBreakpoint(); // Initial call
        }
        
        /**
         * Make API calls to WordPress backend
         */
        async apiCall(action, data = {}) {
            const $ = global.jQuery;
            if (!$) {
                throw new Error('jQuery is required for API calls');
            }
            
            const requestData = {
                action,
                nonce: global.huraii_nonce || '',
                ...data
            };
            
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.config.api.baseUrl,
                    type: 'POST',
                    data: requestData,
                    timeout: this.config.api.timeout,
                    success: (response) => {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.data || 'API call failed'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(`API call failed: ${error}`));
                    }
                });
            });
        }
        
        /**
         * Utility methods
         */
        
        // Switch to a specific tab
        switchTab(tabId) {
            const navigationTabs = this.getComponent('navigationTabs');
            if (navigationTabs) {
                navigationTabs.switchTab(tabId);
            }
        }
        
        // Create new project/artwork
        createNew() {
            this.eventBus.emit('action:create_new');
        }
        
        // Save current work
        save() {
            this.eventBus.emit('action:save');
        }
        
        // Undo last action
        undo() {
            this.eventBus.emit('action:undo');
        }
        
        // Redo last undone action
        redo() {
            this.eventBus.emit('action:redo');
        }
        
        // Open search interface
        openSearch() {
            this.eventBus.emit('action:open_search');
        }
        
        // Open help
        openHelp() {
            this.eventBus.emit('action:open_help');
        }
        
        // Show keyboard shortcuts
        showShortcuts() {
            this.eventBus.emit('action:show_shortcuts');
        }
        
        // Handle global errors
        handleGlobalError(error) {
            console.error('Global error:', error);
            this.eventBus.emit('error:global', error);
        }
        
        // Deep merge objects
        deepMerge(target, source) {
            const result = { ...target };
            
            for (const key in source) {
                if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                    result[key] = this.deepMerge(result[key] || {}, source[key]);
                } else {
                    result[key] = source[key];
                }
            }
            
            return result;
        }
        
        // Debounce function
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Logging with debug support
        log(...args) {
            if (this.state.debug) {
                console.log('[HURAII]', ...args);
            }
        }
        
        // Check if user has permission
        hasPermission(permission) {
            return this.state.permissions.includes(permission);
        }
        
        // Get current user
        getCurrentUser() {
            return this.state.user;
        }
        
        // Get system state
        getState() {
            return { ...this.state };
        }
        
        // Get configuration
        getConfig() {
            return { ...this.config };
        }
        
        // Cleanup on page unload
        destroy() {
            // Clear intervals
            if (this.autoSaveInterval) {
                clearInterval(this.autoSaveInterval);
            }
            
            // Cleanup components
            this.components.forEach((component, name) => {
                if (typeof component.destroy === 'function') {
                    component.destroy();
                }
            });
            
            this.components.clear();
            this.eventBus.emit('core:destroyed');
        }
    }
    
    // Create global HURAII instance
    global.HURAII = new HuraiiCore();
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            global.HURAII.init().catch(console.error);
        });
    } else {
        // DOM already loaded
        setTimeout(() => {
            global.HURAII.init().catch(console.error);
        }, 0);
    }
    
    // Cleanup on page unload
    global.addEventListener('beforeunload', () => {
        global.HURAII.destroy();
    });
    
})(window); 