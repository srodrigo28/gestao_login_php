<?php

declare(strict_types=1);

if (!function_exists('startAppSession')) {
    /**
     * Inicia sessão usando diretório local do projeto para evitar falha de permissão.
     */
    function startAppSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
        if (!is_dir($sessionDir) && !mkdir($sessionDir, 0777, true) && !is_dir($sessionDir)) {
            throw new RuntimeException("Nao foi possivel criar diretorio de sessao em {$sessionDir}");
        }

        session_save_path($sessionDir);
        session_start();
    }
}
