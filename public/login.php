<?php
require_once '../config/bootstrap.php';
bootApp(false);


// Se já estiver logado, redirecionar para dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Configurações do navbar para esta página
$hideLoginButton = true; // Oculta botão de login
$showRegisterButton = true; // Mostra botão de cadastro

include_once "includes/header.php";
?>

<div class="container-doitly d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="doitly-card glass-strong p-5" data-aos="fade-up" style="max-width: 450px; width: 100%;">
        <div class="text-center mb-4">
            <h2 class="mb-2">Bem-vindo de volta!</h2>
            <p class="text-secondary">Faça login para continuar sua jornada.</p>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger doitly-badge-danger w-100 mb-3 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <form action="../actions/login_action.php" method="post">
            <div class="mb-4">
                <label for="email" class="form-label text-secondary fs-6">Email</label>
                <div class="input-group">
                    <input type="email" class="doitly-input" id="email" name="email" placeholder="seu@email.com"
                        required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label text-secondary fs-6">Senha</label>
                </div>
                <div class="position-relative">
                    <input type="password" class="doitly-input" id="password" name="password" placeholder="••••••••"
                        required style="padding-right: 45px;">
                    <button type="button" class="btn btn-link position-absolute"
                        onclick="togglePasswordVisibility('password', this)"
                        style="right: 8px; top: 50%; transform: translateY(-50%); padding: 4px 8px; color: var(--text-secondary); text-decoration: none;"
                        title="Mostrar/Ocultar senha">
                        <i class="bi bi-eye" style="font-size: 1.1rem;"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                    <label class="form-check-label text-secondary" for="rememberMe">Lembrar-me</label>
                </div>

            </div>

            <button type="submit" class="doitly-btn w-100 mb-3">
                <span>Entrar</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top" style="border-color: var(--border-light) !important;">
            <p class="text-secondary mb-0">
                Não tem uma conta?
                <a href="register.php" class="text-decoration-none fw-medium" style="color: var(--accent-blue);">Criar
                    conta</a>
            </p>
        </div>
    </div>
</div>



<?php include_once "includes/footer.php"; ?>