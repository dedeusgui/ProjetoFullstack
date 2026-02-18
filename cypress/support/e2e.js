Cypress.Commands.add('assertNavbar', () => {
  cy.get('.doitly-navbar').should('be.visible');
  cy.get('.doitly-navbar-brand').should('contain', 'Doitly');
});
