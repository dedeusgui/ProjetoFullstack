const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost/doitly/public',
    defaultCommandTimeout: 8000,
    screenshotsOnRunFailure: true,
    video: false,
    env: {
      testUserEmail: 'teste@doitly.com',
      testUserPassword: 'senha123',
      testUserName: 'Usuário Teste'
    },
    supportFile: 'cypress/support/e2e.js'
  }
});
