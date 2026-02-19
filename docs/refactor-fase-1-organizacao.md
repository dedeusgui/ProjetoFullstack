# Refatoração Fase 1: Organização de Arquivos e Responsabilidades

## Objetivo
Iniciar a refatoração com foco em organização da base e separação de responsabilidades, reduzindo acoplamento entre páginas, ações HTTP e regras de negócio.

## Estrutura alvo da aplicação

```text
config/
  bootstrap.php           # Inicialização compartilhada (sessão + dependências)
  conexao.php             # Infra de banco de dados
  auth.php                # Autenticação e sessão (regras de acesso)
  helpers.php             # Helpers transversais

public/                   # Camada de apresentação (HTML + orquestração)
  *.php                   # Entrada web (sem regra de negócio complexa)
  includes/               # Blocos visuais reutilizáveis

actions/                  # Camada de entrada HTTP para mutações
  *_action.php            # Validar input e delegar para serviços/regras

app/                      # Camada de domínio/aplicação
  recommendation/         # Regras de recomendação e pontuação

sql/
  doitly_unified.sql      # Schema único para setup rápido de ambiente
```

## Regras de responsabilidade por camada

### `public/`
- Responsável por renderização e fluxo de navegação.
- Pode ler estado pronto para exibição.
- Não deve conter regras de negócio profundas nem acesso SQL direto complexo.

### `actions/`
- Responsável por receber requisições, validar dados e retornar redirecionamentos/respostas.
- Deve usar funções/serviços reutilizáveis para lógica de domínio.
- Não deve concentrar múltiplas responsabilidades.

### `config/`
- Responsável por infraestrutura e inicialização comum.
- Deve evitar duplicação de sessão e includes espalhados.

### `app/`
- Responsável por regras de negócio centrais.
- Deve ser a camada preferencial para regras complexas e evolutivas.

## Mudanças entregues nesta fase

1. Criação de `config/bootstrap.php` para centralizar inicialização da aplicação.
2. Atualização de entradas críticas para usar bootstrap compartilhado:
   - `public/login.php`
   - `public/register.php`
   - `public/dashboard.php`
   - `actions/login_action.php`
   - `actions/register_action.php`
   - `actions/logout_action.php`
3. Unificação de scripts SQL em um único arquivo para setup rápido em ambiente local.

## Próximos passos (fase 2 sugerida)

1. Extrair serviços de autenticação para reduzir lógica duplicada em `actions/`.
2. Extrair composição de payload do dashboard para `app/`.
3. Criar camada de repositórios para consultas SQL recorrentes.
4. Padronizar contratos de entrada/saída (DTOs simples).
