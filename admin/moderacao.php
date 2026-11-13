<?php
/**
 * HousePet — Histórico de Moderação
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$baseUrl = '/housePet2';

$tipoFiltro    = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$decisaoFiltro = isset($_GET['decisao']) ? trim($_GET['decisao']) : '';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($tipoFiltro !== '') {
    $where[]        = 'm.tipo_conteudo = :tipo';
    $params['tipo'] = $tipoFiltro;
}

if ($decisaoFiltro !== '') {
    $where[]           = 'm.decisao = :decisao';
    $params['decisao'] = $decisaoFiltro;
}

$whereSQL = implode(' AND ', $where);

// Contagem total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM moderacoes m WHERE $whereSQL");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Lista de moderações
$sql = "
    SELECT m.*, u.nome AS moderador_nome, u.email AS moderador_email
    FROM moderacoes m
    JOIN usuarios u ON u.id = m.moderador_id
    WHERE $whereSQL
    ORDER BY m.data_moderacao DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(":$k", $v);
}
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$historico = $stmt->fetchAll();

$pageTitle = 'Histórico de Moderação — Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h2 fw-bold text-dark mb-1">
                <i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Histórico e Auditoria de Moderação
            </h1>
            <p class="text-muted mb-0">Rastreabilidade completa de todas as decisões tomadas pela equipe de moderação.</p>
        </div>
        <div>
            <a href="<?= $baseUrl ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Painel Admin
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Tipo de Conteúdo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos os tipos</option>
                        <option value="denuncia" <?= ($tipoFiltro === 'denuncia') ? 'selected' : '' ?>>Denúncias de Maus-Tratos</option>
                        <option value="animal" <?= ($tipoFiltro === 'animal') ? 'selected' : '' ?>>Animais para Adoção</option>
                        <option value="desaparecido" <?= ($tipoFiltro === 'desaparecido') ? 'selected' : '' ?>>Animais Desaparecidos</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Decisão Tomada</label>
                    <select name="decisao" class="form-select form-select-sm">
                        <option value="">Todas as decisões</option>
                        <option value="aprovado" <?= ($decisaoFiltro === 'aprovado') ? 'selected' : '' ?>>Aprovado</option>
                        <option value="rejeitado" <?= ($decisaoFiltro === 'rejeitado') ? 'selected' : '' ?>>Rejeitado</option>
                        <option value="em_analise" <?= ($decisaoFiltro === 'em_analise') ? 'selected' : '' ?>>Em Análise</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2 align-self-end">
                    <button type="submit" class="btn btn-sm btn-success w-100">Filtrar</button>
                    <?php if ($tipoFiltro !== '' || $decisaoFiltro !== ''): ?>
                        <a href="<?= $baseUrl ?>/admin/moderacao.php" class="btn btn-sm btn-outline-secondary" title="Limpar">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Auditoria -->
    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Data/Hora</th>
                        <th>Moderador</th>
                        <th>Módulo / Conteúdo</th>
                        <th>ID Item</th>
                        <th>Decisão</th>
                        <th>Observações Registradas</th>
                        <th class="text-end">Acesso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Nenhum registro de moderação localizado com estes filtros.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historico as $h): ?>
                            <?php
                            $badgeDecisao = 'bg-secondary';
                            if ($h['decisao'] === 'aprovado')   $badgeDecisao = 'bg-success';
                            if ($h['decisao'] === 'rejeitado')  $badgeDecisao = 'bg-danger';
                            if ($h['decisao'] === 'em_analise') $badgeDecisao = 'bg-warning text-dark';

                            $linkItem = '#';
                            if ($h['tipo_conteudo'] === 'denuncia')     $linkItem = $baseUrl . '/admin/denuncias.php?id=' . $h['conteudo_id'];
                            if ($h['tipo_conteudo'] === 'animal')       $linkItem = $baseUrl . '/admin/adocoes.php?id=' . $h['conteudo_id'];
                            if ($h['tipo_conteudo'] === 'desaparecido') $linkItem = $baseUrl . '/admin/desaparecidos.php?id=' . $h['conteudo_id'];
                            ?>
                            <tr>
                                <td class="fw-bold">#<?= $h['id'] ?></td>
                                <td><small class="text-muted"><?= formatDate($h['data_moderacao']) ?></small></td>
                                <td class="fw-semibold">
                                    <i class="fa-solid fa-user-shield text-success me-1"></i>
                                    <?= htmlspecialchars($h['moderador_nome']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= ucfirst(htmlspecialchars($h['tipo_conteudo'])) ?>
                                    </span>
                                </td>
                                <td><strong>#<?= (int)$h['conteudo_id'] ?></strong></td>
                                <td>
                                    <span class="badge <?= $badgeDecisao ?>">
                                        <?= ucfirst(htmlspecialchars($h['decisao'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-secondary">
                                        <?= htmlspecialchars($h['observacao'] ?: 'Nenhuma observação informada') ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <a href="<?= $linkItem ?>" class="btn btn-sm btn-outline-secondary" title="Ver item">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white py-3">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">&raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

