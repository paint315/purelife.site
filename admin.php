<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//Проверка ошибок
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require_once 'includes/db.php';
require_once 'includes/functions.php';


// Проверка прав доступа: только админ или менеджер
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    die('Доступ запрещён');
}

$role = $_SESSION['role'];
$action = $_GET['action'] ?? 'orders';
// Пагинация для заказов
$page_orders = isset($_GET['page_orders']) ? (int)$_GET['page_orders'] : 1;
$limit_orders = 5;
$offset_orders = ($page_orders - 1) * $limit_orders;

// Пагинация для отзывов
$page_reviews = isset($_GET['page_reviews']) ? (int)$_GET['page_reviews'] : 1;
$limit_reviews = 5;
$offset_reviews = ($page_reviews - 1) * $limit_reviews;

// Пагинация для пользователей
$page_users = isset($_GET['page_users']) ? (int)$_GET['page_users'] : 1;
$limit_users = 5;
$offset_users = ($page_users - 1) * $limit_users;

// Пагинация для сотрудников
$page_employees = isset($_GET['page_employees']) ? (int)$_GET['page_employees'] : 1;
$limit_employees = 5;
$offset_employees = ($page_employees - 1) * $limit_employees;

// Пагинация для услуг
$page_services = isset($_GET['page_services']) ? (int)$_GET['page_services'] : 1;
$limit_services = 5;
$offset_services = ($page_services - 1) * $limit_services;

$order_status_filter = isset($_GET['order_status']) ? $_GET['order_status'] : '';
$order_date_filter = isset($_GET['order_date']) ? $_GET['order_date'] : '';

$user_email_filter = isset($_GET['user_email']) ? trim($_GET['user_email']) : '';
$user_role_filter = isset($_GET['user_role']) ? $_GET['user_role'] : '';

$employee_name_filter = isset($_GET['employee_name']) ? trim($_GET['employee_name']) : '';
$employee_sort = isset($_GET['employee_sort']) ? $_GET['employee_sort'] : 'rating_desc';

$review_status_filter = isset($_GET['review_status']) ? $_GET['review_status'] : '';

// --- Обработка изменения статуса заказа, сотрудника и оплаты ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    verify_csrf();
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowedStatuses = ['Новый', 'В работе', 'Выполнен', 'Отменён'];
    if (!in_array($status, $allowedStatuses)) {
        die('Недопустимый статус');
    }
    $employeeId = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
    $paymentStatus = $_POST['payment_status'] ?? 'pending';
    $paymentType = $_POST['payment_type'] ?? 'cash';
    
    // Если статус заказа "Выполнен", автоматически меняем статус оплаты на "Оплачен"
    if ($status === 'Выполнен' && $paymentStatus !== 'paid') {
        $paymentStatus = 'paid';
    }
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, employee_id = ?, payment_status = ?, payment_type = ? WHERE id = ?");
    $stmt->execute([$status, $employeeId, $paymentStatus, $paymentType, $orderId]);
    
    header('Location: /admin.php?action=orders');
    exit;
}

// --- Изменение статуса отсутствия сотрудника ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['is_absent']) && ($role === 'admin' || $role === 'manager')) {
    verify_csrf();
    $employeeId = (int)$_POST['employee_id'];
    $isAbsent = (int)$_POST['is_absent'];
    $stmt = $pdo->prepare("UPDATE employees SET is_absent = ? WHERE id = ?");
    $stmt->execute([$isAbsent, $employeeId]);
    
    // Если сотрудника пометили как отсутствующего, снимаем его со всех текущих заказов
    if ($isAbsent == 1) {
        $stmt = $pdo->prepare("UPDATE orders SET employee_id = NULL WHERE employee_id = ? AND status = 'В работе'");
        $stmt->execute([$employeeId]);
    }
    
    header('Location: /admin.php?action=employees');
    exit;
}

// --- Добавление услуги (только админ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service']) && $role === 'admin') {
    verify_csrf();
    $name = sanitize_string($_POST['name']);
    $description = sanitize_string($_POST['description']);
    $price = (float)$_POST['price'];
    $sortOrder = (int)$_POST['sort_order'];
    addService($pdo, $name, $description, $price, $sortOrder);
    header('Location: /admin.php?action=services');
    exit;
}

