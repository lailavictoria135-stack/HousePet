<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

// Filtros via GET
$especie    = isset($_GET['especie'])    ? trim($_GET['especie'])    : '';
$porte      = isset($_GET['porte'])      ? trim($_GET['porte'])      : '';
$localizacao = isset($_GET['localizacao']) ? trim($_GET['localizacao']) : '';

$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 9;
$offset   = ($page - 1) * $perPage;

// Query base
$where  = ["a.status IN ('disponivel','em_adocao')"];
$params = [];

if ($especie !== '') {
    $where[]          = 'a.especie = :especie';
    $params['especie'] = $especie;
}
if ($porte !== '') {
    $where[]       = 'a.porte = :porte';
    $params['porte'] = $porte;
}
if ($localizacao !== '') {
    $where[]             = 'a.localizacao LIKE :localizacao';
    $params['localizacao'] = '%' . $localizacao . '%';
}

$whereSQL = implode(' AND ', $where);

// Total para paginação
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM animais a WHERE $whereSQL");
$stmtCount->execute($params);
$total     = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Busca animais
$sql  = "SELECT a.*, u.nome AS dono_nome FROM animais a
         LEFT JOIN usuarios u ON u.id = a.usuario_id
         WHERE $whereSQL
         ORDER BY a.data_cadastro DESC
         LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(":$k", $v);
}
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$animais = $stmt->fetchAll();

$pageTitle = 'Adoção - HousePet';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex align-items-center mb-4">
        <span class="display-6 me-3">🐾</span>
        <div>
            <h1 class="h2 mb-0 text-success fw-bold">Animais para Adoção</h1>
            <p class="text-muted mb-0">Encontre seu novo companheiro</p>
        </div>
        <?php if (isLoggedIn()): ?>
        <a href="<?= $baseUrl ?>/adocao/cadastrar.php" class="btn btn-success ms-auto">
            <i class="fas fa-plus me-1"></i> Cadastrar Animal
        </a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Espécie</label>
                    <select name="especie" class="form-select">
                        <option value="">Todas</option>
                        <?php
                        $especies = ['cachorro','gato','ave','coelho','outro'];
                        foreach ($especies as $e):
                            $sel = ($especie === $e) ? 'selected' : '';
                        ?>
                        <option value="<?= htmlspecialchars($e) ?>" <?= $sel ?>><?= ucfirst(htmlspecialchars($e)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Porte</label>
                    <select name="porte" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $portes = ['pequeno','medio','grande','gigante'];
                        foreach ($portes as $p):
                            $sel = ($porte === $p) ? 'selected' : '';
                        ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $sel ?>><?= ucfirst(htmlspecialchars($p)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Localização</label>
                    <input type="text" name="localizacao" class="form-control"
                           placeholder="Cidade, bairro..."
                           value="<?= htmlspecialchars($localizacao) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="<?= $baseUrl ?>/adocao/index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultado -->
    <?php if (empty($animais)): ?>
    <div class="text-center py-5">
        <span style="font-size:4rem;">🐾</span>
        <h3 class="mt-3 text-muted">Nenhum animal encontrado</h3>
        <p class="text-muted">Tente ajustar os filtros de busca.</p>
    </div>
    <?php else: ?>
    <p class="text-muted mb-3"><strong><?= $total ?></strong> animal(is) encontrado(s)</p>
    <div class="row g-4">
        <?php foreach ($animais as $animal): ?>
        <?php
            $img = $animal['imagem']
                ? $baseUrl . '/public/assets/images/uploads/animais/' . htmlspecialchars($animal['imagem'])
                : null;
            $statusClass = $animal['status'] === 'disponivel' ? 'success' : 'warning';
            $statusLabel = $animal['status'] === 'disponivel' ? 'Disponível' : 'Em adoção';
        ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 animal-card">
                <div class="position-relative">
                    <?php if ($img): ?>
                        <img src="<?= $img ?>"
                             alt="<?= htmlspecialchars($animal['nome']) ?>"
                             class="card-img-top animal-thumb"
                             style="height:220px;object-fit:cover;">
                    <?php else: ?>
                        <div style="height:220px; background:#e8f5e9; display:flex; align-items:center; justify-content:center; font-size:4.5rem; color:#2d8c4e;">
                            🐾
                        </div>
                    <?php endif; ?>
                    <span class="badge bg-<?= $statusClass ?> position-absolute top-0 end-0 m-2">
                        <?= $statusLabel ?>
                    </span>
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-bold"><?= htmlspecialchars($animal['nome']) ?></h5>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <span class="badge bg-light text-dark border">
                            <i class="fas fa-paw me-1 text-success"></i><?= ucfirst(htmlspecialchars($animal['especie'])) ?>
                        </span>
                        <span class="badge bg-light text-dark border">
                            <i class="fas fa-weight-hanging me-1 text-success"></i><?= ucfirst(htmlspecialchars($animal['porte'])) ?>
                        </span>
                        <?php if ($animal['sexo'] && $animal['sexo'] !== 'nao_informado'): ?>
                        <span class="badge bg-light text-dark border">
                            <?= $animal['sexo'] === 'macho' ? '♂ Macho' : '♀ Fêmea' ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-1">
                        <i class="fas fa-map-marker-alt me-1 text-success"></i>
                        <?= htmlspecialchars($animal['localizacao'] ?? 'Localização não informada') ?>
                    </p>
                    <?php if ($animal['descricao']): ?>
                    <p class="card-text text-muted small flex-grow-1">
                        <?= htmlspecialchars(mb_substr($animal['descricao'], 0, 80)) ?>...
                    </p>
                    <?php endif; ?>
                    <a href="<?= $baseUrl ?>/adocao/detalhes.php?id=<?= (int)$animal['id'] ?>"
                       class="btn btn-success mt-2 w-100">
                        <i class="fas fa-heart me-1"></i> Ver Detalhes
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
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    &laquo; Anterior
                </a>
            </li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    Próxima &raquo;
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.animal-card { transition: transform .2s, box-shadow .2s; border-radius: 12px; overflow: hidden; }
.animal-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
