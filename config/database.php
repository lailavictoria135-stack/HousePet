<?php
/**
 * HousePet — Conexão PDO com MySQL
 */

// Função auxiliar para carregar variáveis do arquivo .env
if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $filePath): void {
        if (!file_exists($filePath)) {
            return;
        }
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                if (!isset($_ENV[$k])) {
                    $_ENV[$k] = $v;
                    putenv("{$k}={$v}");
                }
            }
        }
    }
}

// Carrega .env da raiz do projeto
loadEnvFile(dirname(__DIR__) . '/.env');

$host     = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$dbname   = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'housepet_db');
$username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? 'alunoifro');

$tentativas = [
    $password,
    'alunoifro',
    ''
];

$pdo = null;
$ultimoErro = null;

foreach (array_unique($tentativas) as $senhaTentativa) {
    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $senhaTentativa,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        break; // Conexão bem-sucedida!
    } catch (PDOException $e) {
        $ultimoErro = $e;
    }
}

if (!$pdo) {
    error_log('PDO connection error: ' . ($ultimoErro ? $ultimoErro->getMessage() : 'Desconhecido'));
    die('Não foi possível conectar ao banco de dados: ' . ($ultimoErro ? htmlspecialchars($ultimoErro->getMessage()) : ''));
}
