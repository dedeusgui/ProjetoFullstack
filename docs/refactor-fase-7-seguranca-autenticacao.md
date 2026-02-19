# Refatoração Fase 7: Segurança de Autenticação (CSRF)

## Objetivo
Reduzir risco de requisições forjadas em fluxos de autenticação críticos (login e cadastro).

## Entregas
- Helpers CSRF adicionados em `config/action_helpers.php`:
  - `ensureCsrfToken()`
  - `getCsrfToken()`
  - `actionRequireCsrf()`
- Inclusão de token CSRF em formulários:
  - `public/login.php`
  - `public/register.php`
- Validação obrigatória de CSRF em actions:
  - `actions/login_action.php`
  - `actions/register_action.php`

## Benefício
- Protege o fluxo de autenticação contra envios forjados entre origens (CSRF) sem alterar UX principal.
