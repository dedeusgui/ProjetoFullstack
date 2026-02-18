# Deploy no InfinityFree (guia prático para o Doitly)

Este guia foi feito para este projeto PHP/MySQL.

## 1) Banco de dados (InfinityFree)

1. No painel do InfinityFree, abra **MySQL Databases**.
2. Crie um banco e guarde:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
3. Abra o **phpMyAdmin** do InfinityFree.
4. Selecione o banco recém-criado.
5. Importe o arquivo `sql/doitly_infinityfree.sql` (já compatível com hospedagem compartilhada).

> Observação: esse arquivo remove `PROCEDURE`, `DEFINER` e objetos que normalmente falham no InfinityFree.

## 2) Configurar conexão no projeto

Edite `config/conexao.php` com os dados do InfinityFree:

```php
define('DB_HOST', 'SEU_HOST_MYSQL');
define('DB_USER', 'SEU_USUARIO_MYSQL');
define('DB_PASS', 'SUA_SENHA_MYSQL');
define('DB_NAME', 'SEU_BANCO_MYSQL');
```

## 3) Estrutura de upload no File Manager

No InfinityFree, entre em `htdocs` e suba estas pastas do projeto:

- `public/`
- `actions/`
- `config/`
- `app/`

Opcional: não precisa enviar a pasta `sql/` para produção.

## 4) Abrir o site sem `/public` na URL

Crie `htdocs/index.php` com:

```php
<?php
header('Location: public/index.php');
exit;
```

## 5) Atualizações futuras

InfinityFree **não atualiza automaticamente** quando você dá push na branch.

Opções:
- Manual: subir arquivos alterados via File Manager/FTP.
- Automatizado: GitHub Actions + FTP.

## 6) Hospedar só o front (landing estática)

Se quiser publicar só a landing (sem login/cadastro funcionando), envie apenas o conteúdo estático de `public/` e ajuste links que dependem de backend.

Para app completo (login, dashboard, hábitos), backend PHP + banco são obrigatórios.
