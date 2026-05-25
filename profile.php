<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Запрет доступа для администраторов и менеджеров во вкладку "Личный кабинет"
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')) {
    header('Location: /admin.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Проверка на блокировку
$stmt = $pdo->prepare("SELECT is_blocked FROM users WHERE id = ?");
$stmt->execute([$userId]);
$isBlocked = $stmt->fetchColumn();

if ($isBlocked) {
    require_once 'includes/header.php';
    ?>
    <div class="container profile-container" style="text-align: center; padding: 50px 20px;">
        <h1 style="color: red;">Заблокирован</h1>
        <p style="font-size: 1.2rem; margin-top: 20px;">
            Для того, чтобы узнать подробности о блокировке, обратитесь по почте 
            <a href="mailto:info@purelife.ru">info@purelife.ru</a> 
            или позвоните по телефону: <a href="tel:+79991234567">+7 (999) 123-45-67</a>.
        </p>
        <a href="/logout.php" class="btn btn-primary" style="margin-top: 30px;">Выйти из аккаунта</a>
    </div>
    <?php
    require_once 'includes/footer.php';
    exit;
}

// Пагинация для заказов
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Поиск по дате
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

$totalOrders = getTotalUserOrders($pdo, $userId, $filter_date);
$totalPages = ceil($totalOrders / $limit);
$orders = getUserOrdersPaginated($pdo, $userId, $limit, $offset, $filter_date);

$user = getUserById($pdo, $userId);

// --- Обработка отмены заказа (POST с CSRF) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    verify_csrf();
    $orderId = (int)$_POST['cancel_order'];
    $stmt = $pdo->prepare("SELECT status, is_canceled FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
    if ($order && $order['status'] === 'Новый' && !$order['is_canceled']) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Отменён', is_canceled = TRUE, canceled_at = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);
    }
    header('Location: /profile.php');
    exit;
}

// --- Обработка добавления отзыва (POST с CSRF) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    verify_csrf();
    $orderId = (int)$_POST['order_id'];
    $rating = (int)$_POST['rating'];
    $employeeRating = (int)$_POST['employee_rating'];
    $reviewText = sanitize_string($_POST['review_text']);
    
    $stmt = $pdo->prepare("SELECT employee_id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $orderData = $stmt->fetch();
    $employeeId = $orderData['employee_id'] ?? null;

    if (!hasReview($pdo, $userId, $orderId) && !empty($reviewText) && $rating >= 1 && $rating <= 5 && $employeeRating >= 1 && $employeeRating <= 5) {
        addReview($pdo, $userId, $orderId, $employeeId, $rating, $employeeRating, $reviewText);
        if ($employeeId) {
            $stmt = $pdo->prepare("UPDATE employees SET rating = (SELECT AVG(employee_rating) FROM reviews WHERE employee_id = ?) WHERE id = ?");
            $stmt->execute([$employeeId, $employeeId]);
        }
        header('Location: /profile.php?review_sent=1');
        exit;
    }
}

// --- Обновление профиля (имя, телефон) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verify_csrf();
    $name = sanitize_string($_POST['name']);
    $phone = trim($_POST['phone']);
    if (updateUserProfile($pdo, $userId, $name, $phone)) {
        $_SESSION['user_name'] = $name;
        header('Location: /profile.php?updated=1');
        exit;
    }
}

// --- Смена пароля ---
$password_error = '';
$password_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    verify_csrf();
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();
    if (!password_verify($current_password, $hash)) {
        $password_error = 'Неверный текущий пароль.';
    } elseif (strlen($new_password) < 6) {
        $password_error = 'Новый пароль должен содержать не менее 6 символов.';
    } elseif ($new_password !== $confirm_password) {
        $password_error = 'Новый пароль и подтверждение не совпадают.';
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $userId]);
        $password_success = 'Пароль успешно изменён!';
    }
}

$userDiscount = getUserDiscount($pdo, $userId);
$rescheduleDisabledReason = '';

// === ОТОБРАЖЕНИЕ ОШИБКИ ПЕРЕНОСА ===
if (isset($_SESSION['reschedule_error'])) {
    $reschedule_error = $_SESSION['reschedule_error'];
    unset($_SESSION['reschedule_error']);
} else {
    $reschedule_error = '';
}

require_once 'includes/header.php';
?>

