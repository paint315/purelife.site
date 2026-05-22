<?php
require_once 'includes/header.php';
$error = '';
$success = '';
$email = isset($_SESSION['pending_verification_email']) ? $_SESSION['pending_verification_email'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (empty($email)) {
        $error = 'Введите email';
    } else {
        // Находим неактивного пользователя
        $stmt = $pdo->prepare("SELECT id, name, last_verification_sent FROM users WHERE email = ? AND is_verified = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Проверка времени (5 минут)
            $now = new DateTime();
            $lastSent = new DateTime($user['last_verification_sent']);
            $diffMinutes = ($now->getTimestamp() - $lastSent->getTimestamp()) / 60;
            
            if ($diffMinutes < 5) {
                $waitMinutes = ceil(5 - $diffMinutes);
                $error = "⏳ Вы уже запрашивали письмо. Повторно запросить можно через $waitMinutes минут.";
            } else {
                // Новый токен и срок
                $newToken = bin2hex(random_bytes(32));
                $newExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $nowStr = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, verification_token_expires_at = ?, last_verification_sent = ? WHERE id = ?");
                $stmt->execute([$newToken, $newExpires, $nowStr, $user['id']]);
                
                // HTML-письмо
                $verifyLink = "https://{$_SERVER['HTTP_HOST']}/verify.php?token=$newToken";
                $subject = "Подтверждение регистрации на PureLife";
                
                $htmlMessage = '
                <!DOCTYPE html>
                <html>
                <head><meta charset="UTF-8"><title>Подтверждение регистрации</title></head>
                <body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f4;">
                    <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f4f4f4">
                        <tr><td align="center" style="padding: 30px 10px;">
                            <table width="500" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                                <tr><td align="center" style="padding: 30px 30px 10px 30px;">
                                    <img src="https://purelife.site/assets/images/logo2.svg" alt="PureLife" width="180">
                                </td></tr>
                                <tr><td align="center" style="padding: 10px 30px;">
                                    <h1 style="color: #159F54;">Подтверждение регистрации</h1>
                                </td></tr>
                                <tr><td align="center" style="padding: 20px 30px; color: #042960;">
                                    Здравствуйте, <strong>' . h($user['name']) . '</strong>!<br><br>
                                    Для завершения регистрации нажмите на кнопку ниже:
                                </td></tr>
                                <tr><td align="center" style="padding: 10px 30px 30px 30px;">
                                    <a href="' . $verifyLink . '" style="display: inline-block; background-color: #159F54; color: white; padding: 12px 32px; text-decoration: none; border-radius: 30px;">Подтвердить email</a>
                                </td></tr>
                                <tr><td align="center" style="padding: 0 30px 20px 30px; font-size: 12px; color: #888;">
                                    Или скопируйте ссылку:<br>
                                    <a href="' . $verifyLink . '" style="color: #159F54;">' . $verifyLink . '</a>
                                </td></tr>
                                <tr><td align="center" style="padding: 20px 30px; background-color: #f8fafc; font-size: 12px; color: #666;">
                                    © ' . date('Y') . ' PureLife. Профессиональный клининг в Санкт-Петербурге.
                                </td></tr>
                            </table>
                        </td></tr>
                    </table>
                </body>
                </html>
                ';
                
                $plainMessage = "Здравствуйте, {$user['name']}!\n\nДля завершения регистрации перейдите по ссылке:\n$verifyLink\n\nСсылка действительна 24 часа.\n\n--\nPureLife";
                
                $headers = "From: PureLife <info@purelife.site>\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                
                mail($email, $subject, $htmlMessage, $headers);
                $success = '✅ Новое письмо отправлено. Проверьте почту (в том числе спам).';
                
                // Обновляем email в сессии
                $_SESSION['pending_verification_email'] = $email;
            }
        } else {
            $success = 'Если аккаунт с таким email существует и не подтверждён, новое письмо отправлено.';
        }
    }
}
?>
<div class="container form-container">
    <h2>Повторная отправка письма подтверждения</h2>
    <?php if ($error): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?= h($success) ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Ваш email" value="<?= h($email) ?>" required>
        <button type="submit" class="btn btn-primary">Выслать письмо</button>
    </form>
    <p><a href="/login.php">Вернуться ко входу</a></p>
</div>
<?php require_once 'includes/footer.php'; ?>