// --- Редактирование услуги (только админ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service']) && $role === 'admin') {
    verify_csrf();
    $service_id = (int)$_POST['service_id'];
    $name = sanitize_string($_POST['name']);
    $description = sanitize_string($_POST['description']);
    $price = (float)$_POST['price'];
    $sort_order = (int)$_POST['sort_order'];
    
    updateService($pdo, $service_id, $name, $description, $price, $sort_order);
    header('Location: /admin.php?action=services');
    exit;
}

// --- Удаление услуги (только админ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service']) && $role === 'admin') {
    verify_csrf();
    $serviceId = (int)$_POST['delete_service'];
    deleteService($pdo, $serviceId);
    header('Location: /admin.php?action=services');
    exit;
}

// --- Смена роли пользователя (только админ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role']) && $role === 'admin') {
    verify_csrf();
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'];
    $allowedRoles = ['client', 'manager', 'admin'];
    if (!in_array($newRole, $allowedRoles)) {
        die('Недопустимая роль');
    }
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userId]);
    header('Location: /admin.php?action=users');
    exit;
}

// --- Модерация отзывов (одобрить / отклонить) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['moderate_review']) && $role === 'admin') {
    verify_csrf();
    $reviewId = (int)$_POST['moderate_review'];
    $status = $_POST['status'] ?? '';
    if ($status === 'approve') {
        $newStatus = 'approved';
    } elseif ($status === 'reject') {
        $newStatus = 'rejected';
    } else {
        die('Неверный статус');
    }
    $stmt = $pdo->prepare("UPDATE reviews SET moderation_status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $reviewId]);
    header("Location: /admin.php?action=reviews");
    exit;
}

// --- Удаление отзыва (только админ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review']) && $role === 'admin') {
    verify_csrf();
    $reviewId = (int)$_POST['delete_review'];
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([$reviewId]);
    header("Location: /admin.php?action=reviews");
    exit;
}

// --- ДОБАВЛЕНИЕ НОВОГО СОТРУДНИКА (только админ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee']) && $role === 'admin') {
    verify_csrf();
    $name = sanitize_string($_POST['name']);
    $email = !empty($_POST['email']) ? sanitize_email($_POST['email']) : null;
    $phone = !empty($_POST['phone']) ? sanitize_phone($_POST['phone']) : null;
    $years_experience = (int)$_POST['years_experience'];
    
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $maxFileSize = 2 * 1024 * 1024; // 2 MB
        if ($_FILES['photo']['size'] > $maxFileSize) {
            die('Файл слишком большой. Максимум 2 МБ.');
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            die('Недопустимый тип файла.');
        }
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/employees/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = uniqid() . '.' . $ext;
            $full_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $full_path)) {
                $photo_path = '/assets/images/employees/' . $filename;
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO employees (name, email, phone, photo, years_experience, rating) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->execute([$name, $email, $phone, $photo_path, $years_experience]);
    header('Location: /admin.php?action=employees');
    exit;
}

// --- РЕДАКТИРОВАНИЕ СОТРУДНИКА (только админ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee']) && $role === 'admin') {
    verify_csrf();
    $employee_id = (int)$_POST['employee_id'];
    $name = sanitize_string($_POST['name']);
    $email = !empty($_POST['email']) ? sanitize_email($_POST['email']) : null;
    $phone = !empty($_POST['phone']) ? sanitize_phone($_POST['phone']) : null;
    $years_experience = (int)$_POST['years_experience'];
    
    $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ?, years_experience = ? WHERE id = ?");
    $stmt->execute([$name, $email, $phone, $years_experience, $employee_id]);
    
    // Обновление фото, если загружено новое
    if (isset($_FILES['photo_edit']) && $_FILES['photo_edit']['error'] === UPLOAD_ERR_OK) {
        $maxFileSize = 2 * 1024 * 1024; // 2 MB
        if ($_FILES['photo_edit']['size'] > $maxFileSize) {
            die('Файл слишком большой. Максимум 2 МБ.');
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['photo_edit']['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            die('Недопустимый тип файла.');
        }
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/employees/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['photo_edit']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            // Удаляем старое фото
            $stmt = $pdo->prepare("SELECT photo FROM employees WHERE id = ?");
            $stmt->execute([$employee_id]);
            $old_photo = $stmt->fetchColumn();
            if ($old_photo && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_photo)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $old_photo);
            }
            
            $filename = uniqid() . '.' . $ext;
            $full_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['photo_edit']['tmp_name'], $full_path)) {
                $photo_path = '/assets/images/employees/' . $filename;
                $stmt = $pdo->prepare("UPDATE employees SET photo = ? WHERE id = ?");
                $stmt->execute([$photo_path, $employee_id]);
            }
        }
    }
    header('Location: /admin.php?action=employees');
    exit;
}

