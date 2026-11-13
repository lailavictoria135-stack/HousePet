<?php
/**
 * HousePet — Cadastrar Nova Denúncia de Maus-Tratos
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
        $errors[] = 'Token de segurança inválido. Tente recarregar a página.';
    } else {
        $tipo_ocorrencia    = sanitize($_POST['tipo_ocorrencia'] ?? '');
        $localizacao        = sanitize($_POST['localizacao'] ?? '');
        $data_ocorrencia    = sanitize($_POST['data_ocorrencia'] ?? '');
        $informacoes_animal = sanitize($_POST['informacoes_animal'] ?? '');
        $descricao          = sanitize($_POST['descricao'] ?? '');

        $old = compact('tipo_ocorrencia', 'localizacao', 'data_ocorrencia', 'informacoes_animal', 'descricao');

        if (empty($tipo_ocorrencia)) $errors[] = 'Informe o tipo de ocorrência.';
        if (empty($localizacao))     $errors[] = 'A localização do fato é obrigatória.';
        if (empty($descricao))       $errors[] = 'A descrição detalhada da situação é fundamental.';

        // Upload de imagem da denúncia
        $imagemNome = null;
        if (!empty($_FILES['imagem']['name']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $destDir = __DIR__ . '/../public/assets/images/uploads/denuncias/';
            $imagemNome = uploadImage($_FILES['imagem'], $destDir);
            if (!$imagemNome) {
                $errors[] = 'Erro ao enviar fotografia como evidência. Formatos aceitos: JPG, PNG, WEBP ou GIF até 5MB.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO denuncias
                (usuario_id, tipo_ocorrencia, localizacao, data_ocorrencia, informacoes_animal, descricao, imagem, status)
                VALUES (:uid, :tipo, :local, :data, :info, :desc, :imagem, 'pendente')
            ");
            $stmt->execute([
                ':uid'    => $_SESSION['user_id'],
                ':tipo'   => $tipo_ocorrencia,
                ':local'  => $localizacao,
                ':data'   => !empty($data_ocorrencia) ? $data_ocorrencia : null,
                ':info'   => $informacoes_animal,
                ':desc'   => $descricao,
                ':imagem' => $imagemNome,
            ]);

            setFlashMessage('success', 'Sua denúncia foi protocolada com sucesso e encaminhada para a equipe de moderação. Seus dados permanecem em sigilo.');
            redirect($baseUrl . '/denuncias/minhas_denuncias.php');
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Registrar Denúncia de Maus-Tratos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5" style="max-width:780px;">
    <!-- Aviso de sigilo e não substituição oficial -->
    <div class="alert alert-info border-info d-flex align-items-center mb-4">
        <i class="fa-solid fa-user-secret fs-3 text-info me-3"></i>
        <div>
            <strong>Privacidade Garantida:</strong> Seus dados de denunciante <strong>NÃO</strong> são exibidos publicamente na plataforma em hipótese alguma.
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius:14px; overflow:hidden;">
        <div class="card-header bg-danger text-white py-3">
            <h2 class="h4 mb-0"><i class="fa-solid fa-triangle-exclamation me-2"></i>Registrar Ocorrência de Maus-Tratos</h2>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small mb-4">
                Descreva com o máximo de clareza o que está acontecendo. As informações ajudam na catalogação de casos para apoio a resgates e denúncias formais.
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
                        <label class="form-label fw-semibold">Tipo de Ocorrência *</label>
                        <select name="tipo_ocorrencia" class="form-select" required>
                            <option value="">Selecione o tipo...</option>
                            <?php
                            $tipos = [
                                'Abandono em via pública ou imóvel',
                                'Agressão física ou espancamento',
                                'Privação de água e alimento',
                                'Animal preso em corrente curta ou sem abrigo',
                                'Condições sanitárias insalubres e confinamento',
                                'Falta de socorro veterinário a animal ferido',
                                'Tráfico, exploração ou comércio irregular',
                                'Outro tipo de negligência ou violência'
                            ];
                            foreach ($tipos as $t):
                                $sel = (isset($old['tipo_ocorrencia']) && $old['tipo_ocorrencia'] === $t) ? 'selected' : '';
                            ?>
                            <option value="<?= $t ?>" <?= $sel ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Data da Ocorrência / Início</label>
                        <input type="date" name="data_ocorrencia" class="form-control"
                               value="<?= htmlspecialchars($old['data_ocorrencia'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Local Exato da Situação *</label>
                        <input type="text" name="localizacao" class="form-control"
                               value="<?= htmlspecialchars($old['localizacao'] ?? '') ?>"
                               placeholder="Ex: Rua das Flores, 123 (ao lado da padaria), Bairro Centro, Porto Velho - RO" required>
                        <small class="text-muted">Informe pontos de referência para facilitar a localização do endereço.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Informações sobre o Animal (ou animais)</label>
                        <input type="text" name="informacoes_animal" class="form-control"
                               value="<?= htmlspecialchars($old['informacoes_animal'] ?? '') ?>"
                               placeholder="Ex: 2 cães sem raça definida, porte médio, cor preta e amarela; ou 1 gato filhote debilitado">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descrição Detalhada dos Fatos *</label>
                        <textarea name="descricao" rows="5" class="form-control"
                                  placeholder="Relate detalhadamente o que você presenciou, com que frequência acontece, estado físico aparente do animal, se há responsáveis identificáveis no local, etc." required><?= htmlspecialchars($old['descricao'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Anexar Fotografia / Evidência (quando disponível)</label>
                        <input type="file" name="imagem" id="inputFotoDenuncia" class="form-control" accept="image/*">
                        <small class="text-muted">Imagens comprovatórias dão mais peso e agilidade à análise (máx. 5MB).</small>
                        <div id="previewDenuncia" class="mt-2" style="display:none;">
                            <img id="imgDenuncia" src="" alt="Prévia" style="max-height:180px; border-radius:8px;">
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-4 small">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                    <strong>Lembre-se:</strong> Falsa comunicação de crime também acarreta responsabilidade legal. Registre somente fatos verídicos.
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <a href="<?= $baseUrl ?>/denuncias/index.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Voltar
                    </a>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="fa-solid fa-paper-plane me-1"></i> Protocolar Denúncia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('inputFotoDenuncia').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('imgDenuncia').src = evt.target.result;
            document.getElementById('previewDenuncia').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
