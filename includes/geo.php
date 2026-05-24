<?php
// Важно: запускаем сессию для работы с $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getCityByIp($ip = null) {
    // ===== РЕЖИМ ТЕСТИРОВАНИЯ =====
    // Чтобы симулировать другой город, раскомментируйте нужную строку ниже
    // или передайте параметр в URL: ?test_city=Moscow
    
    // ВАРИАНТ 1: Через GET-параметр (например, ?test_city=Moscow)
    if (isset($_GET['test_city'])) {
        $testCity = $_GET['test_city'];
        // Сохраняем тестовый город в сессию, чтобы он применялся на всех страницах
        $_SESSION['test_city'] = $testCity;
        return $testCity;
    }
    
    // ВАРИАНТ 2: Использовать город из сессии (для постоянного теста)
    if (isset($_SESSION['test_city'])) {
        return $_SESSION['test_city'];
    }
    // ===== КОНЕЦ РЕЖИМА ТЕСТИРОВАНИЯ =====
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Для локальной разработки возвращаем Санкт-Петербург
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return 'Saint Petersburg';
    }
    
    $url = "http://ip-api.com/json/{$ip}?fields=status,city";
    $json = @file_get_contents($url);
    if ($json === false) {
        return false;
    }
    $data = json_decode($json, true);
    if ($data && $data['status'] === 'success') {
        return $data['city'];
    }
    return false;
}

function checkCity() {
    // Если пользователь уже выбрал город вручную, НЕ трогаем его
    if (isset($_SESSION['city_manually_selected']) && $_SESSION['city_manually_selected'] === true) {
        $allowed = ['Saint Petersburg', 'St Petersburg', 'Санкт-Петербург', 'Sankt-Peterburg'];
        $isAllowed = in_array($_SESSION['user_city'], $allowed);
        $_SESSION['city_warning'] = !$isAllowed;
        $_SESSION['city_unknown'] = false;
        return $isAllowed;
    }

    // Автоматическое определение города по IP
    if (!isset($_SESSION['user_city']) || !isset($_SESSION['city_expires']) || $_SESSION['city_expires'] < time()) {
        $city = getCityByIp();
        $_SESSION['user_city'] = $city ?: 'unknown';
        $_SESSION['city_expires'] = time() + 3600;
    }

    // Сброс при тестировании
    if (isset($_GET['reset_city']) || isset($_GET['test_city'])) {
        $city = getCityByIp();
        $_SESSION['user_city'] = $city ?: 'unknown';
        unset($_SESSION['city_manually_selected']); // сбрасываем ручной выбор
    }

    $allowed = ['Saint Petersburg', 'St Petersburg', 'Санкт-Петербург', 'Sankt-Peterburg'];
    $isAllowed = in_array($_SESSION['user_city'], $allowed);

    // Устанавливаем флаг unknown для модального окна (только если не выбран вручную)
    if ($_SESSION['user_city'] === 'unknown') {
        $_SESSION['city_unknown'] = true;
        $isAllowed = false;
    } else {
        $_SESSION['city_unknown'] = false;
    }

    $_SESSION['city_warning'] = !$isAllowed;
    return $isAllowed;
}

function resetTestCity() {
    if (isset($_SESSION['test_city'])) {
        unset($_SESSION['test_city']);
    }
    if (isset($_SESSION['user_city'])) {
        unset($_SESSION['user_city']);
    }
    if (isset($_SESSION['city_expires'])) {
        unset($_SESSION['city_expires']);
    }
    $_SESSION['city_warning'] = false;
    $_SESSION['city_unknown'] = false;
}
?>