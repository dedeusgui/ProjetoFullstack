const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://127.0.0.1:8000/public',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'cypress/support/e2e.js',
    viewportWidth: 1366,
    viewportHeight: 768,
    video: false,
    screenshotOnRunFailure: true,
  },
});
