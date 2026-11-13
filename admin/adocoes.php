<?php
/**
 * HousePet — Moderação e Gestão de Animais para Adoção
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$baseUrl = '/housePet2';
$statusFiltro = isset($_GET['status']) ? trim($_GET['status']) : '';
$detalheId    = (int)($_GET['id'] ?? 0);

// Ação de Moderação (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'moderar_animal') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Token de segurança inválido.');
    } else {
        $animalId   = (int)($_POST['animal_id'] ?? 0);
        $novoStatus = sanitize($_POST['novo_status'] ?? '');
        $observacao = sanitize($_POST['observacao'] ?? '');

        $statusValidos = ['pendente', 'disponivel', 'em_adocao', 'adotado', 'rejeitado'];
        if (in_array($novoStatus, $statusValidos, true) && $animalId > 0) {
            $stmtUp = $pdo->prepare("UPDATE animais SET status = ? WHERE id = ?");
            $stmtUp->execute([$novoStatus, $animalId]);

            $decisaoMod = ($novoStatus === 'rejeitado') ? 'rejeitado' : 'aprovado';
            $stmtMod = $pdo->prepare("
                INSERT INTO moderacoes (moderador_id, tipo_conteudo, conteudo_id, decisao, observacao)
                VALUES (?, 'animal', ?, ?, ?)
            ");
            $stmtMod->execute([$_SESSION['user_id'], $animalId, $decisaoMod, $observacao]);

            setFlashMessage('success', 'Publicação do animal #' . $animalId . ' atualizada para: ' . getStatusLabel($novoStatus) . '. Moderação salva.');
            redirect($baseUrl . '/admin/adocoes.php' . ($statusFiltro ? '?status=' . $statusFiltro : ''));
        }
    }
}

// Filtro SQL
$where  = ['1=1'];
$params = [];

if ($statusFiltro !== '') {
    $where[]          = 'a.status = :status';
    $params['status'] = $statusFiltro;
}

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT a.*, u.nome AS tutor_nome, u.email AS tutor_email
    FROM animais a
    LEFT JOIN usuarios u ON u.id = a.usuario_id
    WHERE $whereSQL
    ORDER BY FIELD(a.status, 'pendente', 'disponivel', 'em_adocao', 'adotado', 'rejeitado'), a.data_cadastro DESC
");
$stmt->execute($params);
$animais = $stmt->fetchAll();

// Detalhe selecionado
$animalDetalhe = null;
if ($detalheId > 0) {
    $stmtDet = $pdo->prepare("
        SELECT a.*, u.nome AS tutor_nome, u.email AS tutor_email
        FROM animais a
        LEFT JOIN usuarios u ON u.id = a.usuario_id
        WHERE a.id = ?
    ");
    $stmtDet->execute([$detalheId]);
    $animalDetalhe = $stmtDet->fetch();
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Moderação de Adoções — Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h2 fw-bold text-dark mb-1">
                <i class="fa-solid fa-paw text-success me-2"></i>Moderação de Animais para Adoção
            </h1>
            <p class="text-muted mb-0">Revise anúncios cadastrados antes de liberá-los para adoção pública.</p>
        </div>
        <div>
            <a href="<?= $baseUrl ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Painel Admin
            </a>
        </div>
    </div>

    <!-- Filtros de Status -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= $baseUrl ?>/admin/adocoes.php" class="btn btn-sm <?= empty($statusFiltro) ? 'btn-dark' : 'btn-outline-dark' ?>">Todos</a>
        <a href="<?= $baseUrl ?>/admin/adocoes.php?status=pendente" class="btn btn-sm <?= ($statusFiltro === 'pendente') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?>">Pendentes</a>
        <a href="<?= $baseUrl ?>/admin/adocoes.php?status=disponivel" class="btn btn-sm <?= ($statusFiltro === 'disponivel') ? 'btn-success' : 'btn-outline-success' ?>">Disponíveis</a>
        <a href="<?= $baseUrl ?>/admin/adocoes.php?status=em_adocao" class="btn btn-sm <?= ($statusFiltro === 'em_adocao') ? 'btn-info text-dark' : 'btn-outline-info text-dark' ?>">Em Processo</a>
        <a href="<?= $baseUrl ?>/admin/adocoes.php?status=adotado" class="btn btn-sm <?= ($statusFiltro === 'adotado') ? 'btn-secondary' : 'btn-outline-secondary' ?>">Adotados</a>
        <a href="<?= $baseUrl ?>/admin/adocoes.php?status=rejeitado" class="btn btn-sm <?= ($statusFiltro === 'rejeitado') ? 'btn-danger' : 'btn-outline-danger' ?>">Rejeitados</a>
    </div>

    <!-- Análise de Item Selecionado -->
    <?php if ($animalDetalhe): ?>
        <div class="card border-success shadow mb-5" style="border-radius:14px; overflow:hidden;">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Moderar Animal: <?= htmlspecialchars($animalDetalhe['nome']) ?> (#<?= $animalDetalhe['id'] ?>)
                </h5>
                <a href="<?= $baseUrl ?>/admin/adocoes.php<?= $statusFiltro ? '?status=' . $statusFiltro : '' ?>" class="btn-close btn-close-white" aria-label="Fechar"></a>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-7">
                        <p class="mb-1"><strong>Espécie:</strong> <?= ucfirst(htmlspecialchars($animalDetalhe['especie'])) ?> | <strong>Raça:</strong> <?= htmlspecialchars($animalDetalhe['raca'] ?: 'SRD') ?></p>
                        <p class="mb-1"><strong>Porte:</strong> <?= ucfirst(htmlspecialchars($animalDetalhe['porte'])) ?> | <strong>Sexo:</strong> <?= ucfirst(htmlspecialchars($animalDetalhe['sexo'])) ?></p>
                        <p class="mb-1"><strong>Localização:</strong> <?= htmlspecialchars($animalDetalhe['localizacao']) ?></p>
                        <p class="mb-1"><strong>Contato do Tutor:</strong> <?= htmlspecialchars($animalDetalhe['contato']) ?></p>
                        <p class="mb-3"><strong>Cadastrado por:</strong> <?= htmlspecialchars($animalDetalhe['tutor_nome'] ?? 'Usuário') ?> (<?= htmlspecialchars($animalDetalhe['tutor_email'] ?? '—') ?>)</p>

                        <h6 class="fw-bold text-dark mt-3">Descrição da Publicação:</h6>
                        <div class="p-3 bg-light rounded small mb-3" style="line-height:1.7;">
                            <?= nl2br(htmlspecialchars($animalDetalhe['descricao'] ?: 'Sem descrição')) ?>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <?php if ($animalDetalhe['imagem']): ?>
                            <img src="<?= $baseUrl ?>/public/assets/images/uploads/animais/<?= htmlspecialchars($animalDetalhe['imagem']) ?>"
                                 alt="Foto" class="img-fluid rounded border mb-3" style="max-height:200px; width:100%; object-fit:cover;">
                        <?php endif; ?>

                        <div class="card bg-light border-0 p-3">
                            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-gavel me-1 text-success"></i> Decisão da Moderação</h6>
                            <form method="POST">
                                <input type="hidden" name="acao" value="moderar_animal">
                                <input type="hidden" name="animal_id" value="<?= $animalDetalhe['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Definir Status:</label>
                                    <select name="novo_status" class="form-select form-select-sm" required>
                                        <option value="disponivel" <?= ($animalDetalhe['status'] === 'disponivel') ? 'selected' : '' ?>>Aprovar (Disponível no catálogo público)</option>
                                        <option value="em_adocao" <?= ($animalDetalhe['status'] === 'em_adocao') ? 'selected' : '' ?>>Em Processo de Adoção</option>
                                        <option value="adotado" <?= ($animalDetalhe['status'] === 'adotado') ? 'selected' : '' ?>>Finalizado como Adotado</option>
                                        <option value="rejeitado" <?= ($animalDetalhe['status'] === 'rejeitado') ? 'selected' : '' ?>>Rejeitar Publicação</option>
                                        <option value="pendente" <?= ($animalDetalhe['status'] === 'pendente') ? 'selected' : '' ?>>Deixar Pendente</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Observação do Moderador:</label>
                                    <textarea name="observacao" rows="3" class="form-control form-control-sm"
                                              placeholder="Ex: Anúncio verificado e aprovado..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="fa-solid fa-check me-1"></i> Confirmar Decisão
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabela de Animais -->
    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Animal</th>
                        <th>Espécie</th>
                        <th>Porte</th>
                        <th>Tutor / Responsável</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($animais)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Nenhum animal cadastrado nesta categoria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($animais as $a): ?>
                            <tr class="<?= ($detalheId === (int)$a['id']) ? 'table-success' : '' ?>">
                                <td class="fw-bold">#<?= $a['id'] ?></td>
                                <td class="fw-semibold">
                                    <i class="fa-solid fa-paw text-success me-1"></i><?= htmlspecialchars($a['nome']) ?>
                                    <?php if ($a['imagem']): ?>
                                        <i class="fa-solid fa-camera text-muted ms-1" title="Com foto"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?= ucfirst(htmlspecialchars($a['especie'])) ?></td>
                                <td><?= ucfirst(htmlspecialchars($a['porte'])) ?></td>
                                <td><small class="text-secondary"><?= htmlspecialchars($a['tutor_nome'] ?? 'Usuário') ?></small></td>
                                <td><small class="text-muted"><?= formatDate($a['data_cadastro']) ?></small></td>
                                <td><span class="badge <?= getStatusClass($a['status']) ?>"><?= getStatusLabel($a['status']) ?></span></td>
                                <td class="text-end">
                                    <a href="<?= $baseUrl ?>/admin/adocoes.php?id=<?= $a['id'] ?><?= $statusFiltro ? '&status=' . $statusFiltro : '' ?>"
                                       class="btn btn-sm btn-outline-success">
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
