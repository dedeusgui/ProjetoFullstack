# Doitly — Rastreador de Hábitos com Gamificação

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-DB-4479A1?logo=mysql&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-10.x-366488?logo=php&logoColor=white)
![Testes](https://img.shields.io/badge/Testes-195%20passing-brightgreen)
![Arquitetura](https://img.shields.io/badge/Arquitetura-Camadas%20%2B%20Handlers-blue)
![Docs](https://img.shields.io/badge/Docs-Engineering%20Hub-informational)

Aplicação web de acompanhamento de hábitos com XP, conquistas, streaks e recomendações personalizadas — construída em PHP com arquitetura em camadas, testes automatizados e documentação técnica organizada.

## Contexto do Projeto

O Doitly começou como um projeto acadêmico desenvolvido em coautoria. Com o tempo, a ideia deixou de ser apenas uma entrega do técnico e passou a se tornar um projeto pessoal, que sigo evoluindo no meu tempo livre.

Essa continuidade mudou o papel do projeto: ele deixou de ser só um exercício de implementação e passou a ser um espaço real de evolução técnica, decisões de arquitetura e amadurecimento de produto.

## Principais Funcionalidades

- Gestão de hábitos com criação, edição, arquivamento e exclusão
- Registro diário de conclusões com validação por agendamento e data
- Dashboard e histórico com métricas de progresso e taxa de conclusão
- Gamificação com XP, níveis, conquistas, recompensas e streaks
- Recomendações personalizadas com base em padrões de comportamento
- Perfil do usuário com preferências, fuso horário e exportação de dados em CSV

## O que este projeto demonstra

- Evolução real de uma base de código ao longo do tempo, sem reescrita — refatoração incremental sobre PHP legado
- Separação de responsabilidades entre interface, ações HTTP, domínio e persistência
- Testes automatizados cobrindo fluxos relevantes, com suítes separadas para lógica pura e banco
- Documentação técnica organizada: decisões, progresso e próximos passos versionados em `docs/`

## Arquitetura e Qualidade Técnica

O projeto segue uma organização em camadas:

```text
public/          -> páginas e composição de interface
actions/         -> adaptadores HTTP, validações e respostas
app/             -> regras de negócio, serviços e payload builders
app/repository/  -> consultas e operações de persistência
```

Na prática, a base vem sendo evoluída para concentrar comportamento em classes testáveis e deixar `actions/*.php` mais enxutas.

### Destaques técnicos

- Arquitetura em camadas com fronteiras documentadas
- Padrão handler/adaptor para reduzir acoplamento com `header()` e `exit`
- PHPUnit 10 com suítes separadas para lógica pura e fluxos com banco
- Documentação de engenharia com status, worklog, feature docs e ADRs

### Comandos de validação

```bash
composer test:db:reset
composer test:unit
composer test:action
composer test
composer qa
```

## Stack

- PHP 8.2+
- MySQL / MariaDB
- Bootstrap 5
- JavaScript (vanilla)
- PHPUnit 10
- Composer

## Como Executar Localmente

### Pré-requisitos

- PHP 8.2+
- MySQL ou MariaDB
- Composer
- Apache / XAMPP

### Instalação

```bash
composer install
mysql -u root -p < sql/doitly_unified.sql
```

Configure as variáveis de ambiente do banco, se necessário, ou ajuste os valores padrão em `config/database.php`.

### Executando no XAMPP

Com o projeto dentro de `htdocs/`, acesse:

```text
http://localhost/ProjetoFullstack/public/
```

## Documentação Complementar

Os detalhes de engenharia ficam centralizados em `docs/`. Os pontos de entrada mais úteis são:

- [`docs/README.md`](docs/README.md) — hub da documentação técnica
- [`docs/STATUS.md`](docs/STATUS.md) — estado atual, foco e próximos passos
- [`docs/architecture/system-architecture.md`](docs/architecture/system-architecture.md) — visão de arquitetura
- [`docs/standards/engineering-handbook.md`](docs/standards/engineering-handbook.md) — padrões de engenharia e verificação
- [`docs/WORKLOG.md`](docs/WORKLOG.md) — histórico de sessões e validações

## Autoria

Projeto iniciado em coautoria por:

- Guilherme de Deus
- Ismael Gomes (Rex)

Atualmente, o Doitly segue em evolução contínua como projeto pessoal.

