/**
 * Cypress support file for VORTEX AI MARKETPLACE
 */

// Import commands
import './commands';

// Global configuration
Cypress.on('uncaught:exception', (err, runnable) => {
  // Prevent Cypress from failing on WordPress admin JavaScript errors
  if (err.message.includes('$ is not defined') || 
      err.message.includes('jQuery is not defined') ||
      err.message.includes('wp is not defined')) {
    return false;
  }
  
  // Let other errors fail the test
  return true;
});

// Custom commands for VORTEX testing
Cypress.Commands.add('loginToWordPress', (username = 'admin', password = 'admin') => {
  cy.visit('/wp-login.php');
  cy.get('#user_login').type(username);
  cy.get('#user_pass').type(password);
  cy.get('#wp-submit').click();
});

Cypress.Commands.add('checkVortexShortcode', (shortcode) => {
  cy.get(`[data-vortex-shortcode="${shortcode}"]`).should('exist');
});

Cypress.Commands.add('waitForVortexInit', () => {
  cy.window().should('have.property', 'vortexAjax');
  cy.window().its('vortexAjax').should('not.be.undefined');
});

Cypress.Commands.add('fillVortexForm', (formData) => {
  Object.keys(formData).forEach(field => {
    cy.get(`[name="${field}"]`).type(formData[field]);
  });
});

// WordPress-specific helpers
Cypress.Commands.add('activatePlugin', (pluginSlug) => {
  cy.visit('/wp-admin/plugins.php');
  cy.get(`[data-slug="${pluginSlug}"] .activate a`).click();
});

Cypress.Commands.add('visitVortexAdmin', () => {
  cy.visit('/wp-admin/admin.php?page=vortex-ai-marketplace');
}); 