/**
 * Custom Cypress commands for VORTEX AI MARKETPLACE
 */

// Custom assertions
Cypress.Commands.add('shouldHaveVortexClass', { prevSubject: 'element' }, (subject) => {
  cy.wrap(subject).should('satisfy', ($el) => {
    return $el.attr('class').includes('vortex') || $el.attr('id').includes('vortex');
  });
});

// API testing helpers
Cypress.Commands.add('apiRequest', (endpoint, method = 'GET', body = null) => {
  return cy.request({
    method,
    url: `/wp-json/vortex/v1/${endpoint}`,
    body,
    failOnStatusCode: false
  });
});

// Form helpers
Cypress.Commands.add('submitVortexForm', (formSelector = 'form[class*="vortex"]') => {
  cy.get(formSelector).within(() => {
    cy.get('input[type="submit"], button[type="submit"]').click();
  });
});

// Wait helpers
Cypress.Commands.add('waitForAjax', () => {
  cy.window().then((win) => {
    if (win.jQuery) {
      cy.wrap(null).should(() => {
        expect(win.jQuery.active).to.equal(0);
      });
    }
  });
});

// Screenshot helpers
Cypress.Commands.add('screenshotOnFailure', (testName) => {
  cy.screenshot(`${testName}-failure`);
}); 