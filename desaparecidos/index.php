<?php
/**
 * HousePet — Animais Desaparecidos (Mural Público)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$baseUrl = '/housePet2';

$especie     = isset($_GET['especie']) ? trim($_GET['especie']) : '';
$localizacao = isset($_GET['localizacao']) ? trim($_GET['localizacao']) : '';
$statusFiltro= isset($_GET['status']) ? trim($_GET['status']) : 'desaparecido';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($statusFiltro === 'todos') {
    $where[] = "d.status IN ('desaparecido', 'encontrado')";
} elseif ($statusFiltro === 'encontrado') {
    $where[] = "d.status = 'encontrado'";
} else {
    $where[] = "d.status = 'desaparecido'";
}

if ($especie !== '') {
    $where[]           = 'd.especie = :especie';
    $params['especie'] = $especie;
}

if ($localizacao !== '') {
    $where[]               = 'd.local_desaparecimento LIKE :local';
    $params['local']       = '%' . $localizacao . '%';
}

$whereSQL = implode(' AND ', $where);

// Contagem total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM desaparecidos d WHERE $whereSQL");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Lista de desaparecidos
$sql = "
    SELECT d.*, u.nome AS tutor_nome
    FROM desaparecidos d
    LEFT JOIN usuarios u ON u.id = d.usuario_id
    WHERE $whereSQL
    ORDER BY d.data_cadastro DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(":$k", $v);
}
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$desaparecidos = $stmt->fetchAll();

$pageTitle = 'Animais Desaparecidos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h2 mb-1 text-danger fw-bold">
                <i class="fa-solid fa-bullhorn me-2"></i>Animais Desaparecidos
            </h1>
            <p class="text-muted mb-0">Ajude um pet perdido a reencontrar sua família com amor e segurança.</p>
        </div>
        <div>
            <?php if (isLoggedIn()): ?>
                <a href="<?= $baseUrl ?>/desaparecidos/cadastrar.php" class="btn btn-danger">
                    <i class="fa-solid fa-plus me-1"></i> Divulgar Pet Desaparecido
                </a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/auth/login.php" class="btn btn-outline-danger">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Entrar para Divulgar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius:12px;">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Espécie</label>
                    <select name="especie" class="form-select form-select-sm">
                        <option value="">Todas as espécies</option>
                        <?php
                        $especies = ['cachorro' => 'Cachorros', 'gato' => 'Gatos', 'ave' => 'Aves', 'outro' => 'Outros'];
                        foreach ($especies as $k => $lbl):
                            $sel = ($especie === $k) ? 'selected' : '';
                        ?>
                        <option value="<?= $k ?>" <?= $sel ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Local do Desaparecimento</label>
                    <input type="text" name="localizacao" class="form-control form-control-sm"
                           placeholder="Bairro, rua, cidade..."
                           value="<?= htmlspecialchars($localizacao) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Situação</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="desaparecido" <?= ($statusFiltro === 'desaparecido') ? 'selected' : '' ?>>Ainda Desaparecidos</option>
                        <option value="encontrado" <?= ($statusFiltro === 'encontrado') ? 'selected' : '' ?>>Já Encontrados 🎉</option>
                        <option value="todos" <?= ($statusFiltro === 'todos') ? 'selected' : '' ?>>Todos os Registros</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-danger w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Filtrar
                    </button>
                    <a href="<?= $baseUrl ?>/desaparecidos/index.php" class="btn btn-sm btn-outline-secondary" title="Limpar">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Pets -->
    <?php if (empty($desaparecidos)): ?>
        <div class="text-center py-5 bg-white rounded shadow-sm">
            <span style="font-size:3.5rem;">🐾</span>
            <h3 class="h5 mt-3 text-secondary">Nenhum animal desaparecido encontrado com esses filtros.</h3>
            <p class="text-muted small">Tente pesquisar com termos mais amplos ou remover filtros.</p>
        </div>
    <?php else: ?>
        <p class="text-muted small mb-3">Exibindo <strong><?= count($desaparecidos) ?></strong> de <strong><?= $total ?></strong> registro(s)</p>
        <div class="row g-4">
            <?php foreach ($desaparecidos as $pet): ?>
                <?php
                $isEncontrado = ($pet['status'] === 'encontrado');
                $cardBadgeClass = $isEncontrado ? 'bg-success' : 'bg-danger';
                $cardBadgeText  = $isEncontrado ? 'ENCONTRADO' : 'DESAPARECIDO';
                ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 animal-card" style="border-radius:12px; overflow:hidden;">
                        <div class="position-relative">
                            <?php if (!empty($pet['imagem'])): ?>
                                <img src="<?= $baseUrl ?>/public/assets/images/uploads/desaparecidos/<?= htmlspecialchars($pet['imagem']) ?>"
                                     alt="<?= htmlspecialchars($pet['nome_animal']) ?>"
                                     class="card-img-top"
                                     style="height:220px; object-fit:cover;">
                            <?php else: ?>
                                <div style="height:220px; background:#ffebee; display:flex; align-items:center; justify-content:center; font-size:4rem; color:#e57373;">
                                    🐾
                                </div>
                            <?php endif; ?>
                            <span class="badge <?= $cardBadgeClass ?> position-absolute top-0 end-0 m-2 px-2 py-1">
                                <?= $cardBadgeText ?>
                            </span>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold text-dark mb-1">
                                <?= htmlspecialchars($pet['nome_animal']) ?>
                            </h5>
                            <span class="badge bg-light text-dark border align-self-start mb-2">
                                <?= ucfirst(htmlspecialchars($pet['especie'])) ?> <?= $pet['raca'] ? '• ' . htmlspecialchars($pet['raca']) : '' ?>
                            </span>

                            <p class="small text-muted mb-1">
                                <i class="fa-solid fa-location-dot text-danger me-1"></i>
                                <?= htmlspecialchars($pet['local_desaparecimento']) ?>
                            </p>
                            <?php if ($pet['data_desaparecimento']): ?>
                                <p class="small text-muted mb-2">
                                    <i class="fa-solid fa-calendar-day text-secondary me-1"></i>
                                    Visto em: <?= formatDate($pet['data_desaparecimento']) ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($pet['caracteristicas']): ?>
                                <p class="card-text text-secondary small flex-grow-1" style="line-height:1.4;">
                                    <?= htmlspecialchars(mb_substr($pet['caracteristicas'], 0, 90)) ?>...
                                </p>
                            <?php endif; ?>

                            <a href="<?= $baseUrl ?>/desaparecidos/detalhes.php?id=<?= $pet['id'] ?>"
                               class="btn btn-outline-danger btn-sm mt-3 w-100">
                                <i class="fa-solid fa-eye me-1"></i> Ver Informações e Contato
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Anterior</a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Próxima &raquo;</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
