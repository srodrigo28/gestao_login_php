<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        if (mb_strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'AL';
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$sessionUser = $_SESSION['auth_user'] ?? null;
if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
    header('Location: ../index.php');
    exit;
}

$userId = (int)$sessionUser['id'];
if ($userId <= 0) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$errors = [];

try {
    $pdo = dbConnect();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_activity') {
        $title = trim((string)($_POST['activity_title'] ?? ''));
        $dueDate = trim((string)($_POST['activity_due_date'] ?? ''));

        if (mb_strlen($title) < 3) {
            $errors[] = 'A atividade deve ter pelo menos 3 caracteres.';
        }

        if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $errors[] = 'Data invalida para a atividade.';
        }

        if (count($errors) === 0) {
            $stmt = $pdo->prepare('INSERT INTO activities (user_id, title, due_date, status) VALUES (:user_id, :title, :due_date, :status)');
            $stmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':due_date' => $dueDate !== '' ? $dueDate : null,
                ':status' => 'pending',
            ]);

            header('Location: home.php');
            exit;
        }
    }

    if (isset($_GET['done'])) {
        $activityId = (int)$_GET['done'];
        if ($activityId > 0) {
            $stmt = $pdo->prepare('UPDATE activities SET status = :status WHERE id = :id AND user_id = :user_id');
            $stmt->execute([
                ':status' => 'done',
                ':id' => $activityId,
                ':user_id' => $userId,
            ]);
        }
        header('Location: home.php');
        exit;
    }

    $userStmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $dbUser = $userStmt->fetch();

    if (!is_array($dbUser)) {
        session_destroy();
        header('Location: ../index.php');
        exit;
    }

    $_SESSION['auth_user']['name'] = (string)$dbUser['name'];
    $_SESSION['auth_user']['email'] = (string)$dbUser['email'];

    $stats = [
        'pending' => 0,
        'done' => 0,
        'enrollments' => 0,
        'skills' => 0,
    ];

    $taskStmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS done
         FROM activities
         WHERE user_id = :user_id"
    );
    $taskStmt->execute([':user_id' => $userId]);
    $taskRow = $taskStmt->fetch();
    if (is_array($taskRow)) {
        $stats['pending'] = (int)($taskRow['pending'] ?? 0);
        $stats['done'] = (int)($taskRow['done'] ?? 0);
    }

    $enrollStmt = $pdo->prepare('SELECT COUNT(*) FROM user_courses WHERE user_id = :user_id');
    $enrollStmt->execute([':user_id' => $userId]);
    $stats['enrollments'] = (int)$enrollStmt->fetchColumn();

    $skillStmt = $pdo->prepare('SELECT COUNT(*) FROM user_skills WHERE user_id = :user_id');
    $skillStmt->execute([':user_id' => $userId]);
    $stats['skills'] = (int)$skillStmt->fetchColumn();

    $activitiesStmt = $pdo->prepare(
        "SELECT id, title, status, due_date
         FROM activities
         WHERE user_id = :user_id
         ORDER BY
            CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC,
            CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC,
            due_date ASC,
            id DESC
         LIMIT 8"
    );
    $activitiesStmt->execute([':user_id' => $userId]);
    $activities = $activitiesStmt->fetchAll() ?: [];

    $coursesStmt = $pdo->prepare(
        "SELECT c.name, uc.progress
         FROM user_courses uc
         INNER JOIN courses c ON c.id = uc.course_id
         WHERE uc.user_id = :user_id
         ORDER BY c.id ASC"
    );
    $coursesStmt->execute([':user_id' => $userId]);
    $courses = $coursesStmt->fetchAll() ?: [];
} catch (Throwable $e) {
    $errors[] = 'Nao foi possivel carregar dados do dashboard. Execute as migrations em migrations/run.php.';
    $dbUser = [
        'name' => (string)($sessionUser['name'] ?? 'Aluno'),
        'email' => (string)($sessionUser['email'] ?? 'email@exemplo.com'),
    ];
    $stats = [
        'pending' => 0,
        'done' => 0,
        'enrollments' => 0,
        'skills' => 0,
    ];
    $activities = [];
    $courses = [];
}

