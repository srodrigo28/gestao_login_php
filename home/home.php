<?php
declare(strict_types=1);

session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$user = $_SESSION['auth_user'] ?? null;
if (!is_array($user)) {
    header('Location: ../index.php');
    exit;
}

$name = (string)($user['name'] ?? 'Aluno');
$email = (string)($user['email'] ?? 'email@exemplo.com');
$initials = strtoupper(substr($name, 0, 1));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | EduPortal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="./home.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-badge"><i class="fa-solid fa-graduation-cap"></i></div>
                <strong>EduPortal</strong>
            </div>

            <small class="group-title">MEU ESPACO</small>
            <a class="menu-item active" href="#"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a>

            <small class="group-title">ESTUDOS</small>
            <a class="menu-item" href="#"><i class="fa-solid fa-list-check"></i> Atividades</a>
            <a class="menu-item" href="#"><i class="fa-regular fa-calendar"></i> Agenda Semanal</a>
            <a class="menu-item" href="#"><i class="fa-solid fa-circle-play"></i> Cursos</a>
            <a class="menu-item" href="#"><i class="fa-solid fa-id-card"></i> Curriculo</a>

            <div class="profile">
                <div class="avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                <div>
                    <strong><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></strong>
                    <p><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <a class="logout" href="?logout=1"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
            </div>
        </aside>

        <main class="content">
            <header class="content-header">
                <h1>Dashboard</h1>
                <span class="role">Aluno</span>
            </header>

            <section class="hero">
                <p>Boa tarde <span>👋</span></p>
                <h2><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h2>
                <h3>Continue seu progresso. Voce esta indo muito bem!</h3>
                <div class="hero-actions">
                    <a href="#"><i class="fa-regular fa-calendar"></i> Ver Agenda</a>
                    <a href="#"><i class="fa-solid fa-play"></i> Meus Cursos</a>
                </div>
            </section>

            <section class="stats">
                <article><span>Tarefas Pendentes</span><strong>0</strong></article>
                <article><span>Tarefas Feitas</span><strong>0</strong></article>
                <article><span>Matriculas</span><strong>3</strong></article>
                <article><span>Habilidades</span><strong>2</strong></article>
            </section>

            <section class="grid">
                <article class="panel">
                    <header>
                        <h4>Proximas Atividades</h4>
                        <a href="#">Ver todas</a>
                    </header>
                    <div class="empty">
                        <i class="fa-solid fa-list-check"></i>
                        <p>Nenhuma atividade ainda.</p>
                        <a href="#">Adicionar atividade →</a>
                    </div>
                </article>

                <article class="panel">
                    <header>
                        <h4>Meus Cursos</h4>
                        <a href="#">Ver todos</a>
                    </header>
                    <div class="course">
                        <div><p>Banco de Dados SQL</p><span>0%</span></div>
                        <b><i style="width: 0%"></i></b>
                    </div>
                    <div class="course">
                        <div><p>Git e GitHub</p><span>0%</span></div>
                        <b><i style="width: 0%"></i></b>
                    </div>
                    <div class="course">
                        <div><p>Python do Zero</p><span>50%</span></div>
                        <b><i style="width: 50%"></i></b>
                    </div>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
