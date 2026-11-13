<?php
/**
 * HousePet — Header global
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
$flash       = getFlashMessage();
$baseUrl     = '/housePet2';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HousePet — Plataforma completa de proteção e bem-estar animal: adoção responsável, registro de denúncias e divulgação de animais desaparecidos.">
    <meta name="theme-color" content="#2d8c4e">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | HousePet' : 'HousePet — Proteção e Bem-Estar Animal' ?></title>

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6.5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
          integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkA=="
          crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/assets/css/style.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar" id="navbar">
    <div class="container navbar-inner">

        <!-- Logo -->
        <a href="<?= $baseUrl ?>/" class="navbar-brand">
            🐾 <span>HousePet</span>
        </a>

        <!-- Hamburger (mobile) -->
        <button class="hamburger" id="hamburger" aria-label="Abrir menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <!-- Links de Navegação -->
        <ul class="nav-links" id="navLinks">
            <li><a href="<?= $baseUrl ?>/" class="nav-link">Início</a></li>
            <li><a href="<?= $baseUrl ?>/adocao/index.php" class="nav-link">Adoção</a></li>
            <li><a href="<?= $baseUrl ?>/desaparecidos/index.php" class="nav-link">Desaparecidos</a></li>
            <li><a href="<?= $baseUrl ?>/denuncias/index.php" class="nav-link">Denúncias</a></li>

            <?php if ($currentUser): ?>
                <!-- Usuário autenticado: Dropdown -->
                <li class="nav-dropdown">
                    <button class="nav-link dropdown-toggle" id="userDropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa-solid fa-circle-user"></i>
                        <span><?= sanitize($currentUser['nome']) ?></span>
                        <i class="fa-solid fa-chevron-down fa-xs"></i>
                    </button>
                    <ul class="dropdown-menu" id="userDropdownMenu">
                        <li><a href="<?= $baseUrl ?>/usuario/dashboard.php"><i class="fa-solid fa-gauge"></i> Meu Painel</a></li>
                        <li><a href="<?= $baseUrl ?>/adocao/cadastrar.php"><i class="fa-solid fa-paw"></i> Cadastrar Animal</a></li>
                        <li><a href="<?= $baseUrl ?>/desaparecidos/cadastrar.php"><i class="fa-solid fa-bullhorn"></i> Divulgar Desaparecido</a></li>
                        <li><a href="<?= $baseUrl ?>/denuncias/cadastrar.php"><i class="fa-solid fa-triangle-exclamation"></i> Fazer Denúncia</a></li>
                        <li><a href="<?= $baseUrl ?>/denuncias/minhas_denuncias.php"><i class="fa-solid fa-list-check"></i> Minhas Denúncias</a></li>
                        <?php if (isModerador()): ?>
                            <li class="dropdown-divider"></li>
                            <li><a href="<?= $baseUrl ?>/admin/dashboard.php" class="admin-link"><i class="fa-solid fa-shield-halved"></i> Painel Admin</a></li>
                        <?php endif; ?>
                        <li class="dropdown-divider"></li>
                        <li><a href="<?= $baseUrl ?>/auth/logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <!-- Visitante não autenticado -->
                <li class="nav-auth">
                    <a href="<?= $baseUrl ?>/auth/login.php" class="btn btn-outline-sm">Entrar</a>
                    <a href="<?= $baseUrl ?>/auth/cadastro.php" class="btn btn-primary-sm">Cadastrar</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- ===== FLASH MESSAGES ===== -->
<?php if ($flash): ?>
<div class="flash-container">
    <div class="alert alert-<?= sanitize($flash['type']) ?>" role="alert" id="flashMsg">
        <span><?= sanitize($flash['message']) ?></span>
        <button class="alert-close" aria-label="Fechar">&times;</button>
    </div>
</div>
<?php endif; ?>

<!-- ===== CONTEÚDO PRINCIPAL ===== -->
<main class="main-content">
