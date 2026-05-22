<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/geo.php';
checkCity();

// Определяем URL сайта для OG-тегов
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$site_url = $protocol . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta property="og:title" content="PureLife - Профессиональный клининг в Санкт-Петербурге">
    <meta property="og:description" content="Уборка квартир, домов и офисов. Опытные клинеры, безопасные средства, накопительные скидки до 10%. Закажите уборку онлайн!">
    <meta property="og:image" content="<?= $site_url ?>/assets/images/logo_meta.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?= $site_url ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="PureLife">
    <meta property="og:locale" content="ru_RU">
    
    <meta name="telegram:channel" content="@purelife_spb">

    <title>PureLife - Клининг в Санкт-Петербурге</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=2.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/Icon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Модальное окно для выбора города (показывается только если город не определён) -->
<?php if (isset($_SESSION['city_unknown']) && $_SESSION['city_unknown'] === true): ?>
<div id="cityModal" class="city-modal-overlay">
    <div class="city-modal-content">
        <h3>Ваш город не определился</h3>
        <p>Пожалуйста, выберите ваш город, чтобы продолжить:</p>
        <form method="POST" action="/set_city.php">
            <input type="hidden" name="set_city" value="1">
            <button type="submit" name="city" value="Saint Petersburg" style="background:#159F54; color:white; padding:10px 20px; margin:10px; border:none; border-radius:30px; cursor:pointer;">Санкт-Петербург</button>
            <button type="submit" name="city" value="other" style="background:#ccc; color:#333; padding:10px 20px; margin:10px; border:none; border-radius:30px; cursor:pointer;">Другой город</button>
        </form>
        <p style="font-size:12px; margin-top:15px;">Услуги доступны только в Санкт-Петербурге.</p>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['city_warning']) && $_SESSION['city_warning'] === true): ?>
<div class="city-warning">
    ⚠️ Наш сервис временно работает только в Санкт-Петербурге. 
    Ваш город: <?= htmlspecialchars($_SESSION['user_city'] ?? 'не определён') ?>. Заказы из других регионов не принимаются.
</div>
<?php endif; ?>

<header>
    <div class="container header-container">
        <div class="logo">
            <a href="/"><img src="/assets/images/logo2.svg" alt="PureLife"></a>
        </div>
        
        <nav class="desktop-nav">
            <ul>
                <li class="under-line"><a href="/">Главная</a></li>
                <li class="under-line"><a href="/services.php">Услуги</a></li>
                <li class="under-line"><a href="/about.php">О компании</a></li>
                <li class="under-line"><a href="/contacts.php">Контакты</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'client'): ?>
                        <li class="under-line"><a href="/profile.php">Личный кабинет</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
                        <li class="under-line"><a href="/admin.php">Админ-панель</a></li>
                    <?php endif; ?>
                    <li class="under-line"><a href="/logout.php">Выйти</a></li>
                <?php else: ?>
                    <li class="under-line"><a href="/login.php">Вход</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <button class="burger-btn" id="burgerBtn">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<div class="fullscreen-menu" id="fullscreenMenu">
    <button class="close-menu" id="closeMenuBtn">&times;</button>
    <nav class="mobile-nav">
        <ul>
            <li><a href="/">Главная</a></li>
            <li><a href="/services.php">Услуги</a></li>
            <li><a href="/about.php">О компании</a></li>
            <li><a href="/contacts.php">Контакты</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'client'): ?>
                    <li><a href="/profile.php">Личный кабинет</a></li>
                <?php endif; ?>
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
                    <li><a href="/admin.php">Админ-панель</a></li>
                <?php endif; ?>
                <li><a href="/logout.php">Выйти</a></li>
            <?php else: ?>
                <li><a href="/login.php">Вход</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<main>