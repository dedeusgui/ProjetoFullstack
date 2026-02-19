# Arquitetura de Responsabilidades da Codebase

## Objetivo
Definir com clareza o que **cada tipo de arquivo** deve fazer no sistema para reduzir acoplamento, facilitar manutenção e acelerar evolução da aplicação.

## Regras gerais
- Um arquivo deve possuir **responsabilidade principal única**.
- Regras de negócio devem ficar em `app/` sempre que possível.
- `actions/` devem tratar requisição HTTP e delegar processamento.
- `public/` deve focar em apresentação e orquestração da view.
- `config/` concentra bootstrap e infraestrutura transversal.

---

## Mapa por diretório

### 1) `config/`
**Responsabilidade:** inicialização e infraestrutura compartilhada.

- `config/bootstrap.php`  
  Ponto único de boot para sessão, autenticação, helpers e conexão opcional com banco.
- `config/conexao.php`  
  Conexão com banco e funções utilitárias de execução SQL.
- `config/auth.php`  
  Regras de autenticação/sessão e helpers de usuário logado.
- `config/helpers.php`  
  Helpers transversais de domínio já consolidados.

**Não deve fazer:** renderização de tela e regra de interface.

### 2) `public/`
**Responsabilidade:** renderizar páginas e controlar navegação entre telas.

- `public/login.php` e `public/register.php`  
  Mostrar formulários e mensagens de sessão.
- `public/dashboard.php`, `public/habits.php`, `public/history.php`  
  Consumir dados preparados e renderizar UI.

**Não deve fazer:** SQL complexo e regra de negócio crítica.

### 3) `actions/`
**Responsabilidade:** entrada HTTP para ações mutáveis (POST/GET específicos).

- `actions/login_action.php`  
  Validar input de login, delegar autenticação e redirecionar.
- `actions/register_action.php`  
  Validar cadastro, delegar criação de usuário e redirecionar.
- `actions/habit_*_action.php`  
  CRUD e marcação de hábitos com validação de usuário e contexto.
- `actions/update_profile_action.php` e `actions/reset_appearance_action.php`  
  Atualização de preferências e perfil.

**Não deve fazer:** centralizar múltiplos fluxos de negócio em um único arquivo.

### 4) `app/`
**Responsabilidade:** domínio e aplicação (regras centrais).

- `app/auth/AuthService.php`  
  Serviço de autenticação/cadastro desacoplado da camada HTTP.
- `app/recommendation/*`  
  Motor de recomendação, análise de comportamento e score.

**Não deve fazer:** redirecionamento HTTP e renderização de interface.

### 5) `sql/`
**Responsabilidade:** provisionamento de banco e objetos de dados.

- `sql/doitly_unified.sql`  
  Script único de setup para ambiente local e testes.

---

## Fluxo recomendado entre camadas

```text
public/ ou actions/
   -> app/ (serviços e regras)
      -> config/conexao.php (infra SQL)
```

## Critérios para novas refatorações
1. Toda nova regra de negócio deve ser candidata a ir para `app/`.
2. Sempre que houver duplicação em `actions/`, extrair para serviço reutilizável.
3. Evitar dependência direta entre arquivos de `public/` e SQL.
4. Atualizar este documento ao introduzir nova pasta/camada.
