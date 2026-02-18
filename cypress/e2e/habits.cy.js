const testUser = {
  email: Cypress.env('E2E_USER_EMAIL') || 'teste@doitly.com',
  password: Cypress.env('E2E_USER_PASSWORD') || 'senha123'
};

describe('Hábitos', () => {
  beforeEach(() => {
    // Cada teste parte de uma sessão autenticada para acessar a página de hábitos.
    cy.login(testUser.email, testUser.password);
    cy.visit('/habits.php');
  });

  it('deve criar, concluir, arquivar e deletar um hábito', () => {
    // Cobre o fluxo mínimo de ciclo de vida de um hábito na interface.
    const habitName = `Hábito Cypress ${Date.now()}`;

    // Abre modal de criação e envia o formulário com campos obrigatórios.
    cy.contains('button', 'Novo Hábito').click();
    cy.get('#habitModal').should('be.visible');
    cy.get('#habitName').type(habitName);
    cy.get('#habitCategory').select(1);
    cy.get('#habitTime').select('Manhã');
    cy.get('#habitForm').submit();

    // Valida que o hábito aparece na lista principal.
    cy.contains('.habit-card h4', habitName).should('be.visible');

    // Marca o hábito como concluído.
    cy.contains('.habit-card h4', habitName)
      .parents('.habit-card')
      .within(() => {
        cy.contains('button', 'Concluir').click();
      });

    cy.contains('.habit-card h4', habitName)
      .parents('.habit-card')
      .within(() => {
        cy.contains('button', 'Feito').should('exist');
      });

    // Arquiva o hábito e confirma que ele sai da lista de hoje.
    cy.contains('.habit-card h4', habitName)
      .parents('.habit-card')
      .within(() => {
        cy.get('button[title="Arquivar"]').click();
      });

    cy.contains('.habit-card h4', habitName).should('not.exist');

    // Restaura o hábito arquivado para seguir com exclusão no fluxo principal.
    cy.contains('.habit-item strong', habitName)
      .parents('.habit-item')
      .within(() => {
        cy.contains('button', 'Restaurar').click();
      });

    // Deleta o hábito usando stub de window.confirm.
    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true).as('confirmDelete');
    });

    cy.contains('.habit-card h4', habitName)
      .parents('.habit-card')
      .within(() => {
        cy.get('button[title="Excluir"]').click();
      });

    cy.get('@confirmDelete').should('have.been.calledOnce');
    cy.contains('.habit-card h4', habitName).should('not.exist');
  });
});
