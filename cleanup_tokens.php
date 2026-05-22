<?php
if (php_sapi_name() !== 'cli') {
    die('Доступ запрещён');
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Вызываем функцию очистки
cleanupExpiredVerificationTokens($pdo);

echo "Просроченные токены очищены.\n";
