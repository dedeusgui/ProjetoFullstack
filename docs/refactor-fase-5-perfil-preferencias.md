# Refatoração Fase 5: Perfil e Preferências

## Objetivo
Extrair regras de atualização de perfil/preferências da camada HTTP para um serviço de domínio em `app/profile/`.

## Entregas

### 1) Serviço de domínio de perfil
- Arquivo: `app/profile/ProfileService.php`.
- Responsabilidades centralizadas:
  - validação de input de perfil (email, URL, cores, escala);
  - validação de troca de senha (incluindo senha atual);
  - atualização transacional de `users` + `user_settings`;
  - reset de aparência para os padrões.

### 2) Simplificação dos actions
- `actions/update_profile_action.php` agora delega toda regra ao `ProfileService`.
- `actions/reset_appearance_action.php` agora delega reset ao `ProfileService`.

## Ganhos
- Menos complexidade e branching em endpoints HTTP.
- Maior consistência das regras de perfil em ponto único.
- Base pronta para próxima extração de repositórios de dados.
