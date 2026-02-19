# Refatoração Fase 2: Serviços de Domínio e Padronização de Actions

## Objetivo da fase
Escalar o desacoplamento iniciado na Fase 1, reduzindo lógica duplicada em endpoints HTTP e movendo regras reutilizáveis para componentes de aplicação.

## Entregas desta fase

### 1) Padronização de fluxo HTTP em `actions/`
- Criação de `config/action_helpers.php` com funções utilitárias de actions:
  - `actionRequireLoggedIn()`
  - `actionRequirePost()`
  - `actionResolveReturnPath()`
  - `actionFlashAndRedirect()`
- Benefício: remove repetição de `header(...) + exit + session message` em múltiplos pontos.

### 2) Extração de validação/sanitização de hábitos
- Criação de `app/habits/HabitInputSanitizer.php`.
- Centralização de regras compartilhadas entre criar/editar hábito:
  - validação de título/categoria/período;
  - normalização de frequência e tipo de meta;
  - saneamento de cor;
  - normalização de dias alvo.
- Benefício: reduz duplicação e facilita manutenção de regras de formulário.

### 3) Actions mais finos e orientados a responsabilidade
- `actions/habit_create_action.php` e `actions/habit_update_action.php` agora delegam validação para serviço dedicado.
- `actions/login_action.php` e `actions/register_action.php` usam fluxo de resposta padronizado para erros/sucesso.
- `actions/update_profile_action.php` e `actions/reset_appearance_action.php` adotam fluxo consistente de retorno.

## Resultado arquitetural
- Menor acoplamento entre parsing de request e regras de domínio.
- Menor repetição de validações entre endpoints.
- Base pronta para Fase 3 (repositórios e serviços de hábitos/perfil).

## Próxima fase sugerida
1. Extrair consultas SQL de hábitos/perfil para repositórios (`app/*Repository`).
2. Criar serviços transacionais para operações complexas (`HabitService`, `ProfileService`).
3. Adicionar testes de regressão para validações centrais de formulário.
