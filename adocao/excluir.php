<?php
/**
 * HousePet — Excluir Animal para Adoção
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$baseUrl = '/housePet2';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('danger', 'Animal inválido.');
    redirect($baseUrl . '/usuario/dashboard.php');
}

$stmt = $pdo->prepare("SELECT * FROM animais WHERE id = ?");
$stmt->execute([$id]);
$animal = $stmt->fetch();

if (!$animal) {
    setFlashMessage('danger', 'Animal não encontrado.');
    redirect($baseUrl . '/usuario/dashboard.php');
}

$isDono = ((int)$_SESSION['user_id'] === (int)$animal['usuario_id']);
if (!$isDono && !isModerador()) {
    setFlashMessage('danger', 'Você não tem permissão para excluir esta publicação.');
    redirect($baseUrl . '/usuario/dashboard.php');
}

// Remove arquivo físico de imagem se existir
if (!empty($animal['imagem'])) {
    $filePath = __DIR__ . '/../public/assets/images/uploads/animais/' . $animal['imagem'];
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
}

// Exclui registro
$stmtDel = $pdo->prepare("DELETE FROM animais WHERE id = ?");
$stmtDel->execute([$id]);

setFlashMessage('success', 'Publicação do animal removida com sucesso.');
redirect($baseUrl . '/usuario/dashboard.php');

