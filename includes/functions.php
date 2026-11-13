<?php
/**
 * HousePet — Funções utilitárias globais
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─────────────────────────────────────────────
// Sanitização e redirecionamento
// ─────────────────────────────────────────────

/**
 * Remove espaços extras e aplica htmlspecialchars.
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Redireciona para uma URL e encerra o script.
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

// ─────────────────────────────────────────────
// Autenticação / sessão
// ─────────────────────────────────────────────

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'admin';
}

function isModerador(): bool
{
    return isLoggedIn() && isset($_SESSION['user_tipo'])
        && in_array($_SESSION['user_tipo'], ['moderador', 'admin'], true);
}

/**
 * Retorna array com dados do usuário atual ou null.
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'           => $_SESSION['user_id']   ?? null,
        'nome'         => $_SESSION['user_nome']  ?? '',
        'email'        => $_SESSION['user_email'] ?? '',
        'tipo_usuario' => $_SESSION['user_tipo']  ?? 'usuario',
    ];
}

// ─────────────────────────────────────────────
// Upload de imagem
// ─────────────────────────────────────────────

/**
 * Faz upload de uma imagem e retorna o nome do arquivo ou null em caso de erro.
 *
 * @param array  $file       Entrada de $_FILES
 * @param string $destFolder Caminho absoluto da pasta de destino
 */
function uploadImage(array $file, string $destFolder): ?string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo       = finfo_open(FILEINFO_MIME_TYPE);
    $mime        = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMime, true)) {
        return null;
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5 MB
        return null;
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
    $destPath = rtrim($destFolder, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!is_dir($destFolder)) {
        mkdir($destFolder, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return null;
    }

    return $filename;
}

// ─────────────────────────────────────────────
// CSRF
// ─────────────────────────────────────────────

function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ─────────────────────────────────────────────
// Formatação
// ─────────────────────────────────────────────

function formatDate(string $date): string
{
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : $date;
}

// ─────────────────────────────────────────────
// Status helpers
// ─────────────────────────────────────────────

function getStatusLabel(string $status): string
{
    $labels = [
        'pendente'    => 'Pendente',
        'em_analise'  => 'Em Análise',
        'aprovada'    => 'Aprovada',
        'rejeitada'   => 'Rejeitada',
        'disponivel'  => 'Disponível',
        'em_adocao'   => 'Em Adoção',
        'adotado'     => 'Adotado',
        'desaparecido'=> 'Desaparecido',
        'encontrado'  => 'Encontrado',
        'aprovado'    => 'Aprovado',
        'rejeitado'   => 'Rejeitado',
    ];
    return $labels[$status] ?? ucfirst($status);
}

function getStatusClass(string $status): string
{
    $classes = [
        'pendente'    => 'badge-warning',
        'em_analise'  => 'badge-info',
        'aprovada'    => 'badge-success',
        'aprovado'    => 'badge-success',
        'rejeitada'   => 'badge-danger',
        'rejeitado'   => 'badge-danger',
        'disponivel'  => 'badge-success',
        'em_adocao'   => 'badge-info',
        'adotado'     => 'badge-secondary',
        'desaparecido'=> 'badge-danger',
        'encontrado'  => 'badge-success',
    ];
    return $classes[$status] ?? 'badge-secondary';
}

// ─────────────────────────────────────────────
// Flash messages
// ─────────────────────────────────────────────

function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
