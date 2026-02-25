<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

if (!function_exists('dbConnect')) {
    /**
     * Abre conexão PDO com MySQL usando dados do .env na raiz do projeto.
     */
    function dbConnect(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $env = loadEnvFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
        $host = $env['host'] ?? '';
        $port = $env['port'] ?? '3306';
        $dbName = $env['dbname'] ?? '';
        $user = $env['user'] ?? '';
        $password = $env['senha'] ?? ($env['password'] ?? '');

        if ($host === '' || $dbName === '' || $user === '') {
            throw new RuntimeException('Configuração incompleta no .env (host, dbname e user são obrigatórios).');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        return $pdo;
    }
}
