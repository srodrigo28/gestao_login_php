<?php

declare(strict_types=1);

if (!function_exists('loadEnvFile')) {
    /**
     * Carrega variáveis de um arquivo .env simples (chave=valor).
     */
    function loadEnvFile(string $envPath): array
    {
        if (!is_file($envPath)) {
            throw new RuntimeException("Arquivo .env não encontrado em {$envPath}");
        }

        $vars = [];
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Não foi possível ler o .env em {$envPath}");
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $separatorPos = strpos($line, '=');
            if ($separatorPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separatorPos));
            $value = trim(substr($line, $separatorPos + 1));
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $value = rtrim($value, ';');

            if ($key !== '') {
                $vars[$key] = $value;
            }
        }

        return $vars;
    }
}
