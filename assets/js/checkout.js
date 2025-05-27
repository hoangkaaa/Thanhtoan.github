document.addEventListener('DOMContentLoaded', function () {
    // ==== CLICK VÀO 2 FORM ==== //
    const loginLink = document.querySelector('a[href="#return-customer"]');
    const couponLink = document.querySelector('a[href="#have-coupon"]');
    const loginForm = document.querySelector('#return-customer');
    const couponForm = document.querySelector('#have-coupon');

    loginLink.addEventListener('click', function (event) {
        event.preventDefault();
        loginForm.classList.toggle('show');
        couponForm.classList.remove('show');
    });

    couponLink.addEventListener('click', function (event) {
        event.preventDefault();
        couponForm.classList.toggle('show');
        loginForm.classList.remove('show');
    });

    // ==== SMOOTH TRANSITIONS ==== //
    const style = document.createElement('style');
    style.innerHTML = `
      #return-customer, #have-coupon {
          transition: opacity 0.5s ease;
          opacity: 0;
          display: none;
      }
      #return-customer.show, #have-coupon.show {
          opacity: 1;
          display: block;
      }
    `;
    document.head.appendChild(style);

    // ==== TĂNG GIẢM SỐ LƯỢNG SẢN PHẨM & TÍNH TỔNG TIỀN ==== //
    const counters = document.querySelectorAll('.input-counter');
    const totalPriceElement = document.querySelector('.o-summary__table tr:nth-child(2) td:last-child');
    const productCards = document.querySelectorAll('.o-card'); // Lấy từng sản phẩm

    // Hàm tính tổng tiền
    function updateTotalPrice() {
        let total = 0;

        productCards.forEach(card => {
            const priceText = card.querySelector('.o-card__price').innerText.replace('₫', '').replace(',', '').trim();
            const quantityInput = card.querySelector('.input-counter__text');
            const price = parseInt(priceText) || 0; // Giá sản phẩm
            const quantity = parseInt(quantityInput.value) || 1; // Số lượng

            total += price * quantity;
        });

        // Hiển thị tổng tiền với định dạng có dấu phẩy
        totalPriceElement.innerText = total.toLocaleString() + '₫';
    }

    // Xử lý tăng/giảm số lượng
    counters.forEach(counter => {
        const minusButton = counter.querySelector('.input-counter__minus');
        const plusButton = counter.querySelector('.input-counter__plus');
        const inputField = counter.querySelector('.input-counter__text');

        const updateQuantity = () => {
            let currentValue = parseInt(inputField.value);
            const minValue = parseInt(inputField.getAttribute('data-min')) || 1;
            const maxValue = parseInt(inputField.getAttribute('data-max')) || 1000;

            // Giới hạn giá trị trong khoảng hợp lệ
            if (currentValue < minValue) currentValue = minValue;
            if (currentValue > maxValue) currentValue = maxValue;

            inputField.value = currentValue;
            updateTotalPrice(); // Cập nhật tổng tiền
        };

        minusButton.addEventListener('click', function () {
            inputField.value = Math.max(parseInt(inputField.value) - 1, inputField.getAttribute('data-min'));
            updateTotalPrice();
        });

        plusButton.addEventListener('click', function () {
            inputField.value = Math.min(parseInt(inputField.value) + 1, inputField.getAttribute('data-max'));
            updateTotalPrice();
        });

        inputField.addEventListener('input', updateQuantity);
    });

    // Cập nhật tổng tiền ban đầu khi trang load
    updateTotalPrice();
});
