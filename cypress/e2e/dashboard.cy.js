const testUser = {
  email: Cypress.env('E2E_USER_EMAIL') || 'teste@doitly.com',
  password: Cypress.env('E2E_USER_PASSWORD') || 'senha123'
};

describe('Dashboard', () => {
  beforeEach(() => {
    // Reaproveita login para garantir que cada teste comece autenticado.
    cy.login(testUser.email, testUser.password);
  });

  it('deve carregar o dashboard com 4 stat cards e a seção de hábitos de hoje', () => {
    // Confirma renderização dos indicadores principais e da lista/seção de hábitos do dia.
    cy.get('.quick-stats .stat-card').should('have.length', 4);
    cy.contains('.card-title', 'Hábitos de Hoje').should('be.visible');
    cy.get('.grid-col-12 .dashboard-card').contains('Hábitos de Hoje').should('be.visible');
    cy.get('.habit-item, .empty-state').should('exist');
  });
});
