<?php
declare(strict_types=1);

session_start();
$isLogged = isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']);
$studentHref = $isLogged ? 'home/home.php' : 'login-cad/cadastro.php';
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
        <header>
            <div class="logo">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h1>EduPortal</h1>
            <p>Plataforma de Estudos Online</p>
        </header>

        <section class="cards">
            <article class="card student">
                <div class="tag">Portal do Aluno</div>
                <h2>Area do Estudante</h2>
                <p>Acesse seus cursos, acompanhe atividades e evolua no seu aprendizado.</p>
                <ul>
                    <li><i class="fa-solid fa-circle-play"></i> Cursos e conteudos</li>
                    <li><i class="fa-regular fa-calendar"></i> Agenda semanal</li>
                    <li><i class="fa-solid fa-list-check"></i> Atividades e tarefas</li>
                    <li><i class="fa-solid fa-id-card"></i> Curriculo digital</li>
                </ul>
                <a class="btn primary" href="<?= htmlspecialchars($studentHref, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-right-to-bracket"></i> Entrar como Aluno</a>
                <a class="btn ghost" href="login-cad/cadastro.php"><i class="fa-solid fa-user-plus"></i> Criar conta gratis</a>
            </article>

            <article class="card admin">
                <div class="tag">Acesso Restrito</div>
                <h2>Administracao</h2>
                <p>Painel administrativo para gerenciar alunos, cursos e progresso geral.</p>
                <ul>
                    <li><i class="fa-solid fa-users"></i> Gerenciar alunos</li>
                    <li><i class="fa-solid fa-book"></i> Gerenciar cursos</li>
                    <li><i class="fa-solid fa-star"></i> Catalogo de habilidades</li>
                    <li><i class="fa-solid fa-chart-line"></i> Visao geral do sistema</li>
                </ul>
                <a class="btn primary blue" href="login-gestao/gestao.php"><i class="fa-solid fa-lock"></i> Acesso Administrativo</a>
                <small>Acesso restrito a administradores autorizados</small>
            </article>
        </section>

        <footer>© 2026 EduPortal - Todos os direitos reservados</footer>
    </main>
</body>
</html>
