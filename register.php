<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $name = sanitize_string($_POST['name']);
    $phone = sanitize_phone($_POST['phone']);
    
    if (strlen($phone) !== 11) {
        $error = 'Введите корректный номер телефона (11 цифр)';
    } elseif (empty($email) || empty($password) || empty($name) || empty($phone)) {
        $error = 'Заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } elseif (!is_email_deliverable($email)) {
        $error = 'Почтовый домен не существует или не настроен для приёма писем.';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif (isEmailTaken($pdo, $email)) {
        $error= 'Этот email уже зарегистрирован. Используйте другой или восстановите пароль.';
        } else {
            if ($token = registerUser($pdo, $email, $password, $name, $phone)) {
            $subject = "=?UTF-8?B?".base64_encode("Подтверждение регистрации на PureLife")."?=";
            $verifyLink = "https://purelife.site/verify.php?token=" . $token;
            // HTML-шаблон письма
            $htmlMessage = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Подтверждение регистрации</title>
            </head>
            <body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f4;">
                <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f4f4f4">
                    <tr>
                        <td align="center" style="padding: 30px 10px;">
                            <table width="500" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                                <!-- Логотип -->
                                <tr>
                                    <td align="center" style="padding: 30px 30px 10px 30px;">
                                        <img src="https://purelife.site/assets/images/logo_meta.jpg" alt="PureLife" width="180">
                                    </td>
                                </tr>
                                <!-- Заголовок -->
                                <tr>
                                    <td align="center" style="padding: 10px 30px;">
                                        <h1 style="color: #159F54; font-size: 24px; margin:0;">Подтверждение регистрации</h1>
                                    </td>
                                </tr>
                                <!-- Текст -->
                                <tr>
                                    <td align="center" style="padding: 20px 30px; color: #042960; font-size: 16px; line-height: 1.5;">
                                        Здравствуйте, <strong>' . h($name) . '</strong>!<br><br>
                                        Для завершения регистрации нажмите на кнопку ниже:
                                    </td>
                                </tr>
                                <!-- Кнопка -->
                                <tr>
                                    <td align="center" style="padding: 10px 30px 30px 30px;">
                                        <a href="' . $verifyLink . '" style="display: inline-block; background-color: #159F54; color: white; padding: 12px 32px; text-decoration: none; border-radius: 30px; font-size: 16px; font-weight: bold;">Подтвердить email</a>
                                    </td>
                                </tr>
                                <!-- Альтернативная ссылка -->
                                <tr>
                                    <td align="center" style="padding: 0 30px 20px 30px; font-size: 12px; color: #888;">
                                        Если кнопка не работает, скопируйте ссылку в браузер:<br>
                                        <a href="' . $verifyLink . '" style="color: #159F54;">' . $verifyLink . '</a>
                                    </td>
                                </tr>
                                <!-- Футер -->
                                <tr>
                                    <td align="center" style="padding: 20px 30px; background-color: #f8fafc; font-size: 12px; color: #666;">
                                        © ' . date('Y') . ' PureLife. Профессиональный клининг в Санкт-Петербурге.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ';

            // Альтернативный текст для почтовых клиентов, не поддерживающих HTML
            $plainMessage = "Здравствуйте, $name!\n\nДля завершения регистрации перейдите по ссылке:\n$verifyLink\n\nСсылка действительна 24 часа.\n\nЕсли вы не регистрировались, проигнорируйте это письмо.\n\n--\nPureLife";

            $headers = "From: PureLife <info@purelife.site>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            $headers .= "Reply-To: info@purelife.site\r\n";

            mail($email, $subject, $htmlMessage, $headers);
            $_SESSION['pending_verification_email'] = $email;
            header('Location: /login.php?waiting_verification=1');
            exit;
        } else {
            $error = 'Ошибка регистрации. Возможно, email уже занят.';
        }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container form-container">
    <h2>Регистрация</h2>
    <?php if ($error): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Имя:</label>
            <input type="text" name="name" placeholder="Введите ваше имя" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" placeholder="example@mail.ru" required>
        </div>
        
        <div class="form-group">
            <label>Телефон:</label>
            <input type="tel" name="phone" placeholder="+7 (___) ___-__-__" required>
        </div>
        
        <div class="form-group">
            <label>Пароль:</label>
            <input type="password" name="password" placeholder="Не менее 6 символов" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
    </form>
    <p class="question">Уже есть аккаунт? <a href="/login.php">Войти</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>