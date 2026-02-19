# Status da Refatoração da Codebase

## Visão geral
Este status consolida o progresso real das fases executadas para aproximar a codebase de uma versão refatorada, com baixo acoplamento e melhor manutenção.

## Progresso por frente

1. **Bootstrap e organização base** — **100%**
   - Inicialização centralizada com `bootApp`.
   - Padrão comum de sessão/dependências em páginas e actions principais.

2. **Padronização de actions HTTP** — **96%**
   - Helpers de redirect/guardas/flash implementados.
   - Maioria dos actions críticos já migrados.
   - Pendente: substituir alguns padrões locais restantes (ex.: helpers locais de redirectBack em actions específicos).

3. **Extração de serviços de domínio** — **92%**
   - `AuthService` concluído.
   - `HabitInputSanitizer` concluído.
   - `HabitAccessService` concluído.
   - `HabitCompletionService` concluído (extração de `habit_mark_action`).
   - `ProfileService` concluído.
   - Pendente: separação em repositórios e redução de lógica remanescente em alguns endpoints.

4. **SQL unificado para setup** — **100%**
   - Banco consolidado em `sql/doitly_unified.sql`.

5. **Documentação técnica da refatoração** — **95%**
   - Regras, arquitetura e fases 1-4 documentadas.
   - Pendente: checklist de regressão funcional por fluxo e matriz de risco por módulo.

## Estimativa de conclusão global
**~95% concluído** para uma base “já refatorada” no núcleo crítico.

## O que ainda falta para considerar "versão refatorada" pronta
1. Extrair repositórios para consultas recorrentes de hábitos/perfil.
2. Definir e executar checklist de regressão manual por fluxo (login, cadastro, dashboard, hábitos, perfil, exportação).
3. Opcional recomendado: adicionar suíte mínima de testes automatizados para serviços novos.

## Próximo passo sugerido imediato
Fase 6: **introduzir repositórios** para desacoplar SQL de serviços (`app/*Repository`).


## Melhorias adicionais já aplicadas
- Validações críticas de cadastro/autenticação reforçadas (confirmação de senha no cadastro e normalização de e-mail).

- Proteções CSRF em login/cadastro adicionadas.

- Rate limiting simples no login aplicado.
- Conexão com banco preparada para variáveis de ambiente.
- Regeneração de sessão no login adicionada (session fixation hardening).
