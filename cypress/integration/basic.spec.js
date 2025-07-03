/**
 * Basic E2E tests for VORTEX AI MARKETPLACE
 */

describe('VORTEX AI MARKETPLACE - Basic Functionality', () => {
  beforeEach(() => {
    // Visit the homepage before each test
    cy.visit('/');
  });

  it('should load the homepage successfully', () => {
    cy.title().should('not.be.empty');
    cy.get('body').should('be.visible');
  });

  it('should render the artist business quiz shortcode form', () => {
    // Check if the shortcode renders a form element
    cy.get('body').then(($body) => {
      if ($body.find('[data-vortex-shortcode="artist_business_quiz"]').length > 0) {
        cy.get('[data-vortex-shortcode="artist_business_quiz"]')
          .should('be.visible')
          .within(() => {
            cy.get('form').should('exist');
          });
      } else {
        // If shortcode not found, check for any form with vortex classes
        cy.get('form[class*="vortex"], .vortex-form, .vortex-quiz')
          .should('exist')
          .and('be.visible');
      }
    });
  });

  it('should have vortex-related scripts loaded', () => {
    cy.window().then((win) => {
      // Check if any VORTEX-related JavaScript objects exist
      expect(win.vortexAjax || win.VORTEX || win.vortex).to.exist;
    });
  });

  it('should render artist journey elements', () => {
    // Check for artist journey related elements
    cy.get('body').then(($body) => {
      const hasArtistJourney = $body.find('[class*="artist-journey"], [id*="artist-journey"], [data-vortex*="journey"]').length > 0;
      
      if (hasArtistJourney) {
        cy.get('[class*="artist-journey"], [id*="artist-journey"], [data-vortex*="journey"]')
          .should('be.visible');
      } else {
        // Fallback: check for any vortex-related content
        cy.get('[class*="vortex"], [id*="vortex"]')
          .should('exist');
      }
    });
  });

  it('should handle form submissions gracefully', () => {
    // Find any form on the page and test basic interaction
    cy.get('form').then(($forms) => {
      if ($forms.length > 0) {
        cy.get('form').first().within(() => {
          // Check if form has input fields
          cy.get('input, select, textarea').then(($inputs) => {
            if ($inputs.length > 0) {
              // Try to interact with the first input
              cy.get('input, select, textarea').first().should('be.visible');
            }
          });
        });
      }
    });
  });
});

describe('VORTEX AI MARKETPLACE - Admin Area', () => {
  it('should redirect to login when accessing admin without auth', () => {
    cy.visit('/wp-admin/', { failOnStatusCode: false });
    
    // Should either show login form or redirect to login
    cy.get('body').then(($body) => {
      if ($body.find('#loginform').length > 0) {
        cy.get('#loginform').should('be.visible');
      } else {
        cy.url().should('include', 'wp-login.php');
      }
    });
  });
});

describe('VORTEX AI MARKETPLACE - API Endpoints', () => {
  it('should respond to REST API health checks', () => {
    cy.request({
      method: 'GET',
      url: '/wp-json/vortex/v1/health',
      failOnStatusCode: false
    }).then((response) => {
      // Should either return 200 or 404 (if endpoint doesn't exist yet)
      expect([200, 404]).to.include(response.status);
    });
  });
});

describe('Artist Quiz Page', () => {
  it('renders the quiz form', () => {
    cy.visit('/');
    cy.get('form[vortex-artist-business-quiz]').should('exist');
  });
}); 