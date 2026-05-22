<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    die('Доступ запрещён');
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orderId) {
    die('Не указан ID заказа');
}

// Получаем данные заказа (без привязки к текущему пользователю)
$stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Заказ не найден');
}

// Получаем услуги заказа
$stmt = $pdo->prepare("SELECT oi.*, s.name as service_name 
                       FROM order_items oi 
                       JOIN services s ON oi.service_id = s.id 
                       WHERE oi.order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Получаем сотрудника
$employee = null;
if ($order['employee_id']) {
    $employee = getEmployeeById($pdo, $order['employee_id']);
}

$statusMap = [
    'Новый' => 'Новый',
    'В работе' => 'В работе',
    'Выполнен' => 'Выполнен',
    'Отменён' => 'Отменён',
];
$statusDisplay = $statusMap[$order['status']] ?? $order['status'];

require_once 'includes/header.php';
?>

<div class="container order-view-container">
    <h1>Детали заказа №<?= $order['id'] ?></h1>
    
    <div class="order-view-section">
        <h2>Информация о клиенте</h2>
        <p><strong>Имя:</strong> <?= h($order['user_name']) ?></p>
        <p><strong>Email:</strong> <?= h($order['user_email']) ?></p>
        <p><strong>Телефон:</strong> <?= h($order['user_phone']) ?></p>
    </div>
    
    <div class="order-view-section">
        <h2>Детали заказа</h2>
        <p><strong>Статус:</strong> <?= $statusDisplay ?></p>
        <p><strong>Адрес:</strong> <?= h($order['address']) ?></p>
        <p><strong>Тип помещения:</strong> 
            <?= $order['property_type'] == 'apartment' ? 'Квартира' : ($order['property_type'] == 'office' ? 'Офис' : 'Частный дом') ?>
        </p>
        <p><strong>Дата уборки:</strong> <?= $order['date'] ?> в <?= $order['time'] ?></p>
        <?php if ($order['comment']): ?>
            <p><strong>Комментарий клиента:</strong> <?= nl2br(h($order['comment'])) ?></p>
        <?php endif; ?>
    </div>
    
    <?php if ($employee): ?>
    <div class="order-view-section">
        <h2>Назначенный сотрудник</h2>
        <p><strong>Имя:</strong> <?= h($employee['name']) ?></p>
        <p><strong>Рейтинг:</strong> ⭐ <?= $employee['rating'] ?></p>
        <p><strong>Опыт:</strong> <?= $employee['years_experience'] ?> лет</p>
    </div>
    <?php endif; ?>
    
    <div class="order-view-section">
        <h2>Состав заказа</h2>
        <table class="admin-table">
            <thead>
                <tr><th>Услуга</th><th>Количество</th><th>Цена</th><th>Сумма</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= h($item['service_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['price'], 2) ?> ₽</td>
                    <td><?= number_format($item['price'] * $item['quantity'], 2) ?> ₽</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="3">Итого:</th><th><?= number_format($order['total_price'], 2) ?> ₽</th></tr>
            </tfoot>
        </table>
    </div>
    
    <div class="mt-20">
        <a href="/admin.php?action=orders" class="btn btn-primary">← Вернуться к списку заказов</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>