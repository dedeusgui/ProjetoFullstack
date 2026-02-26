# Doitly

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL/MariaDB](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-DB-4479A1?logo=mysql&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-10.x-366488?logo=php&logoColor=white)
![Testes locais](https://img.shields.io/badge/Testes%20locais-validados-brightgreen)
![Docs](https://img.shields.io/badge/Docs-Engineering%20Hub-blue)

Aplicação web de acompanhamento de hábitos com gamificação, métricas de progresso e arquitetura PHP em camadas com evolução incremental.

Este repositório usa `docs/` como fonte canônica da documentação de engenharia (arquitetura, padrões, ADRs, runbooks e progresso). O `README.md` é voltado para a apresentação do projeto no GitHub e para um onboarding rápido.

## Visão geral

O Doitly foi estruturado para equilibrar a entrega de funcionalidades com a melhoria contínua da qualidade técnica:

- CRUD de hábitos com agendamento, conclusão e arquivamento
- Dashboard e métricas de progresso
- Gamificação (XP, conquistas e streaks)
- Arquitetura em camadas (`public` -> `actions` -> `app` -> `app/repository`)
- Base de testes automatizados com PHPUnit e reset de banco de testes MySQL/MariaDB
- Documentação técnica organizada para evolução e handoff

## Funcionalidades principais

- Cadastro, edição, exclusão e arquivamento de hábitos
- Marcação de hábitos concluídos
- Visualização de progresso e estatísticas
- Sistema de progresso do usuário (gamificação)
- Exportação de dados do usuário (CSV)

## Diferenciais técnicos (portfólio)

- Separação de responsabilidades por camada (`public`, `actions`, `app`, `app/repository`)
- Padrão handler/adaptor aplicado em ações HTTP para melhorar a testabilidade
- Estratégia de testes com suítes `Unit` e `Action` + reset de banco dedicado
- Documentação de engenharia com:
  - status atual
  - objetivos estratégicos
  - ADRs (Architecture Decision Records)
  - runbooks
  - worklog com evidências de verificação

## Stack utilizada

- PHP 8.2+
- MySQL / MariaDB (MySQLi)
- Bootstrap 5
- JavaScript (vanilla)
- PHPUnit 10 (dev)
- Composer

## Como executar localmente

### Pré-requisitos

- PHP 8.2+
- Composer
- MySQL ou MariaDB
- Apache/XAMPP (recomendado para ambiente local)

### Instalação

```bash
composer install
```

### Banco de dados

Importe o snapshot do schema:

```bash
mysql -u root -p < sql/doitly_unified.sql
```

As configurações de conexão usam variáveis de ambiente (com fallbacks locais em `config/database.php`).

### Execução (exemplo com XAMPP)

Coloque o projeto dentro de `htdocs/` e acesse:

```text
http://localhost/ProjetoFullstack/public/
```

## Testes e qualidade

Use um banco de testes dedicado (padrão: `doitly_test`) e execute:

```bash
composer test:db:reset
composer test
```

Comandos úteis:

```bash
composer test:unit
composer test:action
composer qa
```

## Estrutura do projeto (resumo)

- `public/`: páginas e composição de interface
- `actions/`: endpoints/adapters HTTP (GET/POST, redirects, respostas)
- `app/`: lógica de domínio/aplicação e serviços reutilizáveis
- `app/repository/`: consultas SQL e persistência
- `config/`: bootstrap e helpers de integração
- `tests/`: testes unitários e de actions
- `docs/`: documentação técnica canônica

## Documentação técnica (canônica)

A documentação técnica principal está em inglês e centralizada em `docs/`.

Pontos de entrada:

- `docs/README.md` (hub de navegação)
- `docs/STATUS.md` (estado atual e próximos passos)
- `docs/standards/engineering-handbook.md` (padrões de engenharia e verificação)
- `docs/architecture/system-architecture.md` (arquitetura e fronteiras)
- `docs/ADR/INDEX.md` (decisões técnicas)

## Contribuição

Para o fluxo de trabalho de desenvolvimento e handoff técnico, consulte:

- `docs/CONTRIBUTING_DEV.md`

Se possível, use Conventional Commits.

## Autores

- Ismael Gomes (Rex)
- Guilherme de Deus
