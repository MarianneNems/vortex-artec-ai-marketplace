/**
 * VORTEX Gradio Integration JavaScript
 * 
 * Handles frontend communication with Gradio AI models
 * Integrates with VORTEX AI agents for real-time predictions
 */

class VortexGradioClient {
    constructor() {
        this.apiUrl = vortex_gradio_vars.ajax_url;
        this.nonce = vortex_gradio_vars.nonce;
        this.gradioUrl = vortex_gradio_vars.gradio_url;
        this.connected = false;
        this.agents = ['huraii', 'cloe', 'horace', 'thorius', 'archer'];
        
        this.init();
    }
    
    init() {
        this.checkConnection();
        this.bindEvents();
        this.initAgentWidgets();
        
        // Auto-reconnect every 5 minutes
        setInterval(() => {
            this.checkConnection();
        }, 300000);
    }
    
    /**
     * Check Gradio connection status
     */
    async checkConnection() {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'vortex_gradio_status',
                    nonce: this.nonce
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.connected = result.data.connected;
                this.updateConnectionStatus(this.connected);
                
                if (this.connected) {
                    console.log('✅ VORTEX Gradio Client: Connected to', this.gradioUrl);
                }
            }
        } catch (error) {
            console.error('❌ VORTEX Gradio Client: Connection check failed', error);
            this.connected = false;
            this.updateConnectionStatus(false);
        }
    }
    
    /**
     * Make prediction with specific agent
     */
    async predict(agent, input, context = {}) {
        if (!this.connected) {
            throw new Error('Gradio client not connected');
        }
        
        if (!this.agents.includes(agent)) {
            throw new Error(`Invalid agent: ${agent}`);
        }
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'vortex_gradio_predict',
                    nonce: this.nonce,
                    agent: agent,
                    input: input,
                    context: JSON.stringify(context)
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.logPrediction(agent, input, result.data);
                return result.data;
            } else {
                throw new Error(result.data || 'Prediction failed');
            }
        } catch (error) {
            console.error(`❌ VORTEX ${agent.toUpperCase()} prediction failed:`, error);
            throw error;
        }
    }
    
    /**
     * HURAII predictions (Learning & Recommendations)
     */
    async huraiiPredict(input, userContext = {}) {
        return await this.predict('huraii', input, {
            user_profile: userContext,
            task: 'recommendation'
        });
    }
    
    /**
     * CLOE predictions (Trend Analysis)
     */
    async cloeAnalyze(input, marketContext = {}) {
        return await this.predict('cloe', input, {
            market_data: marketContext,
            task: 'trend_analysis'
        });
    }
    
    /**
     * HORACE predictions (Content Curation)
     */
    async horaceCurate(input, contentType = 'artwork') {
        return await this.predict('horace', input, {
            type: contentType,
            goals: ['engagement', 'discovery'],
            task: 'content_optimization'
        });
    }
    
    /**
     * THORIUS chat (Platform Guide)
     */
    async thoriusChat(message, conversationHistory = []) {
        return await this.predict('thorius', message, {
            history: conversationHistory,
            task: 'user_assistance'
        });
    }
    
    /**
     * ARCHER orchestration
     */
    async archerOrchestrate(input, systemContext = {}) {
        return await this.predict('archer', input, {
            system_state: systemContext,
            task: 'coordination'
        });
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // THORIUS Chatbot Integration
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('thorius-chat-send')) {
                this.handleThoriusChat(e);
            }
        });
        
        // Art Recommendation Triggers
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('artwork-filter')) {
                this.handleArtworkRecommendations(e);
            }
        });
        
        // Trend Analysis Triggers
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('market-analysis-trigger')) {
                this.handleMarketAnalysis(e);
            }
        });
    }
    
    /**
     * Initialize agent widgets
     */
    initAgentWidgets() {
        // Initialize THORIUS Chatbot
        this.initThoriusChatbot();
        
        // Initialize HURAII Recommendations
        this.initHuraiiRecommendations();
        
        // Initialize CLOE Trend Analysis
        this.initCloeTrendAnalysis();
        
        // Initialize HORACE Content Optimization
        this.initHoraceOptimization();
    }
    
    /**
     * Initialize THORIUS Chatbot Widget
     */
    initThoriusChatbot() {
        const chatbotContainer = document.getElementById('thorius-chatbot');
        if (!chatbotContainer) return;
        
        // Create chat interface if not exists
        if (!chatbotContainer.innerHTML.trim()) {
            chatbotContainer.innerHTML = `
                <div class="thorius-chat-header">
                    <h3>🤖 THORIUS - Your Platform Guide</h3>
                    <span class="connection-status ${this.connected ? 'connected' : 'disconnected'}">
                        ${this.connected ? '🟢 Connected' : '🔴 Disconnected'}
                    </span>
                </div>
                <div class="thorius-chat-messages" id="thorius-messages">
                    <div class="thorius-message bot-message">
                        Hello! I'm THORIUS, your platform guide. How can I help you today?
                    </div>
                </div>
                <div class="thorius-chat-input">
                    <input type="text" id="thorius-input" placeholder="Ask me anything about the platform...">
                    <button class="thorius-chat-send" id="thorius-send">Send</button>
                </div>
            `;
        }
    }
    
    /**
     * Initialize HURAII Recommendations
     */
    initHuraiiRecommendations() {
        const containers = document.querySelectorAll('.huraii-recommendations');
        containers.forEach(container => {
            if (this.connected) {
                this.loadHuraiiRecommendations(container);
            }
        });
    }
    
    /**
     * Initialize CLOE Trend Analysis
     */
    initCloeTrendAnalysis() {
        const containers = document.querySelectorAll('.cloe-trends');
        containers.forEach(container => {
            if (this.connected) {
                this.loadCloeTrends(container);
            }
        });
    }
    
    /**
     * Initialize HORACE Content Optimization
     */
    initHoraceOptimization() {
        const containers = document.querySelectorAll('.horace-optimization');
        containers.forEach(container => {
            if (this.connected) {
                this.loadHoraceOptimizations(container);
            }
        });
    }
    
    /**
     * Handle THORIUS chat interaction
     */
    async handleThoriusChat(event) {
        const input = document.getElementById('thorius-input');
        const messages = document.getElementById('thorius-messages');
        
        if (!input || !messages) return;
        
        const message = input.value.trim();
        if (!message) return;
        
        // Add user message
        this.addChatMessage(messages, message, 'user');
        input.value = '';
        
        // Show typing indicator
        const typingIndicator = this.addTypingIndicator(messages);
        
        try {
            const response = await this.thoriusChat(message);
            
            // Remove typing indicator
            typingIndicator.remove();
            
            // Add bot response
            this.addChatMessage(messages, response.response || response, 'bot');
            
            // Handle actions if provided
            if (response.actions && response.actions.length > 0) {
                this.addChatActions(messages, response.actions);
            }
            
        } catch (error) {
            typingIndicator.remove();
            this.addChatMessage(messages, 'Sorry, I encountered an error. Please try again.', 'bot error');
        }
    }
    
    /**
     * Add chat message to conversation
     */
    addChatMessage(container, message, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `thorius-message ${type}-message`;
        messageDiv.textContent = message;
        container.appendChild(messageDiv);
        container.scrollTop = container.scrollHeight;
        return messageDiv;
    }
    
    /**
     * Add typing indicator
     */
    addTypingIndicator(container) {
        const indicator = document.createElement('div');
        indicator.className = 'thorius-message bot-message typing';
        indicator.innerHTML = 'THORIUS is thinking<span class="dots">...</span>';
        container.appendChild(indicator);
        container.scrollTop = container.scrollHeight;
        return indicator;
    }
    
    /**
     * Update connection status UI
     */
    updateConnectionStatus(connected) {
        const statusElements = document.querySelectorAll('.connection-status');
        statusElements.forEach(element => {
            element.className = `connection-status ${connected ? 'connected' : 'disconnected'}`;
            element.textContent = connected ? '🟢 Connected' : '🔴 Disconnected';
        });
        
        // Enable/disable AI features based on connection
        const aiFeatures = document.querySelectorAll('.ai-feature');
        aiFeatures.forEach(feature => {
            if (connected) {
                feature.classList.remove('disabled');
            } else {
                feature.classList.add('disabled');
            }
        });
    }
    
    /**
     * Load HURAII recommendations
     */
    async loadHuraiiRecommendations(container) {
        try {
            const userPreferences = this.getUserPreferences();
            const recommendations = await this.huraiiPredict('get_recommendations', userPreferences);
            
            if (recommendations && recommendations.recommendations) {
                container.innerHTML = this.renderRecommendations(recommendations);
            }
        } catch (error) {
            console.error('Failed to load HURAII recommendations:', error);
        }
    }
    
    /**
     * Load CLOE trends
     */
    async loadCloeTrends(container) {
        try {
            const trends = await this.cloeAnalyze('current_trends');
            
            if (trends && trends.trends) {
                container.innerHTML = this.renderTrends(trends);
            }
        } catch (error) {
            console.error('Failed to load CLOE trends:', error);
        }
    }
    
    /**
     * Render recommendations HTML
     */
    renderRecommendations(recommendations) {
        return `
            <div class="huraii-recommendations-list">
                <h4>🎨 Recommended for You</h4>
                ${recommendations.recommendations.map(rec => `
                    <div class="recommendation-item">
                        <span class="rec-title">${rec.title || rec}</span>
                        <span class="rec-confidence">${Math.round((recommendations.confidence || 0.8) * 100)}%</span>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    /**
     * Render trends HTML
     */
    renderTrends(trends) {
        return `
            <div class="cloe-trends-list">
                <h4>📈 Market Trends</h4>
                ${trends.trends.map(trend => `
                    <div class="trend-item">
                        <span class="trend-name">${trend.name || trend}</span>
                        <span class="trend-growth">+${trend.growth || '12'}%</span>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    /**
     * Get user preferences for personalization
     */
    getUserPreferences() {
        // Try to get from localStorage or user meta
        const saved = localStorage.getItem('vortex_user_preferences');
        return saved ? JSON.parse(saved) : {};
    }
    
    /**
     * Log prediction for analytics
     */
    logPrediction(agent, input, output) {
        if (typeof console !== 'undefined' && console.log) {
            console.log(`🤖 VORTEX ${agent.toUpperCase()}:`, {
                input: input,
                output: output,
                timestamp: new Date().toISOString()
            });
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof vortex_gradio_vars !== 'undefined') {
        window.VortexGradio = new VortexGradioClient();
        
        // Make client available globally
        window.vortexPredict = {
            huraii: (input, context) => window.VortexGradio.huraiiPredict(input, context),
            cloe: (input, context) => window.VortexGradio.cloeAnalyze(input, context),
            horace: (input, context) => window.VortexGradio.horaceCurate(input, context),
            thorius: (input, history) => window.VortexGradio.thoriusChat(input, history),
            archer: (input, context) => window.VortexGradio.archerOrchestrate(input, context)
        };
        
        console.log('🚀 VORTEX Gradio Integration initialized');
    }
}); 