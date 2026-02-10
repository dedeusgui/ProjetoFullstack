# 🚀 Guia Rápido de Teste - Doitly

## ✅ CORREÇÃO APLICADA

**Problema:** Caminhos absolutos `/actions/...` não funcionavam no XAMPP  
**Solução:** Alterado para caminhos relativos `../actions/...`

---

## 🎯 COMO TESTAR AGORA

### **1. Acessar a Página de Registro**

```
http://localhost:8080/projetos/projetofullstack/public/register.php
```

ou

```
http://localhost/projetos/projetofullstack/public/register.php
```

### **2. Criar uma Conta**

Preencher:

- **Nome:** Seu Nome
- **Email:** seuemail@teste.com
- **Senha:** 123456 (ou qualquer senha com 6+ caracteres)

Clicar em **"Cadastrar"**

**Resultado Esperado:**

- ✅ Deve redirecionar para `dashboard.php`
- ✅ Deve mostrar seu nome no topo
- ✅ Estatísticas devem estar zeradas (novo usuário)

---

### **3. Fazer Logout**

No dashboard, procurar pelo link/botão de logout (se existir) ou acessar diretamente:

```
http://localhost:8080/projetos/projetofullstack/actions/logout_action.php
```

**Resultado Esperado:**

- ✅ Deve redirecionar para `login.php`

---

### **4. Fazer Login**

```
http://localhost:8080/projetos/projetofullstack/public/login.php
```

Preencher:

- **Email:** seuemail@teste.com
- **Senha:** 123456

Clicar em **"Entrar"**

**Resultado Esperado:**

- ✅ Deve redirecionar para `dashboard.php`
- ✅ Deve mostrar seu nome
- ✅ Deve manter a sessão

---

## 🔍 VERIFICAÇÕES

### **Se der erro 404:**

1. Verificar se o XAMPP está rodando
2. Verificar se a URL está correta
3. Verificar se os arquivos existem em `actions/`

### **Se não redirecionar:**

1. Abrir console do navegador (F12)
2. Verificar erros de PHP
3. Verificar se o banco está conectado

### **Se aparecer erro de conexão:**

1. Verificar se o MySQL está rodando no XAMPP
2. Verificar credenciais em `config/conexao.php`:
   - Host: `localhost`
   - User: `root`
   - Pass: `` (vazio)
   - DB: `doitly_db`

### **Se aparecer erro de SQL:**

1. Verificar se o banco `doitly_db` existe
2. Verificar se as tabelas foram criadas
3. Importar `sql/doitly.sql` novamente

---

## 📊 ESTRUTURA DE TESTES

### **Teste 1: Registro** ✅

- [ ] Acessar página de registro
- [ ] Preencher formulário
- [ ] Submeter
- [ ] Verificar redirecionamento
- [ ] Verificar dados no banco

### **Teste 2: Login** ✅

- [ ] Acessar página de login
- [ ] Usar credenciais criadas
- [ ] Submeter
- [ ] Verificar redirecionamento
- [ ] Verificar sessão ativa

### **Teste 3: Dashboard** ✅

- [ ] Verificar nome do usuário
- [ ] Verificar estatísticas
- [ ] Verificar gráfico (vazio)
- [ ] Verificar lista de hábitos (vazia)

### **Teste 4: Proteção** ✅

- [ ] Fazer logout
- [ ] Tentar acessar dashboard sem login
- [ ] Deve redirecionar para login

---

## 🐛 TROUBLESHOOTING

### **Erro: "Call to undefined function password_verify()"**

**Solução:** Atualizar PHP para versão 5.5+

### **Erro: "Headers already sent"**

**Solução:** Verificar se não há espaços/quebras antes de `<?php`

### **Erro: "Access denied for user 'root'@'localhost'"**

**Solução:** Verificar credenciais do MySQL em `config/conexao.php`

### **Erro: "Unknown database 'doitly_db'"**

**Solução:**

1. Abrir phpMyAdmin
2. Criar banco `doitly_db`
3. Importar `sql/doitly.sql`

---

## ✅ CHECKLIST DE VALIDAÇÃO

Após os testes, verificar:

- [ ] ✅ Registro funciona
- [ ] ✅ Login funciona
- [ ] ✅ Logout funciona
- [ ] ✅ Dashboard carrega
- [ ] ✅ Sessão persiste
- [ ] ✅ Proteção funciona
- [ ] ✅ Dados aparecem no banco
- [ ] ❌ Criar hábito (ainda não funciona)
- [ ] ❌ Editar hábito (ainda não funciona)
- [ ] ❌ Deletar hábito (ainda não funciona)

---

## 📝 PRÓXIMOS PASSOS

Depois de validar que login/registro funcionam:

1. **Implementar CRUD de Hábitos**
   - Criar `habit_create_action.php`
   - Criar `habit_update_action.php`
   - Criar `habit_delete_action.php`
   - Criar `habit_mark_action.php`

2. **Conectar JavaScript**
   - Atualizar AJAX em `habits.php`
   - Adicionar feedback visual
   - Atualizar UI sem reload

3. **Testar Fluxo Completo**
   - Criar hábito
   - Marcar conclusão
   - Ver estatísticas atualizadas
   - Ver gráficos com dados

---

**Status:** 🟢 Autenticação funcionando  
**Próximo:** 🔵 Implementar CRUD de hábitos

---

**Boa sorte nos testes! 🚀**
