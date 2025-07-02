/**
 * VORTEX SYSTEM ADMIN JAVASCRIPT
 * 
 * Interactive functionality for the VORTEX AI system dashboard
 * Handles agent controls, real-time monitoring, and admin operations
 */

(function($) {
    'use strict';
    
    // Global VORTEX admin object
    window.VortexSystemAdmin = {
        
        // Configuration
        config: {
            refreshInterval: 30000, // 30 seconds
            activityRefreshInterval: 5000, // 5 seconds
            maxLogEntries: 50,
            animationDuration: 300
        },
        
        // State management
        state: {
            systemStatus: {},
            agentsStatus: {},
            isRefreshing: false,
            lastUpdate: null
        },
        
        // Chart instances
        charts: {},
        
        // Initialization
        init: function() {
            console.log('✅ VORTEX System Admin JavaScript loaded');
            this.bindEvents();
            this.initializeRealTimeMonitoring();
            this.startPerformanceMonitoring();
            this.loadSystemStatus();
        },
        
        // Event binding
        bindEvents: function() {
            var self = this;
            
            // Agent control buttons
            $(document).on('click', '.agent-control-btn', function(e) {
                e.preventDefault();
                const agent = $(this).data('agent');
                const action = $(this).data('action');
                self.controlAgent(agent, action);
            });
            
            // Quick action buttons
            $('#sync-all-agents').on('click', function() {
                self.syncAllAgents();
            });
            
            $('#performance-check').on('click', function() {
                self.performanceCheck();
            });
            
            $('#system-restart').on('click', function() {
                self.confirmSystemRestart();
            });
            
            $('#enable-secret-sauce').on('click', function() {
                self.toggleSecretSauce(true);
            });
            
            $('#disable-secret-sauce').on('click', function() {
                self.toggleSecretSauce(false);
            });
            
            $('#export-logs').on('click', function() {
                self.exportSystemLogs();
            });
            
            // Status refresh button
            $(document).on('click', '.refresh-status', function() {
                self.refreshSystemStatus();
            });
            
            // Real-time toggle
            $(document).on('change', '#real-time-monitoring', function() {
                if ($(this).is(':checked')) {
                    self.startRealTimeMonitoring();
                } else {
                    self.stopRealTimeMonitoring();
                }
            });
        },
        
        // Agent control functions
        controlAgent: function(agent, action) {
            console.log('Controlling agent:', agent, action);
            // Implementation here
        },
        
        syncAllAgents: function() {
            console.log('Syncing all agents');
            // Implementation here
        },
        
        toggleSecretSauce: function(enabled) {
            console.log('Toggling SECRET SAUCE:', enabled);
            // Implementation here
        },
        
        loadSystemStatus: function() {
            console.log('Loading system status');
            // Implementation here
        },
        
        // Real-time monitoring
        initializeRealTimeMonitoring: function() {
            this.startActivityMonitoring();
            this.startSystemMonitoring();
        },
        
        startActivityMonitoring: function() {
            const self = this;
            
            setInterval(() => {
                self.updateActivityLog();
            }, this.config.activityRefreshInterval);
        },
        
        startSystemMonitoring: function() {
            const self = this;
            
            setInterval(() => {
                self.refreshSystemStatus();
            }, this.config.refreshInterval);
        },
        
        updateActivityLog: function() {
            // Simulate real activity (in production, this would fetch real data)
            const activities = [
                { level: 'info', message: 'Agent synchronization completed' },
                { level: 'success', message: 'Performance check passed' },
                { level: 'info', message: 'Cloud connection verified' },
                { level: 'info', message: 'Learning models updated' }
            ];
            
            const randomActivity = activities[Math.floor(Math.random() * activities.length)];
            this.addActivityLog('AUTO', randomActivity.message, randomActivity.level);
        },
        
        addActivityLog: function(type, message, level = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const $logEntry = $(`
                <div class="log-entry">
                    <span class="timestamp">${timestamp}</span>
                    <span class="level ${level}">${type}</span>
                    <span class="message">${message}</span>
                </div>
            `);
            
            $('#real-time-log').prepend($logEntry);
            
            // Animate entry
            $logEntry.hide().slideDown(this.config.animationDuration);
            
            // Remove old entries
            const $entries = $('#real-time-log .log-entry');
            if ($entries.length > this.config.maxLogEntries) {
                $entries.slice(this.config.maxLogEntries).fadeOut(this.config.animationDuration, function() {
                    $(this).remove();
                });
            }
        },
        
        // Performance monitoring
        startPerformanceMonitoring: function() {
            const self = this;
            
            // Monitor page performance
            if (window.performance && window.performance.measure) {
                setInterval(() => {
                    self.recordPerformanceMetrics();
                }, 10000); // Every 10 seconds
            }
        },
        
        recordPerformanceMetrics: function() {
            const metrics = {
                memory: performance.memory ? performance.memory.usedJSHeapSize : 0,
                timing: performance.timing ? performance.timing.loadEventEnd - performance.timing.navigationStart : 0
            };
            
            // Store metrics for later analysis
            this.state.performanceMetrics = metrics;
        },
        
        showPerformanceResults: function(data) {
            const modal = `
                <div id="performance-modal" class="vortex-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Performance Check Results</h2>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="performance-grid">
                                ${data.map(metric => `
                                    <div class="performance-card">
                                        <h3>${metric.agent_name}</h3>
                                        <div class="metric">
                                            <span>Avg Response Time:</span>
                                            <span>${metric.avg_response_time.toFixed(2)}ms</span>
                                        </div>
                                        <div class="metric">
                                            <span>Memory Usage:</span>
                                            <span>${metric.avg_memory_usage.toFixed(2)}MB</span>
                                        </div>
                                        <div class="metric">
                                            <span>Operations:</span>
                                            <span>${metric.operation_count}</span>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            
            // Bind close events
            $('#performance-modal .modal-close, #performance-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#performance-modal').remove();
                }
            });
        },
        
        // UI utilities
        showLoading: function(message = 'Processing...') {
            $('#vortex-loading-overlay').show();
            $('#vortex-loading-overlay .vortex-spinner p').text(message);
        },
        
        hideLoading: function() {
            $('#vortex-loading-overlay').hide();
        },
        
        showNotification: function(message, type = 'info') {
            const notification = $(`
                <div class="notice notice-${type} is-dismissible vortex-notification">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.vortex-system-dashboard').prepend(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.fadeOut(this.config.animationDuration, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual dismiss
            notification.find('.notice-dismiss').on('click', function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },
        
        showRestartCountdown: function() {
            let countdown = 10;
            const $countdownModal = $(`
                <div id="restart-countdown-modal" class="vortex-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>🔄 System Restarting</h2>
                        </div>
                        <div class="modal-body">
                            <div class="countdown-display">
                                <div class="countdown-number">${countdown}</div>
                                <p>Page will reload automatically when system is ready...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append($countdownModal);
            
            const timer = setInterval(() => {
                countdown--;
                $countdownModal.find('.countdown-number').text(countdown);
                
                if (countdown <= 0) {
                    clearInterval(timer);
                    location.reload();
                }
            }, 1000);
        }
    };
    
    // Utility functions
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            
            const callNow = immediate && !timeout;
            
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            
            if (callNow) func.apply(context, args);
        };
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        VortexSystemAdmin.init();
    });
    
})(jQuery); 