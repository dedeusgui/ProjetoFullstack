# Refatoração Fase 9: Repositórios e Checklist Final de Regressão

## Objetivo
Concluir o desacoplamento principal entre serviços e SQL, e formalizar o checklist final de regressão para encerramento da refatoração.

## Entregas desta fase

### 1) Introdução de repositórios
- `app/repository/UserRepository.php`
- `app/repository/UserSettingsRepository.php`

Esses repositórios concentram queries recorrentes de usuário e preferências.

### 2) Serviços atualizados para usar repositórios
- `app/auth/AuthService.php` agora depende de `UserRepository`.
- `app/profile/ProfileService.php` agora depende de `UserRepository` e `UserSettingsRepository`.

Resultado: SQL removido da camada de serviço nesses domínios críticos.

### 3) Checklist final de regressão (manual)
1. Cadastro válido com confirmação de senha.
2. Cadastro com e-mail duplicado deve falhar.
3. Login válido deve redirecionar para dashboard.
4. Login inválido deve respeitar rate limiting.
5. CSRF inválido em login/cadastro deve bloquear request.
6. Atualização de perfil (email/avatar/tema) deve persistir.
7. Troca de senha deve exigir senha atual correta.
8. Criação/edição/marcação/arquivamento/exclusão de hábito devem funcionar.
9. Exportação de dados CSV deve funcionar para usuário logado.
10. Logout deve encerrar sessão e bloquear rotas protegidas.
