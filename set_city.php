<?php
// set_city.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_city'])) {
    $city = $_POST['city'];
    if ($city === 'Saint Petersburg') {
        $_SESSION['user_city'] = 'Saint Petersburg';
        $_SESSION['city_warning'] = false;
        $_SESSION['city_unknown'] = false;
        $_SESSION['city_manually_selected'] = true;
    } else {
        $_SESSION['user_city'] = $city;
        $_SESSION['city_warning'] = true;
        $_SESSION['city_unknown'] = false;
        $_SESSION['city_manually_selected'] = true;
    }
    // Перенаправление обратно на страницу, откуда пришёл пользователь
    $redirect = $_SERVER['HTTP_REFERER'] ?? '/';
    header('Location: ' . $redirect);
    exit;
} else {
    // Если кто-то открыл файл напрямую, перенаправляем на главную
    header('Location: /');
    exit;
}