/**
 * VORTEX Metrics Interface JavaScript
 * 
 * Real-time dashboard with Chart.js integration, live updates, and interactive controls
 */

class VortexMetricsInterface {
    constructor() {
        this.config = vortex_metrics;
        this.charts = new Map();
        this.updateInterval = null;
        this.isRealTimeActive = true;
        this.currentTimeRange = '24h';
        this.animationQueue = [];
        
        // Chart color schemes
        this.colors = {
            primary: '#00ff41',
            secondary: '#ff6b35',
            tertiary: '#4ecdc4',
            quaternary: '#45b7d1',
            success: '#32cd32',
            warning: '#ffed4a',
            danger: '#ff4444',
            dark: '#1a1a1a',
            light: '#ffffff'
        };
        
        // Performance data cache
        this.dataCache = new Map();
        this.lastUpdate = 0;
    }
    
    init() {
        this.setupEventListeners();
        this.initializeCharts();
        this.startRealTimeUpdates();
        this.setupAnimations();
        this.loadInitialData();
        
        console.log('VORTEX Metrics Interface initialized');
    }
    
    // === EVENT LISTENERS ===
    
    setupEventListeners() {
        // Dashboard control buttons
        jQuery(document).on('click', '#refresh-metrics', () => {
            this.refreshAllData();
        });
        
        jQuery(document).on('click', '#export-metrics', () => {
            this.exportMetrics();
        });
        
        jQuery(document).on('click', '#toggle-realtime', (e) => {
            this.toggleRealTime(e.target);
        });
        
        // Time range selector
        jQuery(document).on('change', '#time-range', (e) => {
            this.updateTimeRange(e.target.value);
        });
        
        // Card interactions
        jQuery(document).on('click', '.metric-card', (e) => {
            this.handleCardClick(e.currentTarget);
        });
        
        // Performance metrics hover effects
        jQuery(document).on('mouseenter', '.agent-status', (e) => {
            this.showAgentDetails(e.currentTarget);
        });
        
        jQuery(document).on('mouseleave', '.agent-status', (e) => {
            this.hideAgentDetails(e.currentTarget);
        });
        
        // Responsive handling
        jQuery(window).on('resize', () => {
            this.handleResize();
        });
        
        // Keyboard shortcuts
        jQuery(document).on('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    }
    
    // === CHART INITIALIZATION ===
    
    initializeCharts() {
        this.initBehaviorChart();
        this.initUserAnalyticsChart();
        this.initAIPerformanceChart();
        this.initBehaviorHeatmap();
        this.updateScoreRings();
    }
    
    initBehaviorChart() {
        const ctx = jQuery('#behavior-chart')[0];
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.generateTimeLabels('24h'),
                datasets: [{
                    label: 'User Activity',
                    data: this.generateMockData(24),
                    borderColor: this.colors.primary,
                    backgroundColor: this.hexToRgba(this.colors.primary, 0.1),
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: this.colors.primary,
                    pointBorderColor: this.colors.dark,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#888'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#888'
                        }
                    }
                },
                elements: {
                    point: {
                        hoverRadius: 8
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
        
        this.charts.set('behavior', chart);
    }
    
    initUserAnalyticsChart() {
        const ctx = jQuery('#user-analytics-chart')[0];
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Artworks Created', 'NFTs Purchased', 'Community Interactions', 'Learning Progress'],
                datasets: [{
                    data: [35, 25, 20, 20],
                    backgroundColor: [
                        this.colors.primary,
                        this.colors.secondary,
                        this.colors.tertiary,
                        this.colors.quaternary
                    ],
                    borderWidth: 0,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#fff',
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 2000
                }
            }
        });
        
        this.charts.set('userAnalytics', chart);
    }
    
    initAIPerformanceChart() {
        const ctx = jQuery('#ai-performance-chart')[0];
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Response Time', 'Accuracy', 'Load Efficiency', 'User Satisfaction', 'Resource Usage'],
                datasets: [
                    {
                        label: 'HURAII (GPU)',
                        data: [85, 92, 78, 88, 75],
                        borderColor: this.colors.secondary,
                        backgroundColor: this.hexToRgba(this.colors.secondary, 0.2),
                        pointBackgroundColor: this.colors.secondary,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: this.colors.secondary
                    },
                    {
                        label: 'CPU Agents Avg',
                        data: [92, 88, 85, 90, 88],
                        borderColor: this.colors.tertiary,
                        backgroundColor: this.hexToRgba(this.colors.tertiary, 0.2),
                        pointBackgroundColor: this.colors.tertiary,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: this.colors.tertiary
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                },
                scales: {
                    r: {
                        angleLines: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        pointLabels: {
                            color: '#888'
                        },
                        ticks: {
                            color: '#888',
                            backdropColor: 'transparent'
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
        
        this.charts.set('aiPerformance', chart);
    }
    
    initBehaviorHeatmap() {
        const container = jQuery('#behavior-heatmap-container');
        if (!container.length) return;
        
        // Create a simple heatmap visualization
        const heatmapData = this.generateHeatmapData();
        let heatmapHTML = '<div class="heatmap-grid">';
        
        for (let hour = 0; hour < 24; hour++) {
            for (let day = 0; day < 7; day++) {
                const intensity = heatmapData[hour][day];
                const color = this.getHeatmapColor(intensity);
                
                heatmapHTML += `
                    <div class="heatmap-cell" 
                         data-hour="${hour}" 
                         data-day="${day}" 
                         data-intensity="${intensity}"
                         style="background-color: ${color}"
                         title="Day ${day + 1}, Hour ${hour}:00 - Activity: ${intensity}%">
                    </div>
                `;
            }
        }
        
        heatmapHTML += '</div>';
        container.html(heatmapHTML);
        
        // Add CSS for heatmap
        if (!jQuery('#heatmap-styles').length) {
            jQuery('head').append(`
                <style id="heatmap-styles">
                .heatmap-grid {
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                    grid-template-rows: repeat(24, 1fr);
                    gap: 2px;
                    height: 200px;
                    width: 100%;
                }
                .heatmap-cell {
                    border-radius: 2px;
                    transition: all 0.3s ease;
                    cursor: pointer;
                }
                .heatmap-cell:hover {
                    transform: scale(1.2);
                    z-index: 10;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
                }
                </style>
            `);
        }
    }
    
    updateScoreRings() {
        jQuery('.score-ring').each((i, element) => {
            const ring = jQuery(element);
            const score = parseInt(ring.find('[data-score]').attr('data-score') || 0);
            const circle = ring.find('.circle');
            
            // Animate the progress ring
            setTimeout(() => {
                circle.css('stroke-dasharray', `${score}, 100`);
            }, i * 200);
        });
    }
    
    // === REAL-TIME UPDATES ===
    
    startRealTimeUpdates() {
        if (this.config.realtime && this.isRealTimeActive) {
            this.updateInterval = setInterval(() => {
                this.fetchLatestData();
            }, this.config.update_interval);
        }
    }
    
    stopRealTimeUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }
    
    toggleRealTime(button) {
        this.isRealTimeActive = !this.isRealTimeActive;
        const btn = jQuery(button);
        
        if (this.isRealTimeActive) {
            btn.addClass('active').attr('data-active', 'true');
            this.startRealTimeUpdates();
            this.showNotification('Real-time updates enabled', 'success');
        } else {
            btn.removeClass('active').attr('data-active', 'false');
            this.stopRealTimeUpdates();
            this.showNotification('Real-time updates disabled', 'warning');
        }
    }
    
    fetchLatestData() {
        jQuery.ajax({
            url: this.config.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_metrics_data',
                nonce: this.config.nonce,
                time_range: this.currentTimeRange,
                user_id: this.config.user_id
            },
            success: (response) => {
                if (response.success) {
                    this.updateDashboard(response.data);
                    this.updateTimestamp();
                }
            },
            error: (xhr, status, error) => {
                console.error('Failed to fetch metrics data:', error);
                this.showNotification('Failed to update data', 'error');
            }
        });
    }
    
    // === DATA UPDATES ===
    
    updateDashboard(data) {
        this.updateUserStats(data.user_stats);
        this.updateAIAgentsStatus(data.ai_performance);
        this.updateBehaviorData(data.behavior_data);
        this.updateSystemPerformance(data.system_stats);
        this.updateGamificationScores(data.gamification_scores);
        this.updateActivityFeed(data.recent_activities);
    }
    
    updateUserStats(stats) {
        if (!stats) return;
        
        // Update user overview card
        jQuery('.stat-value').each((i, element) => {
            const el = jQuery(element);
            const label = el.siblings('.stat-label').text().toLowerCase();
            
            if (label.includes('score') && stats.total_score) {
                this.animateNumber(el, stats.total_score);
            }
        });
    }
    
    updateAIAgentsStatus(performance) {
        if (!performance) return;
        
        Object.keys(performance).forEach(agent => {
            const agentData = performance[agent];
            const agentElement = jQuery(`.agent-status[data-agent="${agent}"]`);
            
            if (agentElement.length) {
                // Update load percentage
                const loadElement = agentElement.find(`#${agent.toLowerCase()}-load`);
                if (loadElement.length) {
                    this.animateNumber(loadElement, agentData.load, '%');
                }
                
                // Update response time
                const responseElement = agentElement.find(`#${agent.toLowerCase()}-response`);
                if (responseElement.length) {
                    this.animateNumber(responseElement, agentData.response_time, 's');
                }
                
                // Update connection status
                const statusElement = agentElement.find('.connection-indicator');
                statusElement.removeClass('connected disconnected')
                           .addClass(agentData.connected ? 'connected' : 'disconnected');
            }
        });
    }
    
    updateBehaviorData(data) {
        if (!data || !this.charts.has('behavior')) return;
        
        const chart = this.charts.get('behavior');
        const newData = Array.isArray(data.activity_points) ? 
                       data.activity_points : 
                       this.generateMockData(24);
        
        chart.data.datasets[0].data = newData;
        chart.update('none');
    }
    
    updateSystemPerformance(stats) {
        if (!stats) return;
        
        // Update performance bars
        const metrics = ['gpu', 'cpu', 'memory', 'network'];
        
        metrics.forEach(metric => {
            const value = stats[`${metric}_usage`] || 0;
            const barElement = jQuery(`.bar-fill.${metric}`);
            const valueElement = barElement.closest('.perf-metric').find('.metric-value');
            
            if (barElement.length) {
                barElement.css('width', `${value}%`);
                valueElement.text(`${value}%`);
            }
        });
        
        // Update system health indicator
        const healthScore = Math.round((100 - Math.max(stats.gpu_usage || 0, stats.cpu_usage || 0)));
        jQuery('#system-health').text(`${healthScore}%`);
    }
    
    updateGamificationScores(scores) {
        if (!scores) return;
        
        // Update level badge
        if (scores.level) {
            jQuery('.level-badge').text(`Level ${scores.level}`);
        }
        
        // Update score rings
        jQuery('.score-ring').each((i, element) => {
            const ring = jQuery(element);
            const category = ring.attr('data-category');
            
            if (scores[category]) {
                const circle = ring.find('.circle');
                const percentage = ring.find('.percentage');
                const newScore = scores[category];
                
                circle.css('stroke-dasharray', `${newScore}, 100`);
                percentage.text(`${newScore}%`);
            }
        });
    }
    
    updateActivityFeed(activities) {
        if (!activities || !Array.isArray(activities)) return;
        
        const feedElement = jQuery('#activity-feed');
        feedElement.empty();
        
        activities.slice(0, 10).forEach(activity => {
            const activityHTML = `
                <div class="activity-item" data-type="${activity.type}">
                    <div class="activity-icon">${this.getActivityIcon(activity.type)}</div>
                    <div class="activity-content">
                        <div class="activity-text">${activity.message}</div>
                        <div class="activity-time">${this.formatRelativeTime(activity.timestamp)}</div>
                    </div>
                </div>
            `;
            feedElement.append(activityHTML);
        });
        
        // Update activity count
        jQuery('#activity-count').text(`${activities.length} events`);
    }
    
    // === USER INTERACTIONS ===
    
    handleCardClick(card) {
        const cardType = jQuery(card).attr('data-card');
        
        // Add click animation
        jQuery(card).addClass('card-clicked');
        setTimeout(() => {
            jQuery(card).removeClass('card-clicked');
        }, 200);
        
        // Handle specific card actions
        switch (cardType) {
            case 'ai-status':
                this.showAIDetailsModal();
                break;
            case 'behavior':
                this.expandBehaviorChart();
                break;
            case 'gamification':
                this.showGamificationDetails();
                break;
            case 'performance':
                this.showPerformanceDetails();
                break;
        }
    }
    
    showAgentDetails(agentElement) {
        const agent = jQuery(agentElement).attr('data-agent');
        const tooltip = jQuery(`
            <div class="agent-tooltip" id="tooltip-${agent}">
                <div class="tooltip-header">${agent} Details</div>
                <div class="tooltip-content">
                    <div>Status: Online</div>
                    <div>Uptime: 99.8%</div>
                    <div>Requests: 1,247</div>
                    <div>Avg Response: 0.9s</div>
                </div>
            </div>
        `);
        
        jQuery('body').append(tooltip);
        
        // Position tooltip
        const rect = agentElement.getBoundingClientRect();
        tooltip.css({
            top: rect.top - tooltip.outerHeight() - 10,
            left: rect.left + (rect.width / 2) - (tooltip.outerWidth() / 2)
        });
        
        tooltip.fadeIn(200);
    }
    
    hideAgentDetails(agentElement) {
        const agent = jQuery(agentElement).attr('data-agent');
        jQuery(`#tooltip-${agent}`).fadeOut(200, function() {
            jQuery(this).remove();
        });
    }
    
    updateTimeRange(range) {
        this.currentTimeRange = range;
        this.refreshAllData();
        
        // Update chart time labels
        this.charts.forEach(chart => {
            if (chart.data && chart.data.labels) {
                chart.data.labels = this.generateTimeLabels(range);
                chart.update();
            }
        });
    }
    
    refreshAllData() {
        this.showLoadingState();
        this.fetchLatestData();
        
        setTimeout(() => {
            this.hideLoadingState();
            this.showNotification('Data refreshed successfully', 'success');
        }, 1000);
    }
    
    exportMetrics() {
        jQuery.ajax({
            url: this.config.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_export_metrics',
                nonce: this.config.nonce,
                user_id: this.config.user_id,
                format: 'json'
            },
            success: (response) => {
                if (response.success) {
                    // Create download link
                    const link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    this.showNotification('Metrics exported successfully', 'success');
                } else {
                    this.showNotification('Export failed', 'error');
                }
            },
            error: () => {
                this.showNotification('Export failed', 'error');
            }
        });
    }
    
    // === UTILITY METHODS ===
    
    animateNumber(element, targetValue, suffix = '') {
        const startValue = parseInt(element.text()) || 0;
        const duration = 1000;
        const startTime = Date.now();
        
        const animate = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentValue = Math.round(startValue + (targetValue - startValue) * this.easeOutCubic(progress));
            element.text(currentValue + suffix);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        animate();
    }
    
    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
    
    hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    
    generateTimeLabels(range) {
        const labels = [];
        let count, unit;
        
        switch (range) {
            case '1h':
                count = 12;
                unit = 'minutes';
                for (let i = 0; i < count; i++) {
                    labels.push(`${i * 5}m`);
                }
                break;
            case '24h':
                count = 24;
                unit = 'hours';
                for (let i = 0; i < count; i++) {
                    labels.push(`${i}:00`);
                }
                break;
            case '7d':
                count = 7;
                unit = 'days';
                const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                labels.push(...days);
                break;
            case '30d':
                count = 30;
                unit = 'days';
                for (let i = 1; i <= count; i++) {
                    labels.push(`Day ${i}`);
                }
                break;
        }
        
        return labels;
    }
    
    generateMockData(count) {
        return Array.from({length: count}, () => Math.floor(Math.random() * 100));
    }
    
    generateHeatmapData() {
        const data = [];
        for (let hour = 0; hour < 24; hour++) {
            data[hour] = [];
            for (let day = 0; day < 7; day++) {
                // Simulate higher activity during work hours and weekdays
                let intensity = Math.random() * 50;
                if (hour >= 9 && hour <= 17 && day < 5) {
                    intensity += Math.random() * 40;
                }
                data[hour][day] = Math.round(intensity);
            }
        }
        return data;
    }
    
    getHeatmapColor(intensity) {
        const alpha = intensity / 100;
        return `rgba(0, 255, 65, ${alpha})`;
    }
    
    getActivityIcon(type) {
        const icons = {
            'artwork_created': '🎨',
            'nft_purchased': '💎',
            'comment_posted': '💬',
            'vote_cast': '🗳️',
            'achievement_unlocked': '🏆',
            'default': '📊'
        };
        return icons[type] || icons.default;
    }
    
    formatRelativeTime(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000);
        
        if (diff < 60) return `${diff}s ago`;
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        return `${Math.floor(diff / 86400)}d ago`;
    }
    
    updateTimestamp() {
        const now = new Date();
        jQuery('#last-update-time').text(now.toLocaleTimeString());
    }
    
    showNotification(message, type = 'info') {
        const notification = jQuery(`
            <div class="metrics-notification ${type}">
                ${message}
            </div>
        `);
        
        jQuery('body').append(notification);
        
        setTimeout(() => {
            notification.addClass('show');
        }, 100);
        
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    showLoadingState() {
        jQuery('.metrics-grid').addClass('loading');
    }
    
    hideLoadingState() {
        jQuery('.metrics-grid').removeClass('loading');
    }
    
    setupAnimations() {
        // Intersection Observer for animations
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        jQuery(entry.target).addClass('animate-in');
                    }
                });
            });
            
            jQuery('.metric-card').each((i, card) => {
                observer.observe(card);
            });
        }
    }
    
    handleResize() {
        // Redraw charts on resize
        this.charts.forEach(chart => {
            chart.resize();
        });
    }
    
    handleKeyboardShortcuts(e) {
        // Ctrl+R: Refresh data
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            this.refreshAllData();
        }
        
        // Ctrl+E: Export data
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            this.exportMetrics();
        }
        
        // Ctrl+T: Toggle real-time
        if (e.ctrlKey && e.key === 't') {
            e.preventDefault();
            jQuery('#toggle-realtime').click();
        }
    }
    
    loadInitialData() {
        // Load initial data and setup charts
        this.fetchLatestData();
    }
    
    // Modal functions (placeholders for future implementation)
    showAIDetailsModal() {
        console.log('AI Details Modal - To be implemented');
    }
    
    expandBehaviorChart() {
        console.log('Expand Behavior Chart - To be implemented');
    }
    
    showGamificationDetails() {
        console.log('Gamification Details - To be implemented');
    }
    
    showPerformanceDetails() {
        console.log('Performance Details - To be implemented');
    }
}

