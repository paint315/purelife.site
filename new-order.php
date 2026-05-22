<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// === ОГРАНИЧЕНИЯ ПО ДАТЕ И ВРЕМЕНИ ===
$minDate = date('Y-m-d');
$minTime = '11:00';
$maxTime = '19:00';

// Функция проверки, является ли переданное время рабочим
function isWorkTime($time) {
    $timeStamp = strtotime($time);
    $minTimeStamp = strtotime('11:00');
    $maxTimeStamp = strtotime('19:00');
    return ($timeStamp >= $minTimeStamp && $timeStamp <= $maxTimeStamp);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT is_blocked FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetchColumn()) {
    die('Ваш аккаунт заблокирован. Вы не можете оформлять заказы.');
}

if (isset($_SESSION['city_warning']) && $_SESSION['city_warning'] === true) {
    die('Извините, наш сервис работает только в Санкт-Петербурге.');
}

// Повтор заказа (копирование)
if (isset($_GET['copy']) && is_numeric($_GET['copy'])) {
    $copyOrderId = (int)$_GET['copy'];
    $orderDetails = getOrderDetails($pdo, $copyOrderId, $_SESSION['user_id']);
    if ($orderDetails) {
        $_SESSION['copy_address'] = $orderDetails['address'];
        $_SESSION['copy_property_type'] = $orderDetails['property_type'];
        $_SESSION['copy_comment'] = $orderDetails['comment'] ?? '';
        $_SESSION['copy_date'] = $orderDetails['date'] ?? '';
        $_SESSION['copy_time'] = $orderDetails['time'] ?? '';
        $_SESSION['copy_services'] = [];
        foreach ($orderDetails['items'] as $item) {
            $_SESSION['copy_services'][$item['service_id']] = true;
        }
        header("Location: /new-order.php");
        exit;
    }
}

// Перенос заказа (удаляем старый и копируем)
if (isset($_GET['reschedule']) && is_numeric($_GET['reschedule'])) {
    $rescheduleId = (int)$_GET['reschedule'];
    
    // === НОВОЕ ОГРАНИЧЕНИЕ: Проверяем, можно ли перенести этот заказ ===
    $stmt = $pdo->prepare("SELECT status, is_canceled, rescheduled_from FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$rescheduleId, $_SESSION['user_id']]);
    $orderToReschedule = $stmt->fetch();
    
    // Проверка 1: Заказ существует и принадлежит пользователю
    if (!$orderToReschedule) {
        die('Заказ не найден.');
    }
    
    // Проверка 2: Заказ должен быть в статусе "Новый"
    if ($orderToReschedule['status'] !== 'Новый') {
        die('Можно переносить только заказы в статусе "Новый".');
    }
    
    // Проверка 3: Заказ не должен быть отменён
    if ($orderToReschedule['is_canceled']) {
        die('Отменённый заказ нельзя перенести.');
    }
    
    // === ОСНОВНОЕ ОГРАНИЧЕНИЕ: Проверяем, не является ли заказ уже перенесённым ===
    // Если заказ уже был создан путём переноса (поле rescheduled_from не NULL), 
    // то его нельзя переносить снова
    if ($orderToReschedule['rescheduled_from'] !== null) {
        $_SESSION['reschedule_error'] = 'Данный заказ уже является перенесённым. Повторный перенос невозможен.';
        header('Location: /profile.php');
        exit;
    }
    
    // Проверка 4: Проверяем, не было ли у этого заказа уже переносов
    // Находим все заказы, которые были созданы из этого (проверка по rescheduled_from)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rescheduled_from = ? AND user_id = ?");
    $stmt->execute([$rescheduleId, $_SESSION['user_id']]);
    $rescheduleCount = $stmt->fetchColumn();
    
    if ($rescheduleCount > 0) {
        $_SESSION['reschedule_error'] = 'Этот заказ уже был перенесён ранее. Повторный перенос невозможен.';
        header('Location: /profile.php');
        exit;
    }
    
    $orderDetails = getOrderDetails($pdo, $rescheduleId, $_SESSION['user_id']);
    if ($orderDetails && $orderDetails['status'] === 'Новый') {
        $_SESSION['copy_address'] = $orderDetails['address'];
        $_SESSION['copy_property_type'] = $orderDetails['property_type'];
        $_SESSION['copy_comment'] = $orderDetails['comment'] ?? '';
        $_SESSION['copy_date'] = $orderDetails['date'] ?? '';
        $_SESSION['copy_time'] = $orderDetails['time'] ?? '';
        $_SESSION['copy_services'] = [];
        foreach ($orderDetails['items'] as $item) {
            $_SESSION['copy_services'][$item['service_id']] = true;
        }
        
        // Сохраняем ID старого заказа в сессию для последующей записи в rescheduled_from
        $_SESSION['rescheduled_from'] = $rescheduleId;
        
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$rescheduleId, $_SESSION['user_id']]);
        header("Location: /new-order.php");
        exit;
    }
}

