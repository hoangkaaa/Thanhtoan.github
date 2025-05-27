<?php
// Khởi tạo session và xử lý dữ liệu
session_start();

// Include các file cần thiết
require_once 'cart_actions.php';

// Lấy thông tin giỏ hàng
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_total = getCartTotal();
$cart_count = getCartItemCount();
$shipping_fee = 0; // Miễn phí vận chuyển

// Xử lý form submission
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    // Validate dữ liệu form
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $delivery_method = $_POST['delivery_method'] ?? 'store';
    $payment_method = $_POST['payment_method'] ?? 'visa';
    $terms_accepted = isset($_POST['terms_checkbox']);
    
    // Validation
    if (empty($first_name)) $errors[] = 'Tên không được để trống';
    if (empty($last_name)) $errors[] = 'Họ và tên lót không được để trống';
    if (empty($phone)) $errors[] = 'Số điện thoại không được để trống';
    if (empty($email)) $errors[] = 'Email không được để trống';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (!$terms_accepted) $errors[] = 'Bạn phải đồng ý với điều khoản dịch vụ';
    if (empty($cart_items)) $errors[] = 'Giỏ hàng trống';
    
    // Validate theo phương thức giao hàng
    if ($delivery_method === 'store') {
        $pickup_date = $_POST['pickup_date'] ?? '';
        $pickup_time = $_POST['pickup_time'] ?? '';
        $store_location = $_POST['store'] ?? '';
        
        if (empty($pickup_date)) $errors[] = 'Ngày lấy hàng không được để trống';
        if (empty($pickup_time)) $errors[] = 'Thời gian lấy hàng không được để trống';
        if (empty($store_location)) $errors[] = 'Vui lòng chọn cửa hàng';
    } else {
        $delivery_date = $_POST['delivery_date'] ?? '';
        $delivery_time = $_POST['delivery_time'] ?? '';
        $city = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        
        if (empty($delivery_date)) $errors[] = 'Ngày giao hàng không được để trống';
        if (empty($delivery_time)) $errors[] = 'Thời gian giao hàng không được để trống';
        if (empty($city)) $errors[] = 'Thành phố/Tỉnh không được để trống';
        if (empty($district)) $errors[] = 'Quận/Huyện không được để trống';
        if (empty($address)) $errors[] = 'Địa chỉ không được để trống';
        if (empty($zip_code)) $errors[] = 'Mã zip không được để trống';
        
        // Tính phí vận chuyển cho giao hàng
        $shipping_fee = 25000;
    }
    
    // Nếu không có lỗi, xử lý đơn hàng
    if (empty($errors)) {
        // Tạo order ID
        $order_id = 'ORD_' . date('YmdHis') . '_' . rand(1000, 9999);
        
        // Lưu thông tin đơn hàng vào session hoặc database
        $_SESSION['order'] = [
            'order_id' => $order_id,
            'customer_info' => [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $_POST['country_code'] . $phone,
                'email' => $email
            ],
            'delivery_info' => [
                'method' => $delivery_method,
                'date' => $delivery_method === 'store' ? $pickup_date : $delivery_date,
                'time' => $delivery_method === 'store' ? $pickup_time : $delivery_time,
                'store' => $delivery_method === 'store' ? $store_location : null,
                'address' => $delivery_method === 'delivery' ? [
                    'city' => $city,
                    'district' => $district,
                    'address' => $address,
                    'zip_code' => $zip_code
                ] : null
            ],
            'payment_method' => $payment_method,
            'items' => $cart_items,
            'subtotal' => $cart_total,
            'shipping_fee' => $shipping_fee,
            'total' => $cart_total + $shipping_fee,
            'order_date' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];
        
        // Xóa giỏ hàng sau khi đặt hàng thành công
        $_SESSION['cart'] = [];
        
        // Redirect đến trang xác nhận
        header('Location: order_confirmation.php?order_id=' . $order_id);
        exit;
    }
}

// Dữ liệu cửa hàng
$stores = [
    [
        'id' => 'store1',
        'name' => 'Sunkissed - Chi nhánh 1',
        'address' => '219 Nguyễn Tri Phương, Quận 10, TP. HCM',
        'hours' => 'Giờ mở cửa: 8:00 - 21:00, Thứ 2 - Thứ 7'
    ],
    [
        'id' => 'store2',
        'name' => 'Sunkissed - Chi nhánh 2',
        'address' => '78 Nguyễn Văn Linh, Quận 7, TP. HCM',
        'hours' => 'Giờ mở cửa: 8:00 - 21:00, Thứ 2 - Chủ Nhật'
    ]
];

// Tính tổng tiền cuối cùng
$final_total = $cart_total + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content 