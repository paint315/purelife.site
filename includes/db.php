<?php
// Запрет прямого вызова файла
if (basename($_SERVER['PHP_SELF']) == 'db.php') {
    header('HTTP/1.0 403 Forbidden');
    die('Доступ запрещён');
}

// Параметры подключения (измените под ваше окружение)
$host = 'localhost';
$port = '3306';
$dbname = 'cm903759_purelife';
$username = 'cm903759_purelife';
$password = 'KsJZ7bEM'; 

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Отключаем вывод ошибок на экран (безопасность), включаем логирование в файл
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

?>