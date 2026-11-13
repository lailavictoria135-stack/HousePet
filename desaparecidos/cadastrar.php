<?php
/**
 * HousePet — Cadastrar Animal Desaparecido
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$baseUrl = '/housePet2';
$errors  = [];
$old     = [];

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

        $old = compact('nome_animal', 'especie', 'raca', 'caracteristicas', 'local_desaparecimento', 'data_desaparecimento', 'contato');

        if (empty($nome_animal))           $errors[] = 'O nome ou identificação do animal é obrigatório.';
        if (empty($especie))               $errors[] = 'A espécie é obrigatória.';
        if (empty($local_desaparecimento)) $errors[] = 'O local do desaparecimento é obrigatório.';
        if (empty($contato))               $errors[] = 'O telefone/contato para notícias é obrigatório.';

        // Upload de foto
        $imagemNome = null;
        if (!empty($_FILES['imagem']['name']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $destDir = __DIR__ . '/../public/assets/images/uploads/desaparecidos/';
            $imagemNome = uploadImage($_FILES['imagem'], $destDir);
            if (!$imagemNome) {
                $errors[] = 'Erro ao enviar a fotografia. Formatos suportados: JPG, PNG, WEBP ou GIF até 5MB.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO desaparecidos
                (usuario_id, nome_animal, especie, raca, caracteristicas, local_desaparecimento, data_desaparecimento, contato, imagem, status)
                VALUES (:uid, :nome, :especie, :raca, :carac, :local, :data, :contato, :imagem, 'pendente')
            ");
            $stmt->execute([
                ':uid'     => $_SESSION['user_id'],
                ':nome'    => $nome_animal,
                ':especie' => $especie,
                ':raca'    => $raca,
                ':carac'   => $caracteristicas,
                ':local'   => $local_desaparecimento,
                ':data'    => !empty($data_desaparecimento) ? $data_desaparecimento : null,
                ':contato' => $contato,
                ':imagem'  => $imagemNome,
            ]);

            setFlashMessage('success', 'Animal desaparecido registrado! A publicação passará por moderação para garantir a qualidade das informações.');
            redirect($baseUrl . '/usuario/dashboard.php');
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Divulgar Animal Desaparecido';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5" style="max-width:760px;">
    <div class="card shadow-sm border-0" style="border-radius:14px; overflow:hidden;">
        <div class="card-header bg-danger text-white py-3">
            <h2 class="h4 mb-0"><i class="fa-solid fa-bullhorn me-2"></i>Registrar Animal Desaparecido</h2>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-4">
                Informe os detalhes do animal e do local com máxima precisão para facilitar que vizinhos e voluntários reconheçam o pet.
            </p>

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
                               value="<?= htmlspecialchars($old['nome_animal'] ?? '') ?>" placeholder="Ex: Bob, Pipoca, Thor" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Espécie *</label>
                        <select name="especie" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="cachorro" <?= (isset($old['especie']) && $old['especie'] === 'cachorro') ? 'selected' : '' ?>>Cachorro</option>
                            <option value="gato" <?= (isset($old['especie']) && $old['especie'] === 'gato') ? 'selected' : '' ?>>Gato</option>
                            <option value="ave" <?= (isset($old['especie']) && $old['especie'] === 'ave') ? 'selected' : '' ?>>Ave</option>
                            <option value="outro" <?= (isset($old['especie']) && $old['especie'] === 'outro') ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Raça (se conhecida)</label>
                        <input type="text" name="raca" class="form-control"
                               value="<?= htmlspecialchars($old['raca'] ?? '') ?>" placeholder="Ex: Vira-lata, Labrador, Siamês">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Data do Desaparecimento</label>
                        <input type="date" name="data_desaparecimento" class="form-control"
                               value="<?= htmlspecialchars($old['data_desaparecimento'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Local do Desaparecimento (Rua, Bairro, Ponto de Referência) *</label>
                        <input type="text" name="local_desaparecimento" class="form-control"
                               value="<?= htmlspecialchars($old['local_desaparecimento'] ?? '') ?>" placeholder="Ex: Próximo à praça central, Bairro Embratel, Porto Velho - RO" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Contato Urgente para Notícias *</label>
                        <input type="text" name="contato" class="form-control"
                               value="<?= htmlspecialchars($old['contato'] ?? '') ?>" placeholder="Ex: (69) 99999-9999 / Falar com Mariana" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Características Físicas e Detalhes</label>
                        <textarea name="caracteristicas" rows="4" class="form-control"
                                  placeholder="Descreva cor dos pelos, manchas, coleira, porte, cicatrizes, comportamento (arisco, dócil) ou outros detalhes visíveis..."><?= htmlspecialchars($old['caracteristicas'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Fotografia Nítida do Animal</label>
                        <input type="file" name="imagem" id="inputFotoPet" class="form-control" accept="image/*">
                        <small class="text-muted">Anexar uma boa foto ajuda muito na identificação (máx. 5MB).</small>
                        <div id="previewContainer" class="mt-2" style="display:none;">
                            <img id="previewImgPet" src="" alt="Prévia" style="max-height:180px; border-radius:8px;">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?= $baseUrl ?>/usuario/dashboard.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="fa-solid fa-bullhorn me-1"></i> Registrar Publicação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('inputFotoPet').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('previewImgPet').src = evt.target.result;
            document.getElementById('previewContainer').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

