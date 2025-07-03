/**
 * Cypress plugins file for VORTEX AI MARKETPLACE
 */

module.exports = (on, config) => {
  // Task plugins
  on('task', {
    log(message) {
      console.log(message);
      return null;
    },
    
    table(message) {
      console.table(message);
      return null;
    }
  });

  // Environment-specific configuration
  if (config.env.environment === 'staging') {
    config.baseUrl = 'https://staging.vortex-ai-marketplace.com';
  } else if (config.env.environment === 'production') {
    config.baseUrl = 'https://vortex-ai-marketplace.com';
  }

  // Browser configuration
  on('before:browser:launch', (browser = {}, launchOptions) => {
    if (browser.name === 'chrome') {
      launchOptions.args.push('--disable-web-security');
      launchOptions.args.push('--disable-features=VizDisplayCompositor');
    }
    
    return launchOptions;
  });

  return config;
}; 