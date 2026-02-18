# Guia rápido de Cypress no Doitly

Este guia te mostra **o mínimo necessário** para rodar testes E2E no projeto e começar a evoluir sua suíte.

## 1) O que foi coberto (escopo mínimo)

Foram criados testes para as funcionalidades públicas mais importantes, sem depender de banco:

- Render da landing page (`index.php`) e CTA para cadastro.
- Render da página de login e validação dos campos obrigatórios.
- Comportamento de UI no cadastro (mostrar/ocultar senha).

Arquivo da suíte: `cypress/e2e/public-pages.cy.js`.

## 2) Pré-requisitos

- Cypress já instalado (global ou no projeto).
- PHP disponível no terminal.

## 3) Subir a aplicação para teste

No diretório raiz do projeto:

```bash
php -S 127.0.0.1:8000 -t .
```

> O Cypress está configurado com `baseUrl = http://127.0.0.1:8000/public`.

## 4) Como executar os testes

### Modo interativo (recomendado para aprender)

```bash
cypress open
```

Depois:
1. Escolha **E2E Testing**.
2. Selecione o navegador.
3. Clique no spec `public-pages.cy.js`.

### Modo terminal (CI / execução rápida)

```bash
cypress run --e2e
```

Se você usa `npx`:

```bash
npx cypress run --e2e
```

## 5) Estrutura criada

- `cypress.config.js`: configuração principal de execução E2E.
- `cypress/support/e2e.js`: comando customizado `cy.assertNavbar()`.
- `cypress/e2e/public-pages.cy.js`: cenários mínimos do fluxo público.

## 6) Como criar seu próximo teste (passo a passo)

Use esse template mental:

1. **Escolha 1 funcionalidade crítica** (ex.: login válido).
2. **Defina o resultado esperado** (ex.: redirecionar para dashboard).
3. **Escreva um teste simples** com `cy.visit`, `cy.get`, `cy.contains`, `cy.click`.
4. **Rode só esse spec** no `cypress open` para depurar rápido.
5. Quando estabilizar, rode `cypress run --e2e` para validar tudo.

Exemplo básico:

```js
it('deve abrir a tela de login', () => {
  cy.visit('/login.php');
  cy.contains('h2', 'Bem-vindo de volta!').should('be.visible');
});
```

## 7) Próximos testes recomendados (ordem sugerida)

1. **Cadastro com sucesso** (precisa ambiente com banco disponível).
2. **Login com sucesso e falha**.
3. **Proteção de rota** (dashboard/habits sem sessão).
4. **CRUD de hábitos** (criar, concluir, editar, arquivar).
5. **Filtro e busca de hábitos**.

## 8) Dicas para você aprender mais rápido

- Comece por testes pequenos e independentes.
- Prefira seletores estáveis (`id`, `name`, `data-cy`).
- Evite validar texto muito volátil.
- Sempre que um teste falhar, use o `cypress open` para inspecionar passo a passo.

---

Se quiser, no próximo passo eu posso te entregar uma segunda suíte já cobrindo **login + cadastro com banco seedado** para evoluir do nível básico para intermediário.
