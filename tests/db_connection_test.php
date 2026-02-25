<?php

declare(strict_types=1);

/**
 * Teste simples de conexão com MySQL via PDO usando variáveis do .env.
 * Execute com: php tests/db_connection_test.php
 */
function loadEnv(string $filePath): array
{
    if (!is_file($filePath)) {
        throw new RuntimeException("Arquivo .env não encontrado em: {$filePath}");
    }

    $vars = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $separatorPos = strpos($trimmed, '=');
        if ($separatorPos === false) {
            continue;
        }

        $key = trim(substr($trimmed, 0, $separatorPos));
        $value = trim(substr($trimmed, $separatorPos + 1));
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $value = rtrim($value, ';');

        $vars[$key] = $value;
    }

    return $vars;
}

function requireEnv(array $env, string $key): string
{
    if (!isset($env[$key]) || $env[$key] === '') {
        throw new RuntimeException("Variável '{$key}' ausente no .env");
    }

    return trim($env[$key]);
}

try {
    $rootDir = dirname(__DIR__);
    $env = loadEnv($rootDir . DIRECTORY_SEPARATOR . '.env');

    $host = requireEnv($env, 'host');
    $port = requireEnv($env, 'port');
    $dbName = requireEnv($env, 'dbname');
    $user = requireEnv($env, 'user');
    $password = $env['senha'] ?? ($env['password'] ?? '');

    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    $pdo->query('SELECT 1');
    echo "OK: conexão com banco estabelecida com sucesso." . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "ERRO: falha na conexão com banco. " . $e->getMessage() . PHP_EOL);
    exit(1);
}
