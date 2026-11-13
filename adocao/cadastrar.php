<?php
/**
 * HousePet — Cadastrar Animal para Adoção
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$baseUrl = '/housePet2';
$errors  = [];
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
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

        $old = compact('nome','especie','raca','idade','sexo','porte','descricao','localizacao','contato');

        // Validações obrigatórias
        if (empty($nome))        $errors[] = 'O nome do animal é obrigatório.';
        if (empty($especie))     $errors[] = 'A espécie é obrigatória.';
        if (empty($sexo))        $errors[] = 'O sexo é obrigatório.';
        if (empty($porte))       $errors[] = 'O porte é obrigatório.';
        if (empty($localizacao)) $errors[] = 'A localização é obrigatória.';
        if (empty($contato))     $errors[] = 'O contato para adoção é obrigatório.';

        // Upload de imagem
        $imagemNome = null;
        if (!empty($_FILES['imagem']['name']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $destDir = __DIR__ . '/../public/assets/images/uploads/animais/';
            $imagemNome = uploadImage($_FILES['imagem'], $destDir);
            if (!$imagemNome) {
                $errors[] = 'Falha no upload da imagem. Formatos aceitos: JPG, PNG, GIF, WEBP até 5MB.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO animais
                (usuario_id, nome, especie, raca, idade, sexo, porte, descricao, localizacao, contato, imagem, status)
                VALUES (:uid, :nome, :especie, :raca, :idade, :sexo, :porte, :descricao, :localizacao, :contato, :imagem, 'pendente')
            ");
            $stmt->execute([
                ':uid'        => $_SESSION['user_id'],
                ':nome'       => $nome,
                ':especie'    => $especie,
                ':raca'       => $raca,
                ':idade'      => $idade,
                ':sexo'       => $sexo,
                ':porte'      => $porte,
                ':descricao'  => $descricao,
                ':localizacao'=> $localizacao,
                ':contato'    => $contato,
                ':imagem'     => $imagemNome,
            ]);

            setFlashMessage('success', 'Animal cadastrado com sucesso! Sua publicação passará pela moderação antes de aparecer publicamente.');
            redirect($baseUrl . '/usuario/dashboard.php');
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Cadastrar Animal para Adoção';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5" style="max-width:760px;">
    <div class="card shadow-sm border-0" style="border-radius:14px; overflow:hidden;">
        <div class="card-header bg-success text-white py-3">
            <h2 class="h4 mb-0"><i class="fa-solid fa-paw me-2"></i>Cadastrar Animal para Adoção</h2>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-4">
                Preencha os dados abaixo com carinho. Publicações claras e fotos nítidas aumentam muito as chances de encontrar uma família responsável!
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
                        <input type="text" name="nome" class="form-control"
                               value="<?= htmlspecialchars($old['nome'] ?? '') ?>" placeholder="Ex: Rex, Mel, Thor" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Espécie *</label>
                        <select name="especie" class="form-select" required>
                            <option value="">Selecione a espécie...</option>
                            <?php
                            $especies = ['cachorro' => 'Cachorro', 'gato' => 'Gato', 'ave' => 'Ave', 'coelho' => 'Coelho', 'outro' => 'Outro'];
                            foreach ($especies as $k => $label):
                                $sel = (isset($old['especie']) && $old['especie'] === $k) ? 'selected' : '';
                            ?>
                            <option value="<?= $k ?>" <?= $sel ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Raça (opcional)</label>
                        <input type="text" name="raca" class="form-control"
                               value="<?= htmlspecialchars($old['raca'] ?? '') ?>" placeholder="Ex: Vira-lata (SRD), Poodle, Siames">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Idade aproximada</label>
                        <input type="text" name="idade" class="form-control"
                               value="<?= htmlspecialchars($old['idade'] ?? '') ?>" placeholder="Ex: 6 meses, 2 anos, Idoso">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Sexo *</label>
                        <select name="sexo" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="macho" <?= (isset($old['sexo']) && $old['sexo'] === 'macho') ? 'selected' : '' ?>>Macho</option>
                            <option value="femea" <?= (isset($old['sexo']) && $old['sexo'] === 'femea') ? 'selected' : '' ?>>Fêmea</option>
                            <option value="nao_informado" <?= (isset($old['sexo']) && $old['sexo'] === 'nao_informado') ? 'selected' : '' ?>>Não informado</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Porte *</label>
                        <select name="porte" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="pequeno" <?= (isset($old['porte']) && $old['porte'] === 'pequeno') ? 'selected' : '' ?>>Pequeno</option>
                            <option value="medio" <?= (isset($old['porte']) && $old['porte'] === 'medio') ? 'selected' : '' ?>>Médio</option>
                            <option value="grande" <?= (isset($old['porte']) && $old['porte'] === 'grande') ? 'selected' : '' ?>>Grande</option>
                            <option value="gigante" <?= (isset($old['porte']) && $old['porte'] === 'gigante') ? 'selected' : '' ?>>Gigante</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cidade / Localização *</label>
                        <input type="text" name="localizacao" class="form-control"
                               value="<?= htmlspecialchars($old['localizacao'] ?? '') ?>" placeholder="Ex: Porto Velho, RO - Bairro Centro" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Contato do Responsável *</label>
                        <input type="text" name="contato" class="form-control"
                               value="<?= htmlspecialchars($old['contato'] ?? '') ?>" placeholder="Ex: (69) 99999-9999 / WhatsApp" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descrição / História do Animal</label>
                        <textarea name="descricao" rows="4" class="form-control"
                                  placeholder="Conte sobre o temperamento, cuidados veterinários (vacinado, castrado, vermifugado), convivência com outros pets e motivos da doação..."><?= htmlspecialchars($old['descricao'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Fotografia do Animal</label>
                        <input type="file" name="imagem" id="inputFoto" class="form-control" accept="image/*">
                        <small class="text-muted">Formatos: JPG, PNG, WEBP ou GIF (máx. 5MB).</small>
                        <div id="imagePreview" class="mt-2" style="display:none;">
                            <img id="previewImg" src="" alt="Prévia" style="max-height:180px; border-radius:8px;">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?= $baseUrl ?>/usuario/dashboard.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa-solid fa-check me-1"></i> Publicar para Moderação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('inputFoto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('previewImg').src = evt.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
