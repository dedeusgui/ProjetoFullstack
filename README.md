<div align="center">

<img src="https://img.shields.io/badge/Doitly-Gerenciador%20de%20H%C3%A1bitos-6C63FF?style=for-the-badge" alt="Doitly" />

# Doitly — Gerenciador de Hábitos Diários

**Transforme seus objetivos em hábitos consistentes.**

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.2-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Status](https://img.shields.io/badge/status-em%20desenvolvimento-yellow?style=flat)]()

</div>

---

## Índice

- [Sobre o Projeto](#sobre-o-projeto)
- [Tecnologias](#tecnologias)
- [Funcionalidades](#funcionalidades)
- [Arquitetura](#arquitetura)
- [Estrutura de Pastas](#estrutura-de-pastas)
- [Instalação](#instalação)
- [Endpoints](#endpoints)
- [Roadmap](#roadmap)
- [Autores](#autores)

---

## Sobre o Projeto

**Doitly** é uma aplicação web fullstack para gestão de hábitos diários com foco em consistência, acompanhamento visual de progresso e gamificação.

O sistema conta com um fluxo completo e funcional: autenticação segura, dashboard interativo, CRUD de hábitos, histórico com gráficos, sistema de conquistas e XP, personalização visual e exportação de dados.

---

## Tecnologias

### Frontend
- HTML5 + CSS3 (Design System próprio)
- Bootstrap 5.3.2 + Bootstrap Icons
- JavaScript Vanilla
- ApexCharts (gráficos)
- AOS — Animate On Scroll

### Backend
- PHP 8.0+
- MySQL / MariaDB via MySQLi
- Sessões PHP nativas
- Arquitetura em camadas: `public` → `actions` → `app` → `repository`

### Banco de Dados
- Script unificado: `sql/doitly_unified.sql`
- Stored procedures para conclusão de hábitos e estatísticas
- Views para consultas agregadas
- Tabelas: usuários, hábitos, conclusões, conquistas, configurações e recomendações

---

## Funcionalidades

### Autenticação e Conta
- Cadastro com validações server-side
- Login com proteção CSRF e rate limit de tentativas
- Sessão autenticada e logout seguro
- Atualização de perfil: e-mail, avatar e senha

### Dashboard
- Resumo diário: hábitos ativos, concluídos, taxa de conclusão e streak
- Gráfico de progresso semanal
- Lista de hábitos do dia com marcação direta
- Recomendações adaptativas baseadas no comportamento do usuário

### Gerenciamento de Hábitos
- CRUD completo de hábitos
- Frequências: `daily`, `weekly` e `custom`
- Seleção de dias da semana por hábito
- Metas por tipo: `completion`, `quantity` e `duration`
- Arquivamento e restauração de hábitos
- Filtros por busca, categoria e horário

### Histórico e Gamificação
- Métricas gerais de desempenho histórico
- Gráficos mensais e por categoria
- Hub de conquistas com progresso, raridade e XP
- Sistema de nível do usuário
- Histórico recente de atividade

### Configurações e Exportação
- Personalização de tema (cor primária, secundária e escala de texto)
- Exportação do resumo do usuário em CSV

### Landing Page
- Página pública de apresentação do produto
- Seções de benefícios, recursos, FAQ e CTA
- Layout responsivo com animações

---

## Arquitetura

```
public/           → Páginas e interface do usuário
  └── actions/    → Entrada HTTP (requisições mutáveis)
        └── app/  → Regras de domínio e serviços
              └── app/repository/ + config/conexao.php  → Persistência
                    └── sql/doitly_unified.sql           → Banco de dados
```

> Documentação detalhada: `ARQUITETURA_SISTEMA.md`

---

## Estrutura de Pastas

```
ProjetoFullstack/
├── actions/                  # Endpoints HTTP (actions e APIs)
├── app/
│   ├── auth/                 # Autenticação
│   ├── habits/               # Regras de hábitos
│   ├── profile/              # Perfil do usuário
│   ├── recommendation/       # Recomendações adaptativas
│   └── repository/           # Acesso ao banco de dados
├── config/
│   └── conexao.php           # Configuração da conexão
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── img/
│   │   └── js/
│   ├── includes/
│   ├── index.php             # Landing page
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── habits.php
│   └── history.php
└── sql/
    └── doitly_unified.sql    # Script unificado do banco
```

---

## Instalação

### Pré-requisitos

- PHP 8.0+
- MySQL ou MariaDB
- Apache (XAMPP recomendado)

### 1. Clonar o repositório

```bash
git clone https://github.com/dedeusgui/ProjetoFullstack.git
cd ProjetoFullstack
```

### 2. Importar o banco de dados

```bash
mysql -u root -p < sql/doitly_unified.sql
```

### 3. Configurar a conexão

A conexão usa variáveis de ambiente com fallback automático. Configure conforme necessário:

| Variável  | Padrão      |
|-----------|-------------|
| `DB_HOST` | `localhost` |
| `DB_USER` | `root`      |
| `DB_PASS` | _(vazio)_   |
| `DB_NAME` | `doitly`    |
| `DB_PORT` | `3306`      |

Arquivo de configuração: `config/conexao.php`

### 4. Executar o projeto

No XAMPP, coloque a pasta em `htdocs/` e acesse:

```
http://localhost/ProjetoFullstack/public/
```

---

## Endpoints

| Arquivo | Descrição |
|---|---|
| `actions/login_action.php` | Autenticação de usuário |
| `actions/register_action.php` | Cadastro de novo usuário |
| `actions/logout_action.php` | Encerramento de sessão |
| `actions/habit_create_action.php` | Criação de hábito |
| `actions/habit_update_action.php` | Edição de hábito |
| `actions/habit_delete_action.php` | Exclusão de hábito |
| `actions/habit_mark_action.php` | Marcar hábito como concluído |
| `actions/habit_archive_action.php` | Arquivar/restaurar hábito |
| `actions/api_get_habits.php` | Listagem de hábitos (API) |
| `actions/api_get_stats.php` | Estatísticas do usuário (API) |
| `actions/update_profile_action.php` | Atualização de perfil |
| `actions/reset_appearance_action.php` | Resetar aparência |
| `actions/export_user_data_csv.php` | Exportar dados em CSV |

---

## Roadmap

Melhorias planejadas com base no estado atual do projeto:

| # | Funcionalidade | Status |
|---|---|---|
| 1 | Wizard de boas-vindas (onboarding) no primeiro login | 🔲 Pendente |
| 2 | Notificações in-app com toasts modernos | 🔲 Pendente |
| 3 | Indicador visual de força de senha no cadastro | 🔲 Pendente |
| 4 | Fluxo real de confirmação de e-mail | 🔲 Pendente |
| 5 | Micro-animação ao concluir hábito (confetti/check animado) | 🔲 Pendente |
| 6 | Página dedicada de conquistas (`achievements.php`) | 🔲 Pendente |
| 7 | Favoritos e lembretes avançados para hábitos prioritários | 🔲 Pendente |

> O campo `email_verified` já existe no banco de dados, aguardando implementação do fluxo.

---

## Autores

<div align="center">

Desenvolvido com dedicação por:

| [<img src="https://avatars.githubusercontent.com/u/200134059?v=4" width=100><br>**Ismael Gomes (Rex)**](https://github.com/rex23js) | [<img src="https://avatars.githubusercontent.com/u/202681712?v=4" width=100><br>**Guilherme de Deus**](https://github.com/dedeusgui) |
|:---:|:---:|
| [![GitHub](https://img.shields.io/badge/GitHub-rex23js-181717?style=flat&logo=github)](https://github.com/rex23js) | [![GitHub](https://img.shields.io/badge/GitHub-dedeusgui-181717?style=flat&logo=github)](https://github.com/dedeusgui) |

</div>

---

<div align="center">
  <sub>Este projeto não possui licença declarada. Contate os autores para mais informações.</sub>
</div>
