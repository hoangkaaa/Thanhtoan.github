// Hàm xử lý alert tự động biến mất
function initAutoHideAlert() {
    const alert = document.getElementById('payment-alert');
    if (alert) {
        // Tự động ẩn sau 7 giây
        setTimeout(function() {
            hideAlert();
        }, 7000);
    }
}

function hideAlert() {
    const alert = document.getElementById('payment-alert');
    if (alert) {
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.style.display = 'none';
        }, 500);
    }
}

// Khởi tạo khi trang được load
document.addEventListener('DOMContentLoaded', function() {
    initAutoHideAlert();
}); 