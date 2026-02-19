# Resumo da Arquitetura do Sistema

O projeto segue uma arquitetura em camadas para separar interface, entrada HTTP, regras de negócio e infraestrutura.

## Visão geral das camadas

```text
public/ (UI e páginas)
   -> actions/ (entrada de requisições mutáveis)
      -> app/ (serviços e domínio)
         -> app/repository/ + config/conexao.php (acesso a dados)
            -> sql/doitly_unified.sql (estrutura do banco)
```

## Responsabilidade por diretório

- **public/**
  - Renderiza páginas e componentes visuais.
  - Exibe mensagens de sessão e dados já preparados pelas camadas internas.
  - Não deve concentrar regra de negócio complexa.

- **actions/**
  - Recebe formulários e eventos HTTP (login, cadastro, hábitos, perfil etc.).
  - Faz validações de entrada e controle de fluxo (redirect, mensagens de erro/sucesso).
  - Delega processamento principal para serviços em `app/`.

- **app/**
  - Núcleo de domínio da aplicação.
  - Contém serviços de autenticação, hábitos, perfil e recomendação.
  - Centraliza regras de negócio para reduzir duplicação em páginas/actions.

- **app/repository/**
  - Isola operações de persistência e consultas ao banco.
  - Reduz acoplamento entre serviço de domínio e SQL bruto.

- **config/**
  - Bootstrap e infraestrutura compartilhada.
  - Sessão, autenticação, helpers globais e conexão com banco.

- **sql/**
  - Script de provisionamento/estrutura de dados do sistema.

## Fluxo funcional resumido

1. Usuário acessa uma página em `public/`.
2. Ao enviar um formulário, a requisição é tratada por um arquivo em `actions/`.
3. A action chama serviços de `app/` para aplicar regras do negócio.
4. Serviços usam repositórios e camada de conexão para ler/gravar dados.
5. Action redireciona e a página renderiza o resultado para o usuário.

## Objetivo arquitetural

- Separação clara de responsabilidades.
- Menor acoplamento entre interface e domínio.
- Facilidade de manutenção e evolução.
- Maior segurança e previsibilidade no fluxo HTTP.
