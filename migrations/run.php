<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = dbConnect();
    $migrationFiles = glob(__DIR__ . '/*.sql');

    if ($migrationFiles === false || count($migrationFiles) === 0) {
        throw new RuntimeException('Nenhuma migration .sql encontrada.');
    }

    sort($migrationFiles);

    foreach ($migrationFiles as $file) {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("Falha ao ler migration: {$file}");
        }

        $pdo->exec($sql);
        echo 'Aplicada: ' . basename($file) . PHP_EOL;
    }

    echo 'Migrations finalizadas com sucesso.' . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro ao executar migrations: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
