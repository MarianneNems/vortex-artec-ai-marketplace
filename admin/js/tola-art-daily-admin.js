/**
 * TOLA-ART Daily Automation Admin JavaScript
 * 
 * Handles admin interface interactions, AJAX requests, and real-time updates
 * for the daily artwork automation system.
 */

(function($) {
    'use strict';
    
    // Main admin object
    const TOLAArtAdmin = {
        // Configuration
        config: {
            ajaxUrl: ajaxurl,
            nonce: tolaArtAdminData?.nonce || '',
            refreshInterval: 30000, // 30 seconds
            chartColors: {
                creator: '#826eb4',
                artists: '#46b450',
                sales: '#00a0d2',
                background: 'rgba(0, 160, 210, 0.1)'
            }
        },
        
        // State management
        state: {
            currentTab: 'recent-generations',
            autoRefresh: true,
            refreshTimer: null,
            loadingStates: {},
            chartInstances: {}
        },
        
        /**
         * Initialize admin interface
         */
        init: function() {
            this.bindEvents();
            this.setupTabs();
            this.loadInitialData();
            this.startAutoRefresh();
            this.setupCharts();
            
            console.log('TOLA-ART Admin initialized');
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // Tab navigation
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                const tabId = $(this).attr('href').substring(1);
                self.switchTab(tabId);
            });
            
            // Manual trigger buttons
            $('#manual-trigger-btn, #generate-today-btn').on('click', function() {
                self.triggerDailyGeneration();
            });
            
            // Refresh buttons
            $('#refresh-status-btn').on('click', function() {
                self.refreshCurrentTab();
            });
            
            $('#refresh-logs').on('click', function() {
                self.loadSystemLogs();
            });
            
            // Filter controls
            $('#status-filter').on('change', function() {
                self.filterGenerations($(this).val());
            });
            
            $('#artist-status-filter').on('change', function() {
                self.filterArtists($(this).val());
            });
            
            $('#artist-search').on('input', _.debounce(function() {
                self.searchArtists($(this).val());
            }, 500));
            
            $('#royalty-date-range').on('change', function() {
                self.loadRoyaltyData($(this).val());
            });
            
            $('#log-level-filter').on('change', function() {
                self.filterLogs($(this).val());
            });
            
            // Action buttons
            $(document).on('click', '.view-details', function() {
                const generationId = $(this).data('generation-id');
                self.showGenerationDetails(generationId);
            });
            
            $(document).on('click', '.retry-generation', function() {
                const date = $(this).data('date');
                self.retryGeneration(date);
            });
            
            $(document).on('click', '.view-contract', function() {
                const address = $(this).data('address');
                self.viewContract(address);
            });
            
            $(document).on('click', '.view-artists', function() {
                const generationId = $(this).data('generation-id');
                self.showParticipatingArtists(generationId);
            });
            
            // Artist management
            $('#add-artist-btn').on('click', function() {
                self.showArtistForm();
            });
            
            $(document).on('click', '.edit-artist', function() {
                const artistId = $(this).data('artist-id');
                self.editArtist(artistId);
            });
            
            $(document).on('click', '.toggle-artist-status', function() {
                const artistId = $(this).data('artist-id');
                const isActive = $(this).data('active');
                self.toggleArtistStatus(artistId, !isActive);
            });
            
            // Form submissions
            $('#system-settings-form').on('submit', function(e) {
                e.preventDefault();
                self.saveSystemSettings();
            });
            
            $('#artist-form').on('submit', function(e) {
                e.preventDefault();
                self.saveArtist();
            });
            
            // Export buttons
            $('#export-generations').on('click', function() {
                self.exportData('generations');
            });
            
            $('#export-artists').on('click', function() {
                self.exportData('artists');
            });
            
            $('#export-royalties').on('click', function() {
                self.exportData('royalties');
            });
            
            $('#export-logs').on('click', function() {
                self.exportData('logs');
            });
            
            // Modal handling
            $('.modal-close').on('click', function() {
                $(this).closest('.tola-modal').hide();
            });
            
            $('.tola-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
            
            // Reset settings
            $('#reset-settings').on('click', function() {
                if (confirm('Are you sure you want to reset all settings to defaults?')) {
                    self.resetSettings();
                }
            });
        },
        
        /**
         * Setup tab system
         */
        setupTabs: function() {
            // Set initial active tab
            this.switchTab(this.state.currentTab);
        },
        
        /**
         * Switch between tabs
         */
        switchTab: function(tabId) {
            // Update active states
            $('.nav-tab').removeClass('nav-tab-active');
            $(`a[href="#${tabId}"]`).addClass('nav-tab-active');
            
            $('.tab-content').removeClass('active');
            $(`#${tabId}`).addClass('active');
            
            this.state.currentTab = tabId;
            
            // Load tab-specific data
            this.loadTabData(tabId);
        },
        
        /**
         * Load data for specific tab
         */
        loadTabData: function(tabId) {
            switch(tabId) {
                case 'recent-generations':
                    this.loadRecentGenerations();
                    break;
                case 'artist-management':
                    this.loadArtistsTable();
                    break;
                case 'royalty-tracking':
                    this.loadRoyaltyData();
                    break;
                case 'automation-logs':
                    this.loadSystemLogs();
                    break;
            }
        },
        
        /**
         * Load initial data
         */
        loadInitialData: function() {
            this.loadDashboardStats();
            this.loadTodayStatus();
            this.loadTabData(this.state.currentTab);
        },
        
        /**
         * Load dashboard statistics
         */
        loadDashboardStats: function() {
            this.setLoadingState('dashboard-stats', true);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_daily_art_stats',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateDashboardStats(response.data);
                    }
                },
                complete: () => {
                    this.setLoadingState('dashboard-stats', false);
                }
            });
        },
        
        /**
         * Update dashboard statistics
         */
        updateDashboardStats: function(stats) {
            $('.stat-card.total-generated .stat-number').text(this.formatNumber(stats.total_generated || 0));
            $('.stat-card.successful .stat-number').text(this.formatNumber(stats.successful_generations || 0));
            $('.stat-card.artists .stat-number').text(this.formatNumber(stats.participating_artists || 0));
            $('.stat-card.revenue .stat-number').text(this.formatNumber(stats.total_sales || 0));
            $('.stat-card.royalties .stat-number').text(this.formatNumber(stats.total_royalties_distributed || 0));
        },
        
        /**
         * Load today's status
         */
        loadTodayStatus: function() {
            // Today's status is loaded server-side, but we can refresh it
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_today_art_status',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.updateTodayStatus(response.data);
                    }
                }
            });
        },
        
        /**
         * Update today's status display
         */
        updateTodayStatus: function(status) {
            // Update status indicator
            $('.status-circle').removeClass().addClass(`status-circle ${status.generation_status}`);
            $('.status-text').text(this.formatStatus(status.generation_status));
            
            // Update artwork details if available
            if (status.artwork_id) {
                $('.detail-item:contains("Artwork ID:") span').html(`
                    <a href="${this.getEditUrl(status.artwork_id)}" target="_blank">
                        #${status.artwork_id}
                    </a>
                `);
            }
            
            // Update participating artists count
            $('.detail-item:contains("Participating Artists:") span').text(`${status.participating_artists_count || 0} artists`);
        },
        
        /**
         * Trigger daily generation
         */
        triggerDailyGeneration: function() {
            const button = $('#manual-trigger-btn, #generate-today-btn');
            const originalText = button.html();
            
            button.prop('disabled', true).html('<i class="dashicons dashicons-update spin"></i> Generating...');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_trigger_daily_art',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Daily art generation triggered successfully!', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        this.showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: () => {
                    this.showNotification('Request failed. Please try again.', 'error');
                },
                complete: () => {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },
        
        /**
         * Load recent generations
         */
        loadRecentGenerations: function() {
            this.setLoadingState('recent-generations', true);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_recent_generations',
                    nonce: this.config.nonce,
                    limit: 20
                },
                success: (response) => {
                    if (response.success) {
                        this.renderGenerationsTable(response.data);
                    }
                },
                complete: () => {
                    this.setLoadingState('recent-generations', false);
                }
            });
        },
        
        /**
         * Render generations table
         */
        renderGenerationsTable: function(generations) {
            const tbody = $('.generations-table-wrapper tbody');
            tbody.empty();
            
            if (generations.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="7" class="no-items">
                            <div class="empty-state">
                                <i class="dashicons dashicons-art"></i>
                                <p>No generations found.</p>
                            </div>
                        </td>
                    </tr>
                `);
                return;
            }
            
            generations.forEach(generation => {
                const row = this.createGenerationRow(generation);
                tbody.append(row);
            });
        },
        
        /**
         * Create generation table row
         */
        createGenerationRow: function(generation) {
            const artworkLink = generation.artwork_id ? 
                `<a href="${this.getEditUrl(generation.artwork_id)}" target="_blank">Artwork #${generation.artwork_id}</a>` :
                '<span class="text-muted">Not created</span>';
                
            const listingInfo = generation.marketplace_listing_id ? 
                `<br><small>Listed #${generation.marketplace_listing_id}</small>` : '';
                
            const artistsButton = generation.participating_artists_count > 0 ? 
                `<button class="button-link view-artists" data-generation-id="${generation.id}">View Artists</button>` : '';
                
            const retryButton = generation.generation_status === 'failed' ? 
                `<button class="button-link retry-generation" data-date="${generation.date}" title="Retry Generation">
                    <i class="dashicons dashicons-controls-repeat"></i>
                </button>` : '';
                
            const contractButton = generation.smart_contract_address ? 
                `<button class="button-link view-contract" data-address="${generation.smart_contract_address}" title="View Contract">
                    <i class="dashicons dashicons-admin-links"></i>
                </button>` : '';
            
            return $(`
                <tr data-generation-id="${generation.id}">
                    <td>
                        <strong>${generation.date}</strong>
                        <br>
                        <small>${this.formatDate(generation.created_at)}</small>
                    </td>
                    <td>
                        <span class="status-badge ${generation.generation_status}">
                            ${this.formatStatus(generation.generation_status)}
                        </span>
                    </td>
                    <td>
                        ${artworkLink}
                        ${listingInfo}
                    </td>
                    <td>
                        <span class="artist-count">${generation.participating_artists_count || 0}</span>
                        ${artistsButton}
                    </td>
                    <td>
                        <span class="sales-amount">${this.formatCurrency(generation.total_sales || 0)} TOLA</span>
                    </td>
                    <td>
                        <span class="royalties-amount">${this.formatCurrency(generation.royalties_distributed || 0)} TOLA</span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="button-link view-details" data-generation-id="${generation.id}" title="View Details">
                                <i class="dashicons dashicons-visibility"></i>
                            </button>
                            ${retryButton}
                            ${contractButton}
                        </div>
                    </td>
                </tr>
            `);
        },
        
        /**
         * Load artists table
         */
        loadArtistsTable: function() {
            this.setLoadingState('artist-management', true);
            
            const container = $('#artists-table-container');
            container.html('<div class="loading-placeholder"><i class="dashicons dashicons-update spin"></i> Loading artist data...</div>');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_participating_artists',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderArtistsTable(response.data);
                    }
                },
                complete: () => {
                    this.setLoadingState('artist-management', false);
                }
            });
        },
        
        /**
         * Render artists table
         */
        renderArtistsTable: function(artists) {
            const container = $('#artists-table-container');
            
            if (artists.length === 0) {
                container.html(`
                    <div class="empty-state">
                        <i class="dashicons dashicons-groups"></i>
                        <p>No participating artists found.</p>
                    </div>
                `);
                return;
            }
            
            let tableHtml = `
                <table class="wp-list-table widefat fixed striped artists-table">
                    <thead>
                        <tr>
                            <th>Artist</th>
                            <th>Wallet Address</th>
                            <th>Participation Count</th>
                            <th>Total Royalties Earned</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            artists.forEach(artist => {
                tableHtml += this.createArtistRow(artist);
            });
            
            tableHtml += `
                    </tbody>
                </table>
            `;
            
            container.html(tableHtml);
        },
        
        /**
         * Create artist table row
         */
        createArtistRow: function(artist) {
            const statusBadge = artist.is_active ? 
                '<span class="status-badge active">Active</span>' :
                '<span class="status-badge inactive">Inactive</span>';
                
            const toggleText = artist.is_active ? 'Deactivate' : 'Activate';
            
            return `
                <tr data-artist-id="${artist.user_id}">
                    <td>
                        <strong>${artist.display_name}</strong>
                        <br>
                        <small>${artist.user_email}</small>
                    </td>
                    <td>
                        <code class="wallet-address">${artist.wallet_address}</code>
                    </td>
                    <td>
                        <span class="participation-count">${artist.participation_count || 0}</span>
                    </td>
                    <td>
                        <span class="royalties-earned">${this.formatCurrency(artist.total_royalties_earned || 0)} TOLA</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="button-link edit-artist" data-artist-id="${artist.user_id}" title="Edit Artist">
                                <i class="dashicons dashicons-edit"></i>
                            </button>
                            <button class="button-link toggle-artist-status" data-artist-id="${artist.user_id}" data-active="${artist.is_active}" title="${toggleText}">
                                <i class="dashicons dashicons-${artist.is_active ? 'pause' : 'controls-play'}"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        },
        
        /**
         * Load royalty data
         */
        loadRoyaltyData: function(dateRange = '30') {
            this.setLoadingState('royalty-tracking', true);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_royalty_data',
                    nonce: this.config.nonce,
                    date_range: dateRange
                },
                success: (response) => {
                    if (response.success) {
                        this.updateRoyaltyStats(response.data.stats);
                        this.updateRoyaltyChart(response.data.chart_data);
                        this.renderRoyaltyTable(response.data.distributions);
                    }
                },
                complete: () => {
                    this.setLoadingState('royalty-tracking', false);
                }
            });
        },
        
        /**
         * Update royalty statistics
         */
        updateRoyaltyStats: function(stats) {
            $('#creator-royalties-total').text(`${this.formatCurrency(stats.creator_royalties || 0)} TOLA`);
            $('#artist-pool-total').text(`${this.formatCurrency(stats.artist_pool || 0)} TOLA`);
            $('#total-distributed').text(`${this.formatCurrency(stats.total_distributed || 0)} TOLA`);
            $('#artist-pool-recipients').text(`${stats.participating_artists || 0} artists`);
            $('#distribution-count').text(`${stats.distribution_count || 0} distributions`);
        },
        
        /**
         * Load system logs
         */
        loadSystemLogs: function() {
            this.setLoadingState('automation-logs', true);
            
            const container = $('#logs-container');
            container.html('<div class="logs-loading"><i class="dashicons dashicons-update spin"></i> Loading system logs...</div>');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_system_logs',
                    nonce: this.config.nonce,
                    limit: 100
                },
                success: (response) => {
                    if (response.success) {
                        this.renderSystemLogs(response.data);
                    }
                },
                complete: () => {
                    this.setLoadingState('automation-logs', false);
                }
            });
        },
        
        /**
         * Render system logs
         */
        renderSystemLogs: function(logs) {
            const container = $('#logs-container');
            
            if (logs.length === 0) {
                container.html(`
                    <div class="empty-state">
                        <i class="dashicons dashicons-text-page"></i>
                        <p>No system logs found.</p>
                    </div>
                `);
                return;
            }
            
            let logsHtml = '<div class="logs-list">';
            
            logs.forEach(log => {
                logsHtml += `
                    <div class="log-entry log-${log.level}">
                        <div class="log-header">
                            <span class="log-timestamp">${this.formatDate(log.timestamp)}</span>
                            <span class="log-level log-level-${log.level}">${log.level.toUpperCase()}</span>
                        </div>
                        <div class="log-message">${log.message}</div>
                        ${log.context ? `<div class="log-context">${JSON.stringify(log.context, null, 2)}</div>` : ''}
                    </div>
                `;
            });
            
            logsHtml += '</div>';
            container.html(logsHtml);
        },
        
        /**
         * Setup charts
         */
        setupCharts: function() {
            // Setup Chart.js if available
            if (typeof Chart !== 'undefined') {
                this.initRoyaltyChart();
            }
        },
        
        /**
         * Initialize royalty distribution chart
         */
        initRoyaltyChart: function() {
            const ctx = document.getElementById('royalty-distribution-chart');
            if (!ctx) return;
            
            this.state.chartInstances.royalty = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Creator Royalties (5%)',
                        data: [],
                        borderColor: this.config.chartColors.creator,
                        backgroundColor: this.config.chartColors.creator + '20',
                        tension: 0.4
                    }, {
                        label: 'Artist Pool (95%)',
                        data: [],
                        borderColor: this.config.chartColors.artists,
                        backgroundColor: this.config.chartColors.artists + '20',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'TOLA Tokens'
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Update royalty chart
         */
        updateRoyaltyChart: function(chartData) {
            if (!this.state.chartInstances.royalty) return;
            
            const chart = this.state.chartInstances.royalty;
            chart.data.labels = chartData.labels || [];
            chart.data.datasets[0].data = chartData.creator_royalties || [];
            chart.data.datasets[1].data = chartData.artist_pool || [];
            chart.update();
        },
        
        /**
         * Show generation details modal
         */
        showGenerationDetails: function(generationId) {
            const modal = $('#generation-details-modal');
            const modalBody = modal.find('.modal-body');
            
            modalBody.html('<div class="loading-placeholder"><i class="dashicons dashicons-update spin"></i> Loading details...</div>');
            modal.show();
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_generation_details',
                    nonce: this.config.nonce,
                    generation_id: generationId
                },
                success: (response) => {
                    if (response.success) {
                        modalBody.html(this.renderGenerationDetails(response.data));
                    } else {
                        modalBody.html('<p>Error loading details.</p>');
                    }
                },
                error: () => {
                    modalBody.html('<p>Error loading details.</p>');
                }
            });
        },
        
        /**
         * Show artist form modal
         */
        showArtistForm: function(artistData = null) {
            const modal = $('#artist-form-modal');
            const form = $('#artist-form');
            
            if (artistData) {
                // Populate form for editing
                form.find('#artist-name').val(artistData.display_name);
                form.find('#artist-email').val(artistData.user_email);
                form.find('#artist-wallet').val(artistData.wallet_address);
                form.find('#participation-weight').val(artistData.participation_weight || 1.0);
                form.find('#artist-active').prop('checked', artistData.is_active);
            } else {
                // Clear form for new artist
                form[0].reset();
            }
            
            modal.show();
        },
        
        /**
         * Save system settings
         */
        saveSystemSettings: function() {
            const form = $('#system-settings-form');
            const formData = form.serialize();
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_save_system_settings',
                    nonce: this.config.nonce,
                    settings: formData
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Settings saved successfully!', 'success');
                    } else {
                        this.showNotification('Error saving settings: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error saving settings.', 'error');
                }
            });
        },
        
        /**
         * Export data
         */
        exportData: function(type) {
            const url = `${this.config.ajaxUrl}?action=vortex_export_data&type=${type}&nonce=${this.config.nonce}`;
            window.open(url, '_blank');
        },
        
        /**
         * Start auto refresh
         */
        startAutoRefresh: function() {
            if (this.state.autoRefresh) {
                this.state.refreshTimer = setInterval(() => {
                    this.refreshCurrentTab();
                }, this.config.refreshInterval);
            }
        },
        
        /**
         * Refresh current tab data
         */
        refreshCurrentTab: function() {
            this.loadDashboardStats();
            this.loadTodayStatus();
            this.loadTabData(this.state.currentTab);
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            const notification = $(`
                <div class="notice notice-${type} is-dismissible tola-notification">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after(notification);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                notification.fadeOut();
            }, 5000);
            
            // Handle dismiss button
            notification.find('.notice-dismiss').on('click', function() {
                notification.fadeOut();
            });
        },
        
        /**
         * Set loading state
         */
        setLoadingState: function(component, isLoading) {
            this.state.loadingStates[component] = isLoading;
            
            // Add loading indicators as needed
            if (isLoading) {
                $(`#${component}`).addClass('loading');
            } else {
                $(`#${component}`).removeClass('loading');
            }
        },
        
        /**
         * Format number with commas
         */
        formatNumber: function(num) {
            return new Intl.NumberFormat().format(num);
        },
        
        /**
         * Format currency
         */
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 8
            }).format(amount);
        },
        
        /**
         * Format date
         */
        formatDate: function(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        /**
         * Format status text
         */
        formatStatus: function(status) {
            return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },
        
        /**
         * Get WordPress edit URL
         */
        getEditUrl: function(postId) {
            return `${window.location.origin}/wp-admin/post.php?post=${postId}&action=edit`;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        TOLAArtAdmin.init();
    });
    
    // Export to global scope
    window.TOLAArtAdmin = TOLAArtAdmin;
    
})(jQuery); 