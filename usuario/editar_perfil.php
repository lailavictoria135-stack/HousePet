<?php
/**
 * HousePet — Editar Perfil do Usuário
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$baseUrl = '/housePet2';
$userId  = $_SESSION['user_id'];

// Busca dados atuais do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('danger', 'Usuário não encontrado.');
    redirect($baseUrl . '/auth/logout.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    } else {
        $nome           = sanitize($_POST['nome'] ?? '');
        $email          = sanitize($_POST['email'] ?? '');
        $senha_atual    = $_POST['senha_atual'] ?? '';
        $nova_senha     = $_POST['nova_senha'] ?? '';
        $confirma_senha = $_POST['confirma_senha'] ?? '';

        if (empty($nome)) {
            $errors[] = 'O nome completo é obrigatório.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um endereço de e-mail válido.';
        }

        // Verifica duplicidade de e-mail com outro usuário
        $stmtEmail = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmtEmail->execute([$email, $userId]);
        if ($stmtEmail->fetch()) {
            $errors[] = 'Este e-mail já está sendo utilizado por outra conta.';
        }

        // Se o usuário quer alterar a senha
        $alterarSenha = false;
        if (!empty($nova_senha)) {
            if (empty($senha_atual)) {
                $errors[] = 'Para definir uma nova senha, informe sua senha atual para confirmação.';
            } elseif (!password_verify($senha_atual, $user['senha'])) {
                $errors[] = 'A senha atual informada está incorreta.';
            } elseif (strlen($nova_senha) < 8) {
                $errors[] = 'A nova senha deve ter no mínimo 8 caracteres.';
            } elseif ($nova_senha !== $confirma_senha) {
                $errors[] = 'A confirmação da nova senha não coincide.';
            } else {
                $alterarSenha = true;
            }
        }

        if (empty($errors)) {
            if ($alterarSenha) {
                $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmtUp = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?");
                $stmtUp->execute([$nome, $email, $hash, $userId]);
            } else {
                $stmtUp = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
                $stmtUp->execute([$nome, $email, $userId]);
            }

            // Atualiza sessão
            $_SESSION['user_nome']  = $nome;
            $_SESSION['user_name']  = $nome;
            $_SESSION['user_email'] = $email;

            setFlashMessage('success', 'Seus dados de perfil foram atualizados com sucesso!');
            redirect($baseUrl . '/usuario/dashboard.php');
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Editar Perfil';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5" style="max-width:640px;">
    <div class="card shadow-sm border-0" style="border-radius:14px; overflow:hidden;">
        <div class="card-header bg-success text-white py-3">
            <h2 class="h4 mb-0"><i class="fa-solid fa-user-pen me-2"></i>Atualizar Dados do Perfil</h2>
        </div>
        <div class="card-body p-4">

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nome Completo *</label>
                    <input type="text" name="nome" class="form-control"
                           value="<?= htmlspecialchars($_POST['nome'] ?? $user['nome']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Endereço de E-mail *</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" required>
                </div>

                <hr class="my-4">
                <h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-key me-2"></i>Alterar Senha (opcional)</h5>
                <p class="small text-muted">Deixe os campos abaixo em branco caso não deseje alterar sua senha de acesso.</p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Senha Atual</label>
                    <input type="password" name="senha_atual" class="form-control" placeholder="Informe sua senha atual">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nova Senha</label>
                    <input type="password" name="nova_senha" class="form-control" placeholder="Mínimo de 8 caracteres">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirmar Nova Senha</label>
                    <input type="password" name="confirma_senha" class="form-control" placeholder="Repita a nova senha">
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?= $baseUrl ?>/usuario/dashboard.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Salvar Perfil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

