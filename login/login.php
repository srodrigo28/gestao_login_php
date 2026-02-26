<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

startAppSession();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatDateTime(?string $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }

    return date('d/m/Y H:i', $timestamp);
}

$isLogged = isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']);
$studentHref = $isLogged ? 'home/home.php' : 'login-cad/cadastro.php';
$currentUserName = $isLogged ? (string)($_SESSION['auth_user']['name'] ?? 'Aluno') : '';
$users = [];
$usersError = '';

try {
    $pdo = dbConnect();
    $sql = 'SELECT id, name, email, phone, city, state, created_at FROM users ORDER BY id DESC';
    $users = $pdo->query($sql)->fetchAll() ?: [];
} catch (Throwable $e) {
    $usersError = 'Nao foi possivel carregar os usuarios cadastrados.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduPortal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="login/login.css">
</head>
<body>
    <main class="landing">
        <header class="topbar">
            <div class="brand">
                <div class="logo">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <h1>EduPortal</h1>
                    <p>Plataforma de estudos e gestao academica</p>
                </div>
            </div>

            <div class="status-box">
                <span>Total de alunos</span>
                <strong><?= count($users) ?></strong>
                <?php if ($isLogged): ?>
                    <small>Logado como <?= h($currentUserName) ?></small>
                <?php else: ?>
                    <small>Acesso de visitante</small>
                <?php endif; ?>
            </div>
        </header>

        <section class="cards">
            <article class="card student">
                <div class="tag">Portal do Aluno</div>
                <h2>Area do Estudante</h2>
                <p>Acesse cursos, agenda e atividades em uma experiencia simples e organizada.</p>
                <ul>
                    <li><i class="fa-solid fa-circle-play"></i> Trilhas de estudo personalizadas</li>
                    <li><i class="fa-regular fa-calendar"></i> Agenda semanal e lembretes</li>
                    <li><i class="fa-solid fa-list-check"></i> Gestao de tarefas e entregas</li>
                    <li><i class="fa-solid fa-id-card"></i> Perfil academico completo</li>
                </ul>
                <a class="btn primary" href="<?= h($studentHref) ?>"><i class="fa-solid fa-right-to-bracket"></i> Entrar como Aluno</a>
                <a class="btn ghost" href="login-cad/cadastro.php"><i class="fa-solid fa-user-plus"></i> Criar conta gratis</a>
            </article>

            <article class="card admin">
                <div class="tag">Acesso Restrito</div>
                <h2>Administracao</h2>
                <p>Painel administrativo para gerenciar alunos, cursos e indicadores da plataforma.</p>
                <ul>
                    <li><i class="fa-solid fa-users"></i> Controle de usuarios</li>
                    <li><i class="fa-solid fa-book"></i> Catalogo de cursos</li>
                    <li><i class="fa-solid fa-star"></i> Habilidades e progresso</li>
                    <li><i class="fa-solid fa-chart-line"></i> Visao geral operacional</li>
                </ul>
                <a class="btn primary blue" href="login-gestao/gestao.php"><i class="fa-solid fa-lock"></i> Acesso Administrativo</a>
                <small>Acesso restrito a administradores autorizados</small>
            </article>
        </section>

        <section class="users-section">
            <header class="users-header">
                <h3>Usuarios cadastrados</h3>
                <p>Lista em tempo real dos registros na base.</p>
            </header>

            <?php if ($usersError !== ''): ?>
                <div class="users-alert error"><?= h($usersError) ?></div>
            <?php elseif (count($users) === 0): ?>
                <div class="users-alert empty">Nenhum usuario cadastrado ainda.</div>
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
                                <th>Cadastro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php $location = trim(((string)($user['city'] ?? '')) . '/' . ((string)($user['state'] ?? '')), '/'); ?>
                                <tr>
                                    <td><?= (int)$user['id'] ?></td>
                                    <td><?= h((string)$user['name']) ?></td>
                                    <td><?= h((string)$user['email']) ?></td>
                                    <td><?= h((string)$user['phone']) ?></td>
                                    <td><?= h($location !== '' ? $location : '-') ?></td>
                                    <td><?= h(formatDateTime((string)($user['created_at'] ?? ''))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <footer>&copy; 2026 EduPortal - Todos os direitos reservados</footer>
    </main>
</body>
</html>