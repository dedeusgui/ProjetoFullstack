/// <reference types="cypress" />

describe('Dashboard', () => {
  beforeEach(() => {
    // Evita custo de testar login aqui; foco é conteúdo do dashboard.
    cy.fixture('user').then((user) => {
      cy.loginDirect(user.email, user.password);
    });
    cy.visit('/dashboard.php');
  });

  it('renderiza os 4 cards de estatísticas principais', () => {
    // Valida os indicadores mais críticos da tela inicial.
    cy.get('.stat-card').should('have.length.at.least', 4);
    cy.contains('.stat-card .stat-label', 'Hábitos Ativos').should('be.visible');
    cy.contains('.stat-card .stat-label', 'Concluídos Hoje').should('be.visible');
    cy.contains('.stat-card .stat-label', 'Taxa de Sucesso').should('be.visible');
    cy.contains('.stat-card .stat-label', 'Sequência Atual').should('be.visible');
  });

  it('exibe seção Hábitos de Hoje', () => {
    // Confirma bloco central com hábitos planejados do dia.
    cy.contains('.card-title', 'Hábitos de Hoje').should('be.visible');
  });

  it('exibe card Análise Inteligente com badges de recomendação', () => {
    // Confirma presença de recomendações e estado/tendência.
    cy.contains('.card-title', 'Análise Inteligente').should('be.visible');
    cy.get('.recommendation-card .recommendation-badge').should('have.length.at.least', 2);
  });

  it('mostra ações rápidas com links corretos', () => {
    // Verifica navegação rápida para rotinas principais.
    cy.contains('a.doitly-btn', 'Novo Hábito').should('be.visible').and('have.attr', 'href', 'habits.php');
    cy.contains('a.doitly-btn', 'Ver Estatísticas').should('be.visible').and('have.attr', 'href', 'history.php');
    cy.contains('a.doitly-btn', 'Exportar Dados').should('be.visible').and('have.attr', 'href', '../actions/export_user_data_csv.php');
  });
});
