/**
 * HURAII WordPress Integration Script
 * 
 * This file should be included by WordPress to enable HURAII image processing functionality.
 * 
 * Usage:
 * 1. Upload the huraii-components directory to your WordPress site
 * 2. Include this file in your WordPress plugin or theme
 * 3. Call the initHURAIIImageProcessing function to initialize the image processing
 */

// Check if running in a Node.js environment
if (typeof process !== 'undefined' && process.versions && process.versions.node) {
  // Server-side integration
  
  // Import required modules
  const path = require('path');
  const WordPressIntegration = require('./huraii-components/huraii-wordpress-integration');
  const ImageProcessor = require('./huraii-components/huraii-image-processor');
  
  /**
   * Initialize HURAII image processing for WordPress
   * @param {Object} wp WordPress API object
   * @param {Object} config Configuration options
   * @returns {Object} WordPress integration instance
   */
  function initHURAIIImageProcessing(wp, config = {}) {
    // Initialize WordPress integration
    return WordPressIntegration.init(wp, config);
  }
  
  // Export the initialization function
  module.exports = {
    initHURAIIImageProcessing
  };
} else {
  // Client-side stub (for browser usage)
  console.log('HURAII WordPress Integration loaded');
  
  // Attach to global object for WP admin scripts
  window.HURAII_WordPress = {
    init: function() {
      console.log('HURAII WordPress admin integration initialized');
      
      // Add admin UI functionality if needed
      if (document.getElementById('huraii-wp-admin')) {
        this.initAdminUI();
      }
      
      return this;
    },
    
    initAdminUI: function() {
      // Initialize admin UI components for HURAII
      console.log('HURAII admin UI initialized');
      
      // Setup image management UI (placeholder)
      const adminContainer = document.getElementById('huraii-wp-admin');
      if (adminContainer) {
        adminContainer.innerHTML = `
          <div class="huraii-admin-panel">
            <h2>HURAII Image Processing</h2>
            <div class="huraii-admin-stats">
              <div class="stat-box">
                <span class="stat-value">0</span>
                <span class="stat-label">Images Processed</span>
              </div>
              <div class="stat-box">
                <span class="stat-value">0</span>
                <span class="stat-label">Variations Generated</span>
              </div>
            </div>
            <div class="huraii-admin-controls">
              <button id="huraii-clear-cache" class="button button-primary">Clear Cache</button>
              <button id="huraii-test-connection" class="button">Test Connection</button>
            </div>
          </div>
        `;
        
        // Add event listeners
        document.getElementById('huraii-clear-cache').addEventListener('click', function() {
          // Send AJAX request to clear cache
          console.log('Clearing HURAII cache...');
          
          // Example AJAX request to WordPress
          jQuery.post(
            ajaxurl,
            {
              action: 'vortex_huraii_clear_cache',
              nonce: window.vortexHURAIINonce || ''
            },
            function(response) {
              if (response.success) {
                alert('HURAII cache cleared successfully!');
              } else {
                alert('Error clearing cache: ' + (response.data?.message || 'Unknown error'));
              }
            }
          );
        });
        
        document.getElementById('huraii-test-connection').addEventListener('click', function() {
          // Send AJAX request to test connection
          console.log('Testing HURAII connection...');
          
          // Example AJAX request to WordPress
          jQuery.post(
            ajaxurl,
            {
              action: 'vortex_huraii_test_connection',
              nonce: window.vortexHURAIINonce || ''
            },
            function(response) {
              if (response.success) {
                alert('HURAII connection test successful!');
              } else {
                alert('Connection test failed: ' + (response.data?.message || 'Unknown error'));
              }
            }
          );
        });
      }
    }
  };
  
  // Auto-initialize when document is ready (if using in WP admin)
  if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function() {
      window.HURAII_WordPress.init();
    });
  }
} 