<div class="container profile-container">
    <h1>Личный кабинет</h1>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="success">Профиль успешно обновлён!</div>
    <?php endif; ?>
    <?php if (isset($_GET['review_sent'])): ?>
        <div class="success">Спасибо за ваш отзыв!</div>
    <?php endif; ?>
    <?php if ($reschedule_error): ?>
        <div class="error">⚠️ <?= h($reschedule_error) ?></div>
    <?php endif; ?>
    
    <div class="profile-grid">
        <!-- Блок информации о пользователе -->
        <div class="profile-card">
            <h3>Личные данные</h3>
            <p><strong>Имя:</strong> <?= h($user['name']) ?></p>
            <p><strong>Email:</strong> <?= h($user['email']) ?></p>
            <p><strong>Телефон:</strong> <?= h($user['phone']) ?></p>
            <?php if ($_SESSION['role'] === 'client'): ?>
                <p><strong>Накопительная скидка:</strong> <?= $userDiscount ?>%</p>
            <?php else: ?>
                <p><strong>Роль:</strong> 
                    <?= $_SESSION['role'] === 'admin' ? 'Администратор' : 'Менеджер' ?>
                </p>
            <?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="text" name="name" value="<?= h($user['name']) ?>" required placeholder="Имя">
                <input type="tel" name="phone" value="<?= h($user['phone']) ?>" required placeholder="Телефон">
                <button type="submit" name="update_profile" class="btn btn-primary">Обновить данные</button>
            </form>
        </div>
        
        <!-- Блок смены пароля -->
        <div class="profile-card">
            <h3>Смена пароля</h3>
            <?php if ($password_error): ?>
                <div class="error"><?= $password_error ?></div>
            <?php endif; ?>
            <?php if ($password_success): ?>
                <div class="success"><?= $password_success ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="password" name="current_password" placeholder="Текущий пароль" required>
                <input type="password" name="new_password" placeholder="Новый пароль (мин. 6 символов)" required>
                <input type="password" name="confirm_password" placeholder="Подтвердите новый пароль" required>
                <button type="submit" name="change_password" class="btn btn-primary change-password-btn">Сменить пароль</button>
            </form>
        </div>
    </div>

    <div class="profile-orders">
        <h3>Мои заказы</h3>
        <div class="search-form" style="margin-bottom: 20px;">
            <p>Фильтрация</p>
            <form class="profile-filter-form" method="GET" action="">
                <input type="hidden" name="page" value="1">
                <label>Фильтр по дате:</label>
                <input style="text-align: center;" type="date" name="filter_date" value="<?= h($filter_date) ?>">
                <button type="submit" class="btn-small">Найти</button>
                <a href="/profile.php" class="btn-small cancel">Сбросить</a>
            </form>
        </div>
        <?php if (count($orders) > 0): ?>
            <div class="table-wrapper">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th style="text-align:center;">№</th>
                            <th>Дата</th>
                            <th>Адрес</th>
                            <th>Тип</th>
                            <th>Сумма (₽)</th>
                            <th>Оплата</th>
                            <th>Статус</th>
                            <th style="width: 100px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): 
                            $isCanceled = $order['is_canceled'] ?? false;
                            $status = $order['status'];
                            $rowClass = '';
                            
                            // Определяем, можно ли перенести заказ
                            $canReschedule = false;
                            if ($status === 'Новый' && !$isCanceled) {
                                if ($order['rescheduled_from'] !== null) {
                                    $canReschedule = false;
                                } else {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rescheduled_from = ? AND user_id = ?");
                                    $stmt->execute([$order['id'], $userId]);
                                    $rescheduleCount = $stmt->fetchColumn();
                                    $canReschedule = ($rescheduleCount == 0);
                                }
                            }
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td style="width: 1px; text-align:center;">
                                    <?php if ($status === 'Отменён' || $isCanceled): ?>
                                        <span class="canceled-id"><?= $order['id'] ?></span>
                                    <?php else: ?>
                                        <?= $order['id'] ?>
                                    <?php endif; ?>
                                    <?php if ($order['rescheduled_from'] !== null): ?>
                                        <span class="badge rescheduled-badge" style="background:#ff9800; color:white; padding:2px 6px; border-radius:4px; font-size:10px; margin-left:5px;">перенесён</span>
                                    <?php endif; ?>
                                </td>
                                 <td><?= $order['date'] ?></td>
                                 <td><?= h($order['address']) ?></td>
                                 <td><?= $order['property_type'] ?></td>
                                 <td><?= number_format($order['total_price'], 2) ?></td>
                                <td style="text-align: center;">
                                    <?php
                                    $paymentStatus = $order['payment_status'] ?? 'pending';
                                    if ($status === 'Отменён' || $isCanceled):
                                        if ($paymentStatus == 'paid'): ?>
                                            <span class="status-canceled">🔄 Возврат средств</span>
                                        <?php else: ?>
                                            <span class="status-pending">❌ Отменён (не оплачен)</span>
                                        <?php endif; ?>
                                    <?php else:
                                        if ($paymentStatus == 'paid'): ?>
                                            <span class="status-paid">✅ Оплачен</span>
                                        <?php elseif ($order['payment_status'] == 'cash'): ?>
                                            <span class="status-cash">💰 Наличными</span>
                                        <?php else: ?>
                                            <span class="status-pending">⏳ Не оплачен</span>
                                        <?php endif;
                                    endif; ?>
                                </td>
                                <td><?= $status ?></td>
                                <td class="btn-actions">
                                    <button onclick="toggleOrderDetails(<?= $order['id'] ?>)" class="btn-small details-btn">Детали</button>
                                    <?php if ($status === 'Новый' && !$isCanceled): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Отменить заказ?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="cancel_order" value="<?= $order['id'] ?>">
                                            <button type="submit" class="btn-small cancel">Отменить</button>
                                        </form>
                                        <?php if ($canReschedule): ?>
                                            <a href="/new-order.php?reschedule=<?= $order['id'] ?>" class="btn-small">Перенести</a>
                                        <?php else: ?>
                                            <span class="btn-small disabled" style="background:#ccc; color:#666; cursor: not-allowed;" title="<?= $rescheduleDisabledReason ?>">Перенести</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($status === 'Выполнен'): ?>
                                        <a href="/new-order.php?copy=<?= $order['id'] ?>" class="btn-small">Повторить</a>
                                    <?php endif; ?>
                                    <?php if ($status === 'Выполнен' && !hasReview($pdo, $userId, $order['id'])): ?>
                                        <button onclick="toggleReviewForm(<?= $order['id'] ?>)" class="btn-small review-btn">Оставить отзыв</button>
                                    <?php endif; ?>
                                    <div ></div>
                                </td>
                            </tr>

                            <!-- Шторка деталей заказа -->
                            <tr id="order_details_<?= $order['id'] ?>" style="display: none;">
                                <td colspan="8">
                                    <div class="order-details-mini">
                                        <h4>Детали заказа №<?= $order['id'] ?></h4>
                                        <p><strong>Адрес:</strong> <?= h($order['address']) ?></p>
                                        <p><strong>Тип помещения:</strong> <?= $order['property_type'] ?></p>
                                        <p><strong>Дата:</strong> <?= $order['date'] ?> в <?= $order['time'] ?></p>
                                        <?php if ($order['comment']): ?>
                                            <p><strong>Комментарий:</strong> <?= nl2br(h($order['comment'])) ?></p>
                                        <?php endif; ?>
                                        <h5>Услуги:</h5>
                                        <ul>
                                            <?php 
                                            $details = getOrderDetails($pdo, $order['id'], $userId);
                                            if ($details && isset($details['items'])):
                                                foreach ($details['items'] as $item): ?>
                                                    <li><span><?= h($item['service_name']) ?></span> <span><?= number_format($item['price'] * $item['quantity'], 2) ?> ₽</span></li>
                                            <?php 
                                                endforeach;
                                            endif; 
                                            ?>
                                        </ul>
                                        <p><strong>Итого: <?= number_format($order['total_price'], 2) ?> ₽</strong></p>
                                    </div>
                                </td>
                            </tr>
                            
                            <?php if ($status === 'Выполнен' && !hasReview($pdo, $userId, $order['id'])): ?>
                                <tr id="review_form_<?= $order['id'] ?>" style="display: none;">
                                    <td colspan="8">
                                        <form method="POST" style="padding: 15px; background: #f9f9f9; border-radius: 8px; margin-top: 10px;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <textarea name="review_text" placeholder="Ваш отзыв" rows="3" style="width: 100%;" maxlength="10" required></textarea>
                                            <div style="margin: 10px 0;">
                                                <label>Оценка уборки (1-5): 
                                                    <input type="number" name="rating" min="1" max="5" required style="width: 80px;">
                                                </label>
                                            </div>
                                            <div style="margin: 10px 0;">
                                                <label>Оценка сотрудника (1-5): 
                                                    <input type="number" name="employee_rating" min="1" max="5" required style="width: 80px;">
                                                </label>
                                            </div>
                                            <button type="submit" name="submit_review" class="btn btn-primary">Отправить отзыв</button>
                                            <button type="button" onclick="toggleReviewForm(<?= $order['id'] ?>)" class="btn btn-outline" style="margin-top: 10px;">Отмена</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
                // Формируем базовый URL с фильтром по дате для пагинации
                $paginationBase = '?';
                if (!empty($filter_date)) {
                    $paginationBase .= 'filter_date=' . urlencode($filter_date);
                }
                echo renderPagination($page, $totalPages, $paginationBase, 'page');
            ?>
        <?php else: ?>
            <p>У вас пока нет заказов. <a href="/new-order.php">Сделать первый заказ</a></p>
        <?php endif; ?>
    </div>
    
    <a style="width: 100%" href="/new-order.php" class="btn btn-primary">Новая заявка</a>
</div>

<?php
require_once 'includes/footer.php';
?>