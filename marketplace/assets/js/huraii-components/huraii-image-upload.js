/**
 * HURAII Image Upload Extension
 * Handles image upload functionality, tab switching, and multi-panel view
 */
(function(global, $) {
  'use strict';
  
  // Wait for document ready
  $(document).ready(function() {
    // Initialize the image upload functionality
    initTabs();
    initImageUpload();
    initMultiPanelView();
    initGenerateMultiple();
  });
  
  /**
   * Initialize tab switching functionality
   */
  function initTabs() {
    // Tab click handler
    $('.tab').click(function() {
      // Get the tab data
      const tabId = $(this).data('tab');
      
      // Remove active class from all tabs and contents
      $('.tab').removeClass('active');
      $('.tab-content').removeClass('active');
      
      // Add active class to clicked tab and corresponding content
      $(this).addClass('active');
      $(`#${tabId}-content`).addClass('active');
      
      // Track tab change for learning
      if (window.HURAII) {
        window.HURAII.emit('activity_tracked', {
          action: 'tab_changed',
          data: {
            tab: tabId,
            timestamp: new Date().toISOString()
          }
        });
      }
    });
  }
  
  /**
   * Initialize image upload functionality
   */
  function initImageUpload() {
    const $uploadArea = $('#upload-area');
    const $fileInput = $('#seed-image-input');
    
    // Click on upload area to trigger file input
    $uploadArea.on('click', function() {
      $fileInput.click();
    });
    
    // Handle file selection
    $fileInput.on('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        handleImageUpload(file);
      }
    });
    
    // Setup drag and drop
    $uploadArea.on('dragover', function(e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).addClass('drag-over');
    });
    
    $uploadArea.on('dragleave', function(e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).removeClass('drag-over');
    });
    
    $uploadArea.on('drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).removeClass('drag-over');
      
      const file = e.originalEvent.dataTransfer.files[0];
      if (file && file.type.match('image.*')) {
        handleImageUpload(file);
      }
    });
  }
  
  /**
   * Handle the uploaded image file
   * @param {File} file The uploaded image file
   */
  function handleImageUpload(file) {
    // Validate file type
    if (!file.type.match('image.*')) {
      showStatus('Please upload an image file (.jpg, .png, .gif, etc.)', 'error');
      return;
    }
    
    // Show file info
    $('#seed-image-name').text(file.name);
    
    // Create a preview
    const reader = new FileReader();
    reader.onload = function(e) {
      // Set preview image
      $('#seed-image-preview').attr('src', e.target.result);
      
      // Show preview, hide placeholder
      $('#upload-placeholder').hide();
      $('#upload-preview').show();
      
      // Store data URL for generation
      if (window.HURAII) {
        window.HURAII.state.seedImageData = e.target.result;
      }
      
      // Copy to panel view
      $('#panel1-image').attr('src', e.target.result);
      
      // Show success message
      showStatus('Image uploaded successfully! Ready for generation.', 'success');
    };
    
    // Read the image as data URL
    reader.readAsDataURL(file);
    
    // Track upload for learning
    if (window.HURAII) {
      window.HURAII.emit('activity_tracked', {
        action: 'image_uploaded',
        data: {
          filename: file.name,
          filesize: file.size,
          filetype: file.type,
          timestamp: new Date().toISOString()
        }
      });
    }
  }
  
  /**
   * Initialize multi-panel view toggle
   */
  function initMultiPanelView() {
    $('#multi-panel-toggle').on('change', function() {
      if (this.checked) {
        $('#single-view').hide();
        $('#multi-panel-view').show();
      } else {
        $('#multi-panel-view').hide();
        $('#single-view').show();
      }
      
      // Track view change for learning
      if (window.HURAII) {
        window.HURAII.emit('activity_tracked', {
          action: 'view_changed',
          data: {
            view: this.checked ? 'multi_panel' : 'single',
            timestamp: new Date().toISOString()
          }
        });
      }
    });
    
    // Handle panel download button
    $('#panel-download-all-btn').on('click', function() {
      downloadAllPanels();
    });
  }
  
  /**
   * Initialize generate multiple functionality
   */
  function initGenerateMultiple() {
    $('#generate-multiple-btn').on('click', function() {
      generateMultipleVariations();
    });
  }
  
  /**
   * Generate multiple variations
   */
  function generateMultipleVariations() {
    const HURAII = window.HURAII;
    const api = HURAII?.components?.api;
    
    if (!api) {
      showStatus('API component not initialized', 'error');
      return;
    }
    
    // Determine if we're using text or image prompt
    const isImageMode = $('#image-upload-content').hasClass('active');
    
    // Check if seed image is uploaded in image mode
    if (isImageMode && !HURAII.state.seedImageData) {
      showStatus('Please upload a seed image first', 'error');
      return;
    }
    
    // Show status
    showStatus('Starting generation of 4 variations...', 'info');
    
    // Enable multi-panel view
    $('#multi-panel-toggle').prop('checked', true).trigger('change');
    
    // Track interaction for learning
    trackMultiGeneration(isImageMode);
    
    // Common parameters
    const baseParams = {
      format: $('#format-select').val() || '2d',
      style: $('#style-select').val() || 'realistic',
      width: parseInt($('#width-input').val()) || 1024,
      height: parseInt($('#height-input').val()) || 1024,
      steps: parseInt($('#steps-input').val()) || 30
    };
    
    // Get specific params based on mode
    let specificParams = {};
    if (isImageMode) {
      specificParams = {
        init_image: HURAII.state.seedImageData,
        prompt: $('#image-prompt').val() || 'Enhance colors, add dramatic lighting',
        strength: parseInt($('#image-strength').val()) / 100 || 0.75
      };
    } else {
      specificParams = {
        prompt: $('#prompt-input').val() || 'Colorful landscape with mountains at sunset',
        negative_prompt: $('#negative-prompt').val() || 'blurry, distorted, low quality'
      };
    }
    
    // Merge params
    const params = {...baseParams, ...specificParams};
    
    // Create multiple promises for parallel generation with different seeds
    const variations = [];
    const numVariations = 3; // We'll have the original plus 3 variations
    
    // Queue up the variations
    for (let i = 0; i < numVariations; i++) {
      // Clone parameters and add unique seed and ID
      const variationParams = {...params};
      variationParams.seed = Math.floor(Math.random() * 1000000);
      variationParams.request_id = HURAII.components.api._generateRequestId() + `_var${i+1}`;
      
      // Create a variation promise
      variations.push(
        api.generateArtwork(variationParams)
          .then(result => {
            // Update panel with result
            $(`#panel${i+2}-image`).attr('src', result.image_url);
            return result;
          })
      );
    }
    
    // Wait for all variations to complete
    Promise.all(variations)
      .then(results => {
        showStatus('All variations generated successfully!', 'success');
        
        // Store results for possible saving
        HURAII.state.multiVariationResults = results;
        
        // Enable save button
        $('#save-artwork-btn').prop('disabled', false);
      })
      .catch(error => {
        showStatus('Failed to generate all variations: ' + error.message, 'error');
      });
  }
  
  /**
   * Track multi-generation for learning
   * @param {boolean} isImageMode Whether we're using image or text mode
   */
  function trackMultiGeneration(isImageMode) {
    if (window.HURAII) {
      window.HURAII.emit('activity_tracked', {
        action: 'multiple_generation_requested',
        data: {
          mode: isImageMode ? 'image_based' : 'text_based',
          count: 4,
          timestamp: new Date().toISOString()
        }
      });
    }
  }
  
  /**
   * Download all panel images as a ZIP
   */
  function downloadAllPanels() {
    showStatus('Preparing images for download...', 'info');
    
    // In a real implementation, we would create a ZIP file with all images
    // For this demo, we'll just trigger individual downloads
    
    // Check if we have panel images
    const panels = [
      $('#panel1-image').attr('src'),
      $('#panel2-image').attr('src'),
      $('#panel3-image').attr('src'),
      $('#panel4-image').attr('src')
    ].filter(src => src && !src.includes('placeholder'));
    
    if (panels.length === 0) {
      showStatus('No images to download', 'error');
      return;
    }
    
    // Download each panel image
    panels.forEach((src, index) => {
      const link = document.createElement('a');
      link.href = src;
      link.download = `huraii_variation_${index+1}.png`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });
    
    showStatus(`Downloaded ${panels.length} images`, 'success');
  }
  
  /**
   * Show status message in the UI
   * @param {string} message Status message text
   * @param {string} type Message type (info, success, error)
   */
  function showStatus(message, type = 'info') {
    $('#status-message')
      .text(message)
      .removeClass('info success error')
      .addClass(type);
  }
  
})(window, jQuery); 