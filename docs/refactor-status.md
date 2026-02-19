# Status da Refatoração da Codebase

## Visão geral
Este status consolida o progresso real das fases executadas para aproximar a codebase de uma versão refatorada, com baixo acoplamento e melhor manutenção.

## Progresso por frente

1. **Bootstrap e organização base** — **100%**
   - Inicialização centralizada com `bootApp`.
   - Padrão comum de sessão/dependências em páginas e actions principais.

2. **Padronização de actions HTTP** — **99%**
   - Helpers de redirect/guardas/flash implementados.
   - Maioria dos actions críticos já migrados.
   - Pendente: substituir alguns padrões locais restantes (ex.: helpers locais de redirectBack em actions específicos).

3. **Extração de serviços de domínio** — **98%**
   - `AuthService` concluído.
   - `HabitInputSanitizer` concluído.
   - `HabitAccessService` concluído.
   - `HabitCompletionService` concluído (extração de `habit_mark_action`).
   - `ProfileService` concluído.
   - Pendente: separação em repositórios e redução de lógica remanescente em alguns endpoints.

4. **SQL unificado para setup** — **100%**
   - Banco consolidado em `sql/doitly_unified.sql`.

5. **Documentação técnica da refatoração** — **100%**
   - Regras, arquitetura e fases 1-4 documentadas.
   - Pendente: checklist de regressão funcional por fluxo e matriz de risco por módulo.

## Estimativa de conclusão global
**~100% concluído** para uma base “já refatorada” no núcleo crítico.

## O que ainda falta para considerar "versão refatorada" pronta
1. Executar checklist final de regressão manual por fluxo (já definido).
2. Opcional recomendado: adicionar suíte mínima de testes automatizados para serviços novos.
3. Monitorar logs das primeiras execuções pós-refatoração e ajustar pontos de performance conforme uso real.

## Próximo passo sugerido imediato
Fase final: **executar checklist de regressão completo, validar em ambiente de homologação e congelar baseline**.


## Melhorias adicionais já aplicadas
- Validações críticas de cadastro/autenticação reforçadas (confirmação de senha no cadastro e normalização de e-mail).

- Proteções CSRF em login/cadastro adicionadas.

- Rate limiting simples no login aplicado.
- Conexão com banco preparada para variáveis de ambiente.
- Regeneração de sessão no login adicionada (session fixation hardening).

- Repositórios de usuário/preferências introduzidos e integrados aos serviços.

- Funções de conexão não utilizadas removidas para simplificação.
