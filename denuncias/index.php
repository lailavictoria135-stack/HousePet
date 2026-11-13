<?php
/**
 * HousePet — Canal Informativo de Denúncias de Maus-Tratos
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$baseUrl = '/housePet2';

// Busca denúncias com status 'aprovada' para exibição estatística/informativa (SEM DADOS DO DENUNCIANTE)
$stmt = $pdo->query("
    SELECT id, tipo_ocorrencia, localizacao, data_ocorrencia, data_cadastro, status
    FROM denuncias
    WHERE status = 'aprovada'
    ORDER BY data_cadastro DESC
    LIMIT 6
");
$denunciasAprovadas = $stmt->fetchAll();

// Totalizadores anônimos para conscientização
$totalOcorrencias = (int)$pdo->query("SELECT COUNT(*) FROM denuncias WHERE status IN ('aprovada', 'em_analise')")->fetchColumn();

$pageTitle = 'Canal de Denúncias de Maus-Tratos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <!-- Banner de Alerta e Conscientização -->
    <div class="card border-0 text-white mb-5 shadow" style="border-radius:16px; background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark px-3 py-2 fw-bold mb-3 fs-6">
                        <i class="fa-solid fa-shield-cat me-1"></i> Proteção Animal &mdash; Lei 14.064/2020 (Lei Sansão)
                    </span>
                    <h1 class="display-6 fw-bold mb-3">Maus-tratos contra animais é crime com pena de reclusão!</h1>
                    <p class="lead mb-4" style="opacity: 0.95;">
                        O HousePet oferece um canal organizado para catalogar, evidenciar e encaminhar situações de abandono, violência e negligência contra cães, gatos e outros animais.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= $baseUrl ?>/denuncias/cadastrar.php" class="btn btn-warning btn-lg text-dark fw-bold">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Registrar Denúncia
                        </a>
                        <?php if (isLoggedIn()): ?>
                            <a href="<?= $baseUrl ?>/denuncias/minhas_denuncias.php" class="btn btn-outline-light btn-lg">
                                <i class="fa-solid fa-list-check me-2"></i> Acompanhar Minhas Denúncias
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                    <span style="font-size:7rem; opacity:0.9;">📢</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Aviso Legal Obrigatório -->
    <div class="alert alert-warning border-warning p-4 rounded-3 shadow-sm mb-5">
        <div class="d-flex align-items-start">
            <i class="fa-solid fa-triangle-exclamation text-warning fs-2 me-3 mt-1"></i>
            <div>
                <h4 class="h5 fw-bold text-dark mb-1">Aviso Importante e Responsabilidade Civil</h4>
                <p class="mb-2 text-secondary">
                    Esta plataforma tem caráter comunitário e de apoio técnico-documental à causa animal. <strong>O HousePet não substitui os canais oficiais de urgência e segurança pública.</strong> Em casos de flagrante de violência iminente ou animal agonizando, acione imediatamente as autoridades competentes.
                </p>
                <div class="row g-2 mt-2">
                    <div class="col-md-4">
                        <div class="p-2 bg-white rounded border text-center">
                            <strong>Polícia Militar:</strong> <span class="text-danger fw-bold">190</span> (Flagrantes)
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 bg-white rounded border text-center">
                            <strong>Disque Denúncia:</strong> <span class="text-danger fw-bold">181</span> (Anônimo)
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 bg-white rounded border text-center">
                            <strong>IBAMA:</strong> <span class="text-success fw-bold">0800 61 8080</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Como funciona a denúncia no HousePet -->
    <div class="mb-5">
        <h2 class="h3 fw-bold text-success mb-4 text-center">Como Funciona o Fluxo de Denúncia</h2>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4" style="border-radius:14px;">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:70px; height:70px; font-size:1.8rem; color:#2d8c4e;">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <h5 class="fw-bold">1. Registro Seguro</h5>
                    <p class="text-muted small">
                        Você descreve o ocorrido, local, data e anexa fotografias. Seus dados cadastrais pessoais ficam <strong>estritamente protegidos e confidenciais</strong>.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4" style="border-radius:14px;">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:70px; height:70px; font-size:1.8rem; color:#f39c12;">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <h5 class="fw-bold">2. Análise da Moderação</h5>
                    <p class="text-muted small">
                        Nossos moderadores verificam a consistência das informações, filtrando relatos duplicados ou inconsistentes antes de atualizar o status.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4" style="border-radius:14px;">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:70px; height:70px; font-size:1.8rem; color:#2980b9;">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <h5 class="fw-bold">3. Encaminhamento e Ação</h5>
                    <p class="text-muted small">
                        Casos catalogados servem como base para articulação com ONGs, resgatistas independentes e relatórios encaminhados aos órgãos ambientais.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Ocorrências recentes aprovadas (sem dados do denunciante) -->
    <?php if (!empty($denunciasAprovadas)): ?>
        <div class="card border-0 shadow-sm p-4" style="border-radius:14px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 fw-bold text-dark mb-0">
                    <i class="fa-solid fa-shield-heart text-success me-2"></i>Registros Recentes de Ocorrências Catalogadas
                </h3>
                <span class="badge bg-success-subtle text-success border border-success px-3 py-2">
                    <?= $totalOcorrencias ?> ocorrências em acompanhamento
                </span>
            </div>
            <p class="text-muted small mb-3">
                Para proteção da integridade dos voluntários, <strong>não são divulgados nomes ou e-mails dos denunciantes</strong>.
            </p>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Ocorrência</th>
                            <th>Localidade</th>
                            <th>Data do Fato</th>
                            <th>Registro</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($denunciasAprovadas as $den): ?>
                            <tr>
                                <td class="fw-semibold text-dark">
                                    <i class="fa-solid fa-flag text-danger me-1"></i>
                                    <?= htmlspecialchars($den['tipo_ocorrencia']) ?>
                                </td>
                                <td><i class="fa-solid fa-location-dot text-muted me-1"></i><?= htmlspecialchars($den['localizacao']) ?></td>
                                <td><?= $den['data_ocorrencia'] ? formatDate($den['data_ocorrencia']) : '—' ?></td>
                                <td><small class="text-muted"><?= formatDate($den['data_cadastro']) ?></small></td>
                                <td><span class="badge <?= getStatusClass($den['status']) ?>"><?= getStatusLabel($den['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

