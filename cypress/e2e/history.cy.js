/// <reference types="cypress" />

describe('Histórico', () => {
  beforeEach(() => {
    // Login direto para validar apenas conteúdo da página de histórico.
    cy.fixture('user').then((user) => {
      cy.loginDirect(user.email, user.password);
    });
    cy.visit('/history.php');
  });

  it('renderiza os 4 stat-cards do histórico', () => {
    // Indicadores principais de desempenho histórico.
    cy.get('.stat-card').should('have.length.at.least', 4);
    cy.contains('.stat-card .stat-label', 'Total Concluído').should('be.visible');
    cy.contains('.stat-card .stat-label', 'Taxa de Sucesso').should('be.visible');
    cy.contains('.stat-card .stat-label', 'Sequência Atual').should('be.visible');
    cy.contains('.stat-card .stat-label', 'Melhor Sequência').should('be.visible');
  });

  it('exibe seção de conquistas', () => {
    // Confirma renderização do hub de conquistas.
    cy.contains('.card-title', 'Conquistas (Hub Principal)').should('be.visible');
  });

  it('exibe tabela de Estatísticas por Categoria', () => {
    // Confirma presença da tabela de breakdown por categoria.
    cy.contains('.card-title', 'Estatísticas por Categoria')
      .closest('.dashboard-card')
      .find('table')
      .should('be.visible');
  });

  it('exibe card de progressão e recompensas com barra de XP e nível', () => {
    // Confirma bloco gamificado de progresso.
    cy.contains('.card-title', 'Progressão e Recompensas').should('be.visible');
    cy.contains('.doitly-badge', 'Nível').should('be.visible');
    cy.contains('small', 'XP no nível atual').should('be.visible');
  });
});
