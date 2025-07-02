/**
 * VORTEX AI Terminal Interface JavaScript
 * Multi-agent terminal with GPU/CPU resource allocation
 */

class VortexAITerminal {
    constructor() {
        this.agents = vortex_ai_terminal.agents;
        this.activeQueries = new Map();
        this.connectionStatus = new Map();
        this.commandHistory = new Map();
        this.currentHistoryIndex = new Map();
        this.resizeObserver = null;
        
        // Initialize all agents
        Object.keys(this.agents).forEach(agent => {
            this.connectionStatus.set(agent, true);
            this.commandHistory.set(agent, []);
            this.currentHistoryIndex.set(agent, -1);
        });
    }
    
    init() {
        this.setupEventListeners();
        this.initializeResizing();
        this.startStatusMonitoring();
        this.setupKeyboardShortcuts();
        this.loadSessionHistory();
        
        console.log('VORTEX AI Terminal initialized with agents:', Object.keys(this.agents));
    }
    
    setupEventListeners() {
        // Terminal input handling
        jQuery(document).on('keydown', '.terminal-input', (e) => {
            if (e.key === 'Enter') {
                this.handleQuery(e.target);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.navigateHistory(e.target, 'up');
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.navigateHistory(e.target, 'down');
            } else if (e.key === 'Tab') {
                e.preventDefault();
                this.handleAutoComplete(e.target);
            }
        });
        
        // Terminal control buttons
        jQuery(document).on('click', '.terminal-btn.minimize', () => {
            this.minimizeTerminal();
        });
        
        jQuery(document).on('click', '.terminal-btn.maximize', () => {
            this.maximizeTerminal();
        });
        
        jQuery(document).on('click', '.terminal-btn.close', () => {
            this.closeTerminal();
        });
        
        // Terminal action buttons
        jQuery(document).on('click', '#clear-all', () => {
            this.clearAllTerminals();
        });
        
        jQuery(document).on('click', '#save-session', () => {
            this.saveSession();
        });
        
        jQuery(document).on('click', '#export-log', () => {
            this.exportLog();
        });
        
        // Window resizing
        jQuery(document).on('mousedown', '.resize-btn', (e) => {
            this.startResize(e);
        });
    }
    
    handleQuery(inputElement) {
        const agent = inputElement.dataset.agent;
        const query = inputElement.value.trim();
        
        if (!query) return;
        
        // Add to history
        const history = this.commandHistory.get(agent);
        history.push(query);
        this.currentHistoryIndex.set(agent, history.length);
        
        // Display user input
        this.addMessage(agent, 'user', query);
        
        // Clear input
        inputElement.value = '';
        
        // Show loading indicator
        this.showLoadingIndicator(agent);
        
        // Send to AI agent
        this.sendToAgent(agent, query);
    }
    
