# Testes E2E com Cypress (Doitly)

## 1) Pré-requisitos

1. Tenha o **Node.js 18+** instalado.
2. Tenha o projeto Doitly rodando localmente com Apache/PHP + MySQL.
3. Confirme que a aplicação está acessível em:
   - `http://localhost/doitly/public`
4. Instale o Cypress no projeto:

```bash
npm init -y
npm install --save-dev cypress
```

> Se o projeto já possuir `package.json`, execute apenas o `npm install --save-dev cypress`.

---

## 2) Criar usuário fixo para testes no banco

Para os testes autenticados, use um usuário estável como:

- **E-mail:** `teste@doitly.com`
- **Senha:** `senha123`

### Exemplo SQL (ajuste o hash da senha para seu ambiente)

```sql
INSERT INTO users (name, email, password, is_active, email_verified)
VALUES ('Usuário Teste', 'teste@doitly.com', '$2y$10$QYw9Jv7m1CzR8uSJw8sVQe2VQW5yD9L3tWJxw9gQn2jvN2lJ0P4vG', 1, 1);
```

> O valor acima de `password` é um hash bcrypt de exemplo. Você pode gerar um novo hash com PHP (`password_hash('senha123', PASSWORD_DEFAULT)`).

Se preferir não fixar no código, use variáveis de ambiente ao rodar:

```bash
npx cypress run --env E2E_USER_EMAIL=teste@doitly.com,E2E_USER_PASSWORD=senha123
```

---

## 3) Abrir o Cypress pela primeira vez (modo interativo)

```bash
npx cypress open
```

Passos:
1. O Cypress abrirá a interface gráfica.
2. Escolha **E2E Testing**.
3. Selecione um navegador (ex.: Chrome).
4. Clique no arquivo `.cy.js` desejado para executar.
5. Observe os passos do teste em tempo real.

---

## 4) Rodar todos os testes em modo headless

```bash
npx cypress run
```

Esse comando executa todos os arquivos em `cypress/e2e/**/*.cy.js` sem abrir UI.

---

## 5) Rodar apenas um arquivo de teste específico

Use `--spec`:

```bash
npx cypress run --spec cypress/e2e/auth.cy.js
```

Outros exemplos:

```bash
npx cypress run --spec cypress/e2e/habits.cy.js
npx cypress run --spec cypress/e2e/settings.cy.js
```

---

## 6) Se um teste falhar: debug básico

1. Rode o teste no modo interativo:

```bash
npx cypress open
```

2. Analise:
   - passo exato que falhou;
   - seletor utilizado (`#id`, `[data-*]`, classes);
   - se a página estava autenticada;
   - se o banco tinha os dados esperados.

3. Use os artefatos gerados automaticamente:
   - **screenshots:** `cypress/screenshots/`
   - (se habilitado) **vídeos:** `cypress/videos/`

4. Em caso de flutuação por tempo de resposta:
   - prefira asserts que aguardam estado final (`should('be.visible')`, `should('exist')`);
   - evite `cy.wait()` fixo sem necessidade.

---

## 7) Estrutura dos testes criada

- `cypress/e2e/auth.cy.js`
- `cypress/e2e/dashboard.cy.js`
- `cypress/e2e/habits.cy.js`
- `cypress/e2e/history.cy.js`
- `cypress/e2e/settings.cy.js`
- `cypress/support/commands.js` (com `cy.login(email, password)`)
- `cypress/support/e2e.js`
- `cypress.config.js`

