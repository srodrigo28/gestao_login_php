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
// Ajuste: Mudamos o nome da variável de studentHref para lojistaHref para fazer sentido com o negócio
$lojistaHref = $isLogged ? 'home/home.php' : 'login-cad/cadastro.php';
// Ajuste: Fallback de 'Aluno' para 'Lojista'
$currentUserName = $isLogged ? (string)($_SESSION['auth_user']['name'] ?? 'Lojista') : '';
$users = [];
$usersError = '';

try {
    $pdo = dbConnect();
    $sql = 'SELECT id, name, email, phone, city, state, created_at FROM users ORDER BY id DESC';
    $users = $pdo->query($sql)->fetchAll() ?: [];
} catch (Throwable $e) {
    $usersError = 'Não foi possível carregar os usuários cadastrados.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Gestor | Vendas e Estoque</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="login/login.css">

    <style>
    header {
        margin-top: 100px !important;
        margin-bottom: 100px !important;
    }
    footer{
        margin-top: 350px;
    }
</style>
</head>
<body>
    <main class="landing">
        <header class="topbar">
            <div class="brand">
                <div class="logo">
                    <i class="fa-solid fa-store"></i> 
                </div>
                <div>
                    <h1>Portal Gestor</h1>
                    <p>Gestão de Vendas e Estoque</p>
                </div>
            </div>
        </header>

        <section class="cards">
            <article class="card student">
                <div class="tag">Portal do Lojista</div>
                <h2>Área do Lojista</h2>
                <p>Acesse uma experiência simples e organizada para o seu negócio.</p>
                <ul>
                    <li><i class="fa-solid fa-box-open"></i> Cadastro e gestão de produtos</li>
                    <li><i class="fa-solid fa-users"></i> Gestão de clientes e prospecção</li>
                    <li><i class="fa-solid fa-list-check"></i> Controle de tarefas e entregas</li>
                    <li><i class="fa-solid fa-id-card"></i> Administração da equipe</li>
                </ul>
                <a class="btn primary" href="<?= h($lojistaHref) ?>"><i class="fa-solid fa-right-to-bracket"></i> Acessar Minha Loja</a>
                <a class="btn ghost" href="login-cad/cadastro.php"><i class="fa-solid fa-user-plus"></i> Criar conta grátis</a>
            </article>

            <article class="card admin">
                <div class="tag">Acesso Gestor</div>
                <h2>Administração Central</h2>
                <p>Painel de controle corporativo e métricas.</p>
                <ul>
                    <li><i class="fa-solid fa-user-shield"></i> Controle de usuários</li>
                    <li><i class="fa-solid fa-database"></i> Gerenciamento de backups</li>
                    <li><i class="fa-solid fa-star"></i> Monitoramento de lojistas</li>
                    <li><i class="fa-solid fa-chart-line"></i> Visão geral operacional e relatórios</li>
                </ul>
                <a class="btn primary blue" href="login-gestao/gestao.php"><i class="fa-solid fa-lock"></i> Acesso Administrativo</a>
                <small>Acesso restrito a administradores autorizados</small>
            </article>
        </section>

        <footer>&copy; 2026 Portal Gestor - Todos os direitos reservados</footer>
    </main>
</body>
</html>