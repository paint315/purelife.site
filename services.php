<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';
$services = getAllServices($pdo);
?>
<div class="container">
    <h1>Наши услуги по клинингу</h1>
    <div class="services-full-list">
        <?php foreach ($services as $service): ?>
            <div class="service-full-item">
                <div class="service-info">
                    <h3><?= h($service['name']) ?></h3>
                    <p><?= h($service['description']) ?></p>
                    <div class="price"><?= number_format($service['price'], 0, '.', ' ') ?> ₽</div>
                    <a href="/new-order.php?service_id=<?= $service['id'] ?>" class="btn btn-primary">Заказать</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>