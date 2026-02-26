# Doitly — Rastreador de Hábitos com Gamificação

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-DB-4479A1?logo=mysql&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-10.x-366488?logo=php&logoColor=white)
![Testes](https://img.shields.io/badge/Testes-195%20passing-brightgreen)
![Arquitetura](https://img.shields.io/badge/Arquitetura-Camadas%20%2B%20Handlers-blue)
![Docs](https://img.shields.io/badge/Docs-Engineering%20Hub-informational)

> Aplicação web para acompanhamento de hábitos com XP, conquistas, streaks e recomendações personalizadas — construída sobre uma arquitetura PHP em camadas com foco em testabilidade, qualidade de código e manutenibilidade a longo prazo.

---

## Sobre o Projeto

O Doitly nasceu como um projeto fullstack com propósito duplo: entregar uma experiência funcional de gamificação de hábitos para o usuário final e, ao mesmo tempo, servir como laboratório de boas práticas de engenharia de software.

A evolução da base de código foi deliberada: cada decisão de arquitetura foi documentada, cada refatoração foi justificada, e cada funcionalidade foi validada por testes automatizados — tudo registrado em um sistema de documentação de engenharia centralizado.

---

## Funcionalidades

- **Gestão de hábitos** — cadastro, edição, arquivamento e exclusão com agendamento flexível
- **Registro de conclusões** — marcação diária com validação de agendamento e datas
- **Dashboard e histórico** — métricas de progresso, taxa de conclusão e visualização por período
- **Gamificação** — sistema de XP, níveis, conquistas e streaks com sincronização automática
- **Recomendações personalizadas** — análise comportamental com score e tendências
- **Exportação de dados** — CSV com histórico de hábitos, conquistas e resumo de progresso
- **Perfil e preferências** — edição de dados, senha, aparência e fuso horário

---

## Arquitetura e Decisões Técnicas

### Arquitetura em Camadas

O projeto segue uma separação clara de responsabilidades entre quatro camadas principais:

```
public/          →  Páginas e composição de interface (sem SQL, sem lógica de negócio)
actions/         →  Adaptadores HTTP: validação, autenticação, CSRF e delegação
app/             →  Serviços de domínio, payload builders e lógica de aplicação
app/repository/  →  Acesso a dados via MySQLi com repositórios por agregado
```

### Padrão Handler/Adaptor para Testabilidade

As `actions/*.php` foram progressivamente refatoradas em adaptadores HTTP finos, delegando a lógica de negócio para classes handler em `app/Actions/*`. Isso permite testar o comportamento das ações via PHPUnit sem dependência de `header()` ou `exit`.

```php
// actions/habit_create_action.php — adaptador fino
$handler = new HabitCreateActionHandler($conn);
$response = $handler->handle($_POST, getAuthenticatedUserId());
actionApplyResponse($response);
```

### Módulos de Domínio (`app/`)

| Módulo | Responsabilidade |
|---|---|
| `App\Habits` | CRUD, conclusões, acesso e agendamento de hábitos |
| `App\Stats` | Consultas de estatísticas e histórico |
| `App\Achievements` | Sincronização e leitura de conquistas |
| `App\UserProgress` | Cálculo e persistência de XP e níveis |
| `App\Recommendation` | Análise comportamental, scoring e tendências |
| `App\Repository` | Repositórios por agregado (User, Habit, Category, Stats) |
| `App\Support` | Utilitários de data, fuso horário, contexto de requisição |
| `App\Actions` | Handlers de ações e objeto de resposta (`ActionResponse`) |
| `App\Api\Internal` | Payload builders para endpoints e páginas |

---

## Estratégia de Testes

O projeto utiliza **PHPUnit 10** com duas suítes separadas:

- **`Unit`** — testes de lógica pura, sem dependência de banco: scheduling, sanitização, scoring, formatação
- **`Action`** — testes de handlers e serviços com banco MySQL dedicado (`doitly_test`)

### Cobertura Implementada (Fases 2A–2F)

| Fase | Escopo |
|---|---|
| 2A | Endpoints de API JSON e payload builders |
| 2B | Serviço de autenticação e handlers de login/registro/logout |
| 2C | Serviços de comando, conclusão e acesso a hábitos |
| 2D | Perfil, configurações e exportação CSV |
| 2E | Repositórios, objetos de suporte, recomendação e progresso |
| 2F | Helpers globais de `config/*` e wrappers de integração |

**Resultado local validado:** `195 testes`, `737 asserções`

### Executando os Testes

```bash
# Pré-requisito: MySQL rodando com banco doitly_test
composer test:db:reset   # Recria o schema de testes
composer test:unit       # Suíte de lógica pura
composer test:action     # Suíte com banco de dados
composer test            # Suíte completa
composer qa              # Validação + suíte unitária
```

> **Importante:** execute as suítes com banco de forma sequencial — elas compartilham e resetam o schema `doitly_test`.

---

## Stack

| Tecnologia | Uso |
|---|---|
| PHP 8.2+ | Backend e lógica de aplicação |
| MySQL / MariaDB | Persistência com stored procedures e views |
| Bootstrap 5 | Interface responsiva |
| JavaScript (vanilla) | Interações no cliente |
| PHPUnit 10 | Testes automatizados (dev) |
| Composer | Autoload PSR-4 e gerenciamento de dependências |

---

## Documentação de Engenharia

A documentação técnica canônica está em `docs/`, estruturada para suportar handoffs, decisões arquiteturais e rastreamento de objetivos estratégicos.

| Documento | Conteúdo |
|---|---|
| [`docs/README.md`](docs/README.md) | Hub de navegação |
| [`docs/STATUS.md`](docs/STATUS.md) | Estado atual, bloqueios e próximo passo |
| [`docs/FUTURE_OBJECTIVES.md`](docs/FUTURE_OBJECTIVES.md) | Objetivos estratégicos com IDs rastreáveis |
| [`docs/WORKLOG.md`](docs/WORKLOG.md) | Histórico de sessões com evidências de verificação |
| [`docs/ADR/INDEX.md`](docs/ADR/INDEX.md) | Registro de decisões arquiteturais (ADRs) |
| [`docs/standards/engineering-handbook.md`](docs/standards/engineering-handbook.md) | Padrões de qualidade, SOLID, revisão e gates de verificação |
| [`docs/architecture/system-architecture.md`](docs/architecture/system-architecture.md) | Fronteiras de camadas e direção de refatoração |

---

## Como Executar Localmente

### Pré-requisitos

- PHP 8.2+
- MySQL ou MariaDB
- Composer
- Apache / XAMPP (recomendado para desenvolvimento local)

### Instalação

```bash
# 1. Instalar dependências
composer install

# 2. Importar o schema do banco de dados
mysql -u root -p < sql/doitly_unified.sql

# 3. Configurar variáveis de ambiente (ou usar os valores padrão em config/database.php)
# DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
```

### Executando (XAMPP)

Coloque o projeto dentro de `htdocs/` e acesse:

```
http://localhost/ProjetoFullstack/public/
```

---

## Destaques para Portfólio

Este projeto demonstra na prática:

- **Arquitetura evolutiva** — separação de camadas aplicada de forma incremental, com fronteiras documentadas e dívida técnica rastreada explicitamente
- **Testabilidade como requisito** — padrão handler/adaptor adotado para desacoplar comportamento de efeitos colaterais HTTP, possibilitando suítes de testes realistas com MySQL
- **Documentação como processo de engenharia** — sistema de docs com worklog vinculado a objetivos, ADRs indexados, runbooks reproduzíveis e templates padronizados
- **Qualidade sem reescrita de framework** — refatoração incremental aplicada em codebase PHP procedural legado, sem interrupção de funcionalidades existentes
- **Decisões explícitas e rastreáveis** — cada decisão arquitetural relevante possui um ADR com contexto, consequências e alternativas consideradas

---

## Autores

- **Ismael Gomes (Rex)**
- **Guilherme de Deus**
