<?php require_once 'includes/header.php'; ?>

<div class="container contacts-container">
    <h1>Контакты</h1>
    
    <div class="contacts-grid">
        <!-- Блок с контактной информацией -->
        <div class="contacts-info">
            <div class="contact-item">
                <div class="contact-icon">📍</div>
                <div class="contact-text">
                    <strong>Адрес:</strong><br>
                    г. Санкт-Петербург, <br>
                    Звенигородская ул., 1, корп. 2
                </div>
            </div>
            <div class="contact-item">
                <div class="contact-icon">📞</div>
                <div class="contact-text">
                    <strong>Телефон:</strong><br>
                    <a href="tel:+79991234567">+7 (999) 123-45-67</a><br>
                    <span class="small">(звонки принимаем с 9:00 до 21:00)</span>
                </div>
            </div>
            <div class="contact-item">
                <div class="contact-icon">✉️</div>
                <div class="contact-text">
                    <strong>Email:</strong><br>
                    <a href="mailto:info@purelife.site">info@purelife.site</a>
                </div>
            </div>
            <div class="contact-item">
                <div class="contact-icon">🕒</div>
                <div class="contact-text">
                    <strong>Режим работы:</strong><br>
                    Пн–Вс: 9:00 – 21:00<br>
                    Без выходных
                </div>
            </div>
        </div>
        
        <!-- Яндекс.Карта -->
        <div class="contacts-map">
            <div id="yandex-map"><iframe src="https://yandex.ru/map-widget/v1/?ll=30.335761,59.922324&z=16&pt=30.335761,59.922324,flag" width="100%" height="500" frameborder="0"></iframe></div>
        </div>, 
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>