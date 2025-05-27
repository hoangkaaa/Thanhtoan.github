<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$order_id = $_GET['order_id'] ?? '';
$order = $_SESSION['order'] ?? null;

if (!$order || !$order_id) {
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - Sunkissed</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.0.0/uicons-regular-straight/css/uicons-regular-straight.css" />
</head>
<body>
    <div class="container">
        <div class="success-page">
            <div class="success-icon">
                <i class="fi fi-rs-check"></i>
            </div>
            
            <h1>🎉 Đặt hàng thành công!</h1>
            
            <div class="order-info">
                <p>Cảm ơn bạn đã đặt hàng tại Sunkissed!</p>
                <p>Mã đơn hàng của bạn: <strong><?php echo htmlspecialchars($order_id); ?></strong></p>
                <p>Chúng tôi sẽ liên hệ với bạn sớm nhất có thể để xác nhận đơn hàng.</p>
            </div>
            
            <div class="order-details">
                <h3>Chi tiết đơn hàng:</h3>
                <div class="detail-row">
                    <span>Khách hàng:</span>
                    <span><?php echo htmlspecialchars($order['customer_info']['name']); ?></span>
                </div>
                <div class="detail-row">
                    <span>Email:</span>
                    <span><?php echo htmlspecialchars($order['customer_info']['email']); ?></span>
                </div>
                <div class="detail-row">
                    <span>Số điện thoại:</span>
                    <span><?php echo htmlspecialchars($order['customer_info']['phone']); ?></span>
                </div>
                <div class="detail-row">
                    <span>Phương thức nhận hàng:</span>
                    <span><?php echo $order['delivery_method'] === 'store' ? 'Nhận tại cửa hàng' : 'Giao hàng tận nơi'; ?></span>
                </div>
                <div class="detail-row">
                    <span>Phương thức thanh toán:</span>
                    <span>
                        <?php 
                        switch($order['payment_method']) {
                            case 'cod': echo 'Thanh toán khi nhận hàng'; break;
                            case 'transfer': echo 'Chuyển khoản ngân hàng'; break;
                            case 'visa': echo 'Thẻ tín dụng/ghi nợ'; break;
                            default: echo $order['payment_method'];
                        }
                        ?>
                    </span>
                </div>
                <div class="detail-row total">
                    <span>Tổng tiền:</span>
                    <span><?php echo number_format($order['total'], 0, ',', '.'); ?>₫</span>
                </div>
            </div>
            
            <div class="actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fi fi-rs-home"></i> Về trang chủ
                </a>
                <a href="shop.php" class="btn btn-primary">
                    <i class="fi fi-rs-shopping-bag"></i> Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </div>
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .success-page {
            background: white;
            padding: 50px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .order-info {
            margin-bottom: 30px;
        }
        
        .order-details {
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .detail-row span {
            font-weight: 600;
        }
        
        .actions {
            text-align: right;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</body>
</html> 