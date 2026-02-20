<?php
$currentUserSettings = $userData ?? [];
$userTheme = $currentUserSettings['theme'] ?? 'light';
$userPrimaryColor = $currentUserSettings['primary_color'] ?? '#4a74ff';
$userAccentColor = $currentUserSettings['accent_color'] ?? '#59d186';
$userTextScale = isset($currentUserSettings['text_scale']) ? (float) $currentUserSettings['text_scale'] : 1.00;

if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $userPrimaryColor)) {
    $userPrimaryColor = '#4a74ff';
}

if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $userAccentColor)) {
    $userAccentColor = '#59d186';
}

if ($userTextScale < 0.9 || $userTextScale > 1.2) {
    $userTextScale = 1.00;
}

$htmlThemeAttribute = $userTheme === 'dark' ? ' data-theme="dark"' : '';
$htmlInlineStyle = sprintf(
    '--accent-blue: %s; --accent-blue-hover: %s; --accent-green: %s; font-size: %.2frem;',
    htmlspecialchars($userPrimaryColor, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($userPrimaryColor, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($userAccentColor, ENT_QUOTES, 'UTF-8'),
    $userTextScale
);
?>
<!DOCTYPE html>
<html lang="pt-br"<?php echo $htmlThemeAttribute; ?> style="<?php echo $htmlInlineStyle; ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Doitly - Gerenciador de Hábitos</title>

    <!-- FOUC Prevention: Apply theme BEFORE render -->
    <script>
      (function() {
        const THEME_KEY = 'doitly-theme';
        const getPreferredTheme = () => {
          const persisted = localStorage.getItem(THEME_KEY);
          if (persisted === 'dark' || persisted === 'light') return persisted;
          return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        };
        const theme = getPreferredTheme();
        if (theme === 'dark') {
          document.documentElement.setAttribute('data-theme', 'dark');
        }
      })();
    </script>

    <!-- Bootstrap 5 CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <!-- Doitly Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>" />
    <link rel="stylesheet" href="assets/css/landing-sections.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/landing-sections.css'); ?>" />
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- AOS (Animate On Scroll) -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

</head>

<body>
    <!-- NAVBAR -->
    <nav class="doitly-navbar">
      <a href="index.php" class="doitly-logo-link">
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
