<?php
// Configurações do navbar para esta página
$hideLoginButton = true; // Oculta botão de login
$showRegisterButton = true; // Mostra botão de cadastro

include_once "includes/header.php";
?>

<form action="/actions/login_action.php" method="post">
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <div class="form-group">
        <label for="password">Senha</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Login</button>
</form>



<?php include_once "includes/footer.php"; ?>
