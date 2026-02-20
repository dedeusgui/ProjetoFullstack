# Doitly - Gerenciador de Hábitos Diários

<div align="center">

### Transforme seus objetivos em hábitos consistentes

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.2-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

</div>

---

## Sobre o Projeto

**Doitly** é uma aplicação fullstack para gestão de hábitos com foco em consistência diária, acompanhamento de progresso e gamificação.

O projeto já possui fluxo funcional completo de autenticação, dashboard, gerenciamento de hábitos, histórico com gráficos, conquistas, perfil e exportação de dados.

---

## Tecnologias Utilizadas

### Frontend

- HTML5
- CSS3 (Design System próprio)
- Bootstrap 5.3.2
- JavaScript (Vanilla)
- ApexCharts
- AOS (Animate On Scroll)
- Bootstrap Icons

### Backend

- PHP 8.0+
- MySQL/MariaDB (MySQLi)
- Sessões PHP
- Arquitetura em camadas (`public` -> `actions` -> `app` -> `repository`)

### Banco de Dados

- Script principal: `sql/doitly_unified.sql`
- Procedimentos armazenados para conclusão e estatísticas
- Views para consultas agregadas
- Tabelas de usuários, hábitos, conclusões, conquistas, configurações e recomendações

---

## Funcionalidades Atuais

### Autenticação e Conta

- Cadastro com validações server-side
- Login com proteção CSRF e rate limit de tentativas
- Sessão autenticada e logout
- Atualização de perfil (email, avatar, senha)

### Dashboard

- Resumo diário (hábitos ativos, concluídos, taxa, streak)
- Gráfico de progresso semanal
- Lista de hábitos de hoje com conclusão direta
- Recomendações adaptativas com análise de comportamento

### Gerenciamento de Hábitos

- CRUD completo de hábitos
- Frequência: `daily`, `weekly`, `custom`
- Seleção de dias da semana (target days)
- Metas por tipo (`completion`, `quantity`, `duration`)
- Arquivamento e restauração de hábitos
- Filtros por busca, categoria e horário

### Histórico e Gamificação

- Visão histórica com métricas gerais
- Gráficos mensais e por categoria
- Hub de conquistas com progresso, raridade e XP
- Sistema de nível do usuário
- Histórico recente de desempenho

### Configurações e Exportação

- Modal de configurações com tema e personalização visual
- Ajuste de cor primária/secundária e escala de texto
- Exportação de resumo do usuário em CSV

### Landing Page

- Página pública de apresentação do produto
- Seções de benefícios, recursos, FAQ e CTA
- Interface responsiva e animações

---

## Arquitetura

Visão resumida da arquitetura atual:

```text
public/ (UI e páginas)
   -> actions/ (entrada HTTP mutável)
      -> app/ (regras de domínio e serviços)
         -> app/repository/ + config/conexao.php (persistência)
            -> sql/doitly_unified.sql (estrutura do banco)
```

Documentação complementar: `ARQUITETURA_SISTEMA.md`.

---

## Estrutura do Projeto

```text
ProjetoFullstack/
├── actions/
├── app/
│   ├── auth/
│   ├── habits/
│   ├── profile/
│   ├── recommendation/
│   └── repository/
├── config/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── img/
│   │   └── js/
│   ├── includes/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── habits.php
│   └── history.php
├── sql/
│   └── doitly_unified.sql
└── ARQUITETURA_SISTEMA.md
```

---

## Instalação e Execução

### Pré-requisitos

- PHP 8.0+
- MySQL ou MariaDB
- Apache (XAMPP recomendado)

### 1. Clonar o repositório

```bash
git clone https://github.com/dedeusgui/ProjetoFullstack.git
cd ProjetoFullstack
```

### 2. Configurar banco de dados

Importe o script:

- `sql/doitly_unified.sql`

Exemplo (CLI):

```bash
mysql -u root -p < sql/doitly_unified.sql
```

### 3. Configurar conexão

A conexão usa variáveis de ambiente (com fallback):

- `DB_HOST` (default: `localhost`)
- `DB_USER` (default: `root`)
- `DB_PASS` (default: vazio)
- `DB_NAME` (default: `doitly`)
- `DB_PORT` (default: `3306`)

Arquivo: `config/conexao.php`.

### 4. Executar projeto

No XAMPP, coloque a pasta em `htdocs` e acesse:

```text
http://localhost/ProjetoFullstack/public/
```

---

## Endpoints/Ações Principais

- `actions/login_action.php`
- `actions/register_action.php`
- `actions/logout_action.php`
- `actions/habit_create_action.php`
- `actions/habit_update_action.php`
- `actions/habit_delete_action.php`
- `actions/habit_mark_action.php`
- `actions/habit_archive_action.php`
- `actions/api_get_habits.php`
- `actions/api_get_stats.php`
- `actions/update_profile_action.php`
- `actions/reset_appearance_action.php`
- `actions/export_user_data_csv.php`

---

## Roadmap (Próximas Melhorias)

Itens priorizados com base no estado atual do projeto:

1. Wizard de boas-vindas (onboarding) no primeiro login.
2. Notificações in-app com toasts modernos no lugar de feedback simples.
3. Indicador visual de força de senha no cadastro (frontend).
4. Fluxo real de confirmação de e-mail usando `email_verified`.
5. Micro-animação de conclusão de hábito (confetti/check animado).
6. Página dedicada de conquistas (`achievements.php`) além do hub no `history.php`.
7. Favoritos e lembretes avançados para hábitos prioritários.

---

## Status de Funcionalidades Solicitadas

Com base no código atual:

- `Onboarding/Wizard`: **não implementado**.
- `Toasts in-app`: **não implementado** (feedback atual por alertas/mensagens de sessão).
- `Força de senha no frontend`: **não implementado**.
- `Confirmação de email`: **não implementado** (campo `email_verified` existe no banco, mas sem fluxo).
- `Micro-animação ao concluir hábito`: **não implementado**.
- `Página dedicada de conquistas`: **não implementado** (conquistas ficam no `history.php`).

---

## Equipe

- Guilherme Deus - Frontend & Design
- Ismael Gomes - Backend & Database

GitHub do projeto: [dedeusgui/ProjetoFullstack](https://github.com/dedeusgui/ProjetoFullstack)

---

## Observações

- No estado atual do repositório, não existe arquivo `LICENSE` na raiz.
- O README foi escrito com base no código efetivamente presente no projeto.
