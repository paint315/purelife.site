<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Неверная ссылка подтверждения');
}

// Проверяем токен, срок действия и что пользователь ещё не верифицирован
$stmt = $pdo->prepare("SELECT id, email, name, role FROM users 
                       WHERE verification_token = ? 
                       AND is_verified = 0 
                       AND verification_token_expires_at > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if ($user) {
    // Активируем аккаунт
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, verification_token_expires_at = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Автоматический вход
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    if ($user['role'] === 'admin' || $user['role'] === 'manager') {
        header('Location: /admin.php');
    } else {
        header('Location: /profile.php');
    }
    exit;
} else {
    // Дополнительная проверка: может быть токен истек или уже активирован
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    $expiredUser = $stmt->fetch();
    if ($expiredUser) {
        die('Срок действия ссылки истёк. Запросите новое письмо для подтверждения.');
    } else {
        die('Ссылка недействительна или аккаунт уже активирован');
    }
}
?>