<?php
/**
 * HousePet — Gestão de Usuários (Administração)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$baseUrl = '/housePet2';
$busca   = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

// Ações POST (Alteração de Status ou Perfil)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Token de segurança inválido.');
    } else {
        $acao      = $_POST['acao'] ?? '';
        $targetId  = (int)($_POST['usuario_id'] ?? 0);

        if ($targetId > 0) {
            // Evita auto-modificação perigosa
            if ($targetId === (int)$_SESSION['user_id'] && in_array($acao, ['toggle_ativo', 'mudar_tipo'])) {
                setFlashMessage('warning', 'Você não pode desativar ou alterar o papel da sua própria conta em uso.');
                redirect($baseUrl . '/admin/usuarios.php');
            }

            if ($acao === 'toggle_ativo') {
                $stmtCheck = $pdo->prepare("SELECT ativo FROM usuarios WHERE id = ?");
                $stmtCheck->execute([$targetId]);
                $userTarget = $stmtCheck->fetch();

                if ($userTarget) {
                    $novoAtivo = $userTarget['ativo'] ? 0 : 1;
                    $stmtUp = $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
                    $stmtUp->execute([$novoAtivo, $targetId]);
                    setFlashMessage('success', 'Status da conta de usuário alterado com sucesso.');
                }
            } elseif ($acao === 'mudar_tipo') {
                if (!isAdmin()) {
                    setFlashMessage('danger', 'Apenas administradores plenos podem alterar o tipo de perfil de usuários.');
                } else {
                    $novoTipo = $_POST['tipo_usuario'] ?? 'usuario';
                    if (in_array($novoTipo, ['usuario', 'moderador', 'admin'], true)) {
                        $stmtUp = $pdo->prepare("UPDATE usuarios SET tipo_usuario = ? WHERE id = ?");
                        $stmtUp->execute([$novoTipo, $targetId]);
                        setFlashMessage('success', 'Papel do usuário atualizado para ' . ucfirst($novoTipo) . '.');
                    }
                }
            }
        }
    }
    redirect($baseUrl . '/admin/usuarios.php?busca=' . urlencode($busca) . '&page=' . $page);
}

// Filtro e Busca
$where  = ['1=1'];
$params = [];

if ($busca !== '') {
    $where[]         = '(nome LIKE :busca OR email LIKE :busca)';
    $params['busca'] = '%' . $busca . '%';
}

$whereSQL = implode(' AND ', $where);

// Contagem total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE $whereSQL");
$stmtCount->execute($params);
$totalUsuarios = (int)$stmtCount->fetchColumn();
$totalPages    = max(1, (int)ceil($totalUsuarios / $perPage));

// Lista de usuários
$sql = "
    SELECT id, nome, email, tipo_usuario, ativo, data_cadastro
    FROM usuarios
    WHERE $whereSQL
    ORDER BY id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(":$k", $v);
}
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
$pageTitle = 'Gestão de Usuários — Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h2 fw-bold text-dark mb-1">
                <i class="fa-solid fa-users-gear text-primary me-2"></i>Gestão de Usuários
            </h1>
            <p class="text-muted mb-0">Controle de acessos, ativação de contas e atribuição de permissões.</p>
        </div>
        <div>
            <a href="<?= $baseUrl ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Voltar ao Painel
            </a>
        </div>
    </div>

    <!-- Barra de Busca -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="busca" class="form-control"
                               placeholder="Buscar usuário por nome ou e-mail..."
                               value="<?= htmlspecialchars($busca) ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Pesquisar</button>
                    <?php if ($busca !== ''): ?>
                        <a href="<?= $baseUrl ?>/admin/usuarios.php" class="btn btn-outline-secondary" title="Limpar">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Usuários -->
    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Papel / Perfil</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                        <th class="text-end">Ações de Gestão</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Nenhum usuário encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td class="fw-bold">#<?= $u['id'] ?></td>
                                <td class="fw-semibold text-dark">
                                    <i class="fa-solid fa-circle-user text-secondary me-1"></i>
                                    <?= htmlspecialchars($u['nome']) ?>
                                    <?php if ($u['id'] === (int)$_SESSION['user_id']): ?>
                                        <span class="badge bg-info-subtle text-info border ms-1">Você</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php if (isAdmin()): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="acao" value="mudar_tipo">
                                            <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <select name="tipo_usuario" class="form-select form-select-sm d-inline-block w-auto"
                                                    onchange="this.form.submit()"
                                                    <?= ($u['id'] === (int)$_SESSION['user_id']) ? 'disabled' : '' ?>>
                                                <option value="usuario" <?= $u['tipo_usuario'] === 'usuario' ? 'selected' : '' ?>>Usuário Comum</option>
                                                <option value="moderador" <?= $u['tipo_usuario'] === 'moderador' ? 'selected' : '' ?>>Moderador</option>
                                                <option value="admin" <?= $u['tipo_usuario'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                            </select>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= ucfirst(htmlspecialchars($u['tipo_usuario'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['ativo']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Desativado</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= formatDate($u['data_cadastro']) ?></small></td>
                                <td class="text-end">
                                    <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Alterar o status de ativação desta conta?');">
                                            <input type="hidden" name="acao" value="toggle_ativo">
                                            <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <?php if ($u['ativo']): ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Desativar Acesso">
                                                    <i class="fa-solid fa-ban me-1"></i> Desativar
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Reativar Acesso">
                                                    <i class="fa-solid fa-check me-1"></i> Reativar
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white py-3">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;</a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">&raquo;</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