$name = (string)($dbUser['name'] ?? 'Aluno');
$email = (string)($dbUser['email'] ?? 'email@exemplo.com');
$initials = initials($name);
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
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
                <div class="avatar"><?= h($initials) ?></div>
                <div>
                    <strong><?= h($name) ?></strong>
                    <p><?= h($email) ?></p>
                </div>
                <a class="logout" href="?logout=1"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
            </div>
        </aside>

        <main class="content">
            <header class="content-header">
                <h1>Dashboard</h1>
                <span class="role">Aluno</span>
            </header>

            <?php if (count($errors) > 0): ?>
                <div class="top-error"><?= h(implode(' ', $errors)) ?></div>
            <?php endif; ?>

            <section class="hero">
                <p><?= h($greeting) ?> <span>&#128075;</span></p>
                <h2><?= h($name) ?></h2>
                <h3>Continue seu progresso. Voce esta indo muito bem!</h3>
                <div class="hero-actions">
                    <a href="#"><i class="fa-regular fa-calendar"></i> Ver Agenda</a>
                    <a href="#"><i class="fa-solid fa-play"></i> Meus Cursos</a>
                </div>
            </section>

            <section class="stats">
                <article><span>Tarefas Pendentes</span><strong><?= (int)$stats['pending'] ?></strong></article>
                <article><span>Tarefas Feitas</span><strong><?= (int)$stats['done'] ?></strong></article>
                <article><span>Matriculas</span><strong><?= (int)$stats['enrollments'] ?></strong></article>
                <article><span>Habilidades</span><strong><?= (int)$stats['skills'] ?></strong></article>
            </section>

            <section class="grid">
                <article class="panel">
                    <header>
                        <h4>Proximas Atividades</h4>
                        <a href="#">Ver todas</a>
                    </header>

                    <div class="panel-body">
                        <form class="activity-form" method="post">
                            <input type="hidden" name="action" value="add_activity">
                            <input type="text" name="activity_title" placeholder="Nova atividade (ex: Revisar SQL)" required>
                            <input type="date" name="activity_due_date">
                            <button type="submit">Adicionar</button>
                        </form>

                        <?php if (count($activities) === 0): ?>
                            <div class="empty">
                                <i class="fa-solid fa-list-check"></i>
                                <p>Nenhuma atividade ainda.</p>
                                <a href="#">Adicione sua primeira atividade.</a>
                            </div>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($activities as $activity): ?>
                                    <?php
                                    $isDone = (string)$activity['status'] === 'done';
                                    $dueDate = $activity['due_date'] ? date('d/m/Y', strtotime((string)$activity['due_date'])) : 'Sem data';
                                    ?>
                                    <div class="activity-item">
                                        <div>
                                            <p><?= h((string)$activity['title']) ?></p>
                                            <small><?= h($dueDate) ?></small>
                                        </div>
                                        <div class="activity-actions">
                                            <span class="badge <?= $isDone ? 'done' : 'pending' ?>"><?= $isDone ? 'Concluida' : 'Pendente' ?></span>
                                            <?php if (!$isDone): ?>
                                                <a href="?done=<?= (int)$activity['id'] ?>">Concluir</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="panel">
                    <header>
                        <h4>Meus Cursos</h4>
                        <a href="#">Ver todos</a>
                    </header>

                    <?php if (count($courses) === 0): ?>
                        <div class="empty">
                            <i class="fa-solid fa-book"></i>
                            <p>Nenhum curso vinculado.</p>
                            <a href="#">Finalize o cadastro para receber cursos.</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <?php $progress = max(0, min(100, (int)($course['progress'] ?? 0))); ?>
                            <div class="course">
                                <div><p><?= h((string)$course['name']) ?></p><span><?= $progress ?>%</span></div>
                                <b><i style="width: <?= $progress ?>%"></i></b>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </article>
            </section>
        </main>
    </div>
</body>
</html>