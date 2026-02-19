// cypress/e2e/auth.cy.js
/// <reference types="cypress" />

describe('Autenticação - Login e Cadastro', () => {

  beforeEach(() => {
    // Limpa cookies antes de cada teste para garantir isolamento de sessão
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  // ─── LOGIN ────────────────────────────────────────────────────────────────

  it('deve fazer login com credenciais válidas e ir para o dashboard', () => {
    cy.visit('/login.php');
    cy.get('#email').type('teste@doitly.com');
    cy.get('#password').type('senha123');
    cy.get('form').first().submit();
    cy.url().should('include', 'dashboard.php');
    cy.contains('Bem-vindo').should('be.visible');
  });

  it('deve exibir erro ao logar com senha incorreta', () => {
    cy.visit('/login.php');
    cy.get('#email').type('teste@doitly.com');
    cy.get('#password').type('senhaerrada');
    cy.get('form').first().submit();
    cy.get('.alert-danger, .alert-danger-theme').should('be.visible');
    cy.url().should('include', 'login.php');
  });

  it('deve exibir erro ao logar com email não cadastrado', () => {
    cy.visit('/login.php');
    cy.get('#email').type('naoexiste@teste.com');
    cy.get('#password').type('qualquercoisa');
    cy.get('form').first().submit();
    cy.get('.alert-danger, .alert-danger-theme').should('be.visible');
  });

  it('deve alternar visibilidade da senha ao clicar no botão de olho', () => {
    cy.visit('/login.php');
    // Senha começa oculta
    cy.get('#password').should('have.attr', 'type', 'password');
    // Clica no toggle
    cy.get('button[title*="senha"]').first().click();
    // Senha agora visível
    cy.get('#password').should('have.attr', 'type', 'text');
    // Clica novamente para ocultar
    cy.get('button[title*="senha"]').first().click();
    cy.get('#password').should('have.attr', 'type', 'password');
  });

  it('deve ter link para a página de cadastro', () => {
    cy.visit('/login.php');
    cy.contains('a', 'Criar conta').should('be.visible').and('have.attr', 'href').and('include', 'register.php');
  });

  // ─── CADASTRO ─────────────────────────────────────────────────────────────

  it('deve cadastrar novo usuário com sucesso e redirecionar ao dashboard', () => {
    const uniqueEmail = `novo+${Date.now()}@doitly.com`;
    cy.visit('/register.php');
    cy.get('#name').type('Usuário Cypress');
    cy.get('#email').type(uniqueEmail);
    cy.get('#password').type('senha123');
    cy.get('#confirm_password').type('senha123');
    cy.get('form').first().submit();
    cy.url().should('include', 'dashboard.php');
    cy.contains('Bem-vindo').should('be.visible');
  });

  it('deve exibir erro ao cadastrar com email já existente', () => {
    cy.visit('/register.php');
    cy.get('#name').type('Usuário Teste');
    cy.get('#email').type('teste@doitly.com'); // email já cadastrado
    cy.get('#password').type('senha123');
    cy.get('#confirm_password').type('senha123');
    cy.get('form').first().submit();
    cy.get('.alert-danger, .alert-danger-theme').should('be.visible');
    cy.url().should('include', 'register.php');
  });

  it('deve exibir erro quando as senhas não conferem', () => {
    cy.visit('/register.php');
    cy.get('#name').type('Usuário Novo');
    cy.get('#email').type(`diff+${Date.now()}@doitly.com`);
    cy.get('#password').type('senha123');
    cy.get('#confirm_password').type('senhadiferente');
    cy.get('form').first().submit();
    cy.get('.alert-danger, .alert-danger-theme').should('be.visible');
    cy.url().should('include', 'register.php');
  });

  it('deve ter link para a página de login', () => {
    cy.visit('/register.php');
    cy.contains('a', 'Fazer Login').should('be.visible').and('have.attr', 'href').and('include', 'login.php');
  });
});
