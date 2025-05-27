// Đợi đến khi toàn bộ nội dung của trang được tải xong
document.addEventListener("DOMContentLoaded", function() {

  // Lấy các phần tử nút giảm, tăng số lượng và ô nhập số lượng
  const decreaseBtn = document.querySelector("[data-quantity-remove]");
  const increaseBtn = document.querySelector("[data-quantity-add]");
  const quantityInput = document.querySelector("[data-quantity-input]");

  // Kiểm tra xem các phần tử có tồn tại không trước khi thêm event listener
  if (decreaseBtn && quantityInput) {
    // Hàm xử lý giảm số lượng khi nhấn nút giảm
    decreaseBtn.addEventListener("click", function() {
      let currentValue = parseInt(quantityInput.value);
      if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
      }
    });
  }

  if (increaseBtn && quantityInput) {
    // Hàm xử lý tăng số lượng khi nhấn nút tăng
    increaseBtn.addEventListener("click", function() {
      let currentValue = parseInt(quantityInput.value);
      if (currentValue < 99) {
        quantityInput.value = currentValue + 1;
      }
    });
  }

  // Xử lý cho tất cả các input số lượng trong trang (cho cart)
  const allQuantityInputs = document.querySelectorAll('input[type="number"][name="quantity"]');
  
  allQuantityInputs.forEach(input => {
    input.addEventListener('change', function() {
      let value = parseInt(this.value);
      const min = parseInt(this.getAttribute('min')) || 1;
      const max = parseInt(this.getAttribute('max')) || 99;
      
      if (value < min) {
        this.value = min;
      } else if (value > max) {
        this.value = max;
      }
    });
  });
}); 