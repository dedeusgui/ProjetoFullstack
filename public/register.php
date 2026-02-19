<?php
require_once '../config/bootstrap.php';
bootApp(false);


// Se já estiver logado, redirecionar para dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Configurações do navbar para esta página
$hideLoginButton = false; // Mostra botão de login
$showRegisterButton = false; // Oculta botão de cadastro

include_once "includes/header.php";
?>

<div class="container-doitly d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="doitly-card glass-strong p-5" data-aos="fade-up" style="max-width: 450px; width: 100%;">
        <div class="text-center mb-4">
            <h2 class="mb-2">Crie sua conta</h2>
            <p class="text-secondary">Comece sua jornada hoje mesmo.</p>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-danger-theme doitly-badge-danger w-100 mb-3 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <form action="../actions/register_action.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mb-4">
                <label for="name" class="form-label text-secondary fs-6">Nome</label>
                <div class="input-group">
                    <input type="text" class="doitly-input" id="name" name="name" placeholder="Seu nome" required
                        autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="email" class="form-label text-secondary fs-6">Email</label>
                <div class="input-group">
                    <input type="email" class="doitly-input" id="email" name="email" placeholder="seu@email.com"
                        required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-secondary fs-6">Senha</label>
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


            <div class="mb-4">
                <label for="confirm_password" class="form-label text-secondary fs-6">Confirmar senha</label>
                <div class="position-relative">
                    <input type="password" class="doitly-input" id="confirm_password" name="confirm_password" placeholder="••••••••"
                        required minlength="6" style="padding-right: 45px;">
                    <button type="button" class="btn btn-link position-absolute"
                        onclick="togglePasswordVisibility('confirm_password', this)"
                        style="right: 8px; top: 50%; transform: translateY(-50%); padding: 4px 8px; color: var(--text-secondary); text-decoration: none;"
                        title="Mostrar/Ocultar senha">
                        <i class="bi bi-eye" style="font-size: 1.1rem;"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="doitly-btn w-100 mb-3">
                <span>Cadastrar</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top" style="border-color: var(--border-light) !important;">
            <p class="text-secondary mb-0">
                Já tem uma conta?
                <a href="login.php" class="text-decoration-none fw-medium" style="color: var(--accent-blue);">Fazer
                    Login</a>
            </p>
        </div>
    </div>
</div>



<?php include_once "includes/footer.php"; ?>