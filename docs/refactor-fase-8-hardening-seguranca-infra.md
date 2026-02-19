# Refatoração Fase 8: Hardening de Segurança e Infra

## Objetivo
Fechar a etapa final de refatoração no núcleo crítico com reforços de segurança e infra de conexão.

## Entregas
1. **Conexão de banco com variáveis de ambiente** (`config/conexao.php`)
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT` via env com fallback seguro.
2. **Mitigação de session fixation** (`config/auth.php`)
   - `session_regenerate_id(true)` no login.
3. **Rate limiting simples para login** (`config/action_helpers.php` + `actions/login_action.php`)
   - Bloqueio temporário por excesso de tentativas na janela de tempo.

## Benefício
- Menor exposição de credenciais hardcoded.
- Redução de risco de session hijacking/fixation.
- Redução de risco de brute force em autenticação.