// Auto-initialize when DOM is ready
jQuery(document).ready(function() {
    if (typeof vortex_metrics !== 'undefined') {
        window.vortexMetrics = new VortexMetricsInterface();
        window.vortexMetrics.init();
    }
});

// Global metrics commands
window.VortexMetricsCommands = {
    refreshData: () => {
        if (window.vortexMetrics) {
            window.vortexMetrics.refreshAllData();
        }
    },
    
    toggleRealTime: () => {
        if (window.vortexMetrics) {
            jQuery('#toggle-realtime').click();
        }
    },
    
    exportData: () => {
        if (window.vortexMetrics) {
            window.vortexMetrics.exportMetrics();
        }
    },
    
    updateTimeRange: (range) => {
        if (window.vortexMetrics) {
            window.vortexMetrics.updateTimeRange(range);
        }
    }
};

// Additional CSS for animations and notifications
jQuery(document).ready(function() {
    if (!jQuery('#metrics-animations-css').length) {
        jQuery('head').append(`
            <style id="metrics-animations-css">
            .metrics-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 6px;
                color: #fff;
                font-weight: bold;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            }
            
            .metrics-notification.show {
                transform: translateX(0);
            }
            
            .metrics-notification.success {
                background: linear-gradient(135deg, #00ff41, #32cd32);
                color: #000;
            }
            
            .metrics-notification.error {
                background: linear-gradient(135deg, #ff4444, #ff6b6b);
            }
            
            .metrics-notification.warning {
                background: linear-gradient(135deg, #ffed4a, #ffd93d);
                color: #000;
            }
            
            .metrics-notification.info {
                background: linear-gradient(135deg, #45b7d1, #74c0fc);
            }
            
            .metric-card {
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.6s ease;
            }
            
            .metric-card.animate-in {
                opacity: 1;
                transform: translateY(0);
            }
            
            .metric-card.card-clicked {
                transform: scale(0.98);
            }
            
            .metrics-grid.loading::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 40px;
                height: 40px;
                border: 3px solid #333;
                border-top: 3px solid #00ff41;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                transform: translate(-50%, -50%);
            }
            
            .agent-tooltip {
                position: fixed;
                background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
                border: 1px solid #333;
                border-radius: 8px;
                padding: 12px;
                color: #fff;
                font-size: 11px;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                display: none;
            }
            
            .tooltip-header {
                font-weight: bold;
                margin-bottom: 8px;
                color: #00ff41;
            }
            
            .tooltip-content div {
                margin-bottom: 4px;
            }
            
            @keyframes spin {
                0% { transform: translate(-50%, -50%) rotate(0deg); }
                100% { transform: translate(-50%, -50%) rotate(360deg); }
            }
            </style>
        `);
    }
}); 