# Refatoração Fase 3: Serviços de Domínio para Hábitos

## Objetivo
Continuar a redução de acoplamento em `actions/` extraindo regras reutilizáveis de acesso ao domínio de hábitos.

## Entregas

### 1) Serviço de acesso a hábitos
- Novo arquivo: `app/habits/HabitAccessService.php`.
- Responsabilidade: validar se um hábito pertence ao usuário logado (`userOwnsHabit`).

### 2) Adoção do serviço em endpoints críticos
- `actions/habit_delete_action.php`
- `actions/habit_archive_action.php`
- `actions/habit_update_action.php`

Os endpoints agora delegam a validação de ownership para serviço dedicado, removendo SQL duplicado por action.

### 3) Padronização de retorno com helpers
- Substituição de blocos manuais de `$_SESSION + header + exit` por `actionFlashAndRedirect(...)` nos fluxos de exclusão/arquivamento/exportação.

## Ganhos
- Menos repetição em validações de segurança de acesso por usuário.
- Menor risco de divergência de regra entre endpoints.
- Base mais preparada para extrair `HabitService` completo na próxima fase.

## Próximo passo sugerido
- Extrair a lógica transacional de marcação/desmarcação (`habit_mark_action.php`) para um serviço dedicado em `app/habits/`.
