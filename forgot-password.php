<?php
    require_once 'includes/header.php';
    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        if (empty($email)) {
            $error = 'Введите email';
        } else {
            $token = createPasswordReset($pdo, $email);
            if ($token) {
                $resetLink = "https://{$_SERVER['HTTP_HOST']}/reset-password.php?token=$token";
                $subject = "Восстановление пароля на PureLife";
                $message = "Здравствуйте!\n\nДля сброса пароля перейдите по ссылке:\n$resetLink\n\nСсылка действительна 1 час.\n\nЕсли вы не запрашивали сброс, проигнорируйте это письмо.";
                $headers = "From: info@purelife.site\r\n";
                mail($email, $subject, $message, $headers);
                $success = 'Инструкция отправлена на ваш email. Проверьте почту.';
            } else {
                // Не показываем, существует email или нет (безопасность)
                $success = 'Инструкция отправлена на ваш email. Проверьте почту.';
            }
        }
    }
    ?>
    <div class="container form-container">
        <h2>Восстановление пароля</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Ваш email" required>
            <button type="submit" class="btn btn-primary">Отправить ссылку</button>
        </form>
        <p><a href="/login.php">Вернуться ко входу</a></p>
    </div>
<?php require_once 'includes/footer.php'; ?>