// Предзаполнение
$prefill_address = $_SESSION['copy_address'] ?? '';
$prefill_property_type = $_SESSION['copy_property_type'] ?? 'Квартира';
$prefill_comment = $_SESSION['copy_comment'] ?? '';
$prefill_date = $_SESSION['copy_date'] ?? '';
$prefill_time = $_SESSION['copy_time'] ?? '';
$prefill_services = $_SESSION['copy_services'] ?? [];
unset($_SESSION['copy_address'], $_SESSION['copy_property_type'], $_SESSION['copy_comment'], $_SESSION['copy_date'], $_SESSION['copy_time'], $_SESSION['copy_services']);

$services = getAllServices($pdo);
$employees = getAllEmployees($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = sanitize_string($_POST['address']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $comment = sanitize_string($_POST['comment']);
    $propertyType = sanitize_string($_POST['property_type'] ?? 'Квартира');
    $employeeId = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $servicesData = [];

    // Собираем выбранные услуги
    if (isset($_POST['service']) && is_array($_POST['service'])) {
        foreach ($_POST['service'] as $serviceId => $enabled) {
            if ($enabled === 'on' || $enabled === 1) {
                $service = getServiceById($pdo, $serviceId);
                if ($service) {
                    $servicesData[] = [
                        'service_id' => $serviceId,
                        'quantity' => 1,
                        'price' => $service['price']
                    ];
                }
            }
        }
    }

    if (empty($address) || empty($date) || empty($time) || empty($servicesData)) {
        $error = 'Заполните все поля и выберите хотя бы одну услугу.';
    } 
    elseif ($date < $minDate) {
        $error = 'Дата уборки не может быть раньше сегодняшнего дня (' . date('d.m.Y') . ').';
    }
    elseif (!isWorkTime($time)) {
        $error = 'Время уборки должно быть с 11:00 до 19:00.';
    }
    elseif ($date == date('Y-m-d') && $time < date('H:i')) {
        $error = 'Выбранное время уже прошло. Пожалуйста, выберите будущее время.';
    }
    else {
        $rescheduledFrom = $_SESSION['rescheduled_from'] ?? null;
        $orderId = createOrder($pdo, $_SESSION['user_id'], $servicesData, $address, $date, $time, $comment, $propertyType, $employeeId, $rescheduledFrom, $paymentMethod);
        
        if ($orderId) {
            unset($_SESSION['rescheduled_from']);
            
            if ($paymentMethod === 'online') {
                $_SESSION['payment_order_id'] = $orderId;
                header('Location: /payment.php');
                exit;
            } else {
                header('Location: /profile.php?order_id=' . $orderId . '&payment=cash');
                exit;
            }
        } else {
            $error = 'Ошибка оформления заказа. Попробуйте ещё раз.';
        }
    }
}

$userDiscount = getUserDiscount($pdo, $_SESSION['user_id']);

require_once 'includes/header.php';
?>

<div class="container">
    <h1>Оформление заказа на уборку</h1>
    <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
    
    <?php if (isset($_GET['reschedule'])): ?>
        <div class="info-block">
            📅 Вы переносите заказ. Старый заказ будет удалён, а вы можете изменить любые параметры.
            <br><strong>Внимание:</strong> Перенос возможен только один раз для каждого заказа.
        </div>
    <?php endif; ?>
    
    <form id="orderForm" method="POST">
        <div class="form-group">
            <label>Адрес уборки:</label>
            <input type="text" minlength='10' name="address" value="<?= h($prefill_address) ?>" required>
        </div>
        <div class="form-group">
            <label>Тип помещения:</label>
            <select name="property_type" required>
                <option value="Квартира" <?= $prefill_property_type == 'Квартира' ? 'selected' : '' ?>>Квартира</option>
                <option value="Офис" <?= $prefill_property_type == 'Офис' ? 'selected' : '' ?>>Офис</option>
                <option value="Дом" <?= $prefill_property_type == 'Дом' ? 'selected' : '' ?>>Частный дом</option>
            </select>
        </div>
        <div class="form-group">
            <label>Желаемая дата:</label>
            <input type="date" name="date" value="<?= h($prefill_date) ?>" 
                min="<?= date('Y-m-d') ?>" 
                max="<?= date('Y-m-d', strtotime('December 31')) ?>"
                required>
            <small class="small-text">Минимальная дата — сегодня (<?= date('d.m.Y') ?>), максимальная — 31 декабря <?= date('Y') ?></small>
        </div>
        <div class="form-group">
            <label>Желаемое время:</label>
            <input type="time" name="time" value="<?= h($prefill_time) ?>" min="11:00" max="19:00" step="60" required>
            <small class="small-text">Работаем с 11:00 до 19:00</small>
        </div>
        <div class="form-group">
            <label>Комментарий (особые пожелания):</label>
            <textarea name="comment"><?= h($prefill_comment) ?></textarea>
			<label></label>
        </div>
        <div class="form-group">
            <label>Выберите предпочтительного сотрудника (по желанию):</label>
            <select name="employee_id">
                <option value="">Любой (назначим сами)</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>"><?= h($emp['name']) ?> (⭐ <?= $emp['rating'] ?>, опыт <?= $emp['years_experience'] ?> лет)</option>
                <?php endforeach; ?>
            </select>
            <label>*Сотрудник назначается с учётом его занятости на других заказа и с учётом пожелания клиента.</label>
        </div>
        <div class="form-group">
            <label>Способ оплаты:</label>
            <select name="payment_method" required>
                <option value="cash">💰 Наличные сотруднику</option>
                <option value="online">💳 Оплата картой онлайн (тестовый режим)</option>
            </select>
        </div>

        <h3>Выберите услуги:</h3>
        <table id="servicesTable" class="services-checkbox">
            <thead>
                <tr>
                    <th>Выбрать</th>
                    <th>Услуга</th>
                    <th>Цена (₽)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): 
                    $checked = isset($prefill_services[$service['id']]) ? 'checked' : '';
                ?>
                    <tr data-price="<?= $service['price'] ?>">
                        <td class="table-cell-center"><input type="checkbox" name="service[<?= $service['id'] ?>]" <?= $checked ?> class="service-checkbox"></td>
                        <td><?= h($service['name']) ?></td>
                        <td class="service-price"><?= number_format($service['price'], 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="order-summary"">
            <div class="total-block">
                <strong>Сумма без скидки:</strong> <span id="subtotal">0</span> ₽<br>
                <strong>Ваша скидка:</strong> <span id="discountPercent"><?= $userDiscount ?></span>%<br>
                <strong style="font-size:1.2em;">Итого к оплате:</strong> <span id="totalWithDiscount">0</span> ₽
            </div>
        </div>
        
        <button style="margin: 10px 0 20px 0;" type="submit" class="btn btn-primary btn-done"><?= isset($_GET['reschedule']) ? 'Подтвердить перенос' : 'Заказать' ?></button>
    </form>
    <a href="/profile.php" class="btn btn-primary btn-cancel btn-cancel-order">Отмена</a>
</div>

<script>
// Функция пересчёта итога
function recalcTotal() {
    let subtotal = 0;
    document.querySelectorAll('#servicesTable tbody tr').forEach(row => {
        let chk = row.querySelector('.service-checkbox');
        if (chk && chk.checked) {
            let price = parseFloat(row.getAttribute('data-price') || 0);
            subtotal += price;
        }
    });
    let discountPercent = parseInt(document.getElementById('discountPercent').innerText) || 0;
    let total = subtotal * (100 - discountPercent) / 100;
    document.getElementById('subtotal').innerText = subtotal.toFixed(2);
    document.getElementById('totalWithDiscount').innerText = total.toFixed(2);
}

// При загрузке и при изменении чекбоксов
document.querySelectorAll('.service-checkbox').forEach(chk => {
    chk.addEventListener('change', recalcTotal);
});
window.addEventListener('DOMContentLoaded', recalcTotal);
</script>

<?php require_once 'includes/footer.php'; ?>