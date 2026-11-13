<?php
/**
 * HousePet — Detalhes do Animal para Adoção
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$baseUrl = '/housePet2';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('danger', 'Animal não encontrado.');
    redirect($baseUrl . '/adocao/index.php');
}

// Busca o animal
$stmt = $pdo->prepare("
    SELECT a.*, u.nome AS responsavel_nome, u.email AS responsavel_email
    FROM animais a
    LEFT JOIN usuarios u ON u.id = a.usuario_id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$animal = $stmt->fetch();

if (!$animal) {
    setFlashMessage('danger', 'Animal não encontrado ou registro inexistente.');
    redirect($baseUrl . '/adocao/index.php');
}

// Se não for admin nem dono, e o animal não estiver aprovado
$isDono = isLoggedIn() && ((int)$_SESSION['user_id'] === (int)$animal['usuario_id']);
$canView = $isDono || isModerador() || in_array($animal['status'], ['disponivel', 'em_adocao', 'adotado']);

if (!$canView) {
    setFlashMessage('warning', 'Este animal está em processo de moderação e ainda não está visível publicamente.');
    redirect($baseUrl . '/adocao/index.php');
}

$errors = [];
$successInteresse = false;

// Processar manifestação de interesse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'manifestar_interesse') {
    requireLogin();

    if ($isDono) {
        $errors[] = 'Você é o responsável cadastrado para este animal.';
    } elseif (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    } else {
        $mensagem = sanitize($_POST['mensagem'] ?? '');

        // Verifica se já enviou interesse
        $stmtCheck = $pdo->prepare("
            SELECT id FROM adocoes
            WHERE animal_id = ? AND usuario_interessado_id = ?
        ");
        $stmtCheck->execute([$id, $_SESSION['user_id']]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'Você já enviou uma manifestação de interesse para este animal!';
        } else {
            $stmtInsert = $pdo->prepare("
                INSERT INTO adocoes (animal_id, usuario_interessado_id, mensagem, status)
                VALUES (?, ?, ?, 'pendente')
            ");
            $stmtInsert->execute([$id, $_SESSION['user_id'], $mensagem]);

            setFlashMessage('success', 'Sua manifestação de interesse foi enviada ao responsável pelo animal!');
            redirect($baseUrl . '/adocao/detalhes.php?id=' . $id);
        }
    }
}

// Se o dono estiver vendo, buscar interessados recebidos
$interessados = [];
if ($isDono) {
    $stmtInt = $pdo->prepare("
        SELECT ad.*, u.nome, u.email
        FROM adocoes ad
        JOIN usuarios u ON u.id = ad.usuario_interessado_id
        WHERE ad.animal_id = ?
        ORDER BY ad.data_cadastro DESC
    ");
    $stmtInt->execute([$id]);
    $interessados = $stmtInt->fetchAll();
}

// Verifica se o usuário atual já manifestou interesse
$jaManifestou = false;
if (isLoggedIn() && !$isDono) {
    $stmtCheck = $pdo->prepare("
        SELECT status FROM adocoes
        WHERE animal_id = ? AND usuario_interessado_id = ?
    ");
    $stmtCheck->execute([$id, $_SESSION['user_id']]);
    $jaManifestou = $stmtCheck->fetch();
}

$csrfToken = generateCSRFToken();
$pageTitle = $animal['nome'] . ' — Adoção';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/">Início</a></li>
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/adocao/index.php">Adoção</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($animal['nome']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Coluna da Esquerda: Foto e Badges -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:14px;">
                <?php if ($animal['imagem']): ?>
                    <img src="<?= $baseUrl ?>/public/assets/images/uploads/animais/<?= htmlspecialchars($animal['imagem']) ?>"
                         alt="<?= htmlspecialchars($animal['nome']) ?>"
                         class="img-fluid w-100" style="max-height:420px; object-fit:cover;">
                <?php else: ?>
                    <div style="height:320px; background:#e8f5e9; display:flex; align-items:center; justify-content:center; font-size:5rem; color:#2d8c4e;">
                        🐾
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge <?= getStatusClass($animal['status']) ?> fs-6">
                            <?= getStatusLabel($animal['status']) ?>
                        </span>
                        <small class="text-muted">Cadastrado em <?= formatDate($animal['data_cadastro']) ?></small>
                    </div>

                    <?php if ($isDono): ?>
                        <div class="alert alert-info mt-3 py-2 small">
                            <i class="fa-solid fa-user-shield me-1"></i> Você é o tutor/responsável por este animal.
                            <div class="mt-2">
                                <a href="<?= $baseUrl ?>/adocao/editar.php?id=<?= $animal['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Editar Dados
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Coluna da Direita: Informações e Ação -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4" style="border-radius:14px;">
                <h1 class="h2 text-success fw-bold mb-3"><?= htmlspecialchars($animal['nome']) ?></h1>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Espécie</small>
                            <span class="fw-semibold text-capitalize"><i class="fa-solid fa-paw text-success me-1"></i><?= htmlspecialchars($animal['especie']) ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Raça</small>
                            <span class="fw-semibold"><?= htmlspecialchars($animal['raca'] ?: 'Não informada / SRD') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Porte</small>
                            <span class="fw-semibold text-capitalize"><?= htmlspecialchars($animal['porte']) ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Sexo</small>
                            <span class="fw-semibold text-capitalize"><?= htmlspecialchars($animal['sexo'] === 'macho' ? 'Macho' : ($animal['sexo'] === 'femea' ? 'Fêmea' : 'Não informado')) ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Idade aproximada</small>
                            <span class="fw-semibold"><?= htmlspecialchars($animal['idade'] ?: 'Não informada') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Localização</small>
                            <span class="fw-semibold"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= htmlspecialchars($animal['localizacao'] ?: 'Não informada') ?></span>
                        </div>
                    </div>
                </div>

                <h3 class="h5 fw-bold text-dark mb-2">Sobre o Animal</h3>
                <p class="text-secondary mb-4" style="line-height:1.7;">
                    <?= nl2br(htmlspecialchars($animal['descricao'] ?: 'Nenhuma descrição detalhada informada.')) ?>
                </p>

                <h3 class="h5 fw-bold text-dark mb-2">Contato do Responsável</h3>
                <div class="p-3 border rounded bg-white mb-4">
                    <p class="mb-1"><strong>Tutor:</strong> <?= htmlspecialchars($animal['responsavel_nome'] ?? 'Responsável') ?></p>
                    <p class="mb-0"><strong>Contato direto:</strong> <span class="text-success fw-bold"><?= htmlspecialchars($animal['contato']) ?></span></p>
                </div>

                <!-- Seção de Manifestação de Interesse -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($animal['status'] === 'adotado'): ?>
                    <div class="alert alert-secondary text-center">
                        <i class="fa-solid fa-heart text-danger me-1"></i> Este animal já foi adotado com sucesso! 🎉
                    </div>
                <?php elseif ($isDono): ?>
                    <!-- O dono vê os interessados aqui -->
                    <div class="border-top pt-4 mt-2">
                        <h4 class="h5 fw-bold text-success mb-3"><i class="fa-solid fa-users me-2"></i>Interessados na Adoção (<?= count($interessados) ?>)</h4>
                        <?php if (empty($interessados)): ?>
                            <p class="text-muted">Nenhum interessado registrado até o momento.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($interessados as $it): ?>
                                    <div class="list-group-item list-group-item-action flex-column align-items-start">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($it['nome']) ?> (<?= htmlspecialchars($it['email']) ?>)</h6>
                                            <small><?= formatDate($it['data_cadastro']) ?></small>
                                        </div>
                                        <p class="mb-1 text-secondary"><?= nl2br(htmlspecialchars($it['mensagem'] ?: 'Sem mensagem adicional.')) ?></p>
                                        <small class="badge <?= getStatusClass($it['status']) ?>"><?= getStatusLabel($it['status']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($jaManifestou): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check me-2"></i> Você já manifestou interesse na adoção de <strong><?= htmlspecialchars($animal['nome']) ?></strong>!
                        <p class="mb-0 mt-1 small">Status da sua solicitação: <span class="badge <?= getStatusClass($jaManifestou['status']) ?>"><?= getStatusLabel($jaManifestou['status']) ?></span>. O responsável entrará em contato.</p>
                    </div>
                <?php elseif (isLoggedIn()): ?>
                    <div class="card bg-light border-0 p-3 mt-2">
                        <h4 class="h5 fw-bold text-success mb-2"><i class="fa-solid fa-hand-holding-heart me-1"></i> Tenho interesse em adotar</h4>
                        <p class="small text-muted mb-3">Envie uma mensagem para o tutor demonstrando seu interesse e contando um pouco sobre as condições da sua residência.</p>
                        <form method="POST">
                            <input type="hidden" name="acao" value="manifestar_interesse">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <div class="mb-3">
                                <textarea name="mensagem" class="form-control" rows="3" placeholder="Olá! Tenho muito carinho por animais, tenho espaço adequado e gostaria de conhecer mais sobre..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fa-solid fa-paper-plane me-1"></i> Enviar Mensagem de Interesse
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Para manifestar interesse na adoção pela plataforma, <a href="<?= $baseUrl ?>/auth/login.php" class="fw-bold">faça login</a> ou <a href="<?= $baseUrl ?>/auth/cadastro.php" class="fw-bold">cadastre-se</a>.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

