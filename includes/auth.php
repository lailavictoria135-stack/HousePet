<?php
/**
 * HousePet — Helpers de autenticação e proteção de rotas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

/**
 * Exige que o usuário esteja autenticado.
 * Redireciona para a página de login caso não esteja.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Você precisa estar logado para acessar esta página.');
        redirect('/housePet2/auth/login.php');
    }
}

/**
 * Exige que o usuário seja administrador ou moderador.
 * Redireciona para a home caso não tenha permissão.
 */
function requireAdmin(): void
{
    requireLogin();
    if (!isModerador()) {
        setFlashMessage('danger', 'Acesso restrito. Você não tem permissão para acessar esta área.');
        redirect('/housePet2/');
    }
}

/**
 * Exige que o usuário seja administrador (somente admin, não moderador).
 */
function requireSuperAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('danger', 'Acesso restrito. Apenas administradores podem acessar esta área.');
        redirect('/housePet2/');
    }
}

/**
 * Realiza o login do usuário, populando a sessão.
 */
function loginUser(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']    = $usuario['id'];
    $_SESSION['user_nome']  = $usuario['nome'];
    $_SESSION['user_email'] = $usuario['email'];
    $_SESSION['user_tipo']  = $usuario['tipo_usuario'];
}

/**
 * Encerra a sessão e desloga o usuário.
 */
function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
