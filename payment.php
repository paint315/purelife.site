<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Проверяем, есть ли заказ для оплаты
if (!isset($_SESSION['payment_order_id'])) {
    header('Location: /profile.php');
    exit;
}

$orderId = $_SESSION['payment_order_id'];
$userId = $_SESSION['user_id'];

// Получаем данные заказа
$order = getOrderDetails($pdo, $orderId, $userId);

if (!$order) {
    unset($_SESSION['payment_order_id']);
    header('Location: /profile.php');
    exit;
}

// Обработка формы оплаты
$payment_success = false;
$payment_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_number = preg_replace('/\s/', '', $_POST['card_number']);
    $expiry = $_POST['expiry'];
    $cvv = $_POST['cvv'];
    
    // Простая валидация для демонстрации
    if (empty($card_number) || empty($expiry) || empty($cvv)) {
        $payment_error = 'Заполните все поля карты.';
    } elseif (strlen($card_number) < 16) {
        $payment_error = 'Некорректный номер карты.';
    } elseif (strlen($cvv) < 3) {
        $payment_error = 'Некорректный CVV код.';
    } else {
        // В тестовом режиме всегда успешно
        $payment_success = true;
        
        // Обновляем статус заказа и вид оплаты
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', payment_type = 'online' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // Очищаем сессию
        unset($_SESSION['payment_order_id']);
    }
}

require_once 'includes/header.php';
?>

<div class="container payment-container">
    <?php if ($payment_success): ?>
        <div class="success payment-success">
            <h2>✅ Оплата успешно проведена!</h2>
            <p>Ваш заказ №<?= $orderId ?> оплачен.</p>
            <p>Статус оплаты: <strong>Подтверждён (тестовый режим)</strong></p>
            <a href="/profile.php?order_id=<?= $orderId ?>" class="btn btn-primary" style="margin-top: 20px;">Вернуться к заказу</a>
        </div>
    <?php else: ?>
        <div class="payment-form">
            <h2 style="text-align: center; margin-bottom: 20px;">💳 Оплата заказа №<?= $orderId ?></h2>
            <div class="payment-amount">
                <p style="margin: 0; color: #166534;">
                    <strong>💰 Сумма к оплате: <?= number_format($order['total_price'], 2) ?> ₽</strong>
                </p>
            </div>
            <div class="payment-testmode">
                <p style="margin: 0; font-size: 14px; color: #e65100;">
                    🧪 <strong>Тестовый режим</strong><br>
                    Для оплаты можно использовать любые данные.<br>
                    Пример карты: 1111 1111 1111 1111, срок любой, CVV любой.
                </p>
            </div>
            
            <?php if ($payment_error): ?>
                <div class="error"><?= h($payment_error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Номер карты</label>
                    <input type="text" name="card_number" placeholder="1111 1111 1111 1111" maxlength="19" required>
                </div>
                
                <div class="payment-grid">
                    <div class="form-group">
                        <label>Срок (ММ/ГГ)</label>
                        <input type="text" name="expiry" placeholder="12/25" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label>CVV код</label>
                        <input type="password" name="cvv" placeholder="123" maxlength="3" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary payment-btn">Оплатить <?= number_format($order['total_price'], 2) ?> ₽</button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="/profile.php">← Вернуться без оплаты</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Маска для номера карты
document.querySelector('input[name="card_number"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 16) value = value.slice(0, 16);
    let formatted = '';
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) formatted += ' ';
        formatted += value[i];
    }
    e.target.value = formatted;
});

// Маска для срока
document.querySelector('input[name="expiry"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 4) value = value.slice(0, 4);
    if (value.length > 2) {
        e.target.value = value.slice(0, 2) + '/' + value.slice(2);
    } else {
        e.target.value = value;
    }
});

// Маска для CVV
document.querySelector('input[name="cvv"]').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
});
</script>

<?php require_once 'includes/footer.php'; ?>