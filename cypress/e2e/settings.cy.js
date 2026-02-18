const testUser = {
  email: Cypress.env('E2E_USER_EMAIL') || 'teste@doitly.com',
  password: Cypress.env('E2E_USER_PASSWORD') || 'senha123'
};

describe('Configurações', () => {
  beforeEach(() => {
    // Reaproveita login antes de validar o modal de configurações.
    cy.login(testUser.email, testUser.password);
    cy.visit('/dashboard.php');
  });

  it('deve abrir o modal, alterar o campo de email e fechar o modal', () => {
    // Verifica abertura e fechamento do modal global de configurações.
    cy.get('[data-open-settings-modal]').first().click();
    cy.get('#settingsModalOverlay').should('be.visible');

    cy.get('#settingsEmail')
      .clear()
      .type(`alterado+${Date.now()}@doitly.com`)
      .should('have.value')
      .and('include', '@doitly.com');

    cy.get('[data-close-settings-modal]').first().click();
    cy.get('#settingsModalOverlay').should('not.be.visible');
  });
});
