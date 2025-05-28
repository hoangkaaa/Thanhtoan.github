<?php
session_start();
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->connect();

// Lấy order_id từ URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Kiểm tra thông báo thành công từ session
$payment_success = isset($_SESSION['payment_success']) ? $_SESSION['payment_success'] : false;
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';

// Clear thông báo từ session sau khi hiển thị
if ($payment_success) {
    unset($_SESSION['payment_success']);
    unset($_SESSION['success_message']);
}

$order_info = null;
$order_items = [];
$order_history = [];

if ($order_id > 0) {
    $order = new Order($db);
    $order_info = $order->getOrderById($order_id);
    $order_items = $order->getOrderItems($order_id);
    $order_history = $order->getOrderHistory($order_id);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng - Sunkissed</title>
    <link rel="stylesheet" href="assets/css/payment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 40px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .success-notification {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            animation: slideInDown 0.5s ease-out;
        }
        
        .success-icon {
            font-size: 48px;
            margin-bottom: 15px;
            animation: bounceIn 1s ease-out;
        }
        
        .success-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .success-message {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .order-code {
            font-size: 28px;
            font-weight: bold;
            color: #26551D;
            margin: 25px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #26551D;
        }
        
        .order-details {
            text-align: left;
            margin: 30px 0;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #26551D;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detail-value {
            color: #212529;
        }
        
        .order-items {
            margin: 30px 0;
            text-align: left;
        }
        
        .order-items h3 {
            color: #26551D;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #26551D;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: transform 0.2s ease;
        }
        
        .order-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #26551D;
            margin-bottom: 5px;
        }
        
        .item-quantity {
            color: #6c757d;
            font-size: 14px;
        }
        
        .item-price {
            font-weight: bold;
            color: #26551D;
        }
        
        .order-summary {
            background: #fff;
            border: 2px solid #26551D;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .summary-row.total {
            border-top: 2px solid #26551D;
            margin-top: 15px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: bold;
            color: #26551D;
        }
        
        .discount-info {
            color: #dc3545;
            font-weight: 600;
        }
        
        .btn-group {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #26551D;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e4017;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .order-timeline {
            margin: 30px 0;
            text-align: left;
        }
        
        .timeline-item {
            padding: 10px 0;
            border-left: 2px solid #26551D;
            padding-left: 20px;
            margin-left: 10px;
            position: relative;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 15px;
            width: 10px;
            height: 10px;
            background: #26551D;
            border-radius: 50%;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.1);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @media (max-width: 768px) {
            .confirmation-container {
                margin: 20px;
                padding: 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <?php if ($payment_success && $success_message): ?>
            <div class="success-notification">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="success-title">Thanh toán thành công!</div>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($order_info): ?>
            <h1><i class="fas fa-receipt"></i> Xác nhận đơn hàng</h1>
            
            <div class="order-code">
                <i class="fas fa-barcode"></i> 
                Mã đơn hàng: <?php echo htmlspecialchars($order_info['order_code']); ?>
            </div>
            
            <div class="order-details">
                <h3><i class="fas fa-info-circle"></i> Chi tiết đơn hàng</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Tên khách hàng:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order_info['customer_name']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order_info['customer_email']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Số điện thoại:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order_info['customer_phone']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Địa chỉ:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order_info['customer_address']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Phương thức giao hàng:</span>
                    <span class="detail-value">
                        <?php echo ($order_info['delivery_method'] === 'delivery') ? 'Giao hàng tận nơi' : 'Lấy tại cửa hàng'; ?>
                    </span>
                </div>
                
                <?php if ($order_info['delivery_method'] === 'delivery' && $order_info['delivery_date']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Ngày giao hàng:</span>
                        <span class="detail-value"><?php echo date('d/m/Y', strtotime($order_info['delivery_date'])); ?> - <?php echo $order_info['delivery_time']; ?></span>
                    </div>
                <?php elseif ($order_info['delivery_method'] === 'store' && $order_info['pickup_date']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Ngày lấy hàng:</span>
                        <span class="detail-value"><?php echo date('d/m/Y', strtotime($order_info['pickup_date'])); ?> - <?php echo $order_info['pickup_time']; ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Phương thức thanh toán:</span>
                    <span class="detail-value"><?php echo strtoupper($order_info['payment_method']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Trạng thái đơn hàng:</span>
                    <span class="detail-value">
                        <span class="status-badge status-<?php echo $order_info['order_status']; ?>">
                            <?php 
                            $status_text = [
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'processing' => 'Đang xử lý',
                                'shipping' => 'Đang giao hàng',
                                'delivered' => 'Đã giao hàng',
                                'cancelled' => 'Đã hủy'
                            ];
                            echo $status_text[$order_info['order_status']] ?? $order_info['order_status'];
                            ?>
                        </span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Trạng thái thanh toán:</span>
                    <span class="detail-value">
                        <span class="status-badge status-<?php echo $order_info['payment_status']; ?>">
                            <?php 
                            $payment_status_text = [
                                'pending' => 'Chờ thanh toán',
                                'paid' => 'Đã thanh toán',
                                'failed' => 'Thanh toán thất bại',
                                'refunded' => 'Đã hoàn tiền'
                            ];
                            echo $payment_status_text[$order_info['payment_status']] ?? $order_info['payment_status'];
                            ?>
                        </span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Ngày đặt hàng:</span>
                    <span class="detail-value"><?php echo date('d/m/Y H:i:s', strtotime($order_info['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="order-items">
                <h3><i class="fas fa-shopping-bag"></i> Sản phẩm đã đặt</h3>
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="item-quantity">Số lượng: <?php echo $item['quantity']; ?></div>
                        </div>
                        <div class="item-price"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?> ₫</div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-summary">
                <h3><i class="fas fa-calculator"></i> Tổng thanh toán</h3>
                
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <span><?php echo number_format($order_info['subtotal'], 0, ',', '.'); ?> ₫</span>
                </div>
                
                <div class="summary-row">
                    <span>Phí vận chuyển:</span>
                    <span><?php echo $order_info['shipping_fee'] > 0 ? number_format($order_info['shipping_fee'], 0, ',', '.') . ' ₫' : 'Miễn phí'; ?></span>
                </div>
                
                <?php if ($order_info['discount_amount'] > 0): ?>
                    <div class="summary-row">
                        <span class="discount-info">Giảm giá:</span>
                        <span class="discount-info">-<?php echo number_format($order_info['discount_amount'], 0, ',', '.'); ?> ₫</span>
                    </div>
                <?php endif; ?>
                
                <div class="summary-row total">
                    <span>Tổng cộng:</span>
                    <span><?php echo number_format($order_info['total_amount'], 0, ',', '.'); ?> ₫</span>
                </div>
            </div>
            
            <?php if (!empty($order_history)): ?>
                <div class="order-timeline">
                    <h3><i class="fas fa-history"></i> Lịch sử đơn hàng</h3>
                    <?php foreach ($order_history as $history): ?>
                        <div class="timeline-item">
                            <strong><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></strong><br>
                            <?php echo htmlspecialchars($history['notes']); ?>
                            <?php if ($history['created_by']): ?>
                                <small> - bởi <?php echo htmlspecialchars($history['created_by']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($order_info['notes']): ?>
                <div class="order-details">
                    <h3><i class="fas fa-sticky-note"></i> Ghi chú</h3>
                    <p><?php echo htmlspecialchars($order_info['notes']); ?></p>
                </div>
            <?php endif; ?>
            
            <p><strong>Chúng tôi sẽ liên hệ với bạn sớm để xác nhận đơn hàng.</strong></p>
            <p>Cảm ơn bạn đã tin tưởng và mua sắm tại Sunkissed!</p>
            
            <div class="btn-group">
                <a href="shop.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-cart"></i> Tiếp tục mua sắm
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> In đơn hàng
                </button>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ffc107; margin-bottom: 20px;"></i>
            <h2>Không tìm thấy đơn hàng</h2>
                <p>Đơn hàng bạn tìm kiếm không tồn tại hoặc đã bị xóa.</p>
                <a href="index.php" class="btn btn-primary">Về trang chủ</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Tự động ẩn thông báo thành công sau 5 giây
        setTimeout(function() {
            const successNotification = document.querySelector('.success-notification');
            if (successNotification) {
                successNotification.style.transition = 'all 0.5s ease';
                successNotification.style.opacity = '0';
                successNotification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    successNotification.remove();
                }, 500);
            }
        }, 5000);
        
        // Animation cho các elements khi load trang
        document.addEventListener('DOMContentLoaded', function() {
            const orderItems = document.querySelectorAll('.order-item');
            orderItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html> 