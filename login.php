<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')) {
    header('Location: /admin.php');
    exit;
}

require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Используем функцию loginUser, которая проверяет is_verified = 1
    $user = loginUser($pdo, $email, $password);
    
    if ($user) {
        if ($user['is_blocked']) {
            $error = 'Ваш аккаунт заблокирован. Обратитесь к администратору.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] === 'admin' || $user['role'] === 'manager') {
                header('Location: /admin.php');
            } else {
                header('Location: /profile.php');
            }
            exit;
        }
    } else {
        $error = 'Неверный email или пароль';
    }
}

require_once 'includes/header.php';
?>

<div class="container form-container">
    <h2>Вход в аккаунт</h2>

    <?php if (isset($_GET['waiting_verification']) && $_GET['waiting_verification'] == 1): ?>
        <div class="success">
            📧 На вашу почту отправлено письмо для подтверждения.<br>
            Если письмо не пришло, то проверьте папку <strong>«Спам»</strong> или вы можете <a href="/resend-verification.php">запросить письмо снова</a> (не чаще раза в 5 минут).
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['verify_success'])): ?>
        <div class="success">
            ✅ Аккаунт успешно подтверждён! Теперь вы можете войти.
        </div>
        <?php unset($_SESSION['verify_success']); ?>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit" class="btn btn-primary">Войти</button>
    </form>
    <p class="question"><a href="/forgot-password.php">Забыли пароль?</a></p>
    <p class="question">Нет аккаунта? <a href="/register.php">Зарегистрироваться</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>