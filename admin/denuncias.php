<?php
/**
 * HousePet — Moderação de Denúncias de Maus-Tratos
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$baseUrl = '/housePet2';
$statusFiltro = isset($_GET['status']) ? trim($_GET['status']) : '';
$detalheId    = (int)($_GET['id'] ?? 0);

// Ação de Moderação (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'moderar_denuncia') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Token de segurança inválido.');
    } else {
        $denunciaId = (int)($_POST['denuncia_id'] ?? 0);
        $novoStatus = sanitize($_POST['novo_status'] ?? '');
        $observacao = sanitize($_POST['observacao'] ?? '');

        $statusValidos = ['pendente', 'em_analise', 'aprovada', 'rejeitada'];
        if (in_array($novoStatus, $statusValidos, true) && $denunciaId > 0) {
            // Atualiza status da denúncia
            $stmtUp = $pdo->prepare("UPDATE denuncias SET status = ? WHERE id = ?");
            $stmtUp->execute([$novoStatus, $denunciaId]);

            // Mapeia decisão para enum de moderação ('aprovado', 'rejeitado', 'em_analise')
            $decisaoMod = 'em_analise';
            if ($novoStatus === 'aprovada')  $decisaoMod = 'aprovado';
            if ($novoStatus === 'rejeitada') $decisaoMod = 'rejeitado';

            // Registra ação no log de moderação
            $stmtMod = $pdo->prepare("
                INSERT INTO moderacoes (moderador_id, tipo_conteudo, conteudo_id, decisao, observacao)
                VALUES (?, 'denuncia', ?, ?, ?)
            ");
            $stmtMod->execute([$_SESSION['user_id'], $denunciaId, $decisaoMod, $observacao]);

            setFlashMessage('success', 'Denúncia #' . $denunciaId . ' atualizada para: ' . getStatusLabel($novoStatus) . '. Moderação registrada.');
            redirect($baseUrl . '/admin/denuncias.php' . ($statusFiltro ? '?status=' . $statusFiltro : ''));
        }
    }
}

// Filtro SQL
$where  = ['1=1'];
$params = [];

if ($statusFiltro !== '') {
    $where[]          = 'd.status = :status';
    $params['status'] = $statusFiltro;
}

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT d.*, u.nome AS denunciante_nome, u.email AS denunciante_email
    FROM denuncias d
    LEFT JOIN usuarios u ON u.id = d.usuario_id
    WHERE $whereSQL
    ORDER BY FIELD(d.status, 'pendente', 'em_analise', 'aprovada', 'rejeitada'), d.data_cadastro DESC
");
$stmt->execute($params);
$denuncias = $stmt->fetchAll();

// Se clicou para abrir detalhes de uma específica
$denunciaDetalhe = null;
if ($detalheId > 0) {
    $stmtDet = $pdo->prepare("
        SELECT d.*, u.nome AS denunciante_nome, u.email AS denunciante_email
        FROM denuncias d
        LEFT JOIN usuarios u ON u.id = d.usuario_id
        WHERE d.id = ?
    ");
    $stmtDet->execute([$detalheId]);
    $denunciaDetalhe = $stmtDet->fetch();
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Moderação de Denúncias — Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h2 fw-bold text-dark mb-1">
                <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Moderação de Denúncias
            </h1>
            <p class="text-muted mb-0">Avalie e atualize o status dos relatos de maus-tratos recebidos.</p>
        </div>
        <div>
            <a href="<?= $baseUrl ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Painel Admin
            </a>
        </div>
    </div>

    <!-- Filtro rápido por status -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= $baseUrl ?>/admin/denuncias.php" class="btn btn-sm <?= empty($statusFiltro) ? 'btn-dark' : 'btn-outline-dark' ?>">Todas</a>
        <a href="<?= $baseUrl ?>/admin/denuncias.php?status=pendente" class="btn btn-sm <?= ($statusFiltro === 'pendente') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?>">Pendentes</a>
        <a href="<?= $baseUrl ?>/admin/denuncias.php?status=em_analise" class="btn btn-sm <?= ($statusFiltro === 'em_analise') ? 'btn-info text-dark' : 'btn-outline-info text-dark' ?>">Em Análise</a>
        <a href="<?= $baseUrl ?>/admin/denuncias.php?status=aprovada" class="btn btn-sm <?= ($statusFiltro === 'aprovada') ? 'btn-success' : 'btn-outline-success' ?>">Aprovadas</a>
        <a href="<?= $baseUrl ?>/admin/denuncias.php?status=rejeitada" class="btn btn-sm <?= ($statusFiltro === 'rejeitada') ? 'btn-danger' : 'btn-outline-danger' ?>">Rejeitadas</a>
    </div>

    <!-- Modal/Card de Detalhe em Destaque (se solicitado) -->
    <?php if ($denunciaDetalhe): ?>
        <div class="card border-danger shadow mb-5" style="border-radius:14px; overflow:hidden;">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Análise Detalhada &mdash; Protocolo #<?= str_pad($denunciaDetalhe['id'], 5, '0', STR_PAD_LEFT) ?>
                </h5>
                <a href="<?= $baseUrl ?>/admin/denuncias.php<?= $statusFiltro ? '?status=' . $statusFiltro : '' ?>" class="btn-close btn-close-white" aria-label="Fechar"></a>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-7">
                        <h6 class="fw-bold text-danger">Dados da Ocorrência:</h6>
                        <p class="mb-1"><strong>Tipo:</strong> <?= htmlspecialchars($denunciaDetalhe['tipo_ocorrencia']) ?></p>
                        <p class="mb-1"><strong>Localização:</strong> <?= htmlspecialchars($denunciaDetalhe['localizacao']) ?></p>
                        <p class="mb-1"><strong>Data informada:</strong> <?= $denunciaDetalhe['data_ocorrencia'] ? formatDate($denunciaDetalhe['data_ocorrencia']) : '—' ?></p>
                        <p class="mb-1"><strong>Animais relatados:</strong> <?= htmlspecialchars($denunciaDetalhe['informacoes_animal'] ?: 'Não especificado') ?></p>
                        <p class="mb-3"><strong>Status Atual:</strong> <span class="badge <?= getStatusClass($denunciaDetalhe['status']) ?>"><?= getStatusLabel($denunciaDetalhe['status']) ?></span></p>

                        <h6 class="fw-bold text-dark mt-3">Relato Detalhado:</h6>
                        <div class="p-3 bg-light rounded small" style="line-height:1.7;">
                            <?= nl2br(htmlspecialchars($denunciaDetalhe['descricao'])) ?>
                        </div>

                        <div class="alert alert-secondary mt-3 small">
                            <i class="fa-solid fa-user-secret me-1"></i>
                            <strong>Denunciante (Acesso Restrito):</strong> <?= htmlspecialchars($denunciaDetalhe['denunciante_nome'] ?? 'Anônimo') ?> (<?= htmlspecialchars($denunciaDetalhe['denunciante_email'] ?? '—') ?>)
                        </div>
                    </div>

                    <div class="col-md-5">
                        <?php if ($denunciaDetalhe['imagem']): ?>
                            <h6 class="fw-bold text-dark">Evidência Fotográfica:</h6>
                            <img src="<?= $baseUrl ?>/public/assets/images/uploads/denuncias/<?= htmlspecialchars($denunciaDetalhe['imagem']) ?>"
                                 alt="Foto" class="img-fluid rounded border mb-3" style="max-height:220px; width:100%; object-fit:cover;">
                        <?php endif; ?>

                        <!-- Formulário de Moderação -->
                        <div class="card bg-light border-0 p-3">
                            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-gavel me-1 text-danger"></i> Tomar Ação de Moderação</h6>
                            <form method="POST">
                                <input type="hidden" name="acao" value="moderar_denuncia">
                                <input type="hidden" name="denuncia_id" value="<?= $denunciaDetalhe['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Alterar Status para:</label>
                                    <select name="novo_status" class="form-select form-select-sm" required>
                                        <option value="aprovada" <?= ($denunciaDetalhe['status'] === 'aprovada') ? 'selected' : '' ?>>Aprovar Ocorrência (Status: Aprovada)</option>
                                        <option value="em_analise" <?= ($denunciaDetalhe['status'] === 'em_analise') ? 'selected' : '' ?>>Colocar Em Análise</option>
                                        <option value="rejeitada" <?= ($denunciaDetalhe['status'] === 'rejeitada') ? 'selected' : '' ?>>Rejeitar Denúncia</option>
                                        <option value="pendente" <?= ($denunciaDetalhe['status'] === 'pendente') ? 'selected' : '' ?>>Manter Pendente</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Observação / Justificativa Interna:</label>
                                    <textarea name="observacao" rows="3" class="form-control form-control-sm"
                                              placeholder="Ex: Encaminhado para verificação com protetores locais; ou Foto inconclusiva..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-sm btn-danger w-100">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Salvar Decisão
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabela Geral de Denúncias -->
    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Ocorrência</th>
                        <th>Localidade</th>
                        <th>Denunciante</th>
                        <th>Data Registro</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($denuncias)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Nenhuma denúncia encontrada para esta visualização.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($denuncias as $d): ?>
                            <tr class="<?= ($detalheId === (int)$d['id']) ? 'table-warning' : '' ?>">
                                <td class="fw-bold">#<?= $d['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($d['tipo_ocorrencia']) ?></strong>
                                    <?php if ($d['imagem']): ?>
                                        <i class="fa-solid fa-camera text-primary ms-1" title="Com foto"></i>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= htmlspecialchars($d['localizacao']) ?></small></td>
                                <td><small class="text-secondary"><?= htmlspecialchars($d['denunciante_nome'] ?? 'Anônimo') ?></small></td>
                                <td><small class="text-muted"><?= formatDate($d['data_cadastro']) ?></small></td>
                                <td>
                                    <span class="badge <?= getStatusClass($d['status']) ?>">
                                        <?= getStatusLabel($d['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= $baseUrl ?>/admin/denuncias.php?id=<?= $d['id'] ?><?= $statusFiltro ? '&status=' . $statusFiltro : '' ?>"
                                       class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-gavel me-1"></i> Analisar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

