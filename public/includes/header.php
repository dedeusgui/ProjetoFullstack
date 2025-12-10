<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Doitly - Gerenciador de Hábitos</title>

    <!-- Bootstrap 5 CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <!-- Doitly Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/landing-sections.css" />
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- AOS (Animate On Scroll) -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
</head>

<body>
    <!-- NAVBAR -->
    <nav class="doitly-navbar">
      <a href="index.php">
        <img src="assets/img/logo.png" alt="Logo" class="doitly-logo" />
      </a>
      <h2 class="doitly-navbar-brand">Doitly</h2>
      <div class="d-flex gap-md align-items-center">
        <?php if (!isset($hideLoginButton) || !$hideLoginButton): ?>
          <a href="login.php" class="doitly-btn doitly-btn-md">Login</a>
        <?php endif; ?>
        
        <?php if (isset($showRegisterButton) && $showRegisterButton): ?>
          <a href="register.php" class="doitly-btn doitly-btn-secondary doitly-btn-md">Cadastrar</a>
        <?php endif; ?>
      </div>
    </nav>

    <!-- Main Content Wrapper -->
    <main class="main-content">
