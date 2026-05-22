<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/geo.php';

// Обработка действий
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'set_city':
            if (isset($_GET['city'])) {
                $_SESSION['test_city'] = $_GET['city'];
                // Принудительно обновляем город
                $_SESSION['user_city'] = $_GET['city'];
                $_SESSION['city_expires'] = 0;
                checkCity();
            }
            break;
        case 'reset':
            resetTestCity();
            checkCity();
            break;
    }
    header('Location: test_geo_panel.php');
    exit;
}

// Получаем текущий статус
$currentCity = $_SESSION['user_city'] ?? 'не определён';
$isAllowed = !($_SESSION['city_warning'] ?? false);
$testCity = $_SESSION['test_city'] ?? null;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Тестирование геотаргетинга</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        .status { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .good { background: #d4edda; color: #155724; }
        .bad { background: #f8d7da; color: #721c24; }
        button { padding: 0.5rem 1rem; margin: 0.2rem; cursor: pointer; }
        .city-buttons { margin: 1rem 0; }
    </style>
</head>
<body>
    <h1>Панель тестирования геотаргетинга</h1>
    
    <div class="status <?= $isAllowed ? 'good' : 'bad' ?>">
        <strong>Текущий статус:</strong><br>
        Город: <?= htmlspecialchars($currentCity) ?><br>
        <?php if ($testCity): ?>
        Тестовый город (сессия): <?= htmlspecialchars($testCity) ?><br>
        <?php endif; ?>
        Разрешённый регион: <?= $isAllowed ? '✅ ДА (Санкт-Петербург)' : '❌ НЕТ' ?><br>
        Показывать предупреждение: <?= $_SESSION['city_warning'] ? '⚠️ ДА' : 'НЕТ' ?>
    </div>
    
    <h2>Симуляция города</h2>
    <div class="city-buttons">
        <a href="?action=set_city&city=Saint%20Petersburg"><button>Санкт-Петербург (разрешён)</button></a>
        <a href="?action=set_city&city=Moscow"><button>Москва (запрещён)</button></a>
        <a href="?action=set_city&city=Kazan"><button>Казань (запрещён)</button></a>
        <a href="?action=set_city&city=Novosibirsk"><button>Новосибирск (запрещён)</button></a>
        <a href="?action=set_city&city=New%20York"><button>Нью-Йорк (запрещён)</button></a>
    </div>
    
    <div class="city-buttons">
        <a href="?action=reset"><button>🔄 Сбросить тестовый город (вернуться к реальному IP)</button></a>
        <a href="/"><button>🔗 Перейти на сайт</button></a>
    </div>
    
    <hr>
    
    <h3>Инструкция</h3>
    <ul>
        <li>Нажмите на любой город выше — сайт будет "видеть" этот город</li>
        <li>Если город не Санкт-Петербург, на сайте появится красное предупреждение</li>
        <li>При попытке оформить заказ будет показана ошибка</li>
        <li>Нажмите «Сбросить» — сайт вернётся к определению города по реальному IP</li>
    </ul>
    
    <h3>Проверка страниц</h3>
    <ul>
        <li><a href="/" target="_blank">Главная страница</a> — должно быть предупреждение (если город не СПб)</li>
        <li><a href="/new-order.php" target="_blank">Оформление заказа</a> — должна быть блокировка (если город не СПб)</li>
    </ul>
</body>
</html>