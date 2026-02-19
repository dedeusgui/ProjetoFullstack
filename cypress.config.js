const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8080/ProjetoFullstack/public',
    defaultCommandTimeout: 10000,
    screenshotsOnRunFailure: true,
    video: false,
    env: {
      userEmail: 'teste@doitly.com',
      userPassword: 'senha123',
      userName: 'Usuário Teste'
    }
  }
});