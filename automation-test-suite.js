/**
 * VORTEX AI ENGINE - AUTOMATION TESTING INTERFACE
 * 
 * Real-time automation testing and monitoring dashboard
 */

jQuery(document).ready(function($) {
    'use strict';
    
    const VortexAutomationTester = {
        
        // Configuration
        config: {
            testInterval: 30000, // 30 seconds
            refreshInterval: 5000, // 5 seconds for status updates
            maxRetries: 3
        },
        
        // State management
        state: {
            isRunning: false,
            lastTestResults: null,
            systemHealth: 0,
            alerts: []
        },
        
        /**
         * Initialize the testing interface
         */
        init: function() {
            this.bindEvents();
            this.startRealTimeMonitoring();
            this.loadLastTestResults();
            
            console.log('🚀 VORTEX Automation Tester initialized');
        },
        
        /**
         * Bind UI events
         */
        bindEvents: function() {
            // Run comprehensive test
            $('#vortex-run-automation-test').on('click', this.runComprehensiveTest.bind(this));
            
            // Test individual components
            $('#vortex-test-archer').on('click', () => this.testComponent('archer'));
            $('#vortex-test-agents').on('click', () => this.testComponent('agents'));
            $('#vortex-test-runpod').on('click', () => this.testComponent('runpod'));
            $('#vortex-test-s3').on('click', () => this.testComponent('s3'));
            
            // Force system recovery
            $('#vortex-force-recovery').on('click', this.forceSystemRecovery.bind(this));
            
            // Export test results
            $('#vortex-export-results').on('click', this.exportTestResults.bind(this));
        },
        
        /**
         * Run comprehensive automation test
         */
        runComprehensiveTest: function() {
            if (this.state.isRunning) {
                this.showAlert('Test already running', 'warning');
                return;
            }
            
            this.state.isRunning = true;
            this.updateTestButton('Running comprehensive test...', true);
            this.showProgressBar();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_run_automation_tests',
                    nonce: vortex_automation_nonce
                },
                success: (response) => {
                    this.handleTestResults(response);
                },
                error: (xhr, status, error) => {
                    this.handleTestError(error);
                },
                complete: () => {
                    this.state.isRunning = false;
                    this.updateTestButton('Run Comprehensive Test', false);
                    this.hideProgressBar();
                }
            });
        },
        
        /**
         * Test individual component
         */
        testComponent: function(component) {
            this.showAlert(`Testing ${component.toUpperCase()} component...`, 'info');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_test_component',
                    component: component,
                    nonce: vortex_automation_nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateComponentStatus(component, response.data);
                    } else {
                        this.showAlert(`${component} test failed: ${response.data.message}`, 'error');
                    }
                }
            });
        },
        
        /**
         * Handle test results
         */
        handleTestResults: function(response) {
            if (!response.success) {
                this.showAlert('Test failed: ' + response.data.message, 'error');
                return;
            }
            
            this.state.lastTestResults = response.data;
            this.displayTestResults(response.data);
            this.updateSystemHealth(response.data.overall_score);
            this.updateRecommendations(response.data.recommendations);
            
            // Show success/warning based on score
            if (response.data.overall_score >= 90) {
                this.showAlert('🎉 All systems operational! Score: ' + response.data.overall_score + '%', 'success');
            } else if (response.data.overall_score >= 75) {
                this.showAlert('⚠️ System functional with minor issues. Score: ' + response.data.overall_score + '%', 'warning');
            } else {
                this.showAlert('🚨 System requires attention. Score: ' + response.data.overall_score + '%', 'error');
            }
        },
        
        /**
         * Display test results in dashboard
         */
        displayTestResults: function(results) {
            // Update ARCHER Orchestrator status
            this.updateComponentCard('archer', results.test_results.archer_orchestrator);
            
            // Update AI Agents status
            this.updateComponentCard('agents', results.test_results.ai_agents);
            
            // Update RunPod Vault status
            this.updateComponentCard('runpod', results.test_results.runpod_vault);
            
            // Update S3 Integration status
            this.updateComponentCard('s3', results.test_results.s3_integration);
            
            // Update TOLA-ART Automation status
            this.updateComponentCard('tola-art', results.test_results.tola_art_automation);
            
            // Update Performance metrics
            this.updatePerformanceMetrics(results.test_results.performance);
            
            // Update last test timestamp
            $('#vortex-last-test-time').text(results.timestamp);
        },
        
        /**
         * Update component status card
         */
        updateComponentCard: function(component, data) {
            const $card = $(`#vortex-${component}-card`);
            const $status = $card.find('.component-status');
            const $score = $card.find('.component-score');
            const $details = $card.find('.component-details');
            
            // Update status indicator
            $status.removeClass('status-pass status-fail status-warning')
                   .addClass(`status-${data.status.toLowerCase()}`)
                   .text(data.status);
            
            // Update score
            if (data.success_rate !== undefined) {
                $score.text(data.success_rate + '%');
            } else if (data.health_percentage !== undefined) {
                $score.text(data.health_percentage + '%');
            } else if (data.connectivity_rate !== undefined) {
                $score.text(data.connectivity_rate + '%');
            }
            
            // Update details
            if (data.tests) {
                let detailsHtml = '<ul class="test-details">';
                for (const [test, result] of Object.entries(data.tests)) {
                    const icon = result ? '✅' : '❌';
                    detailsHtml += `<li>${icon} ${this.formatTestName(test)}</li>`;
                }
                detailsHtml += '</ul>';
                $details.html(detailsHtml);
            }
        },
        
        /**
         * Update performance metrics
         */
        updatePerformanceMetrics: function(performanceData) {
            $('#vortex-performance-score').text(performanceData.performance_score + '%');
            $('#vortex-response-time').text(performanceData.total_test_time_ms + 'ms');
            $('#vortex-memory-usage').text(performanceData.memory_usage_mb + 'MB');
            
            // Update performance chart if available
            if (typeof Chart !== 'undefined') {
                this.updatePerformanceChart(performanceData);
            }
        },
        
        /**
         * Start real-time monitoring
         */
        startRealTimeMonitoring: function() {
            setInterval(() => {
                this.checkSystemHealth();
            }, this.config.refreshInterval);
        },
        
        /**
         * Check system health
         */
        checkSystemHealth: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_get_system_status',
                    nonce: vortex_automation_nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateRealTimeStatus(response.data);
                    }
                }
            });
        },
        
        /**
         * Update real-time status indicators
         */
        updateRealTimeStatus: function(statusData) {
            // Update RunPod health indicator
            if (statusData.runpod_health) {
                const runpodHealth = statusData.runpod_health.health_percentage || 0;
                $('#vortex-runpod-realtime').text(runpodHealth + '%')
                    .removeClass('status-good status-warning status-error')
                    .addClass(this.getHealthStatusClass(runpodHealth));
            }
            
            // Update S3 health indicator
            if (statusData.s3_health) {
                const s3Health = statusData.s3_health.health_percentage || 0;
                $('#vortex-s3-realtime').text(s3Health + '%')
                    .removeClass('status-good status-warning status-error')
                    .addClass(this.getHealthStatusClass(s3Health));
            }
            
            // Update system uptime
            if (statusData.system_uptime) {
                $('#vortex-system-uptime').text(statusData.system_uptime);
            }
        },
        
        /**
         * Force system recovery
         */
        forceSystemRecovery: function() {
            if (!confirm('Are you sure you want to force system recovery? This may temporarily interrupt services.')) {
                return;
            }
            
            this.showAlert('Initiating system recovery...', 'info');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_force_system_recovery',
                    nonce: vortex_automation_nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showAlert('✅ System recovery completed successfully', 'success');
                        setTimeout(() => this.runComprehensiveTest(), 2000);
                    } else {
                        this.showAlert('❌ Recovery failed: ' + response.data.message, 'error');
                    }
                }
            });
        },
        
        /**
         * Export test results
         */
        exportTestResults: function() {
            if (!this.state.lastTestResults) {
                this.showAlert('No test results to export', 'warning');
                return;
            }
            
            const dataStr = JSON.stringify(this.state.lastTestResults, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `vortex-automation-test-${new Date().toISOString().slice(0, 10)}.json`;
            link.click();
            
            URL.revokeObjectURL(url);
            this.showAlert('Test results exported successfully', 'success');
        },
        
        /**
         * Utility functions
         */
        updateTestButton: function(text, disabled) {
            $('#vortex-run-automation-test')
                .text(text)
                .prop('disabled', disabled);
        },
        
        showProgressBar: function() {
            $('#vortex-progress-bar').show().find('.progress-fill').width('0%');
            this.animateProgress();
        },
        
        hideProgressBar: function() {
            $('#vortex-progress-bar').hide();
        },
        
        animateProgress: function() {
            const $progressFill = $('.progress-fill');
            let width = 0;
            
            const progressInterval = setInterval(() => {
                if (width >= 100 || !this.state.isRunning) {
                    clearInterval(progressInterval);
                    return;
                }
                width += Math.random() * 10;
                $progressFill.width(Math.min(width, 95) + '%');
            }, 500);
        },
        
        showAlert: function(message, type) {
            const alertClass = `alert-${type}`;
            const alertHtml = `
                <div class="notice notice-${type} is-dismissible vortex-alert">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `;
            
            $('#vortex-alerts-container').prepend(alertHtml);
            
            // Auto-dismiss after 5 seconds for non-error alerts
            if (type !== 'error') {
                setTimeout(() => {
                    $('.vortex-alert').first().fadeOut();
                }, 5000);
            }
        },
        
        formatTestName: function(testName) {
            return testName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },
        
        getHealthStatusClass: function(percentage) {
            if (percentage >= 90) return 'status-good';
            if (percentage >= 70) return 'status-warning';
            return 'status-error';
        },
        
        updateSystemHealth: function(score) {
            this.state.systemHealth = score;
            $('#vortex-overall-health').text(score + '%')
                .removeClass('health-excellent health-good health-warning health-poor')
                .addClass(this.getOverallHealthClass(score));
        },
        
        getOverallHealthClass: function(score) {
            if (score >= 90) return 'health-excellent';
            if (score >= 75) return 'health-good';
            if (score >= 60) return 'health-warning';
            return 'health-poor';
        },
        
        updateRecommendations: function(recommendations) {
            const $container = $('#vortex-recommendations');
            let html = '<h4>System Recommendations:</h4><ul>';
            
            recommendations.forEach(rec => {
                html += `<li>${rec}</li>`;
            });
            
            html += '</ul>';
            $container.html(html);
        },
        
        loadLastTestResults: function() {
            // Load last test results from local storage or server
            const savedResults = localStorage.getItem('vortex_last_test_results');
            if (savedResults) {
                try {
                    this.state.lastTestResults = JSON.parse(savedResults);
                    this.displayTestResults(this.state.lastTestResults);
                } catch (e) {
                    console.warn('Failed to load saved test results:', e);
                }
            }
        },
        
        handleTestError: function(error) {
            this.showAlert('Test execution failed: ' + error, 'error');
            console.error('Automation test error:', error);
        }
    };
    
    // Initialize the automation tester
    VortexAutomationTester.init();
    
    // Make it globally available for debugging
    window.VortexAutomationTester = VortexAutomationTester;
}); 