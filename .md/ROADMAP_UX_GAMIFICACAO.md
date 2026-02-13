# Roadmap de UX e Gamificação (Doitly)

Este documento aprofunda as ideias de onboarding, segurança no cadastro, notificações e gamificação com foco em **entrega incremental**.

## 1) Diagnóstico rápido do estado atual

- O cadastro valida senha mínima apenas no backend (`strlen >= 6`) e sem indicador visual de força no formulário.
- O fluxo de registro cria usuário com `email_verified = 0`, faz login automático e envia direto para o dashboard.
- O dashboard/hábitos exibem feedback com alertas em sessão (renderizados no topo da página após redirect).
- As conquistas hoje aparecem dentro de `history.php`, sem página dedicada.
- O schema já possui colunas úteis para notificações em `user_settings` (`notifications_enabled`, `email_notifications`, `reminder_notifications`, `daily_summary_time`).

---

## 2) Priorização sugerida (impacto x esforço)

### Sprint 1 (alto impacto / baixo risco)
1. **Força de senha no frontend** (item 6)
2. **Onboarding em 2-3 passos** (item 7)
3. **Toast notifications in-app** (item 11)

### Sprint 2 (base de retenção)
4. **Favoritar hábitos + lembretes** (item 8)
5. **Micro-animação ao completar hábito** (item 13)

### Sprint 3 (maturidade / diferencial acadêmico)
6. **Página dedicada de conquistas (`achievements.php`)** (item 15)
7. **Confirmação de email real** (item 4)

---

## 3) Especificação detalhada por ideia

## 6. Força da senha no frontend

### Objetivo
Evitar erro tardio no submit e aumentar taxa de conversão no cadastro.

### UX
- Barra de força com 4 níveis: **Fraca / Média / Boa / Forte**.
- Checklist dinâmico:
  - 6+ caracteres
  - 1 letra maiúscula
  - 1 número
  - 1 caractere especial (opcional no MVP)
- Estado do botão "Cadastrar":
  - MVP: permitir submit quando >= 6 caracteres (compatível com backend atual)
  - Evolução: endurecer política também no backend

### Implementação técnica
- Frontend em `public/register.php` com script inline ou `public/assets/js/auth.js`.
- Manter validação server-side em `actions/register_action.php` como fonte final.

### Critérios de aceite
- Atualização em tempo real sem recarregar página.
- Mensagens claras e acessíveis (`aria-live="polite"`).

---

## 7. Onboarding / Wizard de boas-vindas

### Objetivo
Acelerar "tempo para primeiro valor" (criar 1º hábito em minutos).

### UX (3 passos)
1. **Boas-vindas**: "Vamos configurar sua rotina em 1 minuto".
2. **Criar primeiro hábito** (atalho direto para modal/form de hábito).
3. **Marcar hábito como concluído** (explica streak e progresso).

### Regra de exibição
- Mostrar apenas no primeiro acesso ao dashboard (ou até concluir).
- Salvar estado em `user_settings` (sugestão: `onboarding_completed`, `onboarding_step`).

### Métricas
- `% usuários que criam 1º hábito no dia 0`
- `tempo até 1ª conclusão`

---

## 8. Favoritar hábito + lembrete

### Objetivo
Permitir ao usuário destacar hábitos críticos e receber lembrete contextual.

### Escopo MVP
- Campo `is_favorite` em hábitos.
- Filtro "Somente favoritos" na listagem.
- Ordenação com favoritos no topo.

### Evolução
- `reminder_time` por hábito.
- Notificação local/in-app (badge + toast) respeitando preferências em `user_settings`.

### Critérios de aceite
- Favoritar/desfavoritar sem perder performance.
- Persistência por usuário.

---

## 4. Confirmação de email no cadastro

### Objetivo
Usar efetivamente `email_verified`, reduzir contas inválidas e preparar recuperação/segurança.

### Fluxo recomendado
1. Usuário registra conta.
2. Sistema cria token único com expiração (ex.: 24h).
3. Envia email com link `/verify-email.php?token=...`.
4. Ao confirmar, atualizar `users.email_verified = 1`.
5. Em login, alertar contas não verificadas e oferecer reenvio.

### Estrutura sugerida
- Nova tabela `email_verification_tokens`:
  - `id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`
- Nunca armazenar token em texto puro (hash).

### Observação
- Pode manter login imediato no MVP, mas com banner persistente "Verifique seu email".

---

## 11. Toast notifications in-app

### Objetivo
Substituir alertas estáticos pós-redirect por feedback moderno e discreto.

### UX
- Posição: canto superior direito.
- Tipos: sucesso, erro, informação.
- Auto-dismiss + botão fechar.
- Limite de pilha (ex.: 3 toasts).

### Estratégia técnica compatível com PHP atual
- Continuar usando `$_SESSION['success_message'/'error_message']` no backend.
- Em vez de renderizar `<div class="alert">`, serializar mensagem em `data-*` e exibir via JS.
- Criar componente reutilizável (`public/assets/js/toast.js`).

---

## 13. Micro-animação ao completar hábito

### Objetivo
Reforço positivo (gamificação) no momento de conclusão.

### MVP
- Animação de check no botão "Concluir".
- Pulse no card do hábito concluído.

### Nível 2
- Confetti leve quando usuário atinge meta diária (100% dos hábitos do dia).
- Respeitar `prefers-reduced-motion` para acessibilidade.

### Cuidado técnico
- Evitar animações pesadas para não prejudicar dispositivos modestos.

---

## 15. Página dedicada de conquistas (`achievements.php`)

### Objetivo
Transformar conquistas em vitrine principal de progresso e retenção.

### Estrutura da página
- **Resumo**: pontos totais, conquistas desbloqueadas, raridade média.
- **Grid de cards**: ícone, nome, raridade, pontos, progresso.
- **Filtros**: Todas / Desbloqueadas / Em progresso / Bloqueadas.
- **Detalhe**: condição da conquista + próxima meta.

### Dados
- Reusar payload existente de conquistas usado em `history.php`.
- Evoluir para endpoint dedicado `api_get_achievements.php` se necessário.

### Métrica de sucesso
- aumento de retorno semanal (WAU)
- aumento de hábitos concluídos por usuário ativo

---

## 4) Roteiro técnico de implementação (ordem sugerida)

1. Criar infra de toasts (base para feedback dos demais itens).
2. Adicionar força de senha no cadastro.
3. Implementar onboarding com estado em `user_settings`.
4. Criar favoritos de hábito e filtros.
5. Adicionar micro-animações de conclusão.
6. Extrair conquistas para `achievements.php`.
7. Finalizar confirmação de email transacional.

---

## 5) Riscos e mitigação

- **Complexidade de email**: usar serviço transacional (resend/mailgun/smtp) e fila simples.
- **Acoplamento com redirects PHP**: padronizar flash messages + helper único.
- **Escopo grande para projeto acadêmico**: entregar em fases com métricas claras.

---

## 6) Backlog enxuto (tickets)

- [ ] FE-01 Medidor de força de senha em `register.php`
- [ ] FE-02 Componente global de toast
- [ ] FE-03 Wizard onboarding (3 passos)
- [ ] BE-01 Campo `onboarding_completed` em `user_settings`
- [ ] HB-01 Campo `is_favorite` em `habits`
- [ ] HB-02 Filtro/ordenação por favoritos
- [ ] GM-01 Micro-animação de conclusão
- [ ] GM-02 Página `achievements.php`
- [ ] SEC-01 Tabela + fluxo de verificação de email