// --- УДАЛЕНИЕ СОТРУДНИКА (только админ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee']) && $role === 'admin') {
    verify_csrf();
    $employeeId = (int)$_POST['delete_employee'];
    // Удаление фото и записи
    $stmt = $pdo->prepare("SELECT photo FROM employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    $photo = $stmt->fetchColumn();
    if ($photo && file_exists($_SERVER['DOCUMENT_ROOT'] . $photo)) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $photo);
    }
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    header('Location: /admin.php?action=employees');
    exit;
}

// --- Получение данных для отображения ---
$services = getAllServicesPaginated($pdo, $limit_services, $offset_services);
$totalServices = getTotalServices($pdo);
$totalPagesServices = ceil($totalServices / $limit_services);
$baseUrlServices = '?action=services';

$users = getAllUsersPaginated($pdo, $limit_users, $offset_users, $user_email_filter, $user_role_filter);
$totalUsers = getTotalUsers($pdo, $user_email_filter, $user_role_filter);
$totalPagesUsers = ceil($totalUsers / $limit_users);
$baseUrlUsers = '?action=users'
    . ($user_email_filter ? '&user_email=' . urlencode($user_email_filter) : '')
    . ($user_role_filter ? '&user_role=' . urlencode($user_role_filter) : '');

$employees = getAllEmployeesPaginated($pdo, $limit_employees, $offset_employees, $employee_name_filter, $employee_sort);
$totalEmployees = getTotalEmployees($pdo, $employee_name_filter);
$totalPagesEmployees = ceil($totalEmployees / $limit_employees);
$baseUrlEmployees = '?action=employees'
    . ($employee_name_filter ? '&employee_name=' . urlencode($employee_name_filter) : '')
    . ($employee_sort ? '&employee_sort=' . urlencode($employee_sort) : '');

$reviews = [];
$totalReviews = 0;
$totalPagesReviews = 1;
if ($action === 'reviews') {
    $reviews = getReviewsPaginated($pdo, $limit_reviews, $offset_reviews, $review_status_filter);
    $totalReviews = getTotalReviews($pdo, $review_status_filter);
    $totalPagesReviews = ceil($totalReviews / $limit_reviews);
    $baseUrlReviews = '?action=reviews'
    . ($review_status_filter ? '&review_status=' . urlencode($review_status_filter) : '');
}

// --- БЛОКИРОВКА ПОЛЬЗОВАТЕЛЯ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_user']) && $role === 'admin') {
    verify_csrf();
    $userId = (int)$_POST['block_user'];
    // Проверка, что не блокируем администратора
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $targetRole = $stmt->fetchColumn();
    if ($targetRole === 'admin') {
        $_SESSION['error'] = 'Нельзя заблокировать администратора.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
        $stmt->execute([$userId]);
    }
    header('Location: /admin.php?action=users');
    exit;
}

// --- РАЗБЛОКИРОВКА ПОЛЬЗОВАТЕЛЯ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock_user']) && $role === 'admin') {
    verify_csrf();
    $userId = (int)$_POST['unblock_user'];
    $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
    $stmt->execute([$userId]);
    header('Location: /admin.php?action=users');
    exit;
}

require_once 'includes/header.php';
?>

