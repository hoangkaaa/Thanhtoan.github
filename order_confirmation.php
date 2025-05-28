<?php
session_start();
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->connect();

// Lấy order_id từ URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id > 0) {
    $order = new Order($db);
    $order_info = $order->getOrderById($order_id);
    $order_items = $order->getOrderItems($order_id);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng - Sunkissed</title>
    <link rel="stylesheet" href="assets/css/payment.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            color: #28a745;
            font-size: 60px;
            margin-bottom: 20px;
        }
        .order-code {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 20px 0;
        }
        .order-details {
            text-align: left;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .order-items {
            margin: 20px 0;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .btn-group {
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            background: #ff6b35;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #e55a2b;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <?php if ($order_info): ?>
            <div class="success-icon">✓</div>
            <h1>Đặt hàng thành công!</h1>
            <p>Cảm ơn bạn đã mua hàng tại Sunkissed</p>
            
            <div class="order-code">
                Mã đơn hàng: <?php echo htmlspecialchars($order_info['order_code']); ?>
            </div>
            
            <div class="order-details">
                <h3>Thông tin đơn hàng</h3>
                <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order_info['customer_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order_info['customer_email']); ?></p>
                <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($order_info['customer_phone']); ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order_info['customer_address']); ?></p>
                <p><strong>Phương thức thanh toán:</strong> <?php echo strtoupper($order_info['payment_method']); ?></p>
                <p><strong>Tổng tiền:</strong> <?php echo number_format($order_info['total_amount'], 0, ',', '.'); ?> VND</p>
            </div>
            
            <div class="order-items">
                <h3>Chi tiết sản phẩm</h3>
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div><?php echo htmlspecialchars($item['product_name']); ?> x <?php echo $item['quantity']; ?></div>
                        <div><?php echo number_format($item['subtotal'], 0, ',', '.'); ?> VND</div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p>Chúng tôi sẽ liên hệ với bạn sớm để xác nhận đơn hàng.</p>
            
            <div class="btn-group">
                <a href="shop.php" class="btn btn-secondary">Tiếp tục mua sắm</a>
                <a href="index.php" class="btn">Về trang chủ</a>
            </div>
        <?php else: ?>
            <h2>Không tìm thấy đơn hàng</h2>
            <a href="index.php" class="btn">Về trang chủ</a>
        <?php endif; ?>
    </div>
</body>
</html> 