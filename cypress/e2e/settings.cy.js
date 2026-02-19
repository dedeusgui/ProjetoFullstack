/// <reference types="cypress" />

describe('Modal de configurações', () => {
  const protectedPages = ['/dashboard.php', '/habits.php', '/history.php'];

  beforeEach(() => {
    // Reaproveita sessão autenticada para abrir o modal em páginas protegidas.
    cy.fixture('user').then((user) => {
      cy.loginDirect(user.email, user.password);
    });
  });

  protectedPages.forEach((page) => {
    it(`abre e fecha o modal de configurações em ${page}`, () => {
      // Valida gatilhos de abertura e fechamento do modal.
      cy.visit(page);
      cy.get('[data-open-settings-modal]').first().click();
      cy.get('#settingsModalOverlay').should('be.visible').and('have.attr', 'aria-hidden', 'false');
      cy.get('[data-close-settings-modal]').first().click();
      cy.get('#settingsModalOverlay').should('not.be.visible').and('have.attr', 'aria-hidden', 'true');
    });

    it(`preenche o email de configurações e alterna tema em ${page}`, () => {
      // Valida binding de dados do usuário e efeito visual do toggle de tema.
      cy.fixture('user').then((user) => {
        cy.visit(page);
        cy.get('[data-open-settings-modal]').first().click();

        cy.get('#settingsEmail').should('have.value', user.email);

        cy.get('html').then(($html) => {
          const isDark = $html.attr('data-theme') === 'dark';
          cy.get('[data-theme-toggle]').click({ force: true }); // force: checkbox oculto visualmente pelo CSS

          if (isDark) {
            cy.get('html').should('not.have.attr', 'data-theme', 'dark');
          } else {
            cy.get('html').should('have.attr', 'data-theme', 'dark');
          }
        });
      });
    });
  });
});
