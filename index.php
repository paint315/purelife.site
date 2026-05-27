<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/header.php';
require_once 'includes/functions.php';

$services = getAllServices($pdo);
$employees = getAllEmployees($pdo);
$reviews = getModeratedReviews($pdo, 10); // получаем до 10 одобренных отзывов
$userDiscount = isset($_SESSION['user_id']) ? getUserDiscount($pdo, $_SESSION['user_id']) : 0;
?>

<section class="hero">
    <div class="container">
        <h1>Профессиональный клининг на дому в Санкт-Петербурге</h1>
        <p>Чистота без хлопот – закажите уборку за несколько кликов</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'client'): ?>
                <p>Ваша скидка: <?= $userDiscount ?>%</p>
            <?php else: ?>
                <p>Роль: <?= $_SESSION['role'] === 'admin' ? 'Администратор' : 'Менеджер' ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <a href="/new-order.php" class="btn btn-primary">Заказать уборку</a>
    </div>
</section>

<!-- Все услуги -->
<section class="services-all">
    <div class="container">
        <h2>Наши услуги</h2>
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <h3><?= h($service['name']) ?></h3>
                    <p><?= h($service['description']) ?></p>
                    <div class="price">от <?= number_format($service['price'], 0, '.', ' ') ?> ₽</div>
                    <a href="/new-order.php?service_id=<?= $service['id'] ?>" class="btn btn-outline">Заказать</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Сотрудники -->
<section class="employees">
    <div class="container">
        <h2>Наши специалисты</h2>
        <div class="employees-grid">
            <?php foreach ($employees as $emp): ?>
                <div class="employee-card">
                    <?php if ($emp['photo']): ?>
                        <img src="<?= h($emp['photo']) ?>" alt="<?= h($emp['name']) ?>">
                    <?php else: ?>
                        <img src="/assets/images/avatar-default.png" alt="Сотрудник">
                    <?php endif; ?>
                    <h3><?= h($emp['name']) ?></h3>
                    <p>⭐ Рейтинг: <?= number_format($emp['rating'], 1) ?></p>
                    <p>📅 Опыт: <?= $emp['years_experience'] ?> лет</p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== СЛАЙДЕР ОТЗЫВОВ ========== -->
<section class="reviews-section">
    <div class="container">
        <h2>Отзывы наших клиентов</h2>
        <?php if (count($reviews) > 0): ?>
            <div class="swiper mySwiper">
                <div class="swiper-wrapper">
                    <?php foreach ($reviews as $review): ?>
                        <div class="swiper-slide">
                            <div class="review-slide">
                                <div class="review-header">
                                    <div class="review-author">
                                        <strong><?= h($review['user_name']) ?></strong>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?= $i <= $review['rating'] ? '★' : '☆' ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-text">
                                    "<?php 
                                        $text = $review['text'];
                                        if (mb_strlen($text) > 100) {
                                            $text = mb_substr($text, 0, 100) . '...';
                                        }
                                        echo h($text);
                                        ?>>"
                                </div>
                                <div class="review-date">
                                    <?= date('d.m.Y', strtotime($review['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        <?php else: ?>
            <p class="no-reviews">Пока нет отзывов. Будьте первыми!</p>
        <?php endif; ?>
    </div>
</section>
<!-- Инициализацию Swiper -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var swiper = new Swiper('.mySwiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
        });
    });
</script>

<section class="advantages">
    <div class="container">
        <h2 class="center-title">Почему PureLife?</h2>
        <div class="advantages-grid">
            <div>✅ Профессиональные средства</div>
            <div>✅ Опытные клинеры</div>
            <div>✅ Регистрация на сайте</div>
            <div>✅ Повторый заказ услуг в два клик</div>
            <div>✅ Гарантия качества</div>
            <div>✅ Безопасные гипоаллергенные средства</div>
            <div>✅ Накопительная система скидок до 10%</div>
            <div>✅ Можно оставить отзыв после выполненной работы</div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>