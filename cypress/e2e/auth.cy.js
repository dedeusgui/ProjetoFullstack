const testUser = {
  email: Cypress.env('E2E_USER_EMAIL') || 'teste@doitly.com',
  password: Cypress.env('E2E_USER_PASSWORD') || 'senha123'
};

describe('Autenticação', () => {
  it('deve fazer login com sucesso usando credenciais válidas', () => {
    // Verifica o fluxo principal de login com usuário existente.
    cy.visit('/login.php');
    cy.get('#email').type(testUser.email);
    cy.get('#password').type(testUser.password, { log: false });
    cy.get('form[action="../actions/login_action.php"]').submit();

    cy.url().should('include', '/dashboard.php');
    cy.get('.dashboard-title').should('be.visible');
  });

  it('deve exibir erro ao tentar login com credenciais inválidas', () => {
    // Garante que o sistema bloqueia login com senha incorreta e mostra feedback ao usuário.
    cy.visit('/login.php');
    cy.get('#email').type(testUser.email);
    cy.get('#password').type('senha-errada', { log: false });
    cy.get('form[action="../actions/login_action.php"]').submit();

    cy.url().should('include', '/login.php');
    cy.get('.doitly-badge-danger, .alert-danger, .alert-danger-theme').should('be.visible');
  });

  it('deve cadastrar um novo usuário com dados válidos', () => {
    // Valida o cadastro mínimo criando um usuário único e confirmando redirecionamento ao dashboard.
    const timestamp = Date.now();
    const email = `novo.usuario.${timestamp}@doitly.com`;

    cy.visit('/register.php');
    cy.get('#name').type('Usuário Cypress');
    cy.get('#email').type(email);
    cy.get('#password').type('senha123', { log: false });
    cy.get('form[action="../actions/register_action.php"]').submit();

    cy.url().should('include', '/dashboard.php');
    cy.get('.dashboard-title').should('be.visible');
  });
});
