<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já logado, redireciona
if (isLoggedIn()) {
    redirect('/housePet2/usuario/dashboard.php');
}

$errors = [];
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valida CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $email_value = $email;

        if (empty($email)) {
            $errors['email'] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail inválido.';
        }

        if (empty($senha)) {
            $errors['senha'] = 'A senha é obrigatória.';
        }

        if (empty($errors)) {
            require_once __DIR__ . '/../config/database.php';

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Login bem-sucedido
                loginUser($usuario);
                $_SESSION['user_name'] = $usuario['nome']; // compatibilidade

                setFlashMessage('success', 'Bem-vindo(a), ' . htmlspecialchars($usuario['nome']) . '!');
                redirect('/housePet2/usuario/dashboard.php');
            } else {
                $errors[] = 'E-mail ou senha incorretos. Verifique suas credenciais.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$flash = getFlashMessage();
$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <a href="/housePet2/">🐾 HousePet</a>
        </div>
        <h1 class="auth-title">Entrar na sua conta</h1>
        <p class="auth-subtitle">Acesse para registrar denúncias e acompanhar adoções.</p>

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors) && isset($errors[0])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errors[0]) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="auth-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                <label for="email"><i class="fas fa-envelope"></i> E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email_value) ?>"
                    placeholder="seu@email.com"
                    autocomplete="email"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['senha']) ? 'has-error' : '' ?>">
                <label for="senha"><i class="fas fa-lock"></i> Senha</label>
                <div class="input-with-toggle">
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Sua senha"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-password" aria-label="Mostrar/ocultar senha">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['senha'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['senha']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div class="auth-links">
            <p>Não tem conta? <a href="/housePet2/auth/cadastro.php">Cadastre-se gratuitamente</a></p>
            <p><a href="/housePet2/">← Voltar ao início</a></p>
        </div>
    </div>
</main>

<style>
.auth-wrapper {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    background: linear-gradient(135deg, #e8f5e9 0%, #f9fbe7 100%);
}

.auth-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(45,140,78,0.12);
    padding: 2.5rem 2rem;
    width: 100%;
    max-width: 420px;
}

.auth-logo {
    text-align: center;
    margin-bottom: 1.5rem;
}

.auth-logo a {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2d8c4e;
    text-decoration: none;
}

.auth-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a5c32;
    text-align: center;
    margin: 0 0 0.5rem;
}

.auth-subtitle {
    color: #666;
    text-align: center;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
}

.auth-form .form-group {
    margin-bottom: 1.2rem;
}

.auth-form label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.4rem;
    font-size: 0.9rem;
}

.auth-form label i {
    color: #2d8c4e;
    margin-right: 0.3rem;
}

.auth-form input[type="email"],
.auth-form input[type="password"],
.auth-form input[type="text"] {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1.5px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.auth-form input:focus {
    outline: none;
    border-color: #2d8c4e;
    box-shadow: 0 0 0 3px rgba(45,140,78,0.1);
}

.has-error input {
    border-color: #e53935;
}

.field-error {
    color: #e53935;
    font-size: 0.82rem;
    margin-top: 0.25rem;
    display: block;
}

.input-with-toggle {
    position: relative;
}

.input-with-toggle input {
    padding-right: 2.8rem;
}

.toggle-password {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #888;
    padding: 0;
    font-size: 0.95rem;
}

.toggle-password:hover {
    color: #2d8c4e;
}

.btn-full {
    width: 100%;
    padding: 0.85rem;
    font-size: 1rem;
    margin-top: 0.5rem;
}

.auth-links {
    text-align: center;
    margin-top: 1.5rem;
    font-size: 0.9rem;
    color: #666;
}

.auth-links p {
    margin: 0.3rem 0;
}

.auth-links a {
    color: #2d8c4e;
    text-decoration: none;
    font-weight: 600;
}

.auth-links a:hover {
    text-decoration: underline;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.alert-danger {
    background: #fdecea;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

.alert-success {
    background: #e8f5e9;
    color: #1b5e20;
    border: 1px solid #a5d6a7;
}
</style>

<script>
document.querySelector('.toggle-password')?.addEventListener('click', function() {
    const input = document.getElementById('senha');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
