/// <reference types="cypress" />

describe('Autenticação (login e cadastro)', () => {
  beforeEach(() => {
    // Garante isolamento de sessão entre os testes de autenticação.
    cy.request({ url: 'http://localhost/doitly/actions/logout_action.php', failOnStatusCode: false });
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  it('faz login com credenciais válidas e redireciona para dashboard', () => {
    // Cenário crítico: usuário válido consegue acessar o sistema.
    cy.fixture('user').then((user) => {
      cy.visit('/login.php');
      cy.get('#email').type(user.email);
      cy.get('#password').type(user.password, { log: false });
      cy.get('form[action="../actions/login_action.php"]').submit();

      cy.url().should('include', '/dashboard.php');
      cy.contains('h1', 'Bem-vindo').should('be.visible');
    });
  });

  it('exibe erro no login com credenciais inválidas', () => {
    // Cenário crítico: senha incorreta deve retornar feedback visual.
    cy.visit('/login.php');
    cy.get('#email').type('teste@doitly.com');
    cy.get('#password').type('senha-invalida', { log: false });
    cy.get('form[action="../actions/login_action.php"]').submit();

    cy.url().should('include', '/login.php');
    cy.get('.alert-danger, .alert-danger-theme').should('be.visible').and('contain', 'incorretos');
  });

  it('alterna visibilidade da senha no login', () => {
    // Detalhe de UI: botão com ícone deve alternar password/text.
    cy.visit('/login.php');

    cy.get('#password').should('have.attr', 'type', 'password');
    cy.get('button[title*="senha"]').first().click();
    cy.get('#password').should('have.attr', 'type', 'text');
    cy.get('button[title*="senha"]').first().click();
    cy.get('#password').should('have.attr', 'type', 'password');
  });

  it('mostra link para criar conta na tela de login', () => {
    // Detalhe de navegação: link de onboarding deve estar correto.
    cy.visit('/login.php');
    cy.contains('a', 'Criar conta').should('be.visible').and('have.attr', 'href', 'register.php');
  });

  it('cadastro com dados válidos redireciona para dashboard', () => {
    // Cenário crítico: novo usuário deve conseguir criar conta.
    const uniqueEmail = `novo+${Date.now()}@doitly.com`;

    cy.visit('/register.php');
    cy.get('#name').type('Usuário Cypress');
    cy.get('#email').type(uniqueEmail);
    cy.get('#password').type('senha123', { log: false });
    cy.get('#confirm_password').type('senha123', { log: false });
    cy.get('form[action="../actions/register_action.php"]').submit();

    cy.url().should('include', '/dashboard.php');
    cy.contains('h1', 'Bem-vindo').should('be.visible');
  });

  it('impede cadastro com email já existente', () => {
    // Regra crítica: e-mail deve ser único.
    cy.fixture('user').then((user) => {
      cy.visit('/register.php');
      cy.get('#name').type(user.name);
      cy.get('#email').type(user.email);
      cy.get('#password').type(user.password, { log: false });
      cy.get('#confirm_password').type(user.password, { log: false });
      cy.get('form[action="../actions/register_action.php"]').submit();

      cy.url().should('include', '/register.php');
      cy.get('.alert-danger, .alert-danger-theme').should('be.visible').and('contain', 'já está cadastrado');
    });
  });

  it('valida senhas diferentes no cadastro', () => {
    // Regra crítica: confirmação de senha deve bater com senha.
    cy.visit('/register.php');
    cy.get('#name').type('Usuário Senhas Diferentes');
    cy.get('#email').type(`diff+${Date.now()}@doitly.com`);
    cy.get('#password').type('senha123', { log: false });
    cy.get('#confirm_password').type('senha456', { log: false });
    cy.get('form[action="../actions/register_action.php"]').submit();

    cy.url().should('include', '/register.php');
    cy.get('.alert-danger, .alert-danger-theme').should('be.visible').and('contain', 'não conferem');
  });

  it('mostra link para fazer login na tela de cadastro', () => {
    // Detalhe de navegação: retorno para login deve estar acessível.
    cy.visit('/register.php');
    cy.contains('a', 'Fazer Login').should('be.visible').and('have.attr', 'href', 'login.php');
  });
});
