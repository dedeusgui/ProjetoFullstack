Cypress.Commands.add('login', (email, password) => {
  cy.visit('/login.php');
  cy.get('#email').clear().type(email);
  cy.get('#password').clear().type(password, { log: false });
  cy.get('form[action="../actions/login_action.php"]').submit();
  cy.url().should('include', '/dashboard.php');
});

Cypress.Commands.add('loginDirect', (email, password) => {
  cy.visit('/login.php');
  cy.get('input[name="csrf_token"]').invoke('val').then((csrfToken) => {
    cy.request({
      method: 'POST',
      url: 'http://localhost/doitly/actions/login_action.php',
      form: true,
      body: {
        email,
        password,
        csrf_token: csrfToken
      },
      followRedirect: false
    }).its('status').should('be.oneOf', [302, 303]);
  });
});

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