    sendToAgent(agent, query) {
        const startTime = Date.now();
        this.activeQueries.set(agent, startTime);
        
        jQuery.ajax({
            url: vortex_ai_terminal.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_ai_terminal_query',
                nonce: vortex_ai_terminal.nonce,
                agent: agent,
                query: query,
                session_id: vortex_ai_terminal.session_id
            },
            success: (response) => {
                this.hideLoadingIndicator(agent);
                
                if (response.success) {
                    this.addMessage(agent, 'ai', response.data.response, {
                        processing_time: response.data.processing_time,
                        hardware_usage: response.data.hardware_usage,
                        timestamp: response.data.timestamp
                    });
                    
                    // Update hardware usage display
                    this.updateHardwareUsage(agent, response.data.hardware_usage);
                } else {
                    this.addMessage(agent, 'error', response.data.message || 'Request failed');
                }
                
                this.activeQueries.delete(agent);
            },
            error: (xhr, status, error) => {
                this.hideLoadingIndicator(agent);
                this.addMessage(agent, 'error', `Connection error: ${error}`);
                this.updateConnectionStatus(agent, false);
                this.activeQueries.delete(agent);
            },
            timeout: this.agents[agent].timeout * 1000
        });
    }
    
    addMessage(agent, type, content, metadata = {}) {
        const outputElement = jQuery(`#${agent.toLowerCase()}-output, #${agent.toLowerCase()}-single-output`);
        const timestamp = new Date().toLocaleTimeString();
        
        let messageClass = '';
        let prefix = '';
        
        switch (type) {
            case 'user':
                messageClass = 'user-message';
                prefix = `${agent}@USER:~$ `;
                break;
            case 'ai':
                messageClass = 'ai-response';
                prefix = `${agent}: `;
                break;
            case 'error':
                messageClass = 'error-message';
                prefix = `ERROR: `;
                break;
            case 'system':
                messageClass = 'system-message';
                prefix = 'SYSTEM: ';
                break;
        }
        
        const messageHtml = `
            <div class="${messageClass}">
                <span class="timestamp">[${timestamp}]</span>
                <span class="message-prefix">${prefix}</span>
                <span class="message-content">${this.formatMessage(content)}</span>
                ${metadata.processing_time ? `<span class="processing-time">(${Math.round(metadata.processing_time * 1000)}ms)</span>` : ''}
            </div>
        `;
        
        outputElement.append(messageHtml);
        outputElement.scrollTop(outputElement[0].scrollHeight);
        
        // Add typing animation for AI responses
        if (type === 'ai') {
            this.animateTyping(outputElement.find('.message-content').last());
        }
    }
    
    formatMessage(content) {
        // Handle different content types
        if (typeof content === 'object') {
            return `<pre>${JSON.stringify(content, null, 2)}</pre>`;
        }
        
        // Convert markdown-like formatting
        content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        content = content.replace(/\*(.*?)\*/g, '<em>$1</em>');
        content = content.replace(/`(.*?)`/g, '<code>$1</code>');
        
        // Convert URLs to links
        content = content.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
        
        return content;
    }
    
    animateTyping(element) {
        const text = element.text();
        element.text('');
        
        let i = 0;
        const typeInterval = setInterval(() => {
            element.text(text.substring(0, i));
            i++;
            
            if (i > text.length) {
                clearInterval(typeInterval);
            }
        }, 20);
    }
    
    showLoadingIndicator(agent) {
        const outputElement = jQuery(`#${agent.toLowerCase()}-output, #${agent.toLowerCase()}-single-output`);
        const loadingHtml = `
            <div class="loading-indicator" id="${agent}-loading">
                <span class="timestamp">[${new Date().toLocaleTimeString()}]</span>
                <span class="message-prefix">${agent}: </span>
                <span class="loading-dots">Processing...</span>
            </div>
        `;
        
        outputElement.append(loadingHtml);
        outputElement.scrollTop(outputElement[0].scrollHeight);
        
        // Animate loading dots
        this.animateLoadingDots(agent);
    }
    
    hideLoadingIndicator(agent) {
        jQuery(`#${agent}-loading`).remove();
    }
    
    animateLoadingDots(agent) {
        const dotsElement = jQuery(`#${agent}-loading .dots`);
        let dotCount = 0;
        
        const interval = setInterval(() => {
            if (!jQuery(`#${agent}-loading`).length) {
                clearInterval(interval);
                return;
            }
            
            dotCount = (dotCount + 1) % 4;
            dotsElement.text('.'.repeat(dotCount));
        }, 500);
    }
    
    navigateHistory(inputElement, direction) {
        const agent = inputElement.dataset.agent;
        const history = this.commandHistory.get(agent);
        let currentIndex = this.currentHistoryIndex.get(agent);
        
        if (direction === 'up' && currentIndex > 0) {
            currentIndex--;
        } else if (direction === 'down' && currentIndex < history.length - 1) {
            currentIndex++;
        } else if (direction === 'down' && currentIndex === history.length - 1) {
            currentIndex = history.length;
            inputElement.value = '';
            this.currentHistoryIndex.set(agent, currentIndex);
            return;
        }
        
        if (currentIndex >= 0 && currentIndex < history.length) {
            inputElement.value = history[currentIndex];
            this.currentHistoryIndex.set(agent, currentIndex);
        }
    }
    
    handleAutoComplete(inputElement) {
        const agent = inputElement.dataset.agent;
        const currentValue = inputElement.value;
        
        // Simple autocomplete for common commands
        const suggestions = this.getAutoCompleteSuggestions(agent, currentValue);
        
        if (suggestions.length === 1) {
            inputElement.value = suggestions[0];
        } else if (suggestions.length > 1) {
            this.showAutoCompleteSuggestions(agent, suggestions);
        }
    }
    
    getAutoCompleteSuggestions(agent, input) {
        const commonCommands = {
            'HURAII': ['generate', 'create', 'art', 'image', 'style', 'prompt'],
            'CLOE': ['analyze', 'market', 'trend', 'price', 'collector', 'demand'],
            'HORACE': ['optimize', 'content', 'seo', 'keywords', 'title', 'description'],
            'THORIUS': ['help', 'guide', 'tutorial', 'steps', 'advice', 'recommend']
        };
        
        const commands = commonCommands[agent] || [];
        return commands.filter(cmd => cmd.startsWith(input.toLowerCase()));
    }
    
    showAutoCompleteSuggestions(agent, suggestions) {
        const outputElement = jQuery(`#${agent.toLowerCase()}-output, #${agent.toLowerCase()}-single-output`);
        const suggestionHtml = `
            <div class="autocomplete-suggestions">
                <span class="timestamp">[${new Date().toLocaleTimeString()}]</span>
                <span class="suggestions-label">Suggestions: </span>
                ${suggestions.map(s => `<span class="suggestion">${s}</span>`).join(', ')}
            </div>
        `;
        
        outputElement.append(suggestionHtml);
        outputElement.scrollTop(outputElement[0].scrollHeight);
    }
    
    updateConnectionStatus(agent, connected) {
        this.connectionStatus.set(agent, connected);
        
        const statusElement = jQuery(`.terminal-window[data-agent="${agent}"] .connection-status`);
        statusElement.removeClass('connected disconnected')
                   .addClass(connected ? 'connected' : 'disconnected');
        
        if (!connected) {
            this.addMessage(agent, 'system', 'Connection lost. Attempting to reconnect...');
            setTimeout(() => this.testConnection(agent), 5000);
        }
    }
    
    testConnection(agent) {
        jQuery.ajax({
            url: vortex_ai_terminal.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_ai_terminal_status',
                nonce: vortex_ai_terminal.nonce,
                agent: agent
            },
            success: (response) => {
                if (response.success) {
                    this.updateConnectionStatus(agent, true);
                    this.addMessage(agent, 'system', 'Connection restored.');
                }
            },
            error: () => {
                setTimeout(() => this.testConnection(agent), 10000);
            }
        });
    }
    
    updateHardwareUsage(agent, usage) {
        if (!usage || typeof usage !== 'object') return;
        
        // Update individual agent hardware display (if exists)
        const agentWindow = jQuery(`.terminal-window[data-agent="${agent}"]`);
        
        // Update global system stats
        if (usage.gpu_usage !== undefined) {
            jQuery('.gpu-usage').css('width', `${usage.gpu_usage}%`);
            jQuery('.stat-value').eq(0).text(`${usage.gpu_usage}%`);
        }
        
        if (usage.cpu_usage !== undefined) {
            jQuery('.cpu-usage').css('width', `${usage.cpu_usage}%`);
            jQuery('.stat-value').eq(1).text(`${usage.cpu_usage}%`);
        }
        
        if (usage.memory_usage !== undefined) {
            jQuery('.memory-usage').css('width', `${usage.memory_usage}%`);
            jQuery('.stat-value').eq(2).text(`${usage.memory_usage}%`);
        }
    }
    
    initializeResizing() {
        if (!window.ResizeObserver) return;
        
        this.resizeObserver = new ResizeObserver(entries => {
            entries.forEach(entry => {
                // Handle terminal window resizing
                this.handleWindowResize(entry);
            });
        });
        
        // Observe all terminal windows
        jQuery('.terminal-window').each((i, element) => {
            this.resizeObserver.observe(element);
        });
    }
    
    startResize(e) {
        e.preventDefault();
        
        const startX = e.clientX;
        const startY = e.clientY;
        const terminalWindow = jQuery(e.target).closest('.terminal-window');
        const startWidth = terminalWindow.width();
        const startHeight = terminalWindow.height();
        
        const handleMouseMove = (moveEvent) => {
            const deltaX = moveEvent.clientX - startX;
            const deltaY = moveEvent.clientY - startY;
            
            terminalWindow.css({
                width: Math.max(300, startWidth + deltaX) + 'px',
                height: Math.max(200, startHeight + deltaY) + 'px'
            });
        };
        
        const handleMouseUp = () => {
            jQuery(document).off('mousemove', handleMouseMove);
            jQuery(document).off('mouseup', handleMouseUp);
        };
        
        jQuery(document).on('mousemove', handleMouseMove);
        jQuery(document).on('mouseup', handleMouseUp);
    }
    
    setupKeyboardShortcuts() {
        jQuery(document).on('keydown', (e) => {
            // Ctrl+Shift+C: Clear all terminals
            if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                e.preventDefault();
                this.clearAllTerminals();
            }
            
            // Ctrl+Shift+S: Save session
            if (e.ctrlKey && e.shiftKey && e.key === 'S') {
                e.preventDefault();
                this.saveSession();
            }
            
            // Ctrl+Shift+E: Export log
            if (e.ctrlKey && e.shiftKey && e.key === 'E') {
                e.preventDefault();
                this.exportLog();
            }
            
            // F11: Toggle fullscreen
            if (e.key === 'F11') {
                e.preventDefault();
                this.toggleFullscreen();
            }
        });
    }
    
    startStatusMonitoring() {
        // Monitor system status every 30 seconds
        setInterval(() => {
            this.updateSystemStatus();
        }, 30000);
    }
    
    updateSystemStatus() {
        jQuery.ajax({
            url: vortex_ai_terminal.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_ai_terminal_status',
                nonce: vortex_ai_terminal.nonce
            },
            success: (response) => {
                if (response.success) {
                    // Update connection statuses
                    Object.keys(response.data.agents).forEach(agent => {
                        this.updateConnectionStatus(agent, response.data.agents[agent].connected);
                    });
                    
                    // Update system stats
                    if (response.data.system_stats) {
                        this.updateHardwareUsage('system', response.data.system_stats);
                    }
                }
            }
        });
    }
    
    clearAllTerminals() {
        jQuery('.terminal-output').empty();
        Object.keys(this.agents).forEach(agent => {
            this.addMessage(agent, 'system', 'Terminal cleared');
        });
    }
    
    saveSession() {
        const sessionData = {
            history: Object.fromEntries(this.commandHistory),
            timestamp: new Date().toISOString(),
            agents: Object.keys(this.agents)
        };
        
        localStorage.setItem('vortex_terminal_session', JSON.stringify(sessionData));
        
        // Show confirmation
        jQuery('.terminal-footer').append('<div class="save-notification">Session saved!</div>');
        setTimeout(() => jQuery('.save-notification').remove(), 3000);
    }
    
    loadSessionHistory() {
        const savedSession = localStorage.getItem('vortex_terminal_session');
        
        if (savedSession) {
            try {
                const sessionData = JSON.parse(savedSession);
                
                // Restore command history
                Object.keys(sessionData.history || {}).forEach(agent => {
                    if (this.commandHistory.has(agent)) {
                        this.commandHistory.set(agent, sessionData.history[agent]);
                    }
                });
                
                // Show restore notification
                Object.keys(this.agents).forEach(agent => {
                    this.addMessage(agent, 'system', 'Session history restored');
                });
                
            } catch (e) {
                console.warn('Failed to load session history:', e);
            }
        }
    }
    
    exportLog() {
        const logs = {};
        
        jQuery('.terminal-output').each((i, element) => {
            const agent = jQuery(element).attr('id').split('-')[0].toUpperCase();
            logs[agent] = jQuery(element).text();
        });
        
        const exportData = {
            timestamp: new Date().toISOString(),
            session_id: vortex_ai_terminal.session_id,
            user_id: vortex_ai_terminal.user_id,
            logs: logs
        };
        
        // Create download
        const blob = new Blob([JSON.stringify(exportData, null, 2)], {
            type: 'application/json'
        });
        
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `vortex-ai-terminal-log-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    minimizeTerminal() {
        jQuery('.vortex-ai-terminal-container').addClass('minimized');
    }
    
    maximizeTerminal() {
        jQuery('.vortex-ai-terminal-container').toggleClass('maximized');
    }
    
    closeTerminal() {
        if (confirm('Are you sure you want to close the AI Terminal?')) {
            jQuery('.vortex-ai-terminal-container').fadeOut();
        }
    }
    
    toggleFullscreen() {
        jQuery('.vortex-ai-terminal-container').toggleClass('fullscreen');
    }
    
    handleWindowResize(entry) {
        // Adjust terminal layout based on container size
        const container = jQuery(entry.target);
        const width = entry.contentRect.width;
        
        if (width < 768) {
            container.addClass('mobile-layout');
        } else {
            container.removeClass('mobile-layout');
        }
    }
}

// Auto-initialize when DOM is ready
jQuery(document).ready(function() {
    if (typeof vortex_ai_terminal !== 'undefined') {
        window.vortexTerminal = new VortexAITerminal();
        window.vortexTerminal.init();
    }
});

// Global terminal commands
window.VortexTerminalCommands = {
    clearAgent: (agent) => {
        jQuery(`#${agent.toLowerCase()}-output, #${agent.toLowerCase()}-single-output`).empty();
    },
    
    sendToAgent: (agent, message) => {
        if (window.vortexTerminal) {
            window.vortexTerminal.sendToAgent(agent, message);
        }
    },
    
    getHistory: (agent) => {
        if (window.vortexTerminal) {
            return window.vortexTerminal.commandHistory.get(agent) || [];
        }
        return [];
    }
}; 