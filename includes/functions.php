<?php

// ========== 1. ПОЛЬЗОВАТЕЛИ ==========

function registerUser($pdo, $email, $password, $name, $phone) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name, phone, verification_token, verification_token_expires_at, last_verification_sent, is_verified) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
    if ($stmt->execute([$email, $hash, $name, $phone, $token, $expires, $now])) {
        return $token;
    }
    return false;
}

function loginUser($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_verified = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    return false;
}

function getUserById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT id, email, name, phone, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateUserProfile($pdo, $id, $name, $phone) {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    return $stmt->execute([$name, $phone, $id]);
}

// ========== 2. УСЛУГИ ==========

function getAllServices($pdo) {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY sort_order");
    return $stmt->fetchAll();
}

function getServiceById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addService($pdo, $name, $description, $price, $sortOrder) {
    $stmt = $pdo->prepare("INSERT INTO services (name, description, price, sort_order) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $description, $price, $sortOrder]);
}

function updateService($pdo, $id, $name, $description, $price, $sortOrder) {
    $stmt = $pdo->prepare("UPDATE services SET name=?, description=?, price=?, sort_order=? WHERE id=?");
    return $stmt->execute([$name, $description, $price, $sortOrder, $id]);
}

function deleteService($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM services WHERE id=?");
    return $stmt->execute([$id]);
}

// ========== 3. ЗАКАЗЫ ==========

function createOrder($pdo, $userId, $servicesData, $address, $date, $time, $comment, $propertyType = 'Квартира', $employeeId = null, $rescheduledFrom = null, $paymentType = 'cash') {
    try {
        $pdo->beginTransaction();
        $total = 0;
        foreach ($servicesData as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        $discount = getUserDiscount($pdo, $userId);
        $finalTotal = $total * (100 - $discount) / 100;
        
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, address, date, time, comment, property_type, employee_id, status, rescheduled_from, payment_type, payment_status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Новый', ?, ?, 'pending')");
        $stmt->execute([$userId, $finalTotal, $address, $date, $time, $comment, $propertyType, $employeeId, $rescheduledFrom, $paymentType]);
        
        $orderId = $pdo->lastInsertId();
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, service_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($servicesData as $item) {
            $stmtItem->execute([$orderId, $item['service_id'], $item['quantity'], $item['price']]);
        }
        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getOrderDetails($pdo, $orderId, $userId = null) {
    $sql = "SELECT o.*, oi.service_id, oi.quantity, oi.price, s.name as service_name 
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id 
            JOIN services s ON oi.service_id = s.id 
            WHERE o.id = ?";
    if ($userId) $sql .= " AND o.user_id = ?";
    $stmt = $userId ? $pdo->prepare($sql) : $pdo->prepare($sql);
    $userId ? $stmt->execute([$orderId, $userId]) : $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    if (empty($items)) return null;
    $order = [
        'id' => $items[0]['id'],
        'user_id' => $items[0]['user_id'],
        'status' => $items[0]['status'],
        'total_price' => $items[0]['total_price'],
        'address' => $items[0]['address'],
        'date' => $items[0]['date'],
        'time' => $items[0]['time'],
        'comment' => $items[0]['comment'],
        'property_type' => $items[0]['property_type'] ?? 'Квартира',
        'employee_id' => $items[0]['employee_id'],
        'is_canceled' => $items[0]['is_canceled'],
        'created_at' => $items[0]['created_at'],
        'items' => []
    ];
    foreach ($items as $item) {
        $order['items'][] = [
            'service_id' => $item['service_id'],
            'service_name' => $item['service_name'],
            'quantity' => $item['quantity'],
            'price' => $item['price']
        ];
    }
    return $order;
}

// ========== 4. СОТРУДНИКИ ==========

function getAllEmployees($pdo) {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY rating DESC");
    return $stmt->fetchAll();
}

function getEmployeeById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ========== 5. ОТЗЫВЫ ==========

function addReview($pdo, $userId, $orderId, $employeeId, $rating, $employeeRating, $text) {
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, order_id, employee_id, rating, employee_rating, text, moderation_status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    return $stmt->execute([$userId, $orderId, $employeeId, $rating, $employeeRating, $text]);
}

function hasReview($pdo, $userId, $orderId) {
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND order_id = ?");
    $stmt->execute([$userId, $orderId]);
    return $stmt->fetch() !== false;
}

function getModeratedReviews($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.moderation_status = 'approved' 
        ORDER BY r.created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// ========== 6. НАКОПИТЕЛЬНЫЕ СКИДКИ ==========

function getUserDiscount($pdo, $userId) {
    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
    $stmt = $pdo->prepare("
        SELECT SUM(total_price) 
        FROM orders 
        WHERE user_id = ? 
          AND status = 'Выполнен'
          AND date >= ?
    ");
    $stmt->execute([$userId, $sevenDaysAgo]);
    $total = (float)$stmt->fetchColumn();
    $discount = min(floor($total / 1000) * 0.5, 10);
    return (int)floor($discount);
}

// ========== ПАГИНАЦИЯ ==========

function getAllOrdersAdminPaginated($pdo, $limit, $offset, $status_filter = '', $date_filter = '') {
    $sql = "SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
    $params = [];
    if (!empty($status_filter)) {
        $sql .= " AND o.status = ?";
        $params[] = $status_filter;
    }
    if (!empty($date_filter)) {
        $sql .= " AND o.date = ?";
        $params[] = $date_filter;
    }
    $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $idx = 1;
    foreach ($params as $param) {
        $stmt->bindValue($idx++, $param);
    }
    $stmt->bindValue($idx++, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($idx, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTotalOrdersAdmin($pdo, $status_filter = '', $date_filter = '') {
    $sql = "SELECT COUNT(*) FROM orders o WHERE 1=1";
    $params = [];
    if (!empty($status_filter)) {
        $sql .= " AND o.status = ?";
        $params[] = $status_filter;
    }
    if (!empty($date_filter)) {
        $sql .= " AND o.date = ?";
        $params[] = $date_filter;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function getUserOrdersPaginated($pdo, $userId, $limit, $offset, $filter_date = '') {
    $sql = "SELECT * FROM orders WHERE user_id = ?";
    if (!empty($filter_date)) {
        $sql .= " AND date = ?";
    }
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $idx = 2;
    if (!empty($filter_date)) {
        $stmt->bindValue($idx, $filter_date);
        $idx++;
    }
    $stmt->bindValue($idx, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($idx+1, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTotalUserOrders($pdo, $userId, $filter_date = '') {
    $sql = "SELECT COUNT(*) FROM orders WHERE user_id = ?";
    $params = [$userId];
    if (!empty($filter_date)) {
        $sql .= " AND date = ?";
        $params[] = $filter_date;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function getReviewsPaginated($pdo, $limit, $offset, $status_filter = '') {
    $sql = "SELECT r.*, u.name as user_name, u.email as user_email, o.id as order_id 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            JOIN orders o ON r.order_id = o.id 
            WHERE 1=1";
    $params = [];
    if (!empty($status_filter)) {
        $sql .= " AND r.moderation_status = ?";
        $params[] = $status_filter;
    }
    $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $idx = 1;
    foreach ($params as $param) {
        $stmt->bindValue($idx++, $param);
    }
    $stmt->bindValue($idx++, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($idx, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTotalReviews($pdo, $status_filter = '') {
    $sql = "SELECT COUNT(*) FROM reviews WHERE 1=1";
    $params = [];
    if (!empty($status_filter)) {
        $sql .= " AND moderation_status = ?";
        $params[] = $status_filter;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function getAllUsersPaginated($pdo, $limit, $offset, $email_filter = '', $role_filter = '') {
    $sql = "SELECT id, email, name, phone, role, created_at, is_blocked FROM users WHERE 1=1";
    $params = [];
    if (!empty($email_filter)) {
        $sql .= " AND email LIKE ?";
        $params[] = "%$email_filter%";
    }
    if (!empty($role_filter)) {
        $sql .= " AND role = ?";
        $params[] = $role_filter;
    }
    $sql .= " ORDER BY id LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $idx = 1;
    foreach ($params as $param) {
        $stmt->bindValue($idx++, $param);
    }
    $stmt->bindValue($idx++, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($idx, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTotalUsers($pdo, $email_filter = '', $role_filter = '') {
    $sql = "SELECT COUNT(*) FROM users WHERE 1=1";
    $params = [];
    if (!empty($email_filter)) {
        $sql .= " AND email LIKE ?";
        $params[] = "%$email_filter%";
    }
    if (!empty($role_filter)) {
        $sql .= " AND role = ?";
        $params[] = $role_filter;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function getAllEmployeesPaginated($pdo, $limit, $offset, $name_filter = '', $sort = 'rating_desc') {
    $sql = "SELECT * FROM employees WHERE 1=1";
    $params = [];
    if (!empty($name_filter)) {
        $sql .= " AND name LIKE ?";
        $params[] = "%$name_filter%";
    }
    if ($sort == 'rating_desc') {
        $sql .= " ORDER BY rating DESC";
    } else {
        $sql .= " ORDER BY rating ASC";
    }
    $sql .= " LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $idx = 1;
    foreach ($params as $param) {
        $stmt->bindValue($idx++, $param);
    }
    $stmt->bindValue($idx++, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($idx, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTotalEmployees($pdo, $name_filter = '') {
    $sql = "SELECT COUNT(*) FROM employees WHERE 1=1";
    $params = [];
    if (!empty($name_filter)) {
        $sql .= " AND name LIKE ?";
        $params[] = "%$name_filter%";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function getAllServicesPaginated($pdo, $limit, $offset) {
    $stmt = $pdo->prepare("
        SELECT * FROM services 
        ORDER BY sort_order 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTotalServices($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    return $stmt->fetchColumn();
}

function renderPagination($currentPage, $totalPages, $baseUrl, $paramName = 'page') {
    if ($totalPages <= 1) return '';
    $html = '<div class="pagination">';
    
    // Предыдущая страница
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '&' . $paramName . '=' . ($currentPage - 1) . '" class="pagination-link">&laquo; Предыдущая</a>';
    } else {
        $html .= '<span class="pagination-link disabled">&laquo; Предыдущая</span>';
    }
    
    // Определяем диапазон отображаемых страниц (по 2 слева и справа от текущей)
    $range = 2;
    $start = max(1, $currentPage - $range);
    $end = min($totalPages, $currentPage + $range);
    
    // Первая страница и многоточие (если нужно)
    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '&' . $paramName . '=1" class="pagination-link">1</a>';
        if ($start > 2) {
            $html .= '<span class="pagination-link disabled">...</span>';
        }
    }
    
    // Основной блок страниц
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="pagination-link active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . '&' . $paramName . '=' . $i . '" class="pagination-link">' . $i . '</a>';
        }
    }
    
    // Последняя страница и многоточие (если нужно)
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="pagination-link disabled">...</span>';
        }
        $html .= '<a href="' . $baseUrl . '&' . $paramName . '=' . $totalPages . '" class="pagination-link">' . $totalPages . '</a>';
    }
    
    // Следующая страница
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '&' . $paramName . '=' . ($currentPage + 1) . '" class="pagination-link">Следующая &raquo;</a>';
    } else {
        $html .= '<span class="pagination-link disabled">Следующая &raquo;</span>';
    }
    
    $html .= '</div>';
    return $html;
}

// ========== ВОССТАНОВЛЕНИЕ ПАРОЛЯ ==========

function createPasswordReset($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) return false;
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expires]);
    return $token;
}

function verifyPasswordResetToken($pdo, $token) {
    $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function updatePassword($pdo, $userId, $newPassword) {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    return $stmt->execute([$hash, $userId]);
}

// ========== ОЧИСТКА ТОКЕНОВ И НЕВЕРЕФИЦИРОВАННЫХ ==========

function cleanupExpiredVerificationTokens($pdo) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE verification_token_expires_at < NOW() AND is_verified = 0");
    $stmt->execute();
}

// ========== ЗАЩИТА ОТ CSRF ==========

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('Ошибка безопасности: неверный CSRF-токен');
    }
}

// ========== САНИТИЗАЦИЯ ВХОДНЫХ ДАННЫХ ==========

function sanitize_string($input) {
    return strip_tags(trim($input));
}

function sanitize_email($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

function sanitize_phone($phone) {
    return preg_replace('/\D/', '', $phone);
}

// ========== ЗАЩИТА ОТ XSS ==========

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ========== ПРОВЕРКА EMAIL ==========

function isEmailTaken($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

function is_email_deliverable($email) {
    $domain = substr(strrchr($email, "@"), 1);
    return checkdnsrr($domain, 'MX');
}