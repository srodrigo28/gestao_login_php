<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
startAppSession();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function digitsOnly(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function ensureSchema(PDO $pdo): void
{
    $statements = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            cep VARCHAR(9) NULL,
            street VARCHAR(180) NULL,
            number VARCHAR(20) NULL,
            complement VARCHAR(120) NULL,
            neighborhood VARCHAR(120) NULL,
            city VARCHAR(120) NULL,
            state CHAR(2) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS courses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL UNIQUE,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS user_courses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            course_id INT UNSIGNED NOT NULL,
            progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_course (user_id, course_id),
            CONSTRAINT fk_user_courses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_user_courses_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS activities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            title VARCHAR(190) NOT NULL,
            due_date DATE NULL,
            status ENUM('pending', 'done') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_activities_user_status (user_id, status),
            CONSTRAINT fk_activities_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS user_skills (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            name VARCHAR(120) NOT NULL,
            level TINYINT UNSIGNED NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_skill (user_id, name),
            CONSTRAINT fk_user_skills_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "INSERT IGNORE INTO courses (name) VALUES
            ('Banco de Dados SQL'),
            ('Git e GitHub'),
            ('Python do Zero')",
    ];

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }
}

function seedDashboardData(PDO $pdo, int $userId): void
{
    $courses = $pdo->query('SELECT id, name FROM courses ORDER BY id ASC LIMIT 3')->fetchAll();
    $progressByCourse = [
        'Banco de Dados SQL' => 0,
        'Git e GitHub' => 0,
        'Python do Zero' => 50,
    ];

    $courseStmt = $pdo->prepare('INSERT IGNORE INTO user_courses (user_id, course_id, progress) VALUES (:user_id, :course_id, :progress)');
    foreach ($courses as $course) {
        $name = (string)($course['name'] ?? '');
        $courseStmt->execute([
            ':user_id' => $userId,
            ':course_id' => (int)$course['id'],
            ':progress' => (int)($progressByCourse[$name] ?? 0),
        ]);
    }

    $skillStmt = $pdo->prepare('INSERT IGNORE INTO user_skills (user_id, name, level) VALUES (:user_id, :name, :level)');
    $skillStmt->execute([':user_id' => $userId, ':name' => 'Organizacao', ':level' => 1]);
    $skillStmt->execute([':user_id' => $userId, ':name' => 'Pensamento Logico', ':level' => 2]);
}

