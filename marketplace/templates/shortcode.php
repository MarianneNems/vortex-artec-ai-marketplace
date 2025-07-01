<?php
/**
 * HURAII Shortcode Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Extract shortcode attributes
$mode = isset($atts['mode']) ? $atts['mode'] : 'full';
$width = isset($atts['width']) ? $atts['width'] : '100%';
$height = isset($atts['height']) ? $atts['height'] : 'auto';

// Generate unique ID for this instance
$unique_id = 'huraii-container-' . uniqid();
?>

<div id="<?php echo esc_attr($unique_id); ?>" class="huraii-container huraii-mode-<?php echo esc_attr($mode); ?>" style="width: <?php echo esc_attr($width); ?>; height: <?php echo esc_attr($height); ?>;">
    <div class="huraii-wrapper">
        <div class="huraii-header">
            <h2><?php _e('HURAII Image Generator', 'vortex-huraii'); ?></h2>
            <div class="huraii-tabs">
                <button class="huraii-tab-btn active" data-tab="prompt"><?php _e('Text Prompt', 'vortex-huraii'); ?></button>
                <button class="huraii-tab-btn" data-tab="upload"><?php _e('Image Upload', 'vortex-huraii'); ?></button>
                <button class="huraii-tab-btn" data-tab="gallery"><?php _e('Gallery', 'vortex-huraii'); ?></button>
            </div>
        </div>
        
        <div class="huraii-content">
            <!-- Prompt Tab -->
            <div class="huraii-tab-content active" id="<?php echo esc_attr($unique_id); ?>-prompt">
                <div class="huraii-prompt-container">
                    <div class="huraii-prompt-input">
                        <label for="<?php echo esc_attr($unique_id); ?>-text-prompt"><?php _e('Enter your prompt', 'vortex-huraii'); ?></label>
                        <textarea id="<?php echo esc_attr($unique_id); ?>-text-prompt" placeholder="<?php esc_attr_e('Describe what you want to create...', 'vortex-huraii'); ?>"></textarea>
                        
                        <div class="huraii-prompt-suggestions">
                            <h4><?php _e('Suggested prompts:', 'vortex-huraii'); ?></h4>
                            <div class="huraii-suggestion-chips">
                                <span class="huraii-suggestion-chip" data-prompt="<?php esc_attr_e('A beautiful landscape with mountains', 'vortex-huraii'); ?>"><?php _e('Landscape', 'vortex-huraii'); ?></span>
                                <span class="huraii-suggestion-chip" data-prompt="<?php esc_attr_e('Portrait of a person in a fantasy style', 'vortex-huraii'); ?>"><?php _e('Portrait', 'vortex-huraii'); ?></span>
                                <span class="huraii-suggestion-chip" data-prompt="<?php esc_attr_e('Surrealist artwork with vivid colors', 'vortex-huraii'); ?>"><?php _e('Surreal', 'vortex-huraii'); ?></span>
                                <span class="huraii-suggestion-chip" data-prompt="<?php esc_attr_e('Abstract geometric patterns', 'vortex-huraii'); ?>"><?php _e('Abstract', 'vortex-huraii'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="huraii-prompt-settings">
                        <h4><?php _e('Generation Settings', 'vortex-huraii'); ?></h4>
                        
                        <div class="huraii-setting-item">
                            <label for="<?php echo esc_attr($unique_id); ?>-width"><?php _e('Width', 'vortex-huraii'); ?></label>
                            <select id="<?php echo esc_attr($unique_id); ?>-width">
                                <option value="512">512px</option>
                                <option value="768">768px</option>
                                <option value="1024" selected>1024px</option>
                                <option value="1280">1280px</option>
                            </select>
                        </div>
                        
                        <div class="huraii-setting-item">
                            <label for="<?php echo esc_attr($unique_id); ?>-height"><?php _e('Height', 'vortex-huraii'); ?></label>
                            <select id="<?php echo esc_attr($unique_id); ?>-height">
                                <option value="512">512px</option>
                                <option value="768">768px</option>
                                <option value="1024" selected>1024px</option>
                                <option value="1280">1280px</option>
                            </select>
                        </div>
                        
                        <div class="huraii-setting-item">
                            <label for="<?php echo esc_attr($unique_id); ?>-variations"><?php _e('Variations', 'vortex-huraii'); ?></label>
                            <select id="<?php echo esc_attr($unique_id); ?>-variations">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="4" selected>4</option>
                                <option value="6">6</option>
                            </select>
                        </div>
                        
                        <div class="huraii-setting-item">
                            <label for="<?php echo esc_attr($unique_id); ?>-style"><?php _e('Style', 'vortex-huraii'); ?></label>
                            <select id="<?php echo esc_attr($unique_id); ?>-style">
                                <option value="realistic"><?php _e('Realistic', 'vortex-huraii'); ?></option>
                                <option value="artistic" selected><?php _e('Artistic', 'vortex-huraii'); ?></option>
                                <option value="fantasy"><?php _e('Fantasy', 'vortex-huraii'); ?></option>
                                <option value="abstract"><?php _e('Abstract', 'vortex-huraii'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="huraii-action-container">
                    <button id="<?php echo esc_attr($unique_id); ?>-generate" class="huraii-action-button"><?php _e('Generate', 'vortex-huraii'); ?></button>
                </div>
            </div>
            
            <!-- Upload Tab -->
            <div class="huraii-tab-content" id="<?php echo esc_attr($unique_id); ?>-upload">
                <div class="huraii-upload-container">
                    <div class="huraii-upload-area" id="<?php echo esc_attr($unique_id); ?>-upload-area">
                        <div class="huraii-upload-placeholder">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <p><?php _e('Drag and drop an image here, or click to select a file', 'vortex-huraii'); ?></p>
                            <span><?php _e('Supported formats: JPG, PNG, WEBP', 'vortex-huraii'); ?></span>
                        </div>
                        <div class="huraii-upload-preview" style="display: none;">
                            <img id="<?php echo esc_attr($unique_id); ?>-preview-image" src="" alt="<?php esc_attr_e('Preview', 'vortex-huraii'); ?>">
                            <button class="huraii-remove-image"><?php _e('Remove', 'vortex-huraii'); ?></button>
                        </div>
                        <input type="file" id="<?php echo esc_attr($unique_id); ?>-file-input" accept="image/jpeg,image/png,image/webp" style="display: none;">
                    </div>
                    
                    <div class="huraii-upload-settings">
                        <h4><?php _e('Variation Settings', 'vortex-huraii'); ?></h4>
                        
                        <div class="huraii-setting-item">
                            <label for="<?php echo esc_attr($unique_id); ?>-variation-strength"><?php _e('Variation Strength', 'vortex-huraii'); ?></label>
                            <input type="range" id="<?php echo esc_attr($unique_id); ?>-variation-strength" min="0" max="100" value="50">
                            <div class="huraii-range-labels">
                                <span><?php _e('Subtle', 'vortex-huraii'); ?></span>
                                <span><?php _e('Strong', 'vortex-huraii'); ?></span>
                            </div>
                        </div>
                        
                        <div class="huraii-setting-item">
                            <label for="<?php echo esc_attr($unique_id); ?>-upload-prompt"><?php _e('Additional Prompt (optional)', 'vortex-huraii'); ?></label>
                            <textarea id="<?php echo esc_attr($unique_id); ?>-upload-prompt" placeholder="<?php esc_attr_e('Add details to guide the variation...', 'vortex-huraii'); ?>"></textarea>
                        </div>
                        
                        <div class="huraii-setting-item">
                            <label for="<?php echo esc_attr($unique_id); ?>-upload-variations"><?php _e('Number of Variations', 'vortex-huraii'); ?></label>
                            <select id="<?php echo esc_attr($unique_id); ?>-upload-variations">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="4" selected>4</option>
                                <option value="6">6</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="huraii-action-container">
                    <button id="<?php echo esc_attr($unique_id); ?>-generate-variations" class="huraii-action-button" disabled><?php _e('Generate Variations', 'vortex-huraii'); ?></button>
                </div>
            </div>
            
            <!-- Gallery Tab -->
            <div class="huraii-tab-content" id="<?php echo esc_attr($unique_id); ?>-gallery">
                <div class="huraii-gallery-container">
                    <div class="huraii-gallery-empty">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                            <path d="M21 15L16 10L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <p><?php _e('No generated images yet', 'vortex-huraii'); ?></p>
                        <span><?php _e('Generate images using the Text Prompt or Image Upload tabs', 'vortex-huraii'); ?></span>
                    </div>
                    <div class="huraii-gallery-grid" style="display: none;"></div>
                </div>
            </div>
        </div>
        
        <!-- Results Section -->
        <div class="huraii-results" style="display: none;">
            <div class="huraii-results-header">
                <h3><?php _e('Generated Results', 'vortex-huraii'); ?></h3>
                <button class="huraii-close-results">&times;</button>
            </div>
            
            <div class="huraii-results-loading">
                <div class="huraii-spinner"></div>
                <p><?php _e('Creating your artwork...', 'vortex-huraii'); ?></p>
                <div class="huraii-progress-bar">
                    <div class="huraii-progress-fill"></div>
                </div>
                <button class="huraii-cancel-generation"><?php _e('Cancel', 'vortex-huraii'); ?></button>
            </div>
            
            <div class="huraii-results-grid"></div>
            
            <div class="huraii-results-actions">
                <button class="huraii-regenerate"><?php _e('Regenerate', 'vortex-huraii'); ?></button>
                <button class="huraii-save-all"><?php _e('Save All', 'vortex-huraii'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize HURAII for this instance
            if (typeof window.HURAII !== 'undefined') {
                const containerId = '<?php echo esc_js($unique_id); ?>';
                const container = document.getElementById(containerId);
                
                if (container) {
                    // Initialize tabs
                    const tabButtons = container.querySelectorAll('.huraii-tab-btn');
                    const tabContents = container.querySelectorAll('.huraii-tab-content');
                    
                    tabButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const tabName = this.dataset.tab;
                            
                            // Update active tab button
                            tabButtons.forEach(btn => btn.classList.remove('active'));
                            this.classList.add('active');
                            
                            // Update active tab content
                            tabContents.forEach(content => content.classList.remove('active'));
                            document.getElementById(`${containerId}-${tabName}`).classList.add('active');
                        });
                    });
                    
                    // Initialize file upload
                    const uploadArea = document.getElementById(`${containerId}-upload-area`);
                    const fileInput = document.getElementById(`${containerId}-file-input`);
                    const previewImage = document.getElementById(`${containerId}-preview-image`);
                    const uploadPreview = container.querySelector('.huraii-upload-preview');
                    const uploadPlaceholder = container.querySelector('.huraii-upload-placeholder');
                    const generateVariationsBtn = document.getElementById(`${containerId}-generate-variations`);
                    
                    uploadArea.addEventListener('click', function(e) {
                        if (e.target.closest('.huraii-remove-image')) {
                            // Handle remove button click
                            uploadPreview.style.display = 'none';
                            uploadPlaceholder.style.display = 'flex';
                            fileInput.value = '';
                            generateVariationsBtn.disabled = true;
                            return;
                        }
                        
                        fileInput.click();
                    });
                    
                    uploadArea.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        uploadArea.classList.add('dragging');
                    });
                    
                    uploadArea.addEventListener('dragleave', function() {
                        uploadArea.classList.remove('dragging');
                    });
                    
                    uploadArea.addEventListener('drop', function(e) {
                        e.preventDefault();
                        uploadArea.classList.remove('dragging');
                        
                        if (e.dataTransfer.files.length) {
                            handleFile(e.dataTransfer.files[0]);
                        }
                    });
                    
                    fileInput.addEventListener('change', function() {
                        if (this.files.length) {
                            handleFile(this.files[0]);
                        }
                    });
                    
                    function handleFile(file) {
                        if (!file.type.match('image/(jpeg|png|webp)')) {
                            alert('<?php echo esc_js(__('Please upload a valid image file (JPG, PNG, or WEBP).', 'vortex-huraii')); ?>');
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImage.src = e.target.result;
                            uploadPreview.style.display = 'block';
                            uploadPlaceholder.style.display = 'none';
                            generateVariationsBtn.disabled = false;
                        };
                        reader.readAsDataURL(file);
                    }
                    
                    // Initialize prompt suggestions
                    const suggestionChips = container.querySelectorAll('.huraii-suggestion-chip');
                    const promptTextarea = document.getElementById(`${containerId}-text-prompt`);
                    
                    suggestionChips.forEach(chip => {
                        chip.addEventListener('click', function() {
                            promptTextarea.value = this.dataset.prompt;
                        });
                    });
                    
                    // Initialize generate buttons
                    const generateBtn = document.getElementById(`${containerId}-generate`);
                    const generateVariations = document.getElementById(`${containerId}-generate-variations`);
                    const resultsSection = container.querySelector('.huraii-results');
                    const resultsLoading = container.querySelector('.huraii-results-loading');
                    const resultsGrid = container.querySelector('.huraii-results-grid');
                    const progressFill = container.querySelector('.huraii-progress-fill');
                    const closeResultsBtn = container.querySelector('.huraii-close-results');
                    const cancelBtn = container.querySelector('.huraii-cancel-generation');
                    
                    generateBtn.addEventListener('click', function() {
                        const prompt = promptTextarea.value.trim();
                        if (!prompt) {
                            alert('<?php echo esc_js(__('Please enter a prompt to generate images.', 'vortex-huraii')); ?>');
                            return;
                        }
                        
                        // Get settings
                        const width = document.getElementById(`${containerId}-width`).value;
                        const height = document.getElementById(`${containerId}-height`).value;
                        const variations = document.getElementById(`${containerId}-variations`).value;
                        const style = document.getElementById(`${containerId}-style`).value;
                        
                        // Show results section with loading state
                        resultsSection.style.display = 'block';
                        resultsLoading.style.display = 'block';
                        resultsGrid.style.display = 'none';
                        progressFill.style.width = '0%';
                        
                        // Start progress animation
                        let progress = 0;
                        const progressInterval = setInterval(function() {
                            progress += 1;
                            if (progress > 95) {
                                clearInterval(progressInterval);
                            }
                            progressFill.style.width = `${progress}%`;
                        }, 100);
                        
                        // Call HURAII API to generate images
                        window.HURAII.generate({
                            prompt: prompt,
                            width: parseInt(width),
                            height: parseInt(height),
                            variations: parseInt(variations),
                            style: style,
                            onComplete: function(results) {
                                clearInterval(progressInterval);
                                progressFill.style.width = '100%';
                                
                                // Hide loading, show results
                                setTimeout(function() {
                                    resultsLoading.style.display = 'none';
                                    resultsGrid.style.display = 'grid';
                                    
                                    // Display results
                                    displayResults(results);
                                    
                                    // Add to gallery
                                    addToGallery(results);
                                }, 500);
                            },
                            onError: function(error) {
                                clearInterval(progressInterval);
                                resultsSection.style.display = 'none';
                                alert('<?php echo esc_js(__('Error generating images: ', 'vortex-huraii')); ?>' + error.message);
                            }
                        });
                    });
                    
                    generateVariations.addEventListener('click', function() {
                        // Similar to generate, but using the uploaded image
                        const prompt = document.getElementById(`${containerId}-upload-prompt`).value.trim();
                        const variationStrength = document.getElementById(`${containerId}-variation-strength`).value;
                        const variations = document.getElementById(`${containerId}-upload-variations`).value;
                        
                        // Show results section with loading state
                        resultsSection.style.display = 'block';
                        resultsLoading.style.display = 'block';
                        resultsGrid.style.display = 'none';
                        progressFill.style.width = '0%';
                        
                        // Start progress animation
                        let progress = 0;
                        const progressInterval = setInterval(function() {
                            progress += 1;
                            if (progress > 95) {
                                clearInterval(progressInterval);
                            }
                            progressFill.style.width = `${progress}%`;
                        }, 100);
                        
                        // Call HURAII API to generate variations
                        window.HURAII.generateVariations({
                            imageData: previewImage.src,
                            prompt: prompt,
                            strength: parseInt(variationStrength) / 100,
                            variations: parseInt(variations),
                            onComplete: function(results) {
                                clearInterval(progressInterval);
                                progressFill.style.width = '100%';
                                
                                // Hide loading, show results
                                setTimeout(function() {
                                    resultsLoading.style.display = 'none';
                                    resultsGrid.style.display = 'grid';
                                    
                                    // Display results
                                    displayResults(results);
                                    
                                    // Add to gallery
                                    addToGallery(results);
                                }, 500);
                            },
                            onError: function(error) {
                                clearInterval(progressInterval);
                                resultsSection.style.display = 'none';
                                alert('<?php echo esc_js(__('Error generating variations: ', 'vortex-huraii')); ?>' + error.message);
                            }
                        });
                    });
                    
                    closeResultsBtn.addEventListener('click', function() {
                        resultsSection.style.display = 'none';
                    });
                    
                    cancelBtn.addEventListener('click', function() {
                        // Call HURAII API to cancel generation
                        window.HURAII.cancelGeneration();
                        resultsSection.style.display = 'none';
                    });
                    
                    // Helper function to display results
                    function displayResults(results) {
                        resultsGrid.innerHTML = '';
                        
                        results.forEach(function(result, index) {
                            const resultItem = document.createElement('div');
                            resultItem.className = 'huraii-result-item';
                            
                            const img = document.createElement('img');
                            img.src = result.url;
                            img.alt = `<?php echo esc_js(__('Generated image', 'vortex-huraii')); ?> ${index + 1}`;
                            
                            const actions = document.createElement('div');
                            actions.className = 'huraii-result-actions';
                            
                            const saveBtn = document.createElement('button');
                            saveBtn.className = 'huraii-save-result';
                            saveBtn.innerHTML = '<?php echo esc_js(__('Save', 'vortex-huraii')); ?>';
                            saveBtn.dataset.imageId = result.id;
                            
                            const variationBtn = document.createElement('button');
                            variationBtn.className = 'huraii-create-variation';
                            variationBtn.innerHTML = '<?php echo esc_js(__('Variations', 'vortex-huraii')); ?>';
                            variationBtn.dataset.imageId = result.id;
                            
                            actions.appendChild(saveBtn);
                            actions.appendChild(variationBtn);
                            
                            resultItem.appendChild(img);
                            resultItem.appendChild(actions);
                            resultsGrid.appendChild(resultItem);
                            
                            // Add event listeners for save and variation buttons
                            saveBtn.addEventListener('click', function() {
                                window.HURAII.saveArtwork({
                                    id: this.dataset.imageId,
                                    onComplete: function() {
                                        alert('<?php echo esc_js(__('Image saved successfully!', 'vortex-huraii')); ?>');
                                    },
                                    onError: function(error) {
                                        alert('<?php echo esc_js(__('Error saving image: ', 'vortex-huraii')); ?>' + error.message);
                                    }
                                });
                            });
                            
                            variationBtn.addEventListener('click', function() {
                                // Switch to variations tab and set the image
                                // This is simplified - in a real implementation, you would need to set up the image properly
                                tabButtons.forEach(btn => {
                                    if (btn.dataset.tab === 'upload') {
                                        btn.click();
                                    }
                                });
                                
                                previewImage.src = result.url;
                                uploadPreview.style.display = 'block';
                                uploadPlaceholder.style.display = 'none';
                                generateVariationsBtn.disabled = false;
                                
                                // Close results
                                resultsSection.style.display = 'none';
                            });
                        });
                    }
                    
                    // Helper function to add results to gallery
                    function addToGallery(results) {
                        const galleryGrid = container.querySelector('.huraii-gallery-grid');
                        const galleryEmpty = container.querySelector('.huraii-gallery-empty');
                        
                        // Show gallery grid, hide empty state
                        galleryGrid.style.display = 'grid';
                        galleryEmpty.style.display = 'none';
                        
                        // Add results to gallery
                        results.forEach(function(result) {
                            const galleryItem = document.createElement('div');
                            galleryItem.className = 'huraii-gallery-item';
                            
                            const img = document.createElement('img');
                            img.src = result.url;
                            img.alt = '<?php echo esc_js(__('Gallery image', 'vortex-huraii')); ?>';
                            
                            galleryItem.appendChild(img);
                            galleryGrid.appendChild(galleryItem);
                            
                            // Add click event to show the image in results view
                            galleryItem.addEventListener('click', function() {
                                displayResults([result]);
                                resultsSection.style.display = 'block';
                                resultsLoading.style.display = 'none';
                                resultsGrid.style.display = 'grid';
                            });
                        });
                    }
                }
            } else {
                console.error('HURAII library not loaded');
            }
        });
    })();
</script> 