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
$form = ['nome' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    } else {
        $nome  = sanitize($_POST['nome'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $senha_confirma = $_POST['senha_confirma'] ?? '';

        $form['nome']  = $nome;
        $form['email'] = $email;

        // Validações
        if (empty($nome)) {
            $errors['nome'] = 'O nome é obrigatório.';
        } elseif (strlen($nome) < 2) {
            $errors['nome'] = 'O nome deve ter pelo menos 2 caracteres.';
        }

        if (empty($email)) {
            $errors['email'] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Digite um e-mail válido.';
        }

        if (empty($senha)) {
            $errors['senha'] = 'A senha é obrigatória.';
        } elseif (strlen($senha) < 8) {
            $errors['senha'] = 'A senha deve ter pelo menos 8 caracteres.';
        }

        if (empty($senha_confirma)) {
            $errors['senha_confirma'] = 'Confirme sua senha.';
        } elseif ($senha !== $senha_confirma) {
            $errors['senha_confirma'] = 'As senhas não coincidem.';
        }

        if (empty($errors)) {
            require_once __DIR__ . '/../config/database.php';

            // Verifica email duplicado
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors['email'] = 'Este e-mail já está cadastrado. Tente fazer login.';
            } else {
                // Insere usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo) VALUES (?, ?, ?, 'usuario', 1)"
                );
                $stmt->execute([$nome, $email, $senha_hash]);
                $novo_id = $pdo->lastInsertId();

                // Login automático
                $novoUsuario = [
                    'id'           => $novo_id,
                    'nome'         => $nome,
                    'email'        => $email,
                    'tipo_usuario' => 'usuario'
                ];
                loginUser($novoUsuario);
                $_SESSION['user_name'] = $nome;

                setFlashMessage('success', 'Conta criada com sucesso! Bem-vindo(a), ' . htmlspecialchars($nome) . '!');
                redirect('/housePet2/usuario/dashboard.php');
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$pageTitle = 'Criar Conta';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="auth-wrapper">
    <div class="auth-card auth-card--wide">
        <div class="auth-logo">
            <a href="/housePet2/">🐾 HousePet</a>
        </div>
        <h1 class="auth-title">Criar conta gratuita</h1>
        <p class="auth-subtitle">Junte-se à nossa comunidade de proteção animal.</p>

        <?php if (!empty($errors) && isset($errors[0])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors[0]) ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="auth-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group <?= isset($errors['nome']) ? 'has-error' : '' ?>">
                <label for="nome"><i class="fas fa-user"></i> Nome completo</label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    value="<?= htmlspecialchars($form['nome']) ?>"
                    placeholder="Seu nome"
                    autocomplete="name"
                    required
                >
                <?php if (isset($errors['nome'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['nome']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                <label for="email"><i class="fas fa-envelope"></i> E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($form['email']) ?>"
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
                        placeholder="Mínimo 8 caracteres"
                        autocomplete="new-password"
                        required
                    >
                    <button type="button" class="toggle-password" data-target="senha" aria-label="Mostrar senha">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="pwd-strength"></div>
                <?php if (isset($errors['senha'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['senha']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['senha_confirma']) ? 'has-error' : '' ?>">
                <label for="senha_confirma"><i class="fas fa-lock"></i> Confirmar senha</label>
                <div class="input-with-toggle">
                    <input
                        type="password"
                        id="senha_confirma"
                        name="senha_confirma"
                        placeholder="Repita a senha"
                        autocomplete="new-password"
                        required
                    >
                    <button type="button" class="toggle-password" data-target="senha_confirma" aria-label="Mostrar senha">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['senha_confirma'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['senha_confirma']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-terms">
                <p>Ao criar sua conta, você concorda com nossa política de proteção de dados. Seus dados são usados exclusivamente para funcionamento da plataforma.</p>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-user-plus"></i> Criar conta
            </button>
        </form>

        <div class="auth-links">
            <p>Já tem conta? <a href="/housePet2/auth/login.php">Faça login</a></p>
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

.auth-card--wide {
    max-width: 480px;
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

.toggle-password:hover { color: #2d8c4e; }

.password-strength {
    height: 4px;
    border-radius: 2px;
    margin-top: 6px;
    transition: all 0.3s;
}

.form-terms {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 1rem;
    font-size: 0.82rem;
    color: #666;
}

.btn-full {
    width: 100%;
    padding: 0.85rem;
    font-size: 1rem;
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

.auth-links a:hover { text-decoration: underline; }

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
</style>

<script>
// Toggle visibilidade de senha
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function() {
        const target = document.getElementById(this.dataset.target);
        const icon = this.querySelector('i');
        if (target.type === 'password') {
            target.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            target.type = 'password';
            icon.className = 'fas fa-eye';
        }
    });
});

// Força da senha
const senhaInput = document.getElementById('senha');
const pwdStrength = document.getElementById('pwd-strength');
senhaInput?.addEventListener('input', function() {
    const val = this.value;
    let strength = 0;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const colors = ['', '#e53935', '#ff9800', '#fdd835', '#2d8c4e'];
    const widths = ['0%', '25%', '50%', '75%', '100%'];
    pwdStrength.style.background = colors[strength] || '#ccc';
    pwdStrength.style.width = widths[strength];
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
