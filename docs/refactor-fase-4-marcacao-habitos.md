# Refatoração Fase 4: Serviço de Marcação de Hábitos

## Objetivo
Extrair a lógica de marcação/desmarcação de hábitos de `actions/habit_mark_action.php` para um serviço de domínio reutilizável.

## Entregas

### 1) Novo serviço de domínio
- Arquivo: `app/habits/HabitCompletionService.php`.
- Responsabilidades extraídas:
  - validação de status do hábito (ativo/arquivado);
  - validação de agendamento por data;
  - alternância entre conclusão e remoção de conclusão;
  - fallback transacional para stored procedures;
  - invalidação de snapshot de recomendação;
  - cálculo da mensagem de próxima execução.

### 2) `habit_mark_action.php` simplificado
- Action reduzido para:
  - validação de entrada HTTP;
  - coleta de payload da request;
  - delegação ao serviço;
  - definição de flash message e redirect.

## Ganhos
- Redução forte de complexidade no endpoint.
- Melhor testabilidade da regra de negócio de conclusão de hábito.
- Menor acoplamento entre camada HTTP e lógica transacional.

## Próximo passo sugerido
- Fase 5: extrair serviço de perfil/preferências para reduzir complexidade de `update_profile_action.php`.
