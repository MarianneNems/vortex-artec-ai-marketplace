/**
 * Vortex Artist Categories
 * 
 * Handles the artist category selection interface, including:
 * - Interactive category selection
 * - Visualization of selected categories
 * - Personalized agent recommendations based on selections
 * - Data collection for AI personalization
 */

(function($) {
    'use strict';

    // Store the selected categories
    let selectedCategories = [];
    const MAX_CATEGORIES = 3;

    // Category definitions with metadata
    const categoryData = {
        'musician': {
            name: 'Musician',
            icon: 'music',
            description: 'Music composition and sound art',
            relatedAgents: ['prompt_engineer', 'community_assistant']
        },
        'choreographer': {
            name: 'Choreographer',
            icon: 'person-walking',
            description: 'Dance and movement art',
            relatedAgents: ['community_assistant', 'artwork_advisor']
        },
        'sculptor': {
            name: 'Sculptor',
            icon: 'cubes',
            description: '3D artwork and sculptures',
            relatedAgents: ['artwork_advisor', 'marketplace_guide']
        },
        'fine_artist': {
            name: 'Fine Artist',
            icon: 'paint-brush',
            description: 'Traditional painting and drawing',
            relatedAgents: ['marketplace_guide', 'artwork_advisor']
        },
        'digital_artist': {
            name: 'Digital Artist',
            icon: 'desktop',
            description: 'Digital artwork and designs',
            relatedAgents: ['prompt_engineer', 'artwork_advisor']
        },
        'film': {
            name: 'Film',
            icon: 'film',
            description: 'Film and video art',
            relatedAgents: ['prompt_engineer', 'community_assistant']
        },
        'graphic_designer': {
            name: 'Graphic Designer',
            icon: 'bezier-curve',
            description: 'Graphic design and illustration',
            relatedAgents: ['marketplace_guide', 'prompt_engineer']
        },
        'fashion_designer': {
            name: 'Fashion Designer',
            icon: 'tshirt',
            description: 'Fashion and textile art',
            relatedAgents: ['artwork_advisor', 'community_assistant']
        },
        'architect': {
            name: 'Architect',
            icon: 'building',
            description: 'Architectural design and concepts',
            relatedAgents: ['marketplace_guide', 'prompt_engineer']
        },
        'interior_designer': {
            name: 'Interior Designer',
            icon: 'couch',
            description: 'Interior and spatial design',
            relatedAgents: ['artwork_advisor', 'marketplace_guide']
        },
        'dancer': {
            name: 'Dancer',
            icon: 'user-ninja',
            description: 'Dance performance and choreography',
            relatedAgents: ['community_assistant', 'artwork_advisor']
        },
        'other': {
            name: 'Other',
            icon: 'palette',
            description: 'Other art forms not listed',
            relatedAgents: ['technical_support', 'community_assistant']
        }
    };

    $(document).ready(function() {
        // Initialize the category interface
        initCategoryInterface();
        
        // Load saved categories for returning users
        loadSavedCategories();
        
        // Bind events
        bindEvents();
    });

    /**
     * Initialize the category interface
     */
    function initCategoryInterface() {
        const $container = $('.vortex-artist-categories-container');
        
        // Exit if container doesn't exist
        if (!$container.length) return;
        
        // Create category selection UI if it doesn't exist
        if (!$('.vortex-category-grid').length) {
            const $categoryGrid = $('<div class="vortex-category-grid"></div>');
            
            // Add categories to the grid
            $.each(categoryData, function(id, data) {
                const $category = $(`
                    <div class="vortex-category-item" data-category="${id}">
                        <div class="vortex-category-icon">
                            <i class="fa-solid fa-${data.icon}"></i>
                        </div>
                        <div class="vortex-category-info">
                            <h4>${data.name}</h4>
                            <p>${data.description}</p>
                        </div>
                        <div class="vortex-category-checkbox">
                            <input type="checkbox" id="category-${id}" name="vortex_artist_categories[]" value="${id}">
                            <span class="checkmark"></span>
                        </div>
                    </div>
                `);
                
                $categoryGrid.append($category);
            });
            
            // Add selected categories display
            const $selectedContainer = $(`
                <div class="vortex-selected-categories">
                    <h3>Your Selected Categories</h3>
                    <div class="vortex-category-chips"></div>
                    <p class="vortex-category-hint">Select up to ${MAX_CATEGORIES} categories that best describe your art</p>
                </div>
            `);
            
            // Add recommended agents based on categories
            const $recommendedAgents = $(`
                <div class="vortex-recommended-agents">
                    <h3>Recommended AI Agents</h3>
                    <p>Based on your categories, these AI agents can help you the most:</p>
                    <div class="vortex-agent-recommendations"></div>
                </div>
            `);
            
            // Assemble the full interface
            $container.append($categoryGrid, $selectedContainer, $recommendedAgents);
        }
    }

    /**
     * Bind events to the category interface
     */
    function bindEvents() {
        // Category selection
        $(document).on('change', '.vortex-category-item input[type="checkbox"]', function() {
            const $checkbox = $(this);
            const categoryId = $checkbox.val();
            
            if ($checkbox.is(':checked')) {
                // Check if maximum categories reached
                if (selectedCategories.length >= MAX_CATEGORIES) {
                    $checkbox.prop('checked', false);
                    showCategoryError(`You can only select up to ${MAX_CATEGORIES} categories`);
                    return;
                }
                
                // Add category to selected list
                selectedCategories.push(categoryId);
                $(`.vortex-category-item[data-category="${categoryId}"]`).addClass('selected');
            } else {
                // Remove category from selected list
                selectedCategories = selectedCategories.filter(id => id !== categoryId);
                $(`.vortex-category-item[data-category="${categoryId}"]`).removeClass('selected');
            }
            
            // Update UI
            updateSelectedCategoriesUI();
            updateRecommendedAgentsUI();
            
            // Save categories
            saveCategories();
        });
        
        // Remove category from chips
        $(document).on('click', '.vortex-category-chip .vortex-remove-category', function() {
            const categoryId = $(this).closest('.vortex-category-chip').data('category');
            
            // Uncheck the checkbox
            $(`#category-${categoryId}`).prop('checked', false).trigger('change');
        });
    }

    /**
     * Update the selected categories UI
     */
    function updateSelectedCategoriesUI() {
        const $chips = $('.vortex-category-chips');
        $chips.empty();
        
        if (selectedCategories.length === 0) {
            $chips.append('<p class="vortex-no-categories">No categories selected yet</p>');
            return;
        }
        
        // Add a chip for each selected category
        selectedCategories.forEach(function(categoryId) {
            const data = categoryData[categoryId];
            
            const $chip = $(`
                <div class="vortex-category-chip" data-category="${categoryId}">
                    <i class="fa-solid fa-${data.icon}"></i>
                    <span>${data.name}</span>
                    <button type="button" class="vortex-remove-category">×</button>
                </div>
            `);
            
            $chips.append($chip);
        });
    }

    /**
     * Update the recommended agents UI based on selected categories
     */
    function updateRecommendedAgentsUI() {
        const $recommendations = $('.vortex-agent-recommendations');
        $recommendations.empty();
        
        if (selectedCategories.length === 0) {
            $recommendations.append('<p class="vortex-no-recommendations">Select categories to see agent recommendations</p>');
            $('.vortex-recommended-agents').hide();
            return;
        }
        
        $('.vortex-recommended-agents').show();
        
        // Count agent recommendations across all selected categories
        const agentCounts = {};
        
        selectedCategories.forEach(function(categoryId) {
            const relatedAgents = categoryData[categoryId].relatedAgents || [];
            
            relatedAgents.forEach(function(agentId) {
                agentCounts[agentId] = (agentCounts[agentId] || 0) + 1;
            });
        });
        
        // Sort agents by recommendation count
        const sortedAgents = Object.keys(agentCounts).sort(function(a, b) {
            return agentCounts[b] - agentCounts[a];
        });
        
        // Get the top 3 agents
        const topAgents = sortedAgents.slice(0, 3);
        
        // Display top agent recommendations
        if (topAgents.length > 0) {
            topAgents.forEach(function(agentId) {
                let agentName, agentDescription, agentIcon;
                
                // Get agent info from the global vortexAgentData if available
                if (typeof vortexAgentData !== 'undefined' && vortexAgentData[agentId]) {
                    agentName = vortexAgentData[agentId].name;
                    agentDescription = vortexAgentData[agentId].description;
                    agentIcon = vortexAgentData[agentId].icon;
                } else {
                    // Default values if global data not available
                    switch (agentId) {
                        case 'artwork_advisor':
                            agentName = 'Artwork Advisor';
                            agentDescription = 'Get advice on improving your artwork and selling strategies';
                            agentIcon = 'palette';
                            break;
                        case 'marketplace_guide':
                            agentName = 'Marketplace Guide';
                            agentDescription = 'Learn how to navigate the marketplace effectively';
                            agentIcon = 'shopping-cart';
                            break;
                        case 'prompt_engineer':
                            agentName = 'Prompt Engineer';
                            agentDescription = 'Get help crafting effective prompts for AI art';
                            agentIcon = 'wand-magic-sparkles';
                            break;
                        case 'community_assistant':
                            agentName = 'Community Assistant';
                            agentDescription = 'Connect with other artists and community events';
                            agentIcon = 'users';
                            break;
                        case 'technical_support':
                            agentName = 'Technical Support';
                            agentDescription = 'Get help with technical issues';
                            agentIcon = 'wrench';
                            break;
                        default:
                            agentName = 'AI Agent';
                            agentDescription = 'Helpful AI assistant';
                            agentIcon = 'robot';
                    }
                }
                
                const $agent = $(`
                    <div class="vortex-recommended-agent" data-agent="${agentId}">
                        <div class="vortex-agent-icon">
                            <i class="fa-solid fa-${agentIcon}"></i>
                        </div>
                        <div class="vortex-agent-info">
                            <h4>${agentName}</h4>
                            <p>${agentDescription}</p>
                        </div>
                    </div>
                `);
                
                $recommendations.append($agent);
            });
        } else {
            $recommendations.append('<p class="vortex-no-recommendations">No specific agent recommendations available</p>');
        }
    }

    /**
     * Save selected categories to storage
     */
    function saveCategories() {
        // Save to hidden form field if it exists
        const $hiddenField = $('#vortex-selected-categories');
        if ($hiddenField.length) {
            $hiddenField.val(JSON.stringify(selectedCategories));
        }
        
        // Save to localStorage for returning users
        try {
            localStorage.setItem('vortexArtistCategories', JSON.stringify(selectedCategories));
        } catch (e) {
            console.error('Failed to save categories to localStorage:', e);
        }
        
        // Send to server via AJAX if user is logged in
        if (typeof vortexData !== 'undefined' && vortexData.userId) {
            $.ajax({
                url: vortexData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_save_artist_categories',
                    categories: selectedCategories,
                    nonce: vortexData.nonce,
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Categories saved successfully');
                    }
                }
            });
        }
    }

    /**
     * Load saved categories
     */
    function loadSavedCategories() {
        let savedCategories = [];
        
        // Check for categories in localStorage
        try {
            const stored = localStorage.getItem('vortexArtistCategories');
            if (stored) {
                savedCategories = JSON.parse(stored);
            }
        } catch (e) {
            console.error('Failed to load categories from localStorage:', e);
        }
        
        // Check for categories in hidden form field (takes precedence)
        const $hiddenField = $('#vortex-selected-categories');
        if ($hiddenField.length && $hiddenField.val()) {
            try {
                savedCategories = JSON.parse($hiddenField.val());
            } catch (e) {
                console.error('Failed to parse categories from hidden field:', e);
            }
        }
        
        // Apply saved categories
        if (savedCategories.length > 0) {
            selectedCategories = savedCategories;
            
            // Check corresponding checkboxes
            selectedCategories.forEach(function(categoryId) {
                $(`#category-${categoryId}`).prop('checked', true);
                $(`.vortex-category-item[data-category="${categoryId}"]`).addClass('selected');
            });
            
            // Update UI
            updateSelectedCategoriesUI();
            updateRecommendedAgentsUI();
        }
    }

    /**
     * Show an error message
     */
    function showCategoryError(message) {
        const $error = $('<div class="vortex-category-error"></div>').text(message);
        $('.vortex-category-hint').after($error);
        
        setTimeout(function() {
            $error.fadeOut(function() {
                $error.remove();
            });
        }, 3000);
    }

})(jQuery); 