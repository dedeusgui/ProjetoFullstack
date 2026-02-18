Cypress.Commands.add('login', (email, password) => {
  cy.clearCookies();
  cy.clearLocalStorage();

  cy.visit('/login.php');
  cy.get('#email').should('be.visible').clear().type(email);
  cy.get('#password').should('be.visible').clear().type(password, { log: false });
  cy.get('form[action="../actions/login_action.php"]').submit();

  cy.url().should('include', '/dashboard.php');
});
