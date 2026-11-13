<?php
/**
 * HousePet — Dashboard do Usuário
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$user       = getCurrentUser();
$userId     = $user['id'];
$baseUrl    = '/housePet2';
$pageTitle  = 'Meu Painel';

// ── Contagens resumo ────────────────────────────────────────────────────────
$stmtCountDen = $pdo->prepare('SELECT COUNT(*) FROM denuncias WHERE usuario_id = ?');
$stmtCountDen->execute([$userId]);
$totalDenuncias = (int)$stmtCountDen->fetchColumn();

$stmtCountAni = $pdo->prepare('SELECT COUNT(*) FROM animais WHERE usuario_id = ?');
$stmtCountAni->execute([$userId]);
$totalAnimais = (int)$stmtCountAni->fetchColumn();

$stmtCountDes = $pdo->prepare('SELECT COUNT(*) FROM desaparecidos WHERE usuario_id = ?');
$stmtCountDes->execute([$userId]);
$totalDesaparecidos = (int)$stmtCountDes->fetchColumn();

// ── Últimas 5 denúncias ─────────────────────────────────────────────────────
$stmtDen = $pdo->prepare(
    'SELECT id, tipo_ocorrencia, status, data_cadastro FROM denuncias
     WHERE usuario_id = ? ORDER BY data_cadastro DESC LIMIT 5'
);
$stmtDen->execute([$userId]);
$minhasDenuncias = $stmtDen->fetchAll();

// ── Últimos 5 animais para adoção ───────────────────────────────────────────
$stmtAni = $pdo->prepare(
    'SELECT id, nome, especie, status, imagem FROM animais
     WHERE usuario_id = ? ORDER BY data_cadastro DESC LIMIT 5'
);
$stmtAni->execute([$userId]);
$meusAnimais = $stmtAni->fetchAll();

// ── Últimos 5 desaparecidos ─────────────────────────────────────────────────
$stmtDes = $pdo->prepare(
    'SELECT id, nome_animal, especie, status, data_desaparecimento FROM desaparecidos
     WHERE usuario_id = ? ORDER BY data_cadastro DESC LIMIT 5'
);
$stmtDes->execute([$userId]);
$meusDesaparecidos = $stmtDes->fetchAll();

// ── Interesses recebidos em meus animais ────────────────────────────────────
$stmtIntRecebidos = $pdo->prepare(
    'SELECT a.id AS adocao_id, u.nome AS interessado, an.nome AS animal,
            a.mensagem, a.status, a.data_cadastro
     FROM adocoes a
     JOIN animais an ON an.id = a.animal_id
     JOIN usuarios u ON u.id = a.usuario_interessado_id
     WHERE an.usuario_id = ?
     ORDER BY a.data_cadastro DESC LIMIT 10'
);
$stmtIntRecebidos->execute([$userId]);
$interessesRecebidos = $stmtIntRecebidos->fetchAll();

// ── Minhas solicitações de adoção ───────────────────────────────────────────
$stmtIntEnviados = $pdo->prepare(
    'SELECT a.id AS adocao_id, an.nome AS animal, an.especie,
            u.nome AS dono, a.status, a.data_cadastro
     FROM adocoes a
     JOIN animais an ON an.id = a.animal_id
     JOIN usuarios u ON u.id = an.usuario_id
     WHERE a.usuario_interessado_id = ?
     ORDER BY a.data_cadastro DESC LIMIT 10'
);
$stmtIntEnviados->execute([$userId]);
$minhasSolicitacoes = $stmtIntEnviados->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Dashboard Layout ───────────────────────────────────────────── */
.dashboard-wrapper {
    display: flex;
    gap: 1.5rem;
    padding: 2rem 0;
    align-items: flex-start;
}
.dashboard-sidebar {
    width: 260px;
    flex-shrink: 0;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.08);
    padding: 1.5rem 1rem;
    position: sticky;
    top: 80px;
}
.sidebar-avatar {
    text-align: center;
    margin-bottom: 1rem;
}
.sidebar-avatar .avatar-circle {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg,#2d8c4e,#1a5c32);
    color: #fff;
    font-size: 2rem;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto .6rem;
    font-weight: 700;
}
.sidebar-avatar h3 {
    font-size: .95rem;
    color: #1a5c32;
    margin: 0 0 .2rem;
    font-weight: 600;
}
.sidebar-avatar small { color: #777; font-size: .78rem; }
.sidebar-nav { list-style: none; padding: 0; margin: 1rem 0 0; }
.sidebar-nav li a {
    display: flex; align-items: center; gap: .6rem;
    padding: .6rem .8rem;
    border-radius: 8px;
    color: #444;
    text-decoration: none;
    font-size: .88rem;
    transition: background .2s, color .2s;
}
.sidebar-nav li a:hover,
.sidebar-nav li a.active { background: #e8f5e9; color: #1a5c32; font-weight: 600; }
.sidebar-nav li a i { width: 18px; text-align: center; }
.sidebar-divider { border: none; border-top: 1px solid #eee; margin: .8rem 0; }

/* ── Dashboard Main ─────────────────────────────────────────────── */
.dashboard-main { flex: 1; min-width: 0; }
.dash-greeting { margin-bottom: 1.5rem; }
.dash-greeting h1 { font-size: 1.6rem; color: #1a5c32; margin: 0 0 .2rem; }
.dash-greeting p  { color: #666; font-size: .9rem; margin: 0; }

/* ── Summary Cards ─────────────────────────────────────────────── */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.sum-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    padding: 1.2rem 1rem;
    text-align: center;
    border-left: 4px solid;
}
.sum-card.green  { border-color: #2d8c4e; }
.sum-card.blue   { border-color: #3498db; }
.sum-card.orange { border-color: #e67e22; }
.sum-card .num   { font-size: 2.2rem; font-weight: 700; color: #1a5c32; }
.sum-card.blue .num  { color: #3498db; }
.sum-card.orange .num{ color: #e67e22; }
.sum-card label  { font-size: .78rem; color: #777; display: block; margin-top: .2rem; }

/* ── Sections ───────────────────────────────────────────────────── */
.dash-section {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    padding: 1.4rem 1.5rem;
    margin-bottom: 1.5rem;
}
.dash-section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1rem;
}
.dash-section-header h2 {
    font-size: 1.05rem; color: #1a5c32; margin: 0; display:flex; align-items:center; gap:.5rem;
}
.dash-section-header a { font-size: .8rem; color: #2d8c4e; }
.dash-table { width: 100%; border-collapse: collapse; font-size: .87rem; }
.dash-table th { padding: .5rem .7rem; text-align: left; color: #555; font-weight: 600;
    border-bottom: 2px solid #eee; }
.dash-table td { padding: .6rem .7rem; border-bottom: 1px solid #f0f0f0; color: #333; }
.dash-table tr:last-child td { border-bottom: none; }
.empty-state { text-align: center; padding: 1.5rem; color: #aaa; font-size: .9rem; }

/* ── Badges ─────────────────────────────────────────────────────── */
.badge {
    display: inline-block; padding: .22em .6em;
    border-radius: 20px; font-size: .72rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .4px;
}
.badge-warning   { background: #fff3cd; color: #856404; }
.badge-info      { background: #cff4fc; color: #055160; }
.badge-success   { background: #d1e7dd; color: #0a3622; }
.badge-danger    { background: #f8d7da; color: #842029; }
.badge-secondary { background: #e2e3e5; color: #41464b; }

/* ── Animal Cards ───────────────────────────────────────────────── */
.animal-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}
.animal-card-mini {
    border: 1px solid #eee; border-radius: 10px; overflow: hidden;
    text-align: center; font-size: .82rem;
}
.animal-card-mini img {
    width: 100%; height: 100px; object-fit: cover; display: block;
}
.animal-card-mini .no-img {
    width: 100%; height: 100px; background: #e8f5e9;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; color: #2d8c4e;
}
.animal-card-mini .card-body-mini { padding: .5rem; }
.animal-card-mini h4 { margin: 0 0 .3rem; font-size: .88rem; color: #1a5c32; }
.animal-card-mini .btn-xs {
    display: inline-block; padding: .22rem .6rem; border-radius: 6px;
    font-size: .75rem; text-decoration: none; margin: .15rem .1rem;
}
.btn-xs.edit   { background: #2d8c4e; color: #fff; }
.btn-xs.del    { background: #dc3545; color: #fff; }
.btn-xs.view   { background: #3498db; color: #fff; }

/* ── Quick Links ─────────────────────────────────────────────────── */
.quick-links {
    display: flex; gap: .8rem; flex-wrap: wrap; margin-bottom: 1.5rem;
}
.quick-link-btn {
    display: flex; align-items: center; gap: .5rem;
    padding: .6rem 1.1rem;
    border-radius: 25px;
    text-decoration: none; font-size: .87rem; font-weight: 500;
    transition: transform .2s;
}
.quick-link-btn:hover { transform: translateY(-2px); }
.ql-green  { background: #2d8c4e; color: #fff; }
.ql-blue   { background: #3498db; color: #fff; }
.ql-orange { background: #e67e22; color: #fff; }
.ql-red    { background: #e74c3c; color: #fff; }

/* Interesses */
.interesse-item {
    border-left: 3px solid #2d8c4e;
    padding: .6rem .8rem;
    margin-bottom: .6rem;
    border-radius: 0 8px 8px 0;
    background: #f9fefb;
    font-size: .85rem;
}
.interesse-item strong { color: #1a5c32; }
.interesse-item p { margin: .2rem 0 0; color: #555; }

@media (max-width: 768px) {
    .dashboard-wrapper { flex-direction: column; }
    .dashboard-sidebar { width: 100%; position: static; }
}
</style>

<div class="container dashboard-wrapper">

    <!-- ══ SIDEBAR ══════════════════════════════════════════════════════════ -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-avatar">
            <div class="avatar-circle">
                <?= strtoupper(mb_substr($user['nome'], 0, 1)) ?>
            </div>
            <h3><?= htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
            <small><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></small>
        </div>

        <ul class="sidebar-nav">
            <li><a href="<?= $baseUrl ?>/usuario/dashboard.php" class="active">
                <i class="fa-solid fa-gauge"></i> Meu Painel
            </a></li>
            <li><a href="<?= $baseUrl ?>/adocao/cadastrar.php">
                <i class="fa-solid fa-paw"></i> Cadastrar Animal
            </a></li>
            <li><a href="<?= $baseUrl ?>/desaparecidos/cadastrar.php">
                <i class="fa-solid fa-magnifying-glass"></i> Registrar Desaparecido
            </a></li>
            <li><a href="<?= $baseUrl ?>/denuncias/cadastrar.php">
                <i class="fa-solid fa-flag"></i> Fazer Denúncia
            </a></li>
            <hr class="sidebar-divider">
            <li><a href="<?= $baseUrl ?>/usuario/editar_perfil.php">
                <i class="fa-solid fa-user-pen"></i> Editar Perfil
            </a></li>
            <?php if (isModerador()): ?>
            <li><a href="<?= $baseUrl ?>/admin/dashboard.php">
                <i class="fa-solid fa-shield-halved"></i> Painel Admin
            </a></li>
            <?php endif; ?>
            <hr class="sidebar-divider">
            <li><a href="<?= $baseUrl ?>/auth/logout.php" style="color:#e74c3c;">
                <i class="fa-solid fa-right-from-bracket"></i> Sair
            </a></li>
        </ul>
    </aside>

    <!-- ══ MAIN ═════════════════════════════════════════════════════════════ -->
    <main class="dashboard-main">

        <!-- Saudação -->
        <div class="dash-greeting">
            <h1>Olá, <?= htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8') ?>! 👋</h1>
            <p>Bem-vindo ao seu painel. Gerencie suas publicações e atividades.</p>
        </div>

        <!-- Links rápidos -->
        <div class="quick-links">
            <a href="<?= $baseUrl ?>/adocao/cadastrar.php" class="quick-link-btn ql-green">
                <i class="fa-solid fa-plus"></i> Cadastrar Animal
            </a>
            <a href="<?= $baseUrl ?>/desaparecidos/cadastrar.php" class="quick-link-btn ql-blue">
                <i class="fa-solid fa-magnifying-glass"></i> Registrar Desaparecido
            </a>
            <a href="<?= $baseUrl ?>/denuncias/cadastrar.php" class="quick-link-btn ql-red">
                <i class="fa-solid fa-flag"></i> Fazer Denúncia
            </a>
            <a href="<?= $baseUrl ?>/usuario/editar_perfil.php" class="quick-link-btn ql-orange">
                <i class="fa-solid fa-user-pen"></i> Editar Perfil
            </a>
        </div>

        <!-- Cards resumo -->
        <div class="summary-cards">
            <div class="sum-card orange">
                <div class="num"><?= $totalDenuncias ?></div>
                <label><i class="fa-solid fa-flag"></i> Denúncias</label>
            </div>
            <div class="sum-card green">
                <div class="num"><?= $totalAnimais ?></div>
                <label><i class="fa-solid fa-paw"></i> Animais Cadastrados</label>
            </div>
            <div class="sum-card blue">
                <div class="num"><?= $totalDesaparecidos ?></div>
                <label><i class="fa-solid fa-magnifying-glass"></i> Desaparecidos</label>
            </div>
        </div>

        <!-- ── Minhas Denúncias ─────────────────────────────────────────── -->
        <div class="dash-section">
            <div class="dash-section-header">
                <h2><i class="fa-solid fa-flag"></i> Minhas Denúncias</h2>
                <a href="<?= $baseUrl ?>/denuncias/minhas_denuncias.php">Ver todas →</a>
            </div>
            <?php if (empty($minhasDenuncias)): ?>
                <p class="empty-state">Nenhuma denúncia registrada ainda.</p>
            <?php else: ?>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($minhasDenuncias as $den): ?>
                    <tr>
                        <td><?= (int)$den['id'] ?></td>
                        <td><?= htmlspecialchars($den['tipo_ocorrencia'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= getStatusClass($den['status']) ?>">
                                <?= getStatusLabel($den['status']) ?>
                            </span>
                        </td>
                        <td><?= formatDate($den['data_cadastro']) ?></td>
                        <td>
                            <a href="<?= $baseUrl ?>/denuncias/view.php?id=<?= (int)$den['id'] ?>" class="btn-xs view">Ver</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- ── Meus Animais para Adoção ─────────────────────────────────── -->
        <div class="dash-section">
            <div class="dash-section-header">
                <h2><i class="fa-solid fa-paw"></i> Meus Animais para Adoção</h2>
                <a href="<?= $baseUrl ?>/adocao/index.php">Ver catálogo público →</a>
            </div>
            <?php if (empty($meusAnimais)): ?>
                <p class="empty-state">Nenhum animal cadastrado ainda.</p>
            <?php else: ?>
            <div class="animal-cards-grid">
                <?php foreach ($meusAnimais as $ani): ?>
                <div class="animal-card-mini">
                    <?php if ($ani['imagem']): ?>
                        <img src="<?= $baseUrl ?>/public/assets/images/uploads/animais/<?= htmlspecialchars($ani['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                             alt="<?= htmlspecialchars($ani['nome'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <div class="no-img">🐾</div>
                    <?php endif; ?>
                    <div class="card-body-mini">
                        <h4><?= htmlspecialchars($ani['nome'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <span class="badge <?= getStatusClass($ani['status']) ?>">
                            <?= getStatusLabel($ani['status']) ?>
                        </span><br>
                        <a href="<?= $baseUrl ?>/adocao/editar.php?id=<?= (int)$ani['id'] ?>" class="btn-xs edit">Editar</a>
                        <a href="<?= $baseUrl ?>/adocao/excluir.php?id=<?= (int)$ani['id'] ?>"
                           class="btn-xs del"
                           onclick="return confirm('Excluir este animal?')">Excluir</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Meus Animais Desaparecidos ───────────────────────────────── -->
        <div class="dash-section">
            <div class="dash-section-header">
                <h2><i class="fa-solid fa-magnifying-glass"></i> Meus Animais Desaparecidos</h2>
                <a href="<?= $baseUrl ?>/desaparecidos/index.php">Ver mural público →</a>
            </div>
            <?php if (empty($meusDesaparecidos)): ?>
                <p class="empty-state">Nenhum registro de desaparecido ainda.</p>
            <?php else: ?>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Animal</th>
                        <th>Espécie</th>
                        <th>Status</th>
                        <th>Data Desap.</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meusDesaparecidos as $des): ?>
                    <tr>
                        <td><?= htmlspecialchars($des['nome_animal'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($des['especie'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= getStatusClass($des['status']) ?>">
                                <?= getStatusLabel($des['status']) ?>
                            </span>
                        </td>
                        <td><?= $des['data_desaparecimento'] ? formatDate($des['data_desaparecimento']) : '—' ?></td>
                        <td>
                            <a href="<?= $baseUrl ?>/desaparecidos/editar.php?id=<?= (int)$des['id'] ?>" class="btn-xs edit">Editar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- ── Interesses Recebidos ──────────────────────────────────────── -->
        <div class="dash-section">
            <div class="dash-section-header">
                <h2><i class="fa-solid fa-heart"></i> Interesses em Meus Animais</h2>
            </div>
            <?php if (empty($interessesRecebidos)): ?>
                <p class="empty-state">Nenhum interesse recebido ainda.</p>
            <?php else: ?>
                <?php foreach ($interessesRecebidos as $int): ?>
                <div class="interesse-item">
                    <strong><?= htmlspecialchars($int['interessado'], ENT_QUOTES, 'UTF-8') ?></strong>
                    quer adotar <strong><?= htmlspecialchars($int['animal'], ENT_QUOTES, 'UTF-8') ?></strong>
                    &nbsp;
                    <span class="badge <?= getStatusClass($int['status']) ?>">
                        <?= getStatusLabel($int['status']) ?>
                    </span>
                    <?php if ($int['mensagem']): ?>
                    <p><?= htmlspecialchars($int['mensagem'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <small style="color:#999"><?= formatDate($int['data_cadastro']) ?></small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ── Minhas Solicitações de Adoção ────────────────────────────── -->
        <div class="dash-section">
            <div class="dash-section-header">
                <h2><i class="fa-solid fa-hand-holding-heart"></i> Minhas Solicitações de Adoção</h2>
            </div>
            <?php if (empty($minhasSolicitacoes)): ?>
                <p class="empty-state">Você ainda não solicitou nenhuma adoção.</p>
            <?php else: ?>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Animal</th>
                        <th>Espécie</th>
                        <th>Responsável</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($minhasSolicitacoes as $sol): ?>
                    <tr>
                        <td><?= htmlspecialchars($sol['animal'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($sol['especie'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($sol['dono'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= getStatusClass($sol['status']) ?>">
                                <?= getStatusLabel($sol['status']) ?>
                            </span>
                        </td>
                        <td><?= formatDate($sol['data_cadastro']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
