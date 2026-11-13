<?php
/**
 * HousePet — Minhas Denúncias Registradas
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$baseUrl = '/housePet2';
$userId  = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT * FROM denuncias
    WHERE usuario_id = ?
    ORDER BY data_cadastro DESC
");
$stmt->execute([$userId]);
$denuncias = $stmt->fetchAll();

$pageTitle = 'Minhas Denúncias Registradas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h2 fw-bold text-danger mb-1">
                <i class="fa-solid fa-list-check me-2"></i>Minhas Denúncias
            </h1>
            <p class="text-muted mb-0">Acompanhe o andamento e o status das ocorrências que você registrou no sistema.</p>
        </div>
        <div>
            <a href="<?= $baseUrl ?>/denuncias/cadastrar.php" class="btn btn-danger">
                <i class="fa-solid fa-plus me-1"></i> Nova Denúncia
            </a>
        </div>
    </div>

    <!-- Aviso de sigilo e status -->
    <div class="alert alert-light border shadow-sm mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <i class="fa-solid fa-shield-halved text-success me-2"></i>
                Seus registros são anônimos perante a área pública. Apenas moderadores autorizados têm acesso às informações para triagem.
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <span class="badge bg-warning text-dark me-1">Pendente</span>
                <span class="badge bg-info text-dark me-1">Em Análise</span>
                <span class="badge bg-success me-1">Aprovada</span>
                <span class="badge bg-danger">Rejeitada</span>
            </div>
        </div>
    </div>

    <?php if (empty($denuncias)): ?>
        <div class="text-center py-5 bg-white rounded-3 shadow-sm">
            <span style="font-size:3.5rem;">📋</span>
            <h3 class="h5 mt-3 text-secondary">Você ainda não registrou nenhuma denúncia.</h3>
            <p class="text-muted small">Presenciou algum caso de abandono ou crueldade? Ajude registrando uma ocorrência.</p>
            <a href="<?= $baseUrl ?>/denuncias/cadastrar.php" class="btn btn-sm btn-outline-danger mt-2">
                Registrar Primeira Denúncia
            </a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Protocolo</th>
                            <th>Tipo de Ocorrência</th>
                            <th>Localidade</th>
                            <th>Data do Fato</th>
                            <th>Data de Envio</th>
                            <th>Status Atual</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($denuncias as $d): ?>
                            <tr>
                                <td class="fw-bold">#<?= str_pad($d['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($d['tipo_ocorrencia']) ?></span>
                                    <?php if ($d['imagem']): ?>
                                        <i class="fa-solid fa-paperclip text-muted ms-1" title="Contém foto"></i>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($d['localizacao']) ?></small></td>
                                <td><?= $d['data_ocorrencia'] ? formatDate($d['data_ocorrencia']) : '—' ?></td>
                                <td><small class="text-muted"><?= formatDate($d['data_cadastro']) ?></small></td>
                                <td>
                                    <span class="badge <?= getStatusClass($d['status']) ?> px-2 py-1">
                                        <?= getStatusLabel($d['status']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= $baseUrl ?>/denuncias/view.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-solid fa-eye me-1"></i> Detalhes
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
