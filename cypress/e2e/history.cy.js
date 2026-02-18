const testUser = {
  email: Cypress.env('E2E_USER_EMAIL') || 'teste@doitly.com',
  password: Cypress.env('E2E_USER_PASSWORD') || 'senha123'
};

describe('Histórico', () => {
  beforeEach(() => {
    // Garante autenticação antes de acessar a área de histórico.
    cy.login(testUser.email, testUser.password);
    cy.visit('/history.php');
  });

  it('deve carregar os gráficos principais e a seção de conquistas', () => {
    // Confirma presença dos containers de gráficos e do bloco de conquistas.
    cy.get('#monthlyChart').should('be.visible');
    cy.get('#categoryChart').should('be.visible');
    cy.get('#completionRateChart').should('be.visible');
    cy.contains('.card-title', 'Conquistas').should('be.visible');
  });
});
