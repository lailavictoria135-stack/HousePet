<?php
/**
 * HousePet — Painel Administrativo e Moderação (Dashboard)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$baseUrl = '/housePet2';

// ── Indicadores e Contadores Globais ──────────────────────────────
$totalUsuarios       = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalDenuncias      = (int)$pdo->query("SELECT COUNT(*) FROM denuncias")->fetchColumn();
$denunciasPendentes  = (int)$pdo->query("SELECT COUNT(*) FROM denuncias WHERE status = 'pendente'")->fetchColumn();
$totalAnimais        = (int)$pdo->query("SELECT COUNT(*) FROM animais")->fetchColumn();
$animaisPendentes    = (int)$pdo->query("SELECT COUNT(*) FROM animais WHERE status = 'pendente'")->fetchColumn();
$totalDesaparecidos  = (int)$pdo->query("SELECT COUNT(*) FROM desaparecidos")->fetchColumn();
$desaparecidosPendentes = (int)$pdo->query("SELECT COUNT(*) FROM desaparecidos WHERE status = 'pendente'")->fetchColumn();

// ── Publicações Pendentes de Análise Rápida ─────────────────────────
$stmtPendDen = $pdo->query("
    SELECT d.id, d.tipo_ocorrencia, d.localizacao, d.data_cadastro, u.nome AS autor
    FROM denuncias d
    LEFT JOIN usuarios u ON u.id = d.usuario_id
    WHERE d.status = 'pendente'
    ORDER BY d.data_cadastro ASC
    LIMIT 5
");
$pendenciasDenuncias = $stmtPendDen->fetchAll();

$stmtPendAni = $pdo->query("
    SELECT a.id, a.nome, a.especie, a.data_cadastro, u.nome AS autor
    FROM animais a
    LEFT JOIN usuarios u ON u.id = a.usuario_id
    WHERE a.status = 'pendente'
    ORDER BY a.data_cadastro ASC
    LIMIT 5
");
$pendenciasAnimais = $stmtPendAni->fetchAll();

$stmtPendDes = $pdo->query("
    SELECT d.id, d.nome_animal, d.especie, d.data_cadastro, u.nome AS autor
    FROM desaparecidos d
    LEFT JOIN usuarios u ON u.id = d.usuario_id
    WHERE d.status = 'pendente'
    ORDER BY d.data_cadastro ASC
    LIMIT 5
");
$pendenciasDesaparecidos = $stmtPendDes->fetchAll();

// ── Últimas Ações de Moderação ─────────────────────────────────────
$stmtMod = $pdo->query("
    SELECT m.*, u.nome AS moderador_nome
    FROM moderacoes m
    JOIN usuarios u ON u.id = m.moderador_id
    ORDER BY m.data_moderacao DESC
    LIMIT 6
");
$ultimasModeracoes = $stmtMod->fetchAll();

$pageTitle = 'Painel de Administração e Moderação';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h2 fw-bold text-dark mb-1">
                <i class="fa-solid fa-shield-halved text-success me-2"></i>Painel de Moderação e Gestão
            </h1>
            <p class="text-muted mb-0">Controle central de segurança, triagem de ocorrências e aprovação de publicações.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $baseUrl ?>/admin/usuarios.php" class="btn btn-outline-dark">
                <i class="fa-solid fa-users me-1"></i> Gestão de Usuários
            </a>
            <a href="<?= $baseUrl ?>/admin/moderacao.php" class="btn btn-outline-success">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Histórico de Moderação
            </a>
        </div>
    </div>

    <!-- Cards de Indicadores com Destaque para Pendências -->
    <div class="row g-3 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-primary" style="border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Usuários</span>
                        <h2 class="h3 fw-bold mb-0 text-dark"><?= $totalUsuarios ?></h2>
                    </div>
                    <div class="fs-1 text-primary opacity-50"><i class="fa-solid fa-users"></i></div>
                </div>
                <div class="mt-2 small text-muted">Contas registradas no sistema</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-danger" style="border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Denúncias</span>
                        <h2 class="h3 fw-bold mb-0 text-danger"><?= $totalDenuncias ?></h2>
                    </div>
                    <div class="fs-1 text-danger opacity-50"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
                <div class="mt-2 small">
                    <span class="badge bg-danger-subtle text-danger fw-bold"><?= $denunciasPendentes ?> aguardando análise</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-success" style="border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Adoção</span>
                        <h2 class="h3 fw-bold mb-0 text-success"><?= $totalAnimais ?></h2>
                    </div>
                    <div class="fs-1 text-success opacity-50"><i class="fa-solid fa-paw"></i></div>
                </div>
                <div class="mt-2 small">
                    <span class="badge bg-success-subtle text-success fw-bold"><?= $animaisPendentes ?> para aprovação</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-warning" style="border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Desaparecidos</span>
                        <h2 class="h3 fw-bold mb-0 text-warning"><?= $totalDesaparecidos ?></h2>
                    </div>
                    <div class="fs-1 text-warning opacity-50"><i class="fa-solid fa-bullhorn"></i></div>
                </div>
                <div class="mt-2 small">
                    <span class="badge bg-warning-subtle text-warning fw-bold"><?= $desaparecidosPendentes ?> para aprovação</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila de Moderação Prioritária (Pendentes) -->
    <div class="row g-4 mb-5">
        <!-- Denúncias Pendentes -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fs-6 fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Denúncias Pendentes</h5>
                    <a href="<?= $baseUrl ?>/admin/denuncias.php?status=pendente" class="badge bg-white text-danger text-decoration-none">Ver todas (<?= $denunciasPendentes ?>)</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pendenciasDenuncias)): ?>
                        <div class="p-4 text-center text-muted small">Nenhuma denúncia pendente de triagem.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendenciasDenuncias as $pd): ?>
                                <a href="<?= $baseUrl ?>/admin/denuncias.php?id=<?= $pd['id'] ?>" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-dark">#<?= $pd['id'] ?> - <?= htmlspecialchars($pd['tipo_ocorrencia']) ?></strong>
                                        <small class="text-muted"><?= formatDate($pd['data_cadastro']) ?></small>
                                    </div>
                                    <small class="text-muted d-block"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($pd['localizacao']) ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Animais Pendentes -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fs-6 fw-bold"><i class="fa-solid fa-paw me-1"></i> Adoções Pendentes</h5>
                    <a href="<?= $baseUrl ?>/admin/adocoes.php?status=pendente" class="badge bg-white text-success text-decoration-none">Ver todas (<?= $animaisPendentes ?>)</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pendenciasAnimais)): ?>
                        <div class="p-4 text-center text-muted small">Nenhum animal pendente de moderação.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendenciasAnimais as $pa): ?>
                                <a href="<?= $baseUrl ?>/admin/adocoes.php?id=<?= $pa['id'] ?>" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-dark"><?= htmlspecialchars($pa['nome']) ?> (<?= htmlspecialchars($pa['especie']) ?>)</strong>
                                        <small class="text-muted"><?= formatDate($pa['data_cadastro']) ?></small>
                                    </div>
                                    <small class="text-muted d-block">Por: <?= htmlspecialchars($pa['autor'] ?? 'Usuário') ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Desaparecidos Pendentes -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fs-6 fw-bold"><i class="fa-solid fa-bullhorn me-1"></i> Desaparecidos Pendentes</h5>
                    <a href="<?= $baseUrl ?>/admin/desaparecidos.php?status=pendente" class="badge bg-dark text-white text-decoration-none">Ver todos (<?= $desaparecidosPendentes ?>)</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pendenciasDesaparecidos)): ?>
                        <div class="p-4 text-center text-muted small">Nenhum pet desaparecido aguardando moderação.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendenciasDesaparecidos as $pdes): ?>
                                <a href="<?= $baseUrl ?>/admin/desaparecidos.php?id=<?= $pdes['id'] ?>" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-dark"><?= htmlspecialchars($pdes['nome_animal']) ?> (<?= htmlspecialchars($pdes['especie']) ?>)</strong>
                                        <small class="text-muted"><?= formatDate($pdes['data_cadastro']) ?></small>
                                    </div>
                                    <small class="text-muted d-block">Por: <?= htmlspecialchars($pdes['autor'] ?? 'Tutor') ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Moderações Realizadas -->
    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h5 class="h6 mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i>Histórico Recente de Ações de Moderação</h5>
            <a href="<?= $baseUrl ?>/admin/moderacao.php" class="text-success small fw-semibold text-decoration-none">Ver Histórico Completo →</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ultimasModeracoes)): ?>
                <div class="p-4 text-center text-muted small">Nenhuma ação de moderação registrada no histórico ainda.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Moderador</th>
                                <th>Tipo de Conteúdo</th>
                                <th>Item #</th>
                                <th>Decisão</th>
                                <th>Observação Registrada</th>
                                <th>Data/Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasModeracoes as $m): ?>
                                <tr>
                                    <td class="fw-semibold"><i class="fa-solid fa-user-shield text-success me-1"></i><?= htmlspecialchars($m['moderador_nome']) ?></td>
                                    <td><span class="badge bg-secondary-subtle text-dark"><?= ucfirst(htmlspecialchars($m['tipo_conteudo'])) ?></span></td>
                                    <td>#<?= (int)$m['conteudo_id'] ?></td>
                                    <td>
                                        <span class="badge <?= $m['decisao'] === 'aprovado' ? 'bg-success' : ($m['decisao'] === 'rejeitado' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                            <?= ucfirst(htmlspecialchars($m['decisao'])) ?>
                                        </span>
                                    </td>
                                    <td><small class="text-secondary"><?= htmlspecialchars($m['observacao'] ?: '—') ?></small></td>
                                    <td><small class="text-muted"><?= formatDate($m['data_moderacao']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

