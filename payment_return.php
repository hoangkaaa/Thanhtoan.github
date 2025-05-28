<?php
session_start();

// Include các file cần thiết
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->connect();

// Khởi tạo Cart class và Order class
$cart = new Cart($db);
$order = new Order($db);

// Lấy các tham số trả về từ MoMo
$resultCode = $_GET['resultCode'] ?? null;
$orderId = $_GET['orderId'] ?? null;
$amount = $_GET['amount'] ?? 0;
$orderInfo = $_GET['orderInfo'] ?? '';
$message = $_GET['message'] ?? '';

// Kiểm tra kết quả thanh toán
if ($resultCode == '0') {
    // Thanh toán thành công
    // Lấy thông tin đã lưu trong session
    if (isset($_SESSION['payment_info'])) {
        $payment_info = $_SESSION['payment_info'];
        
        try {
            // Chuẩn bị thông tin khách hàng
            $customer_info = [
                'name' => $payment_info['first_name'] . ' ' . $payment_info['last_name'],
                'email' => $payment_info['email'],
                'phone' => $payment_info['phone'],
                'address' => $payment_info['delivery_method'] === 'delivery' ? 
                    "Giao hàng tận nơi" : 
                    "Nhận tại cửa hàng",
                'city' => null,
                'district' => null,
                'zipcode' => null
            ];
            
            // Chuẩn bị chi tiết đơn hàng
            $order_details = [
                'delivery_method' => $payment_info['delivery_method'],
                'payment_method' => 'momo',
                'store_id' => null,
                'pickup_date' => null,
                'pickup_time' => null,
                'delivery_date' => null,
                'delivery_time' => null,
                'city' => null,
                'district' => null,
                'zipcode' => null,
                'subtotal' => $payment_info['cart_total'],
                'shipping_fee' => $payment_info['shipping_fee'],
                'discount_amount' => $payment_info['discount_amount'],
                'coupon_code' => null,
                'total_amount' => $payment_info['total_amount'],
                'momo_order_id' => $orderId,
                'payment_status' => 'paid'
            ];
            
            // Tạo đơn hàng
            $result = $order->createOrder($payment_info['user_id'], $customer_info, $payment_info['cart_items'], $order_details);
            
            if ($result['success']) {
                // Clear cart after successful order
                $cart->clearCart($payment_info['user_id']);
                
                // Lưu thông tin đơn hàng vào session
                $_SESSION['last_order_id'] = $result['order_id'];
                $_SESSION['last_order_code'] = $result['order_code'];
                $_SESSION['payment_success'] = true;
                $_SESSION['success_message'] = 'Thanh toán MoMo thành công! Mã đơn hàng: ' . $result['order_code'];
                
                // Xóa thông tin payment_info tạm thời
                unset($_SESSION['payment_info']);
                
                // Chuyển hướng đến trang xác nhận đơn hàng
                header('Location: order_confirmation.php?order_id=' . $result['order_id']);
                exit;
            } else {
                throw new Exception("Không thể tạo đơn hàng: " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            // Lỗi khi tạo đơn hàng
            $_SESSION['payment_error'] = true;
            $_SESSION['error_message'] = 'Thanh toán thành công nhưng có lỗi khi tạo đơn hàng: ' . $e->getMessage();
            header('Location: payment.php');
            exit;
        }
    } else {
        // Không có thông tin thanh toán trong session
        $_SESSION['payment_error'] = true;
        $_SESSION['error_message'] = 'Không tìm thấy thông tin đơn hàng. Vui lòng thử lại.';
        header('Location: payment.php');
        exit;
    }
} else {
    // Thanh toán thất bại hoặc bị hủy
    $_SESSION['payment_cancelled'] = true;
    $_SESSION['cancel_message'] = 'Thanh toán không thành công. Đơn hàng đã bị hủy.';
    
    // Chuyển hướng về trang chủ thay vì trang thanh toán
    header('Location: index.php');
    exit;
}
?> 