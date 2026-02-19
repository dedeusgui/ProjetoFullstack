/// <reference types="cypress" />

describe('Página de hábitos', () => {
  beforeEach(() => {
    // Login por request para focar nos fluxos de hábitos.
    cy.fixture('user').then((user) => {
      cy.loginDirect(user.email, user.password);
    });
    cy.visit('/habits.php');
  });

  it('abre modal de criação ao clicar em Novo Hábito', () => {
    // Fluxo de UI essencial para iniciar criação de hábito.
    cy.contains('button', 'Novo Hábito').first().click();
    cy.get('#habitModal').should('be.visible');
    cy.get('#habitForm').should('be.visible');
  });

  it('cria hábito com sucesso pelo formulário modal', () => {
    // Fluxo crítico de cadastro de hábito.
    const habitName = `Hábito Cypress ${Date.now()}`;

    cy.get('#habitCategory option').eq(1).invoke('text').then((categoryName) => {
      cy.createHabit(habitName, categoryName.trim(), 'Manhã');
      cy.get('.alert-success-theme').should('contain', 'criado com sucesso');
    });
  });

  it('marca hábito como concluído e muda botão para Feito', () => {
    // Fluxo crítico de conclusão diária.
    const habitName = `Concluir Cypress ${Date.now()}`;

    cy.get('#habitCategory option').eq(1).invoke('text').then((categoryName) => {
      cy.createHabit(habitName, categoryName.trim(), 'Manhã');
      cy.contains('.habit-card', habitName).within(() => {
        cy.contains('button', 'Concluir').click();
      });

      cy.contains('.habit-card', habitName).within(() => {
        cy.contains('button', 'Feito').should('be.visible');
      });
    });
  });

  it('arquiva hábito e remove da lista ativa', () => {
    // Fluxo destrutivo controlado: hábito sai da lista principal.
    const habitName = `Arquivar Cypress ${Date.now()}`;

    cy.get('#habitCategory option').eq(1).invoke('text').then((categoryName) => {
      cy.createHabit(habitName, categoryName.trim(), 'Tarde');
      cy.contains('.habit-card', habitName).within(() => {
        cy.get('form[action="../actions/habit_archive_action.php"]').submit();
      });

      cy.get('.alert-success-theme').should('contain', 'arquivado com sucesso');
      cy.contains('.habit-card', habitName).should('not.exist');
    });
  });

  it('deleta hábito confirmando o window.confirm', () => {
    // Fluxo destrutivo com confirmação explícita.
    const habitName = `Excluir Cypress ${Date.now()}`;

    cy.get('#habitCategory option').eq(1).invoke('text').then((categoryName) => {
      cy.createHabit(habitName, categoryName.trim(), 'Noite');
      cy.window().then((win) => {
        cy.stub(win, 'confirm').returns(true);
      });

      cy.contains('.habit-card', habitName).within(() => {
        cy.get('button[title="Excluir"]').click();
      });

      cy.get('.alert-success-theme').should('contain', 'deletado com sucesso');
      cy.contains('.habit-card', habitName).should('not.exist');
    });
  });

  it('filtra hábitos por nome em tempo real', () => {
    // Detalhe de UX: busca instantânea por título do hábito.
    const nameA = `Filtro A ${Date.now()}`;
    const nameB = `Filtro B ${Date.now()}`;

    cy.get('#habitCategory option').eq(1).invoke('text').then((categoryName) => {
      const category = categoryName.trim();
      cy.createHabit(nameA, category, 'Manhã');
      cy.createHabit(nameB, category, 'Tarde');

      cy.get('#searchInput').clear().type(nameA);
      cy.contains('.habit-card', nameA).should('be.visible');
      cy.contains('.habit-card', nameB).should('not.be.visible');
    });
  });
});
