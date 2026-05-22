// Ждём полной загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // === 1. МАСКА ДЛЯ ТЕЛЕФОНА ===
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            let formatted = '';
            if (value.length > 0) formatted = '+7';
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length > 4) formatted += ') ' + value.slice(4, 7);
            if (value.length > 7) formatted += '-' + value.slice(7, 9);
            if (value.length > 9) formatted += '-' + value.slice(9, 11);
            e.target.value = formatted;
        });
    });

    // === 2. ПЕРЕКЛЮЧЕНИЕ ФОРМЫ ОТЗЫВА ===
    window.toggleReviewForm = function(orderId) {
        const form = document.getElementById('review_form_' + orderId);
        if (form) {
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'table-row';
            } else {
                form.style.display = 'none';
            }
        }
    };

    // === 3. КАЛЬКУЛЯТОР НА СТРАНИЦЕ ОФОРМЛЕНИЯ ЗАКАЗА ===
    function recalcOrderTotal() {
        const subtotalSpan = document.getElementById('subtotal');
        const totalSpan = document.getElementById('totalWithDiscount');
        const discountSpan = document.getElementById('discountPercent');
        if (!subtotalSpan || !totalSpan || !discountSpan) return;
        
        let subtotal = 0;
        document.querySelectorAll('#servicesTable tbody tr').forEach(row => {
            const chk = row.querySelector('.service-checkbox');
            if (chk && chk.checked) {
                const price = parseFloat(row.getAttribute('data-price') || 0);
                subtotal += price;
            }
        });
        const discountPercent = parseInt(discountSpan.innerText) || 0;
        const total = subtotal * (100 - discountPercent) / 100;
        subtotalSpan.innerText = subtotal.toFixed(2);
        totalSpan.innerText = total.toFixed(2);
    }

    // Если таблица услуг существует – вешаем обработчики
    const servicesTable = document.getElementById('servicesTable');
    if (servicesTable) {
        servicesTable.querySelectorAll('.service-checkbox').forEach(chk => {
            chk.addEventListener('change', recalcOrderTotal);
        });
        recalcOrderTotal();
    }

    // === 4. ШТОРКА ДЛЯ КОММЕНТАРИЯ В АДМИН-ПАНЕЛИ ===
    const buttons = document.querySelectorAll('.toggle-comment');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const commentDiv = document.getElementById('comment-' + id);
            
            if (commentDiv) {
                if (commentDiv.style.display === 'none' || commentDiv.style.display === '') {
                    commentDiv.style.display = 'block';
                    this.textContent = 'Скрыть комментарий';
                } else {
                    commentDiv.style.display = 'none';
                    this.textContent = 'Показать комментарий';
                }
            }
        });
    });

    // === 5. МОДАЛЬНОЕ ОКНО ДЛЯ СОТРУДНИКОВ ===
    window.openEditModal = function(id, name, years) {
        const editId = document.getElementById('edit_id');
        const editName = document.getElementById('edit_name');
        const editYears = document.getElementById('edit_years');
        const modal = document.getElementById('editModal');
        const overlay = document.getElementById('modalOverlay');
        if (editId && editName && editYears && modal && overlay) {
            editId.value = id;
            editName.value = name;
            editYears.value = years;
            modal.style.display = 'block';
            overlay.style.display = 'block';
        }
    };
    
    window.closeModal = function() {
        const modal = document.getElementById('editModal');
        const overlay = document.getElementById('modalOverlay');
        if (modal) modal.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
    };

    // Функция показа/скрытия деталей заказа (шторка)
    window.toggleOrderDetails = function(orderId) {
        const row = document.getElementById('order_details_' + orderId);
        if (row) {
            if (row.style.display === 'none' || row.style.display === '') {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        }
    };

    // Полноэкранное бургер-меню
    const burgerBtn = document.getElementById('burgerBtn');
    const fullscreenMenu = document.getElementById('fullscreenMenu');
    const closeMenuBtn = document.getElementById('closeMenuBtn');

    function openMenu() {
        fullscreenMenu.classList.add('active');
        document.body.style.overflow = 'hidden'; // блокируем прокрутку
    }

    function closeMenu() {
        fullscreenMenu.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (burgerBtn && fullscreenMenu && closeMenuBtn) {
        burgerBtn.addEventListener('click', openMenu);
        closeMenuBtn.addEventListener('click', closeMenu);
        
        // Закрыть меню при клике на фон (на сам fullscreenMenu, но не на внутреннюю навигацию)
        fullscreenMenu.addEventListener('click', function(e) {
            if (e.target === fullscreenMenu) {
                closeMenu();
            }
        });

        // Закрыть меню при клике на любую ссылку внутри .mobile-nav
        const mobileLinks = fullscreenMenu.querySelectorAll('.mobile-nav a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', closeMenu);
        });
    }
    
    // Запрет закрытия модального окна кликом вне его
    const modal = document.getElementById('cityModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                e.stopPropagation();
            }
        });
    }
});