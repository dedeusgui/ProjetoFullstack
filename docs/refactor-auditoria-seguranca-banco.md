# Auditoria de Segurança e Banco (Refatoração Contínua)

## Verificações executadas nesta rodada
1. Revisão de queries em `actions/`, `app/`, `config/` para identificar SQL dinâmico inseguro.
2. Conferência de uso de `prepare + bind_param` nos fluxos críticos de autenticação e hábitos.
3. Revisão de validações de cadastro/login e risco de CSRF em formulários públicos.
4. Revisão de índices relevantes no script unificado `sql/doitly_unified.sql`.

## Achados
- Fluxos principais estão usando **prepared statements** (risco de SQL Injection reduzido).
- Existem índices e constraints importantes no schema unificado (PK/FK/índices por data e usuário).
- Gap de segurança identificado e corrigido nesta fase: ausência de proteção CSRF em login/cadastro.

## Correções aplicadas nesta fase
- Token CSRF adicionado e validado em login/cadastro.
- Normalização de e-mail e confirmação de senha no cadastro já aplicadas nas fases anteriores.

## Otimização de banco (estado atual)
- O schema já possui índice único para e-mail e índices compostos relevantes para hábitos/completions.
- Próxima otimização planejada: extrair repositórios e medir consultas com maior custo antes de criar novos índices (evita indexação prematura).

## Pendências de hardening
1. Expandir CSRF para todos os POST críticos além de login/cadastro.
2. Introduzir política de rate limiting em autenticação.
3. Migrar credenciais para variáveis de ambiente (evitar credenciais fixas em código).
4. Criar checklist de auditoria recorrente por release.
