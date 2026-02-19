# Status da Refatoração da Codebase

## Visão geral
Este status consolida o progresso real das fases executadas para aproximar a codebase de uma versão refatorada, com baixo acoplamento e melhor manutenção.

## Progresso por frente

1. **Bootstrap e organização base** — **100%**
   - Inicialização centralizada com `bootApp`.
   - Padrão comum de sessão/dependências em páginas e actions principais.

2. **Padronização de actions HTTP** — **85%**
   - Helpers de redirect/guardas/flash implementados.
   - Maioria dos actions críticos já migrados.
   - Pendente: substituir alguns padrões locais restantes (ex.: helpers locais de redirectBack em actions específicos).

3. **Extração de serviços de domínio** — **70%**
   - `AuthService` concluído.
   - `HabitInputSanitizer` concluído.
   - `HabitAccessService` concluído.
   - `HabitCompletionService` concluído (extração de `habit_mark_action`).
   - Pendente: serviço transacional de perfil/preferências e possível separação em repositórios.

4. **SQL unificado para setup** — **100%**
   - Banco consolidado em `sql/doitly_unified.sql`.

5. **Documentação técnica da refatoração** — **90%**
   - Regras, arquitetura e fases 1-4 documentadas.
   - Pendente: checklist de regressão funcional por fluxo e matriz de risco por módulo.

## Estimativa de conclusão global
**~80% concluído** para uma base “já refatorada” no núcleo crítico.

## O que ainda falta para considerar "versão refatorada" pronta
1. Extrair `ProfileService` (atualização de usuário + preferências) de `update_profile_action.php`.
2. Extrair repositórios para consultas recorrentes de hábitos/perfil.
3. Reduzir lógica remanescente em actions para somente orquestração HTTP.
4. Definir e executar checklist de regressão manual por fluxo (login, cadastro, dashboard, hábitos, perfil, exportação).
5. Opcional recomendado: adicionar suíte mínima de testes automatizados para serviços novos.

## Próximo passo sugerido imediato
Fase 5: **refatorar perfil/preferências** (`update_profile_action.php` e `reset_appearance_action.php`) para serviço dedicado em `app/profile/`.
