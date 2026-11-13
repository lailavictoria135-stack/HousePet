<?php
/**
 * HousePet — Detalhes do Animal Desaparecido
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$baseUrl = '/housePet2';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('danger', 'Registro não encontrado.');
    redirect($baseUrl . '/desaparecidos/index.php');
}

$stmt = $pdo->prepare("
    SELECT d.*, u.nome AS tutor_nome, u.email AS tutor_email
    FROM desaparecidos d
    LEFT JOIN usuarios u ON u.id = d.usuario_id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$pet = $stmt->fetch();

if (!$pet) {
    setFlashMessage('danger', 'Animal desaparecido não encontrado no sistema.');
    redirect($baseUrl . '/desaparecidos/index.php');
}

$isDono = isLoggedIn() && ((int)$_SESSION['user_id'] === (int)$pet['usuario_id']);
$canView = $isDono || isModerador() || in_array($pet['status'], ['desaparecido', 'encontrado']);

if (!$canView) {
    setFlashMessage('warning', 'Esta publicação está aguardando análise da moderação.');
    redirect($baseUrl . '/desaparecidos/index.php');
}

// Ação de Marcar como Encontrado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'marcar_encontrado') {
    requireLogin();
    if ($isDono || isModerador()) {
        if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $stmtUp = $pdo->prepare("UPDATE desaparecidos SET status = 'encontrado' WHERE id = ?");
            $stmtUp->execute([$id]);
            setFlashMessage('success', 'Que notícia maravilhosa! Animal marcado como ENCONTRADO com sucesso! 🎉');
            redirect($baseUrl . '/desaparecidos/detalhes.php?id=' . $id);
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Desaparecido: ' . $pet['nome_animal'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/">Início</a></li>
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/desaparecidos/index.php">Desaparecidos</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($pet['nome_animal']) ?></li>
        </ol>
    </nav>

    <?php if ($pet['status'] === 'encontrado'): ?>
        <div class="alert alert-success d-flex align-items-center p-3 mb-4 rounded-3 shadow-sm">
            <span style="font-size:2rem;" class="me-3">🎉</span>
            <div>
                <h4 class="alert-heading mb-1 fw-bold">FINAL FELIZ! Este pet já foi encontrado!</h4>
                <p class="mb-0 small">O tutor comunicou que o animal foi localizado e está em segurança com sua família.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Foto e Status -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:14px;">
                <?php if ($pet['imagem']): ?>
                    <img src="<?= $baseUrl ?>/public/assets/images/uploads/desaparecidos/<?= htmlspecialchars($pet['imagem']) ?>"
                         alt="<?= htmlspecialchars($pet['nome_animal']) ?>"
                         class="img-fluid w-100" style="max-height:420px; object-fit:cover;">
                <?php else: ?>
                    <div style="height:320px; background:#ffebee; display:flex; align-items:center; justify-content:center; font-size:5rem; color:#e57373;">
                        🐾
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge <?= $pet['status'] === 'encontrado' ? 'bg-success' : 'bg-danger' ?> fs-6 px-3 py-2">
                            <?= $pet['status'] === 'encontrado' ? 'ENCONTRADO' : 'DESAPARECIDO' ?>
                        </span>
                        <small class="text-muted">Publicado em <?= formatDate($pet['data_cadastro']) ?></small>
                    </div>

                    <?php if ($isDono || isModerador()): ?>
                        <div class="card bg-light border-0 p-3 mt-2">
                            <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-gear me-1"></i> Gerenciamento pelo Tutor</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= $baseUrl ?>/desaparecidos/editar.php?id=<?= $pet['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Editar Dados
                                </a>
                                <?php if ($pet['status'] !== 'encontrado'): ?>
                                    <form method="POST" onsubmit="return confirm('Confirmar que o animal foi encontrado?');">
                                        <input type="hidden" name="acao" value="marcar_encontrado">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fa-solid fa-heart me-1"></i> Marcar como Encontrado
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Detalhes e Contato -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4" style="border-radius:14px;">
                <h1 class="h2 text-danger fw-bold mb-3"><?= htmlspecialchars($pet['nome_animal']) ?></h1>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Espécie</small>
                            <span class="fw-semibold text-capitalize"><i class="fa-solid fa-paw text-danger me-1"></i><?= htmlspecialchars($pet['especie']) ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Raça</small>
                            <span class="fw-semibold"><?= htmlspecialchars($pet['raca'] ?: 'Não informada / SRD') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Data do Desaparecimento</small>
                            <span class="fw-semibold"><?= $pet['data_desaparecimento'] ? formatDate($pet['data_desaparecimento']) : 'Não informada' ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Tutor / Responsável</small>
                            <span class="fw-semibold"><?= htmlspecialchars($pet['tutor_nome'] ?? 'Tutor') ?></span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h3 class="h5 fw-bold text-dark mb-2"><i class="fa-solid fa-location-dot text-danger me-2"></i>Local do Desaparecimento</h3>
                    <p class="text-secondary bg-light p-3 rounded"><?= htmlspecialchars($pet['local_desaparecimento']) ?></p>
                </div>

                <div class="mb-4">
                    <h3 class="h5 fw-bold text-dark mb-2">Características Físicas e Sinais Marcantes</h3>
                    <p class="text-secondary" style="line-height:1.7;">
                        <?= nl2br(htmlspecialchars($pet['caracteristicas'] ?: 'Nenhuma observação física adicional informada.')) ?>
                    </p>
                </div>

                <div class="p-4 border rounded-3 bg-danger-subtle border-danger-subtle">
                    <h4 class="h5 fw-bold text-danger mb-2"><i class="fa-solid fa-phone me-2"></i>Viu este animal? Entre em contato imediatamente!</h4>
                    <p class="mb-2 text-dark">Qualquer pista ou informação sobre o paradeiro é fundamental para ajudar a família:</p>
                    <div class="fs-4 fw-bold text-danger">
                        <i class="fa-solid fa-headset me-2"></i><?= htmlspecialchars($pet['contato']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
