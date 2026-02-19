// cypress/support/commands.js

// ─── LOGIN VIA UI (para testes de autenticação) ───────────────────────────────
Cypress.Commands.add('login', (email, password) => {
  cy.visit('/login.php');
  cy.get('#email').clear().type(email);
  cy.get('#password').clear().type(password);
  cy.get('form').first().submit();
  cy.url().should('include', 'dashboard.php');
});

// ─── LOGIN COM CACHE DE SESSÃO (para testes que não testam login em si) ───────
Cypress.Commands.add('loginDirect', (email, password) => {
  cy.session([email, password], () => {
    cy.visit('/login.php');
    cy.get('#email').clear().type(email);
    cy.get('#password').clear().type(password);
    cy.get('form').first().submit();
    cy.url().should('include', 'dashboard.php');
  });
});

// ─── CRIAR HÁBITO VIA UI ──────────────────────────────────────────────────────
Cypress.Commands.add('createHabit', (name, category, time) => {
  cy.contains('button', 'Novo Hábito').first().click();
  cy.get('#habitModal').should('be.visible');

  cy.get('#habitName').clear().type(name);

  if (category) {
    cy.get('#habitCategory').select(category);
  } else {
    cy.get('#habitCategory option').eq(1).invoke('text').then((optionText) => {
      cy.get('#habitCategory').select(optionText.trim());
    });
  }

  cy.get('#habitTime').select(time || 'Manhã');
  cy.get('#habitForm').submit();

  cy.get('.alert-success-theme').should('be.visible');
  cy.contains('.habit-card', name).should('be.visible');
});
