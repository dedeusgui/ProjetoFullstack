<div align="center">

<img src="https://img.shields.io/badge/Doitly-Gerenciador%20de%20H%C3%A1bitos-6C63FF?style=for-the-badge" alt="Doitly" />

# Doitly — Gerenciador de Hábitos Diários

**Transforme seus objetivos em hábitos consistentes.**

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.2-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Status](https://img.shields.io/badge/status-em%20desenvolvimento-yellow?style=flat)]()
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen?style=flat)]()

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
- [Contribuindo](#contribuindo)
- [Autores](#autores)

---

## Sobre o Projeto

**Doitly** é uma aplicação web fullstack desenvolvida para transformar objetivos pessoais em hábitos sólidos e consistentes. Mais do que um simples rastreador, o Doitly combina gestão de rotinas com elementos de gamificação para manter o usuário engajado e motivado ao longo do tempo.

A ideia central é simples: pequenas ações diárias, feitas de forma consistente, geram grandes resultados. O sistema foi construído para tornar esse processo visual, intuitivo e recompensador — desde o primeiro hábito criado até o acompanhamento de meses de progresso.

### O que o Doitly oferece

Do ponto de vista técnico, a aplicação entrega um fluxo completo e funcional: autenticação segura com proteção CSRF e controle de tentativas, dashboard interativo com resumo diário e gráficos semanais, CRUD completo de hábitos com suporte a diferentes frequências e metas, histórico detalhado com análise mensal e por categoria, e exportação dos dados do usuário em CSV.

Do ponto de vista do usuário, o foco está na experiência: o sistema acompanha streaks, calcula taxas de conclusão, distribui XP e desbloqueia conquistas conforme os hábitos são mantidos. Recomendações adaptativas analisam o comportamento do usuário e sugerem ajustes para melhorar a consistência. A interface é totalmente personalizável, com controle de tema, paleta de cores e escala de texto.

### Contexto do projeto

O Doitly foi desenvolvido como projeto fullstack por [Ismael Gomes](https://github.com/rex23js) e [Guilherme de Deus](https://github.com/dedeusgui), com arquitetura em camadas, banco de dados relacional estruturado com views e stored procedures, e design system próprio construído sobre Bootstrap 5. O projeto está em desenvolvimento ativo, com novas funcionalidades planejadas no roadmap.

---

## Tecnologias

### Frontend
- HTML5 + CSS3 (Design System próprio)
- Bootstrap 5.3.2 + Bootstrap Icons
- JavaScript Vanilla
- [ApexCharts](https://apexcharts.com/) — gráficos interativos
- [AOS](https://michalsnik.github.io/aos/) — Animate On Scroll

### Backend
- PHP 8.0+
- MySQL / MariaDB via MySQLi
- Sessões PHP nativas
- Arquitetura em camadas: `public` → `actions` → `app` → `repository`

### Banco de Dados
- Script unificado: `sql/doitly_unified.sql`
- Stored procedures para conclusão de hábitos e estatísticas
- Views para consultas agregadas
- Tabelas: `users`, `habits`, `completions`, `achievements`, `settings`, `recommendations`

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

O projeto segue uma arquitetura em camadas bem definida, separando responsabilidades entre interface, entrada HTTP, regras de negócio e persistência:

```
public/           → Páginas e interface do usuário
  └── actions/    → Entrada HTTP (requisições mutáveis)
        └── app/  → Regras de domínio e serviços
              └── app/repository/ + config/conexao.php  → Persistência
                    └── sql/doitly_unified.sql           → Banco de dados
```

Essa separação garante que as páginas públicas não acessem o banco diretamente, que as regras de negócio fiquem isoladas dos controllers HTTP, e que a troca de implementação de repositório não afete as camadas superiores.

> Documentação detalhada: `SYSTEM_ARCHITECTURE.md`

---

## Estrutura de Pastas

```
ProjetoFullstack/
├── actions/                  # Endpoints HTTP (actions e APIs)
├── app/
│   ├── auth/                 # Autenticação e controle de sessão
│   ├── habits/               # Regras de negócio de hábitos
│   ├── profile/              # Gerenciamento de perfil
│   ├── recommendation/       # Motor de recomendações adaptativas
│   └── repository/           # Acesso ao banco de dados (DAOs)
├── config/
│   └── conexao.php           # Configuração e conexão com o banco
├── public/
│   ├── assets/
│   │   ├── css/              # Estilos globais e design system
│   │   ├── img/              # Imagens e ícones
│   │   └── js/               # Scripts frontend
│   ├── includes/             # Componentes reutilizáveis (header, footer)
│   ├── index.php             # Landing page
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── habits.php
│   └── history.php
└── sql/
    └── doitly_unified.sql    # Script unificado do banco de dados
```

---

## Instalação

### Pré-requisitos

Certifique-se de ter as seguintes ferramentas instaladas:

- [PHP 8.0+](https://www.php.net/)
- [MySQL](https://www.mysql.com/) ou [MariaDB](https://mariadb.org/)
- [Apache](https://httpd.apache.org/) — recomendado via [XAMPP](https://www.apachefriends.org/)

### 1. Clonar o repositório

```bash
git clone https://github.com/dedeusgui/ProjetoFullstack.git
cd ProjetoFullstack
```

### 2. Importar o banco de dados

```bash
mysql -u root -p < sql/doitly_unified.sql
```

Ou importe manualmente pelo phpMyAdmin caso esteja usando o XAMPP.

### 3. Configurar a conexão

A conexão usa variáveis de ambiente com fallback automático. Configure conforme necessário:

| Variável  | Padrão      | Descrição |
|-----------|-------------|-----------|
| `DB_HOST` | `localhost` | Host do banco de dados |
| `DB_USER` | `root`      | Usuário do banco |
| `DB_PASS` | _(vazio)_   | Senha do banco |
| `DB_NAME` | `doitly`    | Nome do banco de dados |
| `DB_PORT` | `3306`      | Porta de conexão |

Arquivo de configuração: `config/conexao.php`

### 4. Executar o projeto

No XAMPP, mova a pasta para `htdocs/` e acesse no navegador:

```
http://localhost/ProjetoFullstack/public/
```

> **Dica:** Certifique-se de que os módulos `mod_rewrite` e `mysqli` estão habilitados no Apache/PHP.

---

## Endpoints

### Actions (mutações)

| Endpoint | Método | Descrição |
|---|---|---|
| `actions/login_action.php` | POST | Autenticação de usuário |
| `actions/register_action.php` | POST | Cadastro de novo usuário |
| `actions/logout_action.php` | POST | Encerramento de sessão |
| `actions/habit_create_action.php` | POST | Criação de hábito |
| `actions/habit_update_action.php` | POST | Edição de hábito |
| `actions/habit_delete_action.php` | POST | Exclusão de hábito |
| `actions/habit_mark_action.php` | POST | Marcar hábito como concluído |
| `actions/habit_archive_action.php` | POST | Arquivar / restaurar hábito |
| `actions/update_profile_action.php` | POST | Atualização de perfil |
| `actions/reset_appearance_action.php` | POST | Resetar configurações visuais |
| `actions/export_user_data_csv.php` | GET | Exportar dados do usuário em CSV |

### APIs (leitura)

| Endpoint | Método | Descrição |
|---|---|---|
| `actions/api_get_habits.php` | GET | Listagem de hábitos do usuário |
| `actions/api_get_stats.php` | GET | Estatísticas e métricas do usuário |

---

## Roadmap

Melhorias planejadas com base no estado atual do projeto:

| # | Funcionalidade | Status |
|---|---|---|
| 1 | Wizard de boas-vindas (onboarding) no primeiro login | 🔲 Pendente |
| 2 | Notificações in-app com toasts modernos | 🔲 Pendente |
| 3 | Indicador visual de força de senha no cadastro | 🔲 Pendente |
| 4 | Micro-animação ao concluir hábito (confetti / check animado) | 🔲 Pendente |
| 5 | Página dedicada de conquistas (`achievements.php`) | 🔲 Pendente |
| 6 | Favoritos e lembretes avançados para hábitos prioritários | 🔲 Pendente |

> O campo `email_verified` já existe no banco de dados, aguardando implementação do fluxo de confirmação.

---

## Contribuindo

Contribuições são bem-vindas! Para contribuir:

1. Faça um fork do projeto
2. Crie uma branch para sua feature: `git checkout -b feature/minha-feature`
3. Faça commit das suas alterações: `git commit -m 'feat: adiciona minha feature'`
4. Envie para a branch: `git push origin feature/minha-feature`
5. Abra um Pull Request

> Siga o padrão [Conventional Commits](https://www.conventionalcommits.org/pt-br/) para as mensagens de commit.

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
  <sub>Este projeto não possui licença declarada. Contate os autores para mais informações sobre uso e distribuição.</sub>
</div>
