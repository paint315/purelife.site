<?php
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token = $_GET['token'] ?? '';
    $error = '';
    $success = '';

    if (empty($token)) {
        die('Неверная ссылка');
    }

    $reset = verifyPasswordResetToken($pdo, $token);
    if (!$reset) {
        die('Ссылка недействительна или истекла. Запросите восстановление пароля заново.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        if (strlen($password) < 6) {
            $error = 'Пароль должен быть не менее 6 символов';
        } elseif ($password !== $confirm) {
            $error = 'Пароли не совпадают';
        } else {
            if (updatePassword($pdo, $reset['user_id'], $password)) {
                // Удаляем использованный токен
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);
                $success = 'Пароль успешно изменён. Теперь вы можете войти.';
                header('refresh:2;url=/login.php');
            } else {
                $error = 'Ошибка при смене пароля. Попробуйте позже.';
            }
        }
    }

    require_once 'includes/header.php';
    ?>
    <div class="container form-container">
        <h2>Новый пароль</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!$success): ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Новый пароль (мин. 6 символов)" required>
            <input type="password" name="confirm_password" placeholder="Подтвердите пароль" required>
            <button type="submit" class="btn btn-primary">Сменить пароль</button>
        </form>
        <?php endif; ?>
        <p><a href="/login.php">Войти</a></p>
    </div>
<?php require_once 'includes/footer.php'; ?>