<div class="container container-custom">
    <h1>Административная панель</h1>
    <div class="admin-tabs">
        <a href="?action=orders" class="btn <?= $action == 'orders' ? 'active' : '' ?>">Заказы</a>
        <?php if ($role === 'admin'): ?>
            <a href="?action=services" class="btn <?= $action == 'services' ? 'active' : '' ?>">Услуги</a>
            <a href="?action=users" class="btn <?= $action == 'users' ? 'active' : '' ?>">Пользователи</a>
            <a href="?action=employees" class="btn <?= $action == 'employees' ? 'active' : '' ?>">Сотрудники</a>
        <?php endif; ?>
        <a href="?action=reviews" class="btn <?= $action == 'reviews' ? 'active' : '' ?>">Отзывы</a>
    </div>

    <!-- ==================== ВКЛАДКА ЗАКАЗЫ ==================== -->
    <?php if ($action == 'orders'): ?>
        <h2>Заказы</h2>
        <div class="search-form">
            <p>Фильтрация</p>
            <form class="profile-filter-form" method="GET" action="">
                <input type="hidden" name="action" value="orders">
                <input type="hidden" name="page_orders" value="1">
                <label>Статус:</label>
                <select class="filter-center" name="order_status">
                    <option value="">Все</option>
                    <option value="Новый" <?= $order_status_filter == 'Новый' ? 'selected' : '' ?>>Новый</option>
                    <option value="В работе" <?= $order_status_filter == 'В работе' ? 'selected' : '' ?>>В работе</option>
                    <option value="Выполнен" <?= $order_status_filter == 'Выполнен' ? 'selected' : '' ?>>Выполнен</option>
                    <option value="Отменён" <?= $order_status_filter == 'Отменён' ? 'selected' : '' ?>>Отменён</option>
                </select>
                <label>Дата:</label>
                <input class="filter-center" type="date" name="order_date" value="<?= h($order_date_filter) ?>">
                <button type="submit" class="btn-small">Фильтровать</button>
                <a href="?action=orders" class="btn-small cancel">Сбросить</a>
            </form>
        </div>
        <div class="table-wrapper"> 
            <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Клиент</th>
                    <th>Сумма (₽)</th>
                    <th>Адрес</th>
                    <th>Тип помещения</th>
                    <th>Статус</th>
                    <th>Сотрудник</th>
                    <th>Вид оплаты</th>
                    <th>Оплата</th>
                    <th>Комментарий</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $orders = getAllOrdersAdminPaginated($pdo, $limit_orders, $offset_orders, $order_status_filter, $order_date_filter);
                $totalOrders = getTotalOrdersAdmin($pdo, $order_status_filter, $order_date_filter);
                $totalPagesOrders = ceil($totalOrders / $limit_orders);
                $baseUrlOrders = '?action=orders'
                    . ($order_status_filter ? '&order_status=' . urlencode($order_status_filter) : '')
                    . ($order_date_filter ? '&order_date=' . urlencode($order_date_filter) : '');
                // Получаем только присутствующих сотрудников
                $stmt = $pdo->prepare("SELECT * FROM employees WHERE is_absent = 0 ORDER BY rating DESC");
                $stmt->execute();
                $availableEmployees = $stmt->fetchAll();
                ?>
                <?php foreach ($orders as $order): ?>
                <form method="POST" onsubmit="return confirm('Сохранить изменения?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <tr>
                        <!-- ID заказа с подсветкой для отменённых -->
                        <td style="width: 1px; text-align:center;">
                            <?php if ($order['status'] === 'Отменён'): ?>
                                <span class="canceled-id"><?= $order['id'] ?></span>
                            <?php else: ?>
                                <?= $order['id'] ?>
                            <?php endif; ?>
                            <?php if ($order['rescheduled_from'] !== null): ?>
                                <span class="badge rescheduled-badge" style="background:#ff9800; color:white; padding:2px 6px; border-radius:4px; font-size:10px; margin-left:5px;">перенесён</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $order['date'] ?></td>
                        <td><?= h($order['user_name']) ?></td>
                        <td><?= number_format($order['total_price'], 2) ?></td>
                        <td><?= h($order['address']) ?></td>
                        <td><?= $order['property_type'] ?></td>

                        <!-- Статус заказа (выпадающий список) -->
                        <td>
                            <select name="status" class="w-100">
                                <option value="Новый" <?= $order['status'] == 'Новый' ? 'selected' : '' ?>>Новый</option>
                                <option value="В работе" <?= $order['status'] == 'В работе' ? 'selected' : '' ?>>В работе</option>
                                <option value="Выполнен" <?= $order['status'] == 'Выполнен' ? 'selected' : '' ?>>Выполнен</option>
                                <option value="Отменён" <?= $order['status'] == 'Отменён' ? 'selected' : '' ?>>Отменён</option>
                            </select>
                        </td>

                        <!-- Сотрудник (выпадающий список) -->
                        <td>
                            <select name="employee_id" class="w-100">
                                <option value="">— Не назначен —</option>
                                <?php foreach ($availableEmployees as $emp): ?>
                                    <?php
                                    $stmtBusy = $pdo->prepare("SELECT id FROM orders WHERE employee_id = ? AND status = 'В работе' AND id != ?");
                                    $stmtBusy->execute([$emp['id'], $order['id']]);
                                    $isBusy = $stmtBusy->fetch();
                                    ?>
                                    <option value="<?= $emp['id'] ?>" <?= ($order['employee_id'] == $emp['id']) ? 'selected' : '' ?> <?= $isBusy && $order['employee_id'] != $emp['id'] ? 'disabled' : '' ?>>
                                        <?= h($emp['name']) ?> (⭐ <?= $emp['rating'] ?>)
                                        <?= $isBusy && $order['employee_id'] != $emp['id'] ? ' — ЗАНЯТ' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <!-- Вид оплаты (выпадающий список) -->
                        <td>
                            <select name="payment_type" class="w-100">
                                <option value="cash" <?= ($order['payment_type'] ?? 'cash') == 'cash' ? 'selected' : '' ?>>💰 Наличные</option>
                                <option value="online" <?= ($order['payment_type'] ?? 'cash') == 'online' ? 'selected' : '' ?>>💳 Карта онлайн</option>
                            </select>
                        </td>

                        <!-- Оплата: для отменённых – текст, иначе выпадающий список -->
                        <td>
                            <?php if ($order['status'] === 'Отменён'): ?>
                                <?php $paymentStatus = $order['payment_status'] ?? 'pending'; ?>
                                <?php if ($paymentStatus == 'paid'): ?>
                                    <span class="status-canceled">🔄 Возврат средств</span>
                                <?php else: ?>
                                    <span class="status-pending">❌ Отменён (не оплачен)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <select name="payment_status" class="w-100">
                                    <option value="pending" <?= ($order['payment_status'] ?? 'pending') == 'pending' ? 'selected' : '' ?>>⏳ Не оплачен</option>
                                    <option value="paid" <?= ($order['payment_status'] ?? 'pending') == 'paid' ? 'selected' : '' ?>>✅ Оплачен</option>
                                </select>
                            <?php endif; ?>
                        </td>

                        <!-- Комментарий -->
                        <td>
                            <button type="button" class="toggle-comment" data-id="<?= $order['id'] ?>">Показать комментарий</button>
                            <div id="comment-<?= $order['id'] ?>" class="comment-hidden" style="margin-top:5px; padding:5px; background:#f1f5f9; border-radius:5px;">
                                <?= !empty($order['comment']) ? nl2br(h($order['comment'])) : '<em>Нет комментария</em>' ?>
                            </div>
                        </td>

                        <!-- Действия -->
                        <td>
                            <button type="submit" name="update_order" class="btn-small">Сохранить</button>
                            <a href="view_order.php?id=<?= $order['id'] ?>" class="btn-small">Детали</a>
                        </td>
                    </tr>
                </form>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?= renderPagination($page_orders, $totalPagesOrders, $baseUrlOrders, 'page_orders') ?>
    <!-- ==================== ВКЛАДКА УСЛУГИ (только админ) ==================== -->
    <?php elseif ($action == 'services' && $role === 'admin'): ?>
        <h2>Управление услугами</h2>
        <form method="POST" class="add-form">
            <?= csrf_field() ?>
            <input type="text" name="name" placeholder="Название услуги" required>
            <textarea name="description" placeholder="Описание"></textarea>
            <input type="number" name="price" placeholder="Цена (руб)" step="100" required>
            <input type="number" name="sort_order" placeholder="Порядок сортировки" value="0">
            <button class="btn btn-primary" type="submit" name="add_service">Добавить услугу</button>
        </form>
        <div class="table-wrapper">
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Название</th><th>Описание</th><th>Цена (₽)</th><th>Порядок</th><th>Действия</th></tr></thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?= $service['id'] ?></td>
                        <td><?= h($service['name']) ?></td>
                        <td><?= h($service['description']) ?></td>
                        <td><?= number_format($service['price'], 2) ?></td>
                        <td><?= $service['sort_order'] ?></td>
                        <td>
                            <button onclick="openEditServiceModal(<?= $service['id'] ?>, '<?= h(addslashes($service['name'])) ?>', '<?= h(addslashes($service['description'])) ?>', <?= $service['price'] ?>, <?= $service['sort_order'] ?>)" class="btn-small" style="width: 100%">Редактировать</button>
                            <form method="POST" onsubmit="return confirm('Удалить услугу?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="delete_service" value="<?= $service['id'] ?>">
                                <button style="width: 100% " type="submit" class="btn-small">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= renderPagination($page_services, $totalPagesServices, $baseUrlServices, 'page_services') ?>

        <!-- Модальное окно редактирования услуги -->
        <div id="editServiceModal" class="modal modal-service">
            <h3>Редактировать услугу</h3>
            <form method="POST" id="editServiceForm">
                <?= csrf_field() ?>
                <input type="hidden" name="service_id" id="edit_service_id">
                <input type="text" name="name" id="edit_service_name" placeholder="Название услуги" required>
                <textarea name="description" id="edit_service_description" placeholder="Описание" rows="3"></textarea>
                <input type="number" name="price" id="edit_service_price" placeholder="Цена (руб)" step="any" required>
                <input type="number" name="sort_order" id="edit_service_sort_order" placeholder="Порядок сортировки">
                <button class="btn btn-done" type="submit" name="edit_service">Сохранить</button>
                <button class="btn btn-cancel" type="button" onclick="closeServiceModal()">Отмена</button>
            </form>
        </div>
        <div id="serviceModalOverlay" class="modal-overlay" onclick="closeServiceModal()"></div>

    <!-- ==================== ВКЛАДКА ПОЛЬЗОВАТЕЛИ (только админ) ==================== -->
   <?php elseif ($action == 'users' && $role === 'admin'): ?>
        <h2>Пользователи</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= h($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <div class="search-form">
            <p>Фильтрация</p>
            <form class="search-form-inline" method="GET" action="">
                <input type="hidden" name="action" value="users">
                <input type="hidden" name="page_users" value="1">
                <label>Email:</label>
                <input class="filter-center" type="text" name="user_email" placeholder="Поиск по email" value="<?= h($user_email_filter) ?>">
                <label>Роль:</label>
                <select class="filter-center" name="user_role">
                    <option value="">Все</option>
                    <option value="client" <?= $user_role_filter == 'client' ? 'selected' : '' ?>>Клиент</option>
                    <option value="manager" <?= $user_role_filter == 'manager' ? 'selected' : '' ?>>Менеджер</option>
                    <option value="admin" <?= $user_role_filter == 'admin' ? 'selected' : '' ?>>Администратор</option>
                </select>
                <button type="submit" class="btn-small">Найти</button>
                <a href="?action=users" class="btn-small cancel">Сбросить</a>
            </form>
        </div>
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Роль</th>
                        <th>Блокировка аккаунта</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= h($user['name']) ?></td>
                        <td><?= h($user['email']) ?></td>
                        <td><?= h($user['phone']) ?></td>
                        <td>
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select class="profile-filter-form" name="new_role">
                                    <option value="client" <?= $user['role'] == 'client' ? 'selected' : '' ?>>Клиент</option>
                                    <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>>Менеджер</option>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Администратор</option>
                                </select>
                                <button style="width: 100%" type="submit" name="change_role">Сохранить</button>
                            </form>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span>Недоступно</span>
                            <?php elseif (!$user['is_blocked']): ?>
                                <form method="POST" onsubmit="return confirm('Заблокировать пользователя?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="block_user" value="<?= $user['id'] ?>">
                                    <button style="width: 100%" type="submit">Заблокировать</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" onsubmit="return confirm('Разблокировать пользователя?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="unblock_user" value="<?= $user['id'] ?>">
                                    <button style="width: 100%" type="submit">Разблокировать</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td><?= $user['created_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= renderPagination($page_users, $totalPagesUsers, $baseUrlUsers, 'page_users') ?>

    <!-- ==================== ВКЛАДКА СОТРУДНИКИ (только админ) ==================== -->
    <?php elseif ($action == 'employees' && $role === 'admin'): ?>
        <h2>Управление сотрудниками</h2>

        <div class="search-form">
            <p>Фильтрация</p>
            <form class="search-form-inline" method="GET" action="">
                <input type="hidden" name="action" value="employees">
                <input type="hidden" name="page_employees" value="1">
                <label>Имя:</label>
                <input class="filter-center" type="text" name="employee_name" placeholder="Поиск по имени" value="<?= h($employee_name_filter) ?>">
                <label>Сортировка по рейтингу:</label>
                <select class="filter-center" name="employee_sort">
                    <option value="rating_desc" <?= $employee_sort == 'rating_desc' ? 'selected' : '' ?>>По убыванию (высший)</option>
                    <option value="rating_asc" <?= $employee_sort == 'rating_asc' ? 'selected' : '' ?>>По возрастанию (низший)</option>
                </select>
                <button type="submit" class="btn-small">Применить</button>
                <a href="?action=employees" class="btn-small cancel">Сбросить</a>
            </form>
        </div>

        <!-- Форма добавления сотрудника -->
        <div class="add-form">
            <h3>Добавить нового сотрудника</h3>
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="text" name="name" placeholder="ФИО" required>
                <input type="email" name="email" placeholder="Email (необязательно)">
                <input type="tel" name="phone" placeholder="Телефон (необязательно)">
                <input type="number" name="years_experience" placeholder="Опыт (лет)" required>
                <label class="btn btn-primary upload-file" class="filter-center">
                    📁 Выбрать файл
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" required hidden>
                </label>
                <button class="btn btn-primary" type="submit" name="add_employee">Добавить</button>
            </form>
        </div>
    
    <div class="table-wrapper">
        <!-- Список сотрудников -->
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Фото</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Опыт (лет)</th>
                    <th>Отсутствует</th>
                    <th>Рейтинг</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                <tr>
                    <td><?= $emp['id'] ?></td>
                    <td>
                        <?php if ($emp['photo']): ?>
                            <img src="<?= h($emp['photo']) ?>" width="50" height="50" style="object-fit:cover; border-radius:50%;">
                        <?php else: ?>
                            <span style="color:#999;">Нет фото</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($emp['name']) ?></td>
                    <td><?= h($emp['email'] ?? '—') ?></td>
                    <td><?= h($emp['phone'] ?? '—') ?></td>
                    <td><?= $emp['years_experience'] ?></td>
                    <td class="filter-center">
                        <form method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                            <input type="hidden" name="is_absent" value="0">
                            <input type="checkbox" name="is_absent" value="1" <?= $emp['is_absent'] ? 'checked' : '' ?> onchange="this.form.submit()" class="radio-button">
                        </form>
                    <td>⭐ <?= number_format($emp['rating'], 1) ?><p>/ 5</p>
                    <td>
                        <button onclick="openEditModal(<?= $emp['id'] ?>, '<?= h(addslashes($emp['name'])) ?>', <?= $emp['years_experience'] ?>, '<?= h(addslashes($emp['email'])) ?>', '<?= h(addslashes($emp['phone'])) ?>')" class="btn-small">Редактировать</button>
                        <form method="POST" onsubmit="return confirm('Удалить сотрудника?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delete_employee" value="<?= $emp['id'] ?>">
                            <button style="width: 100%" type="submit" class="btn-small cancel">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= renderPagination($page_employees, $totalPagesEmployees, $baseUrlEmployees, 'page_employees') ?>
    
    <!-- Модальное окно редактирования сотрудника -->
    <div id="editModal" class="modal">
        <h3>Редактировать сотрудника</h3>
        <form method="POST" enctype="multipart/form-data" id="editForm">
            <?= csrf_field() ?>
            <input type="hidden" name="employee_id" id="edit_id">
            <input type="text" name="name" id="edit_name" placeholder="ФИО" required>
            <input type="email" name="email" id="edit_email" placeholder="Email">
            <input type="tel" name="phone" id="edit_phone" placeholder="Телефон">
            <input type="number" name="years_experience" id="edit_years" placeholder="Опыт (лет)" required>
            <label class="btn btn-primary upload-file">
                📁 Выбрать файл
                <input type="file" name="photo_edit" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
            </label>
            <p style="font-size:12px; color:#666; margin:5px 0;">Не добавляйте фото, если хотите сохранить прежнее</p>
            <button class="btn btn-done" type="submit" name="edit_employee">Сохранить</button>
            <button class="btn btn-cancel" type="button" onclick="closeModal()">Отмена</button>
        </form>
    </div>
    <div id="modalOverlay" class="modal-overlay" onclick="closeModal()"></div>
    
    <script>
    function openEditModal(id, name, years, email, phone) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_years').value = years;
        document.getElementById('edit_email').value = email || '';
        document.getElementById('edit_phone').value = phone || '';
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('modalOverlay').style.display = 'block';
    }
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }
    </script>

    <!-- ==================== ВКЛАДКА ОТЗЫВЫ (админ и менеджер) ==================== -->
    <?php elseif ($action == 'reviews'): ?>
        <h2>Модерация отзывов</h2>

        <div class="search-form">
            <p>Фильтрация</p>
            <form class="search-form-inline" method="GET" action="">
                <input type="hidden" name="action" value="reviews">
                <input type="hidden" name="page_reviews" value="1">
                <label>Статус:</label>
                <select class="filter-center" name="review_status">
                    <option value="">Все</option>
                    <option value="pending" <?= $review_status_filter == 'pending' ? 'selected' : '' ?>>На модерации</option>
                    <option value="approved" <?= $review_status_filter == 'approved' ? 'selected' : '' ?>>Одобрен</option>
                    <option value="rejected" <?= $review_status_filter == 'rejected' ? 'selected' : '' ?>>Отклонён</option>
                </select>
                <button type="submit" class="btn-small">Фильтровать</button>
                <a href="?action=reviews" class="btn-small cancel">Сбросить</a>
            </form>
        </div>

        <?php if (count($reviews) > 0): ?>
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Пользователь / Email</th><th>Заказ №</th><th>Оценки</th><th>Текст отзыва</th><th>Статус</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                        <tr >
                            <td><?= $review['id'] ?></td>
                            <td><?= h($review['user_name']) ?><br><small><?= h($review['user_email']) ?></small></td>
                            <td><?= $review['order_id'] ?></td>
                            <td>⭐ Уборка: <?= $review['rating'] ?>/5<br>👤 Сотрудник: <?= $review['employee_rating'] ?>/5</td>
                            <td><?= nl2br(h($review['text'])) ?></td>
                            <td>
                                <?php 
                                $statusMap = [
                                    'approved' => '<span style="color:green;">✅ Одобрен</span>',
                                    'rejected' => '<span style="color:red;">❌ Отклонён</span>',
                                    'pending'  => '<span style="color:orange;">⏳ На модерации</span>'
                                ];
                                echo $statusMap[$review['moderation_status']] ?? '<span style="color:gray;">Неизвестно</span>';
                                ?>
                            </td>
                            <td style="width: 100%;" class="btn-actions">
                                <?php if ($review['moderation_status'] == 'pending'): ?>
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="moderate_review" value="<?= $review['id'] ?>">
                                        <input type="hidden" name="status" value="approve">
                                        <button style="width: 100%" type="submit" class="btn-small btn-approve-review">Одобрить</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Отклонить отзыв?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="moderate_review" value="<?= $review['id'] ?>">
                                        <input type="hidden" name="status" value="reject">
                                        <button style="width: 100%" type="submit" class="btn-small btn-reject-review">Отклонить</button>
                                    </form>
                                <?php elseif ($review['moderation_status'] == 'approved' && $role === 'admin'): ?>
                                        <form method="POST" onsubmit="return confirm('Отклонить отзыв?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="moderate_review" value="<?= $review['id'] ?>">
                                        <input type="hidden" name="status" value="reject">
                                        <button style="width: 100%" type="submit" class="btn-small btn-reject-review">Отклонить</button>
                                    </form>
                                <?php elseif ($review['moderation_status'] == 'rejected' && $role === 'admin'): ?>
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="moderate_review" value="<?= $review['id'] ?>">
                                        <input type="hidden" name="status" value="approve">
                                        <button style="width: 100%" type="submit" class="btn-small btn-approve-review">Одобрить</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($role === 'admin'): ?>
                                    <form method="POST" onsubmit="return confirm('Удалить навсегда?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delete_review" value="<?= $review['id'] ?>">
                                        <button style="width: 100%" type="submit" class="btn-small">Удалить</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= renderPagination($page_reviews, $totalPagesReviews, $baseUrlReviews, 'page_reviews') ?>
        <?php else: ?>
            <p>Нет отзывов для отображения.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Шторка для комментариев в заказах
document.querySelectorAll('.toggle-comment').forEach(btn => {
    btn.addEventListener('click', function() {
        let id = this.getAttribute('data-id');
        let div = document.getElementById('comment-' + id);
        if (div.classList.contains('comment-hidden')) {
            div.classList.remove('comment-hidden');
            this.textContent = 'Скрыть комментарий';
        } else {
            div.classList.add('comment-hidden');
            this.textContent = 'Показать комментарий';
        }
    });
});

// Функции для модального окна услуги
function openEditServiceModal(id, name, description, price, sort_order) {
    document.getElementById('edit_service_id').value = id;
    document.getElementById('edit_service_name').value = name;
    document.getElementById('edit_service_description').value = description;
    document.getElementById('edit_service_price').value = price;
    document.getElementById('edit_service_sort_order').value = sort_order;
    document.getElementById('editServiceModal').style.display = 'block';
    document.getElementById('serviceModalOverlay').style.display = 'block';
}
function closeServiceModal() {
    document.getElementById('editServiceModal').style.display = 'none';
    document.getElementById('serviceModalOverlay').style.display = 'none';
}

</script>

<?php require_once 'includes/footer.php'; ?>