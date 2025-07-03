const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost',
    specPattern: 'cypress/integration/**/*.spec.js'
  }
}); 