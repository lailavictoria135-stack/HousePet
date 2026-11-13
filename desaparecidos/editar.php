<?php
/**
 * HousePet — Editar Animal Desaparecido
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$baseUrl = '/housePet2';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('danger', 'Registro inválido.');
    redirect($baseUrl . '/usuario/dashboard.php');
}

$stmt = $pdo->prepare("SELECT * FROM desaparecidos WHERE id = ?");
$stmt->execute([$id]);
$pet = $stmt->fetch();

if (!$pet) {
    setFlashMessage('danger', 'Animal desaparecido não encontrado.');
    redirect($baseUrl . '/usuario/dashboard.php');
}

$isDono = ((int)$_SESSION['user_id'] === (int)$pet['usuario_id']);
if (!$isDono && !isModerador()) {
    setFlashMessage('danger', 'Permissão negada para editar esta publicação.');
    redirect($baseUrl . '/usuario/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    } else {
        $nome_animal           = sanitize($_POST['nome_animal'] ?? '');
        $especie               = sanitize($_POST['especie'] ?? '');
        $raca                  = sanitize($_POST['raca'] ?? '');
        $caracteristicas       = sanitize($_POST['caracteristicas'] ?? '');
        $local_desaparecimento = sanitize($_POST['local_desaparecimento'] ?? '');
        $data_desaparecimento  = sanitize($_POST['data_desaparecimento'] ?? '');
        $contato               = sanitize($_POST['contato'] ?? '');
        $status                = sanitize($_POST['status'] ?? $pet['status']);

        if (empty($nome_animal))           $errors[] = 'O nome do animal é obrigatório.';
        if (empty($especie))               $errors[] = 'A espécie é obrigatória.';
        if (empty($local_desaparecimento)) $errors[] = 'O local do desaparecimento é obrigatório.';
        if (empty($contato))               $errors[] = 'O contato para notícias é obrigatório.';

        $statusValidos = ['pendente', 'desaparecido', 'encontrado'];
        if (!in_array($status, $statusValidos, true)) {
            $status = $pet['status'];
        }

        $imagemNome = $pet['imagem'];
        if (!empty($_FILES['imagem']['name']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $destDir = __DIR__ . '/../public/assets/images/uploads/desaparecidos/';
            $novaFoto = uploadImage($_FILES['imagem'], $destDir);
            if ($novaFoto) {
                $imagemNome = $novaFoto;
            } else {
                $errors[] = 'Erro ao enviar a fotografia. Use formato JPG, PNG, WEBP ou GIF até 5MB.';
            }
        }

        if (empty($errors)) {
            $stmtUp = $pdo->prepare("
                UPDATE desaparecidos SET
                    nome_animal = :nome,
                    especie = :especie,
                    raca = :raca,
                    caracteristicas = :carac,
                    local_desaparecimento = :local,
                    data_desaparecimento = :data,
                    contato = :contato,
                    imagem = :imagem,
                    status = :status
                WHERE id = :id
            ");
            $stmtUp->execute([
                ':nome'    => $nome_animal,
                ':especie' => $especie,
                ':raca'    => $raca,
                ':carac'   => $caracteristicas,
                ':local'   => $local_desaparecimento,
                ':data'    => !empty($data_desaparecimento) ? $data_desaparecimento : null,
                ':contato' => $contato,
                ':imagem'  => $imagemNome,
                ':status'  => $status,
                ':id'      => $id
            ]);

            setFlashMessage('success', 'Publicação do pet desaparecido atualizada com sucesso!');
            redirect($baseUrl . '/desaparecidos/detalhes.php?id=' . $id);
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Editar Desaparecido — ' . $pet['nome_animal'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5" style="max-width:760px;">
    <div class="card shadow-sm border-0" style="border-radius:14px; overflow:hidden;">
        <div class="card-header bg-danger text-white py-3">
            <h2 class="h4 mb-0"><i class="fa-solid fa-pen-to-square me-2"></i>Editar Registro: <?= htmlspecialchars($pet['nome_animal']) ?></h2>
        </div>
        <div class="card-body p-4">

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nome do Animal *</label>
                        <input type="text" name="nome_animal" class="form-control"
                               value="<?= htmlspecialchars($_POST['nome_animal'] ?? $pet['nome_animal']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Situação do Animal *</label>
                        <select name="status" class="form-select" required>
                            <option value="desaparecido" <?= ($pet['status'] === 'desaparecido') ? 'selected' : '' ?>>Ainda Desaparecido</option>
                            <option value="encontrado" <?= ($pet['status'] === 'encontrado') ? 'selected' : '' ?>>Já foi Encontrado 🎉</option>
                            <option value="pendente" <?= ($pet['status'] === 'pendente') ? 'selected' : '' ?>>Pendente de Moderação</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Espécie *</label>
                        <select name="especie" class="form-select" required>
                            <?php $esp = $_POST['especie'] ?? $pet['especie']; ?>
                            <option value="cachorro" <?= ($esp === 'cachorro') ? 'selected' : '' ?>>Cachorro</option>
                            <option value="gato" <?= ($esp === 'gato') ? 'selected' : '' ?>>Gato</option>
                            <option value="ave" <?= ($esp === 'ave') ? 'selected' : '' ?>>Ave</option>
                            <option value="outro" <?= ($esp === 'outro') ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Raça</label>
                        <input type="text" name="raca" class="form-control"
                               value="<?= htmlspecialchars($_POST['raca'] ?? $pet['raca']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Data do Desaparecimento</label>
                        <input type="date" name="data_desaparecimento" class="form-control"
                               value="<?= htmlspecialchars($_POST['data_desaparecimento'] ?? $pet['data_desaparecimento']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Contato Urgente *</label>
                        <input type="text" name="contato" class="form-control"
                               value="<?= htmlspecialchars($_POST['contato'] ?? $pet['contato']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Local do Desaparecimento *</label>
                        <input type="text" name="local_desaparecimento" class="form-control"
                               value="<?= htmlspecialchars($_POST['local_desaparecimento'] ?? $pet['local_desaparecimento']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Características Físicas e Detalhes</label>
                        <textarea name="caracteristicas" rows="4" class="form-control"><?= htmlspecialchars($_POST['caracteristicas'] ?? $pet['caracteristicas']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Substituir Fotografia (opcional)</label>
                        <?php if ($pet['imagem']): ?>
                            <div class="mb-2">
                                <small class="text-muted d-block">Foto atual:</small>
                                <img src="<?= $baseUrl ?>/public/assets/images/uploads/desaparecidos/<?= htmlspecialchars($pet['imagem']) ?>"
                                     alt="Foto atual" style="max-height:100px; border-radius:8px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagem" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?= $baseUrl ?>/desaparecidos/detalhes.php?id=<?= $pet['id'] ?>" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Voltar aos Detalhes
                    </a>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

