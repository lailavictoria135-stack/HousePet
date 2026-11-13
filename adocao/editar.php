<?php
/**
 * HousePet — Editar Animal para Adoção
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$baseUrl = '/housePet2';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('danger', 'Animal não informado.');
    redirect($baseUrl . '/usuario/dashboard.php');
}

// Busca o animal
$stmt = $pdo->prepare("SELECT * FROM animais WHERE id = ?");
$stmt->execute([$id]);
$animal = $stmt->fetch();

if (!$animal) {
    setFlashMessage('danger', 'Animal não encontrado.');
    redirect($baseUrl . '/usuario/dashboard.php');
}

// Verifica se é o dono ou admin
$isDono = ((int)$_SESSION['user_id'] === (int)$animal['usuario_id']);
if (!$isDono && !isModerador()) {
    setFlashMessage('danger', 'Você não tem permissão para editar este animal.');
    redirect($baseUrl . '/usuario/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    } else {
        $nome        = sanitize($_POST['nome'] ?? '');
        $especie     = sanitize($_POST['especie'] ?? '');
        $raca        = sanitize($_POST['raca'] ?? '');
        $idade       = sanitize($_POST['idade'] ?? '');
        $sexo        = sanitize($_POST['sexo'] ?? '');
        $porte       = sanitize($_POST['porte'] ?? '');
        $descricao   = sanitize($_POST['descricao'] ?? '');
        $localizacao = sanitize($_POST['localizacao'] ?? '');
        $contato     = sanitize($_POST['contato'] ?? '');
        $status      = sanitize($_POST['status'] ?? $animal['status']);

        if (empty($nome))        $errors[] = 'O nome do animal é obrigatório.';
        if (empty($especie))     $errors[] = 'A espécie é obrigatória.';
        if (empty($sexo))        $errors[] = 'O sexo é obrigatório.';
        if (empty($porte))       $errors[] = 'O porte é obrigatório.';
        if (empty($localizacao)) $errors[] = 'A localização é obrigatória.';
        if (empty($contato))     $errors[] = 'O contato é obrigatório.';

        $statusValidos = ['pendente', 'disponivel', 'em_adocao', 'adotado'];
        if (!in_array($status, $statusValidos, true)) {
            $status = $animal['status'];
        }

        // Upload de nova imagem (se fornecida)
        $imagemNome = $animal['imagem'];
        if (!empty($_FILES['imagem']['name']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $destDir = __DIR__ . '/../public/assets/images/uploads/animais/';
            $novaFoto = uploadImage($_FILES['imagem'], $destDir);
            if ($novaFoto) {
                $imagemNome = $novaFoto;
            } else {
                $errors[] = 'Erro no upload da nova imagem. Use formato JPG, PNG, WEBP ou GIF até 5MB.';
            }
        }

        if (empty($errors)) {
            $stmtUpdate = $pdo->prepare("
                UPDATE animais SET
                    nome = :nome,
                    especie = :especie,
                    raca = :raca,
                    idade = :idade,
                    sexo = :sexo,
                    porte = :porte,
                    descricao = :descricao,
                    localizacao = :localizacao,
                    contato = :contato,
                    imagem = :imagem,
                    status = :status
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':nome'        => $nome,
                ':especie'    => $especie,
                ':raca'       => $raca,
                ':idade'      => $idade,
                ':sexo'       => $sexo,
                ':porte'      => $porte,
                ':descricao'  => $descricao,
                ':localizacao'=> $localizacao,
                ':contato'    => $contato,
                ':imagem'     => $imagemNome,
                ':status'     => $status,
                ':id'         => $id
            ]);

            setFlashMessage('success', 'Dados do animal atualizados com sucesso!');
            redirect($baseUrl . '/adocao/detalhes.php?id=' . $id);
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Editar Animal — ' . $animal['nome'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5" style="max-width:760px;">
    <div class="card shadow-sm border-0" style="border-radius:14px; overflow:hidden;">
        <div class="card-header bg-success text-white py-3">
            <h2 class="h4 mb-0"><i class="fa-solid fa-pen-to-square me-2"></i>Editar Animal: <?= htmlspecialchars($animal['nome']) ?></h2>
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
                        <input type="text" name="nome" class="form-control"
                               value="<?= htmlspecialchars($_POST['nome'] ?? $animal['nome']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status de Disponibilidade *</label>
                        <select name="status" class="form-select" required>
                            <option value="disponivel" <?= ($animal['status'] === 'disponivel') ? 'selected' : '' ?>>Disponível para Adoção</option>
                            <option value="em_adocao" <?= ($animal['status'] === 'em_adocao') ? 'selected' : '' ?>>Em Processo de Adoção</option>
                            <option value="adotado" <?= ($animal['status'] === 'adotado') ? 'selected' : '' ?>>Adotado 🎉</option>
                            <option value="pendente" <?= ($animal['status'] === 'pendente') ? 'selected' : '' ?>>Pendente de Moderação</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Espécie *</label>
                        <select name="especie" class="form-select" required>
                            <?php
                            $espAtual = $_POST['especie'] ?? $animal['especie'];
                            $especies = ['cachorro' => 'Cachorro', 'gato' => 'Gato', 'ave' => 'Ave', 'coelho' => 'Coelho', 'outro' => 'Outro'];
                            foreach ($especies as $k => $label):
                            ?>
                            <option value="<?= $k ?>" <?= ($espAtual === $k) ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Raça</label>
                        <input type="text" name="raca" class="form-control"
                               value="<?= htmlspecialchars($_POST['raca'] ?? $animal['raca']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Idade aproximada</label>
                        <input type="text" name="idade" class="form-control"
                               value="<?= htmlspecialchars($_POST['idade'] ?? $animal['idade']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Sexo *</label>
                        <select name="sexo" class="form-select" required>
                            <?php $sexoAtual = $_POST['sexo'] ?? $animal['sexo']; ?>
                            <option value="macho" <?= ($sexoAtual === 'macho') ? 'selected' : '' ?>>Macho</option>
                            <option value="femea" <?= ($sexoAtual === 'femea') ? 'selected' : '' ?>>Fêmea</option>
                            <option value="nao_informado" <?= ($sexoAtual === 'nao_informado') ? 'selected' : '' ?>>Não informado</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Porte *</label>
                        <select name="porte" class="form-select" required>
                            <?php $porteAtual = $_POST['porte'] ?? $animal['porte']; ?>
                            <option value="pequeno" <?= ($porteAtual === 'pequeno') ? 'selected' : '' ?>>Pequeno</option>
                            <option value="medio" <?= ($porteAtual === 'medio') ? 'selected' : '' ?>>Médio</option>
                            <option value="grande" <?= ($porteAtual === 'grande') ? 'selected' : '' ?>>Grande</option>
                            <option value="gigante" <?= ($porteAtual === 'gigante') ? 'selected' : '' ?>>Gigante</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Localização *</label>
                        <input type="text" name="localizacao" class="form-control"
                               value="<?= htmlspecialchars($_POST['localizacao'] ?? $animal['localizacao']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Contato para Adoção *</label>
                        <input type="text" name="contato" class="form-control"
                               value="<?= htmlspecialchars($_POST['contato'] ?? $animal['contato']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descrição do Animal</label>
                        <textarea name="descricao" rows="4" class="form-control"><?= htmlspecialchars($_POST['descricao'] ?? $animal['descricao']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Substituir Fotografia (opcional)</label>
                        <?php if ($animal['imagem']): ?>
                            <div class="mb-2">
                                <small class="text-muted d-block">Foto atual:</small>
                                <img src="<?= $baseUrl ?>/public/assets/images/uploads/animais/<?= htmlspecialchars($animal['imagem']) ?>"
                                     alt="Foto atual" style="max-height:100px; border-radius:8px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagem" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?= $baseUrl ?>/usuario/dashboard.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