$errors = [];
$old = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'cep' => '',
    'street' => '',
    'number' => '',
    'complement' => '',
    'neighborhood' => '',
    'city' => '',
    'state' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $field => $default) {
        $old[$field] = trim((string)($_POST[$field] ?? ''));
    }

    if (mb_strlen($old['name']) < 3) {
        $errors[] = 'Informe um nome valido com pelo menos 3 caracteres.';
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail valido.';
    }

    $phoneDigits = digitsOnly($old['phone']);
    if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 11) {
        $errors[] = 'Informe um telefone valido.';
    }

    $cepDigits = digitsOnly($old['cep']);
    if (strlen($cepDigits) !== 8) {
        $errors[] = 'Informe um CEP valido.';
    }

    if ($old['street'] === '') {
        $errors[] = 'Informe a rua/logradouro.';
    }

    if ($old['number'] === '') {
        $errors[] = 'Informe o numero.';
    }

    if ($old['neighborhood'] === '') {
        $errors[] = 'Informe o bairro.';
    }

    if ($old['city'] === '') {
        $errors[] = 'Informe a cidade.';
    }

    if (!preg_match('/^[A-Za-z]{2}$/', $old['state'])) {
        $errors[] = 'Informe o estado com 2 letras.';
    }

    if (count($errors) === 0) {
        try {
            $pdo = dbConnect();
            ensureSchema($pdo);
            $pdo->beginTransaction();
            $sql = 'INSERT INTO users (name, email, phone, cep, street, number, complement, neighborhood, city, state)
                    VALUES (:name, :email, :phone, :cep, :street, :number, :complement, :neighborhood, :city, :state)';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $old['name'],
                ':email' => $old['email'],
                ':phone' => $old['phone'],
                ':cep' => $old['cep'],
                ':street' => $old['street'],
                ':number' => $old['number'],
                ':complement' => $old['complement'],
                ':neighborhood' => $old['neighborhood'],
                ':city' => $old['city'],
                ':state' => strtoupper($old['state']),
            ]);

            $userId = (int)$pdo->lastInsertId();
            seedDashboardData($pdo, $userId);
            $pdo->commit();

            $_SESSION['auth_user'] = [
                'id' => $userId,
                'name' => $old['name'],
                'email' => $old['email'],
            ];

            header('Location: ../home/home.php');
            exit;
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                $errors[] = 'Este e-mail ja esta cadastrado.';
            } else {
                $errors[] = 'Erro ao salvar cadastro. Execute as migrations em migrations/run.php.';
            }
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Falha inesperada ao processar cadastro.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | EduPortal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="./cadastro.css">
</head>
<body>
    <main class="register-page">
        <section class="register-header">
            <div class="logo-badge"><i class="fa-solid fa-graduation-cap"></i></div>
            <h1>EduPortal</h1>
            <p>Crie sua conta gratuita</p>
        </section>

        <section class="stepper">
            <div class="step done">
                <span><i class="fa-solid fa-check"></i></span>
                <small>Conta</small>
            </div>
            <div class="step-line"></div>
            <div class="step done">
                <span><i class="fa-solid fa-check"></i></span>
                <small>Perfil</small>
            </div>
            <div class="step-line"></div>
            <div class="step current">
                <span>3</span>
                <small>Endereco</small>
            </div>
        </section>

        <section class="register-card">
            <header>
                <h2>Cadastro</h2>
                <p>Informe seus dados para criar sua conta.</p>
            </header>

            <?php if (count($errors) > 0): ?>
                <div class="alert error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= h($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="register-form" method="post" novalidate>
                <div class="field-grid two">
                    <label>
                        Nome completo
                        <input type="text" name="name" id="name" value="<?= h($old['name']) ?>" required>
                    </label>
                    <label>
                        E-mail
                        <input type="email" name="email" id="email" value="<?= h($old['email']) ?>" required>
                    </label>
                </div>

                <div class="field-grid one">
                    <label>
                        Telefone
                        <input type="text" name="phone" id="phone" value="<?= h($old['phone']) ?>" placeholder="(00) 00000-0000" required>
                    </label>
                </div>

                <div class="field-grid one">
                    <label>
                        CEP
                        <div class="inline-input">
                            <input type="text" name="cep" id="cep" value="<?= h($old['cep']) ?>" placeholder="00000-000" required>
                            <button type="button" id="buscar-cep"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
                        </div>
                    </label>
                    <p class="cep-feedback" id="cep-feedback"></p>
                </div>

                <div class="field-grid two">
                    <label>
                        Rua / Logradouro
                        <input type="text" name="street" id="street" value="<?= h($old['street']) ?>" required>
                    </label>
                    <label>
                        Numero
                        <input type="text" name="number" id="number" value="<?= h($old['number']) ?>" required>
                    </label>
                </div>

                <div class="field-grid two">
                    <label>
                        Complemento
                        <input type="text" name="complement" id="complement" value="<?= h($old['complement']) ?>">
                    </label>
                    <label>
                        Bairro
                        <input type="text" name="neighborhood" id="neighborhood" value="<?= h($old['neighborhood']) ?>" required>
                    </label>
                </div>

                <div class="field-grid two">
                    <label>
                        Cidade
                        <input type="text" name="city" id="city" value="<?= h($old['city']) ?>" required>
                    </label>
                    <label>
                        Estado (UF)
                        <input type="text" name="state" id="state" maxlength="2" value="<?= h($old['state']) ?>" required>
                    </label>
                </div>

                <div class="actions">
                    <a class="btn secondary" href="../index.php"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
                    <button class="btn primary" type="submit"><i class="fa-solid fa-check"></i> Criar conta</button>
                </div>
            </form>
        </section>

        <p class="footer-link">Ja tem conta? <a href="../index.php">Entrar</a></p>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        (function () {
            const $form = $('#register-form');
            const $feedback = $('#cep-feedback');

            $('#phone').mask('(00) 00000-0000');
            $('#cep').mask('00000-000');
            $('#state').on('input', function () {
                this.value = this.value.replace(/[^a-zA-Z]/g, '').toUpperCase();
            });

            function digitsOnly(value) {
                return value.replace(/\D/g, '');
            }

            function showFeedback(message, isError) {
                $feedback.text(message || '');
                $feedback.toggleClass('error', !!isError);
                $feedback.toggleClass('ok', !isError && !!message);
            }

            function validateClient() {
                const errors = [];
                const name = $('#name').val().trim();
                const email = $('#email').val().trim();
                const phone = digitsOnly($('#phone').val());
                const cep = digitsOnly($('#cep').val());
                const street = $('#street').val().trim();
                const number = $('#number').val().trim();
                const neighborhood = $('#neighborhood').val().trim();
                const city = $('#city').val().trim();
                const state = $('#state').val().trim();

                if (name.length < 3) errors.push('Nome precisa ter pelo menos 3 caracteres.');
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('E-mail invalido.');
                if (phone.length < 10 || phone.length > 11) errors.push('Telefone invalido.');
                if (cep.length !== 8) errors.push('CEP invalido.');
                if (!street) errors.push('Rua/Logradouro e obrigatorio.');
                if (!number) errors.push('Numero e obrigatorio.');
                if (!neighborhood) errors.push('Bairro e obrigatorio.');
                if (!city) errors.push('Cidade e obrigatoria.');
                if (!/^[A-Za-z]{2}$/.test(state)) errors.push('Estado precisa ter 2 letras.');

                return errors;
            }

            $('#buscar-cep').on('click', function () {
                const cep = digitsOnly($('#cep').val());
                if (cep.length !== 8) {
                    showFeedback('Informe um CEP valido para buscar.', true);
                    return;
                }

                showFeedback('Buscando endereco...', false);

                $.getJSON('https://viacep.com.br/ws/' + cep + '/json/')
                    .done(function (data) {
                        if (data.erro) {
                            showFeedback('CEP nao encontrado.', true);
                            return;
                        }

                        $('#street').val(data.logradouro || '');
                        $('#neighborhood').val(data.bairro || '');
                        $('#city').val(data.localidade || '');
                        $('#state').val((data.uf || '').toUpperCase());
                        showFeedback('Endereco encontrado!', false);
                    })
                    .fail(function () {
                        showFeedback('Falha ao consultar CEP. Tente novamente.', true);
                    });
            });

            $form.on('submit', function (event) {
                const errors = validateClient();
                if (errors.length > 0) {
                    event.preventDefault();
                    alert(errors.join('\n'));
                }
            });
        })();
    </script>
</body>
</html>
