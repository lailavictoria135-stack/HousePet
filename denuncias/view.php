<?php
/**
 * HousePet — Visualizar Ocorrência de Denúncia (Acesso Seguro)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$baseUrl = '/housePet2';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('danger', 'Ocorrência não encontrada.');
    redirect($baseUrl . '/denuncias/minhas_denuncias.php');
}

$stmt = $pdo->prepare("
    SELECT d.*, u.nome AS denunciante_nome, u.email AS denunciante_email
    FROM denuncias d
    LEFT JOIN usuarios u ON u.id = d.usuario_id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$denuncia = $stmt->fetch();

if (!$denuncia) {
    setFlashMessage('danger', 'Denúncia não encontrada.');
    redirect($baseUrl . '/denuncias/minhas_denuncias.php');
}

// Acesso permitido somente ao denunciante ou moderadores/admins
$isDono = ((int)$_SESSION['user_id'] === (int)$denuncia['usuario_id']);
if (!$isDono && !isModerador()) {
    setFlashMessage('danger', 'Acesso negado aos detalhes confidenciais desta denúncia.');
    redirect($baseUrl . '/denuncias/index.php');
}

// Busca histórico de moderação desta denúncia
$stmtMod = $pdo->prepare("
    SELECT m.*, u.nome AS moderador_nome
    FROM moderacoes m
    JOIN usuarios u ON u.id = m.moderador_id
    WHERE m.tipo_conteudo = 'denuncia' AND m.conteudo_id = ?
    ORDER BY m.data_moderacao DESC
");
$stmtMod->execute([$id]);
$historico = $stmtMod->fetchAll();

$pageTitle = 'Ocorrência #' . str_pad($denuncia['id'], 5, '0', STR_PAD_LEFT);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5" style="max-width:860px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-danger mb-1">
                Protocolo #<?= str_pad($denuncia['id'], 5, '0', STR_PAD_LEFT) ?>
            </h1>
            <p class="text-muted mb-0">Enviada em <?= formatDate($denuncia['data_cadastro']) ?></p>
        </div>
        <div>
            <span class="badge <?= getStatusClass($denuncia['status']) ?> fs-6 px-3 py-2">
                Status: <?= getStatusLabel($denuncia['status']) ?>
            </span>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px; overflow:hidden;">
        <div class="card-header bg-light py-3">
            <h2 class="h5 mb-0 fw-bold text-dark"><i class="fa-solid fa-file-lines me-2 text-danger"></i>Relatório da Ocorrência</h2>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <small class="text-muted d-block">Tipo de Ocorrência</small>
                    <span class="fw-semibold text-danger"><?= htmlspecialchars($denuncia['tipo_ocorrencia']) ?></span>
                </div>
                <div class="col-sm-6">
                    <small class="text-muted d-block">Data Informada do Fato</small>
                    <span class="fw-semibold"><?= $denuncia['data_ocorrencia'] ? formatDate($denuncia['data_ocorrencia']) : 'Não especificada' ?></span>
                </div>
                <div class="col-12">
                    <small class="text-muted d-block">Localização Relatada</small>
                    <span class="fw-semibold"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= htmlspecialchars($denuncia['localizacao']) ?></span>
                </div>
                <?php if ($denuncia['informacoes_animal']): ?>
                <div class="col-12">
                    <small class="text-muted d-block">Animais Envolvidos</small>
                    <span class="fw-semibold"><?= htmlspecialchars($denuncia['informacoes_animal']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <h3 class="h6 fw-bold text-dark">Descrição dos Fatos:</h3>
                <div class="p-3 bg-light rounded" style="line-height:1.7;">
                    <?= nl2br(htmlspecialchars($denuncia['descricao'])) ?>
                </div>
            </div>

            <?php if ($denuncia['imagem']): ?>
            <div class="mb-4">
                <h3 class="h6 fw-bold text-dark mb-2">Evidência Fotográfica Anexada:</h3>
                <img src="<?= $baseUrl ?>/public/assets/images/uploads/denuncias/<?= htmlspecialchars($denuncia['imagem']) ?>"
                     alt="Evidência da denúncia"
                     class="img-fluid rounded border shadow-sm" style="max-height:360px;">
            </div>
            <?php endif; ?>

            <?php if (isModerador()): ?>
            <div class="alert alert-secondary mt-3">
                <h6 class="fw-bold mb-1"><i class="fa-solid fa-lock me-1"></i> Dados do Denunciante (Visível apenas para moderação):</h6>
                <p class="mb-0 small"><strong>Nome:</strong> <?= htmlspecialchars($denuncia['denunciante_nome'] ?? 'Anônimo') ?> | <strong>E-mail:</strong> <?= htmlspecialchars($denuncia['denunciante_email'] ?? '—') ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Histórico de Moderação -->
    <?php if (!empty($historico)): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
        <div class="card-header bg-light py-3">
            <h3 class="h6 mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i>Histórico de Acompanhamento da Moderação</h3>
        </div>
        <div class="card-body p-4">
            <div class="timeline">
                <?php foreach ($historico as $h): ?>
                <div class="border-start border-3 border-success ps-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="text-success"><?= ucfirst(htmlspecialchars($h['decisao'])) ?></strong>
                        <small class="text-muted"><?= formatDate($h['data_moderacao']) ?></small>
                    </div>
                    <?php if ($h['observacao']): ?>
                        <p class="text-secondary small mb-0 mt-1"><?= nl2br(htmlspecialchars($h['observacao'])) ?></p>
                    <?php endif; ?>
                    <small class="text-muted">Avaliador: <?= htmlspecialchars($h['moderador_nome']) ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between">
        <a href="<?= $isDono ? $baseUrl . '/denuncias/minhas_denuncias.php' : $baseUrl . '/admin/denuncias.php' ?>" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Voltar
        </a>
        <?php if (isModerador()): ?>
            <a href="<?= $baseUrl ?>/admin/denuncias.php" class="btn btn-danger">
                <i class="fa-solid fa-shield-halved me-1"></i> Ir para Painel de Moderação
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
