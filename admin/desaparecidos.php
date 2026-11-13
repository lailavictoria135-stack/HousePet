<?php
/**
 * HousePet — Moderação e Gestão de Animais Desaparecidos
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$baseUrl = '/housePet2';
$statusFiltro = isset($_GET['status']) ? trim($_GET['status']) : '';
$detalheId    = (int)($_GET['id'] ?? 0);

// Ação de Moderação (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'moderar_desaparecido') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Token de segurança inválido.');
    } else {
        $petId      = (int)($_POST['pet_id'] ?? 0);
        $novoStatus = sanitize($_POST['novo_status'] ?? '');
        $observacao = sanitize($_POST['observacao'] ?? '');

        $statusValidos = ['pendente', 'desaparecido', 'encontrado', 'rejeitado'];
        if (in_array($novoStatus, $statusValidos, true) && $petId > 0) {
            $stmtUp = $pdo->prepare("UPDATE desaparecidos SET status = ? WHERE id = ?");
            $stmtUp->execute([$novoStatus, $petId]);

            $decisaoMod = ($novoStatus === 'rejeitado') ? 'rejeitado' : 'aprovado';
            $stmtMod = $pdo->prepare("
                INSERT INTO moderacoes (moderador_id, tipo_conteudo, conteudo_id, decisao, observacao)
                VALUES (?, 'desaparecido', ?, ?, ?)
            ");
            $stmtMod->execute([$_SESSION['user_id'], $petId, $decisaoMod, $observacao]);

            setFlashMessage('success', 'Registro do animal desaparecido #' . $petId . ' atualizado para: ' . getStatusLabel($novoStatus) . '. Moderação salva.');
            redirect($baseUrl . '/admin/desaparecidos.php' . ($statusFiltro ? '?status=' . $statusFiltro : ''));
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
    SELECT d.*, u.nome AS tutor_nome, u.email AS tutor_email
    FROM desaparecidos d
    LEFT JOIN usuarios u ON u.id = d.usuario_id
    WHERE $whereSQL
    ORDER BY FIELD(d.status, 'pendente', 'desaparecido', 'encontrado', 'rejeitado'), d.data_cadastro DESC
");
$stmt->execute($params);
$desaparecidos = $stmt->fetchAll();

// Detalhe selecionado
$petDetalhe = null;
if ($detalheId > 0) {
    $stmtDet = $pdo->prepare("
        SELECT d.*, u.nome AS tutor_nome, u.email AS tutor_email
        FROM desaparecidos d
        LEFT JOIN usuarios u ON u.id = d.usuario_id
        WHERE d.id = ?
    ");
    $stmtDet->execute([$detalheId]);
    $petDetalhe = $stmtDet->fetch();
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Moderação de Animais Desaparecidos — Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h2 fw-bold text-dark mb-1">
                <i class="fa-solid fa-bullhorn text-warning me-2"></i>Moderação de Animais Desaparecidos
            </h1>
            <p class="text-muted mb-0">Valide os cadastros de pets perdidos para exibição no mural público.</p>
        </div>
        <div>
            <a href="<?= $baseUrl ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Painel Admin
            </a>
        </div>
    </div>

    <!-- Filtros de Status -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= $baseUrl ?>/admin/desaparecidos.php" class="btn btn-sm <?= empty($statusFiltro) ? 'btn-dark' : 'btn-outline-dark' ?>">Todos</a>
        <a href="<?= $baseUrl ?>/admin/desaparecidos.php?status=pendente" class="btn btn-sm <?= ($statusFiltro === 'pendente') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?>">Pendentes</a>
        <a href="<?= $baseUrl ?>/admin/desaparecidos.php?status=desaparecido" class="btn btn-sm <?= ($statusFiltro === 'desaparecido') ? 'btn-danger' : 'btn-outline-danger' ?>">Desaparecidos</a>
        <a href="<?= $baseUrl ?>/admin/desaparecidos.php?status=encontrado" class="btn btn-sm <?= ($statusFiltro === 'encontrado') ? 'btn-success' : 'btn-outline-success' ?>">Encontrados 🎉</a>
        <a href="<?= $baseUrl ?>/admin/desaparecidos.php?status=rejeitado" class="btn btn-sm <?= ($statusFiltro === 'rejeitado') ? 'btn-secondary' : 'btn-outline-secondary' ?>">Rejeitados</a>
    </div>

    <!-- Detalhe Selecionado -->
    <?php if ($petDetalhe): ?>
        <div class="card border-warning shadow mb-5" style="border-radius:14px; overflow:hidden;">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Analisar Desaparecido: <?= htmlspecialchars($petDetalhe['nome_animal']) ?> (#<?= $petDetalhe['id'] ?>)
                </h5>
                <a href="<?= $baseUrl ?>/admin/desaparecidos.php<?= $statusFiltro ? '?status=' . $statusFiltro : '' ?>" class="btn-close" aria-label="Fechar"></a>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-7">
                        <p class="mb-1"><strong>Espécie:</strong> <?= ucfirst(htmlspecialchars($petDetalhe['especie'])) ?> | <strong>Raça:</strong> <?= htmlspecialchars($petDetalhe['raca'] ?: 'Não informada') ?></p>
                        <p class="mb-1"><strong>Data do Desaparecimento:</strong> <?= $petDetalhe['data_desaparecimento'] ? formatDate($petDetalhe['data_desaparecimento']) : 'Não informada' ?></p>
                        <p class="mb-1"><strong>Local:</strong> <?= htmlspecialchars($petDetalhe['local_desaparecimento']) ?></p>
                        <p class="mb-1"><strong>Contato Urgente:</strong> <?= htmlspecialchars($petDetalhe['contato']) ?></p>
                        <p class="mb-3"><strong>Cadastrado por:</strong> <?= htmlspecialchars($petDetalhe['tutor_nome'] ?? 'Tutor') ?> (<?= htmlspecialchars($petDetalhe['tutor_email'] ?? '—') ?>)</p>

                        <h6 class="fw-bold text-dark mt-3">Características Relatadas:</h6>
                        <div class="p-3 bg-light rounded small mb-3" style="line-height:1.7;">
                            <?= nl2br(htmlspecialchars($petDetalhe['caracteristicas'] ?: 'Sem características descritas')) ?>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <?php if ($petDetalhe['imagem']): ?>
                            <img src="<?= $baseUrl ?>/public/assets/images/uploads/desaparecidos/<?= htmlspecialchars($petDetalhe['imagem']) ?>"
                                 alt="Foto" class="img-fluid rounded border mb-3" style="max-height:200px; width:100%; object-fit:cover;">
                        <?php endif; ?>

                        <div class="card bg-light border-0 p-3">
                            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-gavel me-1 text-warning"></i> Tomar Ação de Moderação</h6>
                            <form method="POST">
                                <input type="hidden" name="acao" value="moderar_desaparecido">
                                <input type="hidden" name="pet_id" value="<?= $petDetalhe['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Definir Status:</label>
                                    <select name="novo_status" class="form-select form-select-sm" required>
                                        <option value="desaparecido" <?= ($petDetalhe['status'] === 'desaparecido') ? 'selected' : '' ?>>Aprovar (Publicar no Mural de Desaparecidos)</option>
                                        <option value="encontrado" <?= ($petDetalhe['status'] === 'encontrado') ? 'selected' : '' ?>>Marcar como Encontrado 🎉</option>
                                        <option value="rejeitado" <?= ($petDetalhe['status'] === 'rejeitado') ? 'selected' : '' ?>>Rejeitar Publicação</option>
                                        <option value="pendente" <?= ($petDetalhe['status'] === 'pendente') ? 'selected' : '' ?>>Manter Pendente</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Observação do Moderador:</label>
                                    <textarea name="observacao" rows="3" class="form-control form-control-sm"
                                              placeholder="Ex: Dados verificados com sucesso..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-sm btn-dark w-100">
                                    <i class="fa-solid fa-check me-1"></i> Confirmar Decisão
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabela Geral -->
    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Animal</th>
                        <th>Espécie</th>
                        <th>Local do Desaparecimento</th>
                        <th>Tutor</th>
                        <th>Data Registro</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($desaparecidos)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Nenhum registro encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($desaparecidos as $d): ?>
                            <tr class="<?= ($detalheId === (int)$d['id']) ? 'table-warning' : '' ?>">
                                <td class="fw-bold">#<?= $d['id'] ?></td>
                                <td class="fw-semibold">
                                    <i class="fa-solid fa-bullhorn text-warning me-1"></i><?= htmlspecialchars($d['nome_animal']) ?>
                                    <?php if ($d['imagem']): ?>
                                        <i class="fa-solid fa-camera text-muted ms-1" title="Com foto"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?= ucfirst(htmlspecialchars($d['especie'])) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($d['local_desaparecimento']) ?></small></td>
                                <td><small class="text-secondary"><?= htmlspecialchars($d['tutor_nome'] ?? 'Tutor') ?></small></td>
                                <td><small class="text-muted"><?= formatDate($d['data_cadastro']) ?></small></td>
                                <td><span class="badge <?= getStatusClass($d['status']) ?>"><?= getStatusLabel($d['status']) ?></span></td>
                                <td class="text-end">
                                    <a href="<?= $baseUrl ?>/admin/desaparecidos.php?id=<?= $d['id'] ?><?= $statusFiltro ? '&status=' . $statusFiltro : '' ?>"
                                       class="btn btn-sm btn-outline-dark">
                                        <i class="fa-solid fa-gavel me-1"></i> Moderar
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
