<?php
/**
 * HousePet — Página Inicial Oficial
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$baseUrl = '/housePet2';

// ── Consulta os 6 animais mais recentes disponíveis para adoção ──
$stmtAdocao = $pdo->query("
    SELECT id, nome, especie, raca, porte, localizacao, imagem, status
    FROM animais
    WHERE status IN ('disponivel', 'em_adocao')
    ORDER BY data_cadastro DESC
    LIMIT 6
");
$animaisAdocao = $stmtAdocao->fetchAll();

// ── Consulta os 4 pets desaparecidos mais recentes ──────────────
$stmtDesap = $pdo->query("
    SELECT id, nome_animal, especie, raca, local_desaparecimento, data_desaparecimento, imagem, status
    FROM desaparecidos
    WHERE status = 'desaparecido'
    ORDER BY data_cadastro DESC
    LIMIT 4
");
$petsDesaparecidos = $stmtDesap->fetchAll();

// ── Métricas de impacto do projeto ──────────────────────────────
$totalAdotados  = (int)$pdo->query("SELECT COUNT(*) FROM animais WHERE status = 'adotado'")->fetchColumn();
$totalDisponiveis = (int)$pdo->query("SELECT COUNT(*) FROM animais WHERE status = 'disponivel'")->fetchColumn();
$totalEncontrados = (int)$pdo->query("SELECT COUNT(*) FROM desaparecidos WHERE status = 'encontrado'")->fetchColumn();

$pageTitle = 'Página Inicial — Proteção e Adoção Animal';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ===== HERO BANNER PRINCIPAL ===== -->
<section class="hero-section text-white py-5 mb-5" style="background: linear-gradient(135deg, #1b5e20 0%, #2d8c4e 70%, #43a047 100%);">
    <div class="container py-lg-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge bg-light text-success fw-bold px-3 py-2 mb-3 text-uppercase letter-spacing-1">
                    🐾 Plataforma de Proteção e Bem-Estar Animal
                </span>
                <h1 class="display-4 fw-bold mb-3 lh-sm">
                    Amor, proteção e um recomeço para quem não tem voz.
                </h1>
                <p class="lead mb-4" style="opacity: 0.95; font-size: 1.15rem; line-height: 1.7;">
                    O <strong>HousePet</strong> conecta animais resgatados a lares amorosos, ajuda famílias a reencontrarem seus pets perdidos e organiza registros de maus-tratos para que nenhum ato de violência fique impune.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= $baseUrl ?>/adocao/index.php" class="btn btn-warning btn-lg text-dark fw-bold px-4 shadow">
                        <i class="fa-solid fa-heart me-2"></i> Quero Adotar
                    </a>
                    <a href="<?= $baseUrl ?>/desaparecidos/index.php" class="btn btn-outline-light btn-lg px-4 shadow-sm">
                        <i class="fa-solid fa-bullhorn me-2"></i> Pets Desaparecidos
                    </a>
                    <a href="<?= $baseUrl ?>/denuncias/cadastrar.php" class="btn btn-danger btn-lg px-4 shadow-sm">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Fazer Denúncia
                    </a>
                </div>
            </div>

            <div class="col-lg-5 text-center">
                <div class="hero-card bg-white text-dark p-4 rounded-4 shadow-lg text-start">
                    <div class="d-flex align-items-center mb-3">
                        <span class="fs-1 me-3">🐶🐱</span>
                        <div>
                            <h5 class="fw-bold mb-0 text-success">Rede Comunitária de Apoio</h5>
                            <small class="text-muted">Unindo tutores, protetores e voluntários</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <h4 class="fw-bold text-success mb-0"><?= $totalDisponiveis ?></h4>
                            <small class="text-muted" style="font-size:0.75rem;">Para Adoção</small>
                        </div>
                        <div class="col-4 border-start border-end">
                            <h4 class="fw-bold text-primary mb-0"><?= $totalAdotados ?></h4>
                            <small class="text-muted" style="font-size:0.75rem;">Adotados</small>
                        </div>
                        <div class="col-4">
                            <h4 class="fw-bold text-warning mb-0"><?= $totalEncontrados ?></h4>
                            <small class="text-muted" style="font-size:0.75rem;">Reencontrados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container mb-5">
    <!-- ===== CONSCIENTIZAÇÃO ===== -->
    <div class="row g-4 align-items-center mb-5 py-3">
        <div class="col-lg-6">
            <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fw-semibold mb-2">
                Conscientização e Responsabilidade
            </span>
            <h2 class="h2 fw-bold text-dark mb-3">Por que a causa animal precisa da nossa voz?</h2>
            <p class="text-secondary" style="line-height: 1.7;">
                Milhares de cães e gatos enfrentam diariamente o abandono nas ruas, a fome e a crueldade. Adoção responsável não é apenas dar abrigo: é assumir o compromisso de cuidar, vacinar, alimentar e dar afeto por toda a vida do animal.
            </p>
            <p class="text-secondary" style="line-height: 1.7;">
                Quando substituímos publicações perdidas em grupos de mensagens por uma plataforma integrada e organizada, multiplicamos as chances de um resgate seguro e de um final feliz.
            </p>
            <div class="d-flex gap-3 mt-3">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-circle-check text-success fs-4 me-2"></i>
                    <span class="small fw-semibold">Guarda Responsável</span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-circle-check text-success fs-4 me-2"></i>
                    <span class="small fw-semibold">Castração Solidária</span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-circle-check text-success fs-4 me-2"></i>
                    <span class="small fw-semibold">Denúncia de Abuso</span>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="p-4 bg-light rounded-4 border">
                <h3 class="h5 fw-bold text-success mb-3"><i class="fa-solid fa-lightbulb me-2"></i>Os 3 Pilares do HousePet</h3>
                <div class="d-flex mb-3">
                    <div class="me-3 fs-3 text-success"><i class="fa-solid fa-hand-holding-heart"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Adoção Transparente</h6>
                        <small class="text-muted">Perfis completos com foto, histórico, porte e contato direto com quem resgatou.</small>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <div class="me-3 fs-3 text-warning"><i class="fa-solid fa-bullhorn"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Mural de Desaparecidos</h6>
                        <small class="text-muted">Divulgação ágil com filtros geográficos para mobilizar vizinhos e bairros próximos.</small>
                    </div>
                </div>
                <div class="d-flex">
                    <div class="me-3 fs-3 text-danger"><i class="fa-solid fa-shield-cat"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Combate aos Maus-Tratos</h6>
                        <small class="text-muted">Canal seguro e confidencial com triagem e orientações sobre os órgãos públicos.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== SEÇÃO: ANIMAIS PARA ADOÇÃO ===== -->
    <div class="mb-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="text-success fw-bold small text-uppercase letter-spacing-1">Destaques</span>
                <h2 class="h2 fw-bold text-dark mb-0">Eles Estão Esperando Por Você</h2>
            </div>
            <a href="<?= $baseUrl ?>/adocao/index.php" class="btn btn-outline-success">
                Ver Todos os Animais (<?= $totalDisponiveis ?>) →
            </a>
        </div>

        <?php if (empty($animaisAdocao)): ?>
            <div class="p-5 text-center bg-light rounded-4">
                <span style="font-size:3rem;">🐾</span>
                <h4 class="h5 text-muted mt-2">Nenhum animal cadastrado no momento.</h4>
                <p class="text-muted small">Seja o primeiro a divulgar um resgate para adoção!</p>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= $baseUrl ?>/adocao/cadastrar.php" class="btn btn-sm btn-success mt-2">Cadastrar Animal</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($animaisAdocao as $ani): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card h-100 border-0 shadow-sm animal-card" style="border-radius:14px; overflow:hidden;">
                            <div class="position-relative">
                                <?php if ($ani['imagem']): ?>
                                    <img src="<?= $baseUrl ?>/public/assets/images/uploads/animais/<?= htmlspecialchars($ani['imagem']) ?>"
                                         alt="<?= htmlspecialchars($ani['nome']) ?>"
                                         class="card-img-top" style="height:210px; object-fit:cover;">
                                <?php else: ?>
                                    <div style="height:210px; background:#e8f5e9; display:flex; align-items:center; justify-content:center; font-size:4rem; color:#2d8c4e;">
                                        🐾
                                    </div>
                                <?php endif; ?>
                                <span class="badge bg-success position-absolute top-0 end-0 m-2 px-2 py-1">
                                    <?= getStatusLabel($ani['status']) ?>
                                </span>
                            </div>

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($ani['nome']) ?></h5>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <span class="badge bg-light text-dark border small">
                                        <i class="fa-solid fa-paw text-success me-1"></i><?= ucfirst(htmlspecialchars($ani['especie'])) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border small">
                                        <?= ucfirst(htmlspecialchars($ani['porte'])) ?>
                                    </span>
                                </div>
                                <p class="small text-muted mb-3 flex-grow-1">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i>
                                    <?= htmlspecialchars($ani['localizacao'] ?: 'Localização sob consulta') ?>
                                </p>
                                <a href="<?= $baseUrl ?>/adocao/detalhes.php?id=<?= $ani['id'] ?>" class="btn btn-success btn-sm w-100">
                                    <i class="fa-solid fa-heart me-1"></i> Quero Conhecer
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== SEÇÃO: ANIMAIS DESAPARECIDOS RECENTEMENTE ===== -->
    <div class="mb-5 py-4 border-top">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="text-danger fw-bold small text-uppercase letter-spacing-1">Urgência</span>
                <h2 class="h2 fw-bold text-dark mb-0">Animais Desaparecidos Recentemente</h2>
            </div>
            <a href="<?= $baseUrl ?>/desaparecidos/index.php" class="btn btn-outline-danger">
                Mural de Desaparecidos →
            </a>
        </div>

        <?php if (empty($petsDesaparecidos)): ?>
            <div class="p-4 text-center bg-light rounded-4 text-muted small">
                Nenhum animal desaparecido registrado recentemente.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($petsDesaparecidos as $pet): ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="card h-100 border-0 shadow-sm animal-card" style="border-radius:12px; overflow:hidden;">
                            <div class="position-relative">
                                <?php if ($pet['imagem']): ?>
                                    <img src="<?= $baseUrl ?>/public/assets/images/uploads/desaparecidos/<?= htmlspecialchars($pet['imagem']) ?>"
                                         alt="<?= htmlspecialchars($pet['nome_animal']) ?>"
                                         class="card-img-top" style="height:170px; object-fit:cover;">
                                <?php else: ?>
                                    <div style="height:170px; background:#ffebee; display:flex; align-items:center; justify-content:center; font-size:3.5rem; color:#e57373;">
                                        🐾
                                    </div>
                                <?php endif; ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2">DESAPARECIDO</span>
                            </div>

                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($pet['nome_animal']) ?></h6>
                                <small class="text-muted mb-2">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i><?= htmlspecialchars($pet['local_desaparecimento']) ?>
                                </small>
                                <a href="<?= $baseUrl ?>/desaparecidos/detalhes.php?id=<?= $pet['id'] ?>" class="btn btn-outline-danger btn-sm mt-auto w-100">
                                    <i class="fa-solid fa-eye me-1"></i> Ver Pistas
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== SEÇÃO: COMO DENUNCIAR MAUS-TRATOS ===== -->
    <div class="card border-0 text-white p-4 p-md-5 mb-5 shadow" style="border-radius:16px; background: linear-gradient(135deg, #b71c1c 0%, #d32f2f 100%);">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark fw-bold px-3 py-1 mb-2">Canal Seguro</span>
                <h3 class="display-6 fw-bold mb-3">Presenciou violência ou abandono? Denuncie!</h3>
                <p class="mb-3 lead" style="font-size:1.05rem; opacity:0.95;">
                    Não se cale perante a crueldade. No HousePet você pode protocolar ocorrências com fotografias e endereços detalhados. Seus dados cadastrais ficam estritamente confidenciais.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= $baseUrl ?>/denuncias/cadastrar.php" class="btn btn-warning text-dark fw-bold btn-lg">
                        <i class="fa-solid fa-bullhorn me-1"></i> Registrar Denúncia Agora
                    </a>
                    <a href="<?= $baseUrl ?>/denuncias/index.php" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-info-circle me-1"></i> Informações Legais
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="p-3 bg-white text-dark rounded-3 shadow">
                    <i class="fa-solid fa-phone-volume text-danger fs-1 mb-2"></i>
                    <h6 class="fw-bold mb-1">Canais Oficiais de Emergência</h6>
                    <p class="small text-muted mb-2">Para flagrantes em tempo real, acione os órgãos policiais:</p>
                    <div class="badge bg-danger p-2 fs-6 w-100 mb-1">Polícia Militar: 190</div>
                    <div class="badge bg-secondary p-2 fs-6 w-100">Disque Denúncia: 181</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== SEÇÃO: COMO AJUDAR ===== -->
    <div class="mb-5 text-center">
        <span class="text-success fw-bold small text-uppercase letter-spacing-1">Solidariedade</span>
        <h2 class="h2 fw-bold text-dark mb-4">Como Você Pode Fazer a Diferença Hoje</h2>

        <div class="row g-4 text-start">
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-radius:14px;">
                    <div class="fs-1 text-success mb-2"><i class="fa-solid fa-house-chimney-window"></i></div>
                    <h5 class="fw-bold">Adote com Amor</h5>
                    <p class="text-muted small">Dê um lar permanente a um animal resgatado e transforme uma vida para sempre.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-radius:14px;">
                    <div class="fs-1 text-primary mb-2"><i class="fa-solid fa-share-nodes"></i></div>
                    <h5 class="fw-bold">Compartilhe</h5>
                    <p class="text-muted small">Divulgue os pets desaparecidos e os anúncios de adoção nas suas redes e grupos locais.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-radius:14px;">
                    <div class="fs-1 text-warning mb-2"><i class="fa-solid fa-bowl-food"></i></div>
                    <h5 class="fw-bold">Apoie Lares Temporários</h5>
                    <p class="text-muted small">Auxilie voluntários e ONGs com doação de rações, medicamentos e vermífugos.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-radius:14px;">
                    <div class="fs-1 text-danger mb-2"><i class="fa-solid fa-handshake-angle"></i></div>
                    <h5 class="fw-bold">Seja um Voluntário</h5>
                    <p class="text-muted small">Participe de mutirões de banho, feiras de adoção e resgates organizados.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
