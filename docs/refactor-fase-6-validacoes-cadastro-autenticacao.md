# Refatoração Fase 6: Validações de Cadastro e Autenticação

## Objetivo
Corrigir erros comuns que costumam passar despercebidos em bases iniciais de produto, reforçando segurança e consistência no fluxo de autenticação.

## Ajustes aplicados
1. **Confirmação de senha no cadastro**
   - Campo `confirm_password` adicionado na tela de registro.
   - Validação server-side para garantir que `password` e `confirm_password` coincidam.

2. **Normalização de e-mail**
   - E-mails agora são normalizados (`trim + lowercase`) em login e cadastro.
   - Serviço de autenticação (`AuthService`) também normaliza internamente para manter consistência.

## Benefício
- Reduz inconsistências de autenticação por variação de e-mail.
- Evita criação de conta com senha digitada incorretamente por falta de confirmação.
- Aumenta robustez do fluxo sem alterar regra de negócio principal.
