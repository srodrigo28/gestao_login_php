<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

startAppSession();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fmt(?string $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    $time = strtotime($value);
    return $time ? date('d/m/Y H:i', $time) : '-';
}

$users = [];
$error = '';

try {
    $pdo = dbConnect();
    $users = $pdo->query('SELECT id, name, email, phone, city, state, created_at FROM users ORDER BY id DESC')->fetchAll() ?: [];
} catch (Throwable $e) {
    $error = 'Nao foi possivel carregar os usuarios.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestao de Usuarios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="gestao.css">
</head>
<body>
    <main class="panel">
        <header class="panel-header">
            <div>
                <h1>Gestao de Usuarios</h1>
                <p>Visualize os cadastros mais recentes da plataforma.</p>
            </div>
            <div class="header-actions">
                <span class="counter"><i class="fa-solid fa-users"></i> <?= count($users) ?> usuarios</span>
                <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
            </div>
        </header>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= h($error) ?></div>
        <?php elseif (count($users) === 0): ?>
            <div class="alert empty">Nenhum usuario cadastrado.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>Cidade/UF</th>
                            <th>Cadastrado em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php $loc = trim(((string)($user['city'] ?? '')) . '/' . ((string)($user['state'] ?? '')), '/'); ?>
                            <tr>
                                <td>#<?= (int)$user['id'] ?></td>
                                <td><?= h((string)$user['name']) ?></td>
                                <td><?= h((string)$user['email']) ?></td>
                                <td><?= h((string)$user['phone']) ?></td>
                                <td><?= h($loc !== '' ? $loc : '-') ?></td>
                                <td><?= h(fmt((string)($user['created_at'] ?? ''))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>