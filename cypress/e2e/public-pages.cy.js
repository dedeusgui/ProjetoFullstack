describe('Páginas públicas essenciais', () => {
  it('renderiza landing page e permite navegação para cadastro', () => {
    cy.visit('/index.php');

    cy.assertNavbar();
    cy.contains('h1', 'Transforme Seus Objetivos em Hábitos Consistentes').should('be.visible');
    cy.contains('a', 'Começar Gratuitamente').should('have.attr', 'href', 'register.php').click();

    cy.url().should('include', '/register.php');
    cy.contains('h2', 'Crie sua conta').should('be.visible');
  });

  it('exibe os campos obrigatórios na tela de login', () => {
    cy.visit('/login.php');

    cy.assertNavbar();
    cy.get('form[action="../actions/login_action.php"]').should('be.visible');
    cy.get('#email').should('have.attr', 'type', 'email').and('have.attr', 'required');
    cy.get('#password').should('have.attr', 'type', 'password').and('have.attr', 'required');

    cy.contains('button', 'Entrar').should('be.visible');
    cy.contains('a', 'Criar conta').should('have.attr', 'href', 'register.php');
  });

  it('permite alternar visibilidade da senha no cadastro', () => {
    cy.visit('/register.php');

    cy.assertNavbar();
    cy.get('#password').should('have.attr', 'type', 'password');
    cy.get('button[title="Mostrar/Ocultar senha"]').first().click();
    cy.get('#password').should('have.attr', 'type', 'text');

    cy.get('button[title="Ocultar senha"]').first().click();
    cy.get('#password').should('have.attr', 'type', 'password');
  });
});
