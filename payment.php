<?php
// Khởi tạo session và xử lý dữ liệu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include các file cần thiết
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->connect();

// Khởi tạo Cart class
$cart = new Cart($db);

// Giả lập user_id - có thể thay đổi khi có hệ thống đăng nhập
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Lấy thông tin giỏ hàng từ DATABASE
$cart_items = $cart->getItems($user_id);
$cart_total = $cart->getTotal($user_id);
$cart_count = $cart->getItemCount($user_id);

// Khởi tạo các biến
$shipping_fee = 0;
$discount_amount = 0;
$applied_coupon = null;

// Xử lý form submission - TĂNG CƯỜNG XỬ LÝ
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    // Log để debug
    error_log("Payment form submitted: " . print_r($_POST, true));
    
    // Validate dữ liệu form
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $delivery_method = $_POST['delivery_method'] ?? 'store';
    $payment_method = $_POST['payment_method'] ?? 'visa';
    $terms_accepted = isset($_POST['terms_checkbox']);
    
    // Lấy thông tin mã giảm giá nếu có
    $coupon_code = trim($_POST['coupon_code'] ?? '');
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    
    // VALIDATION CẢI TIẾN
    if (empty($first_name)) $errors[] = 'Tên không được để trống';
    if (empty($last_name)) $errors[] = 'Họ và tên lót không được để trống';
    if (empty($phone)) $errors[] = 'Số điện thoại không được để trống';
    if (!preg_match('/^[0-9]{10,11}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ (10-11 số)';
    if (empty($email)) $errors[] = 'Email không được để trống';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (!$terms_accepted) $errors[] = 'Bạn phải đồng ý với điều khoản dịch vụ';
    if (empty($cart_items)) $errors[] = 'Giỏ hàng trống, vui lòng thêm sản phẩm';
    
    // Khởi tạo biến cho giao hàng
    $pickup_date = $pickup_time = $selected_store = '';
    $delivery_date = $delivery_time = $city = $district = $address = $zipcode = '';
    
    // Tính lại shipping fee dựa trên delivery method
    if ($delivery_method === 'delivery') {
        $delivery_date = trim($_POST['delivery_date'] ?? '');
        $delivery_time = trim($_POST['delivery_time'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $zipcode = trim($_POST['zipcode'] ?? '');
        
        if (empty($delivery_date)) $errors[] = 'Ngày giao hàng không được để trống';
        if (empty($delivery_time)) $errors[] = 'Thời gian giao hàng không được để trống';
        if (empty($city)) $errors[] = 'Thành phố/Tỉnh không được để trống';
        if (empty($district)) $errors[] = 'Quận/Huyện không được để trống';
        if (empty($address)) $errors[] = 'Địa chỉ không được để trống';
        if (empty($zipcode)) $errors[] = 'Mã Zip không được để trống';
        
        // Validate ngày giao hàng (phải từ ngày mai trở đi)
        $delivery_timestamp = strtotime($delivery_date);
        $tomorrow = strtotime('+1 day');
        if ($delivery_timestamp < $tomorrow) {
            $errors[] = 'Ngày giao hàng phải từ ngày mai trở đi';
        }
        
        // Tính phí vận chuyển dựa trên thành phố
        $shipping_fee = ($city === 'ho-chi-minh') ? 15000 : 30000;
    } else {
        $pickup_date = trim($_POST['pickup_date'] ?? '');
        $pickup_time = trim($_POST['pickup_time'] ?? '');
        $selected_store = trim($_POST['store'] ?? '');
        
        if (empty($pickup_date)) $errors[] = 'Ngày lấy hàng không được để trống';
        if (empty($pickup_time)) $errors[] = 'Thời gian lấy hàng không được để trống';
        if (empty($selected_store)) $errors[] = 'Vui lòng chọn cửa hàng';
        
        $shipping_fee = 0; // Miễn phí khi lấy tại cửa hàng
    }
    
    // Nếu không có lỗi, xử lý đơn hàng
    if (empty($errors)) {
        try {
            $order = new Order($db);
            
            // Chuẩn bị thông tin khách hàng
            $customer_info = [
                'name' => $first_name . ' ' . $last_name,
                'email' => $email,
                'phone' => $phone,
                'address' => $delivery_method === 'delivery' ? 
                    "$address, $district, $city, $zipcode" : 
                    "Nhận tại cửa hàng: " . $selected_store,
                'city' => $city ?? null,
                'district' => $district ?? null,
                'zipcode' => $zipcode ?? null
            ];
            
            // Tính lại discount nếu có mã giảm giá
            $final_discount = 0;
            if (!empty($coupon_code)) {
                if ($coupon_code === 'GIAIKHATHE') {
                    $final_discount = min($shipping_fee * 0.1, 10000);
                } elseif ($coupon_code === 'GIAIKHAT') {
                    $final_discount = $cart_total * 0.15;
                }
            }
            
            // Chuẩn bị chi tiết đơn hàng
            $order_details = [
                'delivery_method' => $delivery_method,
                'payment_method' => $payment_method,
                'store_id' => $delivery_method === 'store' ? $selected_store : null,
                'pickup_date' => $delivery_method === 'store' ? $pickup_date : null,
                'pickup_time' => $delivery_method === 'store' ? $pickup_time : null,
                'delivery_date' => $delivery_method === 'delivery' ? $delivery_date : null,
                'delivery_time' => $delivery_method === 'delivery' ? $delivery_time : null,
                'city' => $city ?? null,
                'district' => $district ?? null,
                'zipcode' => $zipcode ?? null,
                'subtotal' => $cart_total,
                'shipping_fee' => $shipping_fee,
                'discount_amount' => $final_discount,
                'coupon_code' => $coupon_code,
                'total_amount' => $cart_total + $shipping_fee - $final_discount
            ];
            
            // Tạo đơn hàng
            $result = $order->createOrder($user_id, $customer_info, $cart_items, $order_details);
            
            if ($result['success']) {
                // Clear cart after successful order
                $cart->clearCart($user_id);
                
                // Lưu order_id và order_code vào session để hiển thị trang xác nhận
                $_SESSION['last_order_id'] = $result['order_id'];
                $_SESSION['last_order_code'] = $result['order_code'];
                $_SESSION['payment_success'] = true;
                $_SESSION['success_message'] = 'Đơn hàng của bạn đã được tạo thành công! Mã đơn hàng: ' . $result['order_code'];
                
                // Redirect to confirmation page
                header('Location: order_confirmation.php?order_id=' . $result['order_id']);
                exit;
            }
            
        } catch (Exception $e) {
            $errors[] = 'Có lỗi xảy ra khi tạo đơn hàng: ' . $e->getMessage();
        }
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
$final_total = $cart_total + $shipping_fee - $discount_amount;

// Tính tổng giỏ hàng
$subtotal = $cart->getTotal($user_id);
$shipping = 15000; // Phí vận chuyển mặc định TP.HCM
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!--=============== FLATICON ===============-->
    <link rel="icon" href="./assets/img/logo_hinh_red.svg" type="image/png">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.0.0/uicons-regular-straight/css/uicons-regular-straight.css" />

    <!--=============== CSS ===============-->
    <link rel="stylesheet" href="assets/css/payment.css" />
    <link rel="stylesheet" href="assets/css/breakpoint.css" />

    <title>Sunkissed | Thanh toán</title>
</head>
<body>
    <!--=============== HEADER ===============-->
    <header class="header">
        <div class="header__top">
            <div class="header__container container">
                <div class="header__contact">
                    <span>(+84)335993276</span>
                </div>
                <p class="header__alert-news">
                    Giảm 15% trên mọi đơn hàng từ 20-24/12
                </p>
                <a href="login-register.php" class="header__top-action">
                    Đăng nhập/ Đăng kí
                </a>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="container">
            <div class="nav">
                <a href="index.php" class="nav__logo">
                    <img class="nav__logo-img" src="assets/img/logo_red.svg" alt="Sunkissed logo" />
                </a>
                    
                <ul class="nav__list">
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">Trang chủ</a>
                    </li>
                    <li class="nav__item">
                        <a href="shop.php" class="nav__link">Cửa hàng</a>
                    </li>
                    <li class="nav__item">
                        <a href="aboutus.php" class="nav__link">Về chúng tôi</a>
                    </li>
                    <li class="nav__item">
                        <a href="contact.php" class="nav__link">Liên hệ</a>
                    </li>
                    <li class="nav__item">
                        <a href="accounts.php" class="nav__link">Tài khoản</a>
                    </li>
                </ul>
                    
                <!-- Search box -->
                <div class="header__search">
                    <input type="text" placeholder="Tìm kiếm sản phẩm" class="form__input" />
                </div>

                <!-- Wishlist và Giỏ hàng -->
                <div class="header__user-actions">
                    <a href="wishlist.php" class="header__action-btn" title="Wishlist">
                        <img src="assets/img/icon-heart.svg" alt="Wishlist" />
                        <span class="count">0</span>
                    </a>
                    <a href="cart.php" class="header__action-btn" title="Cart">
                        <img src="assets/img/icon-cart.svg" alt="Cart" />
                        <span class="count"><?php echo $cart_count; ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!--=============== BREADCRUMB ===============-->
    <section class="breadcrumb">
        <div class="container">
            <ul class="breadcrumb__list">
                <li><a href="index.php" class="breadcrumb__link">Trang chủ</a></li>
                <li><span class="breadcrumb__link">></span></li>
                <li><a href="cart.php" class="breadcrumb__link">Giỏ hàng</a></li>
                <li><span class="breadcrumb__link">></span></li>
                <li><span class="breadcrumb__link">Thanh toán</span></li>
            </ul>
        </div>
    </section>

    <!--=============== LOADING INDICATOR ===============-->
    <div id="loading-indicator" class="loading-indicator" style="display: none;">
        <div class="spinner"></div>
        <span>Đang xử lý...</span>
    </div>

    <!--=============== MAIN CONTENT ===============-->
    <main class="main">
        <!-- Hiển thị lỗi nếu có -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error container">
                <h4>Có lỗi xảy ra:</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Kiểm tra giỏ hàng trống -->
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart container">
                <h2>Giỏ hàng của bạn đang trống</h2>
                <p>Vui lòng thêm sản phẩm vào giỏ hàng trước khi thanh toán.</p>
                <a href="shop.php" class="btn">Tiếp tục mua sắm</a>
            </div>
        <?php else: ?>
            <section class="payment section--lg">
                <div class="payment__container container">
                    <section class="payment-form">
                        <h2><a href="cart.php" class="back-arrow">&#8592;</a> Thanh toán</h2>
                        <form id="payment-form" method="POST">
                            <div class="section">
                                <h3>1. Thông tin khách hàng</h3>
                                <div class="form-row">
                                    <div>
                                        <label>Tên <span class="required">*Bắt buộc</span></label>
                                        <input type="text" name="first_name" class="form__input" required 
                                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label>Họ và tên lót <span class="required">*Bắt buộc</span></label>
                                        <input type="text" name="last_name" class="form__input" required 
                                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div>
                                        <label>Số điện thoại <span class="required">*Bắt buộc</span></label>
                                        <div class="phone-input-container">
                                            <div class="phone-country-code">
                                                <button type="button" class="phone-dropdown-btn" id="country-selector">
                                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/21/Flag_of_Vietnam.svg/2000px-Flag_of_Vietnam.svg.png" alt="VN" class="country-flag">
                                                    <span>+84</span>
                                                    <span class="dropdown-arrow">&#9662;</span>
                                                </button>
                                                <div class="country-dropdown" id="country-dropdown">
                                                    <div class="country-option" data-code="+84" data-country="VN">
                                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/21/Flag_of_Vietnam.svg/2000px-Flag_of_Vietnam.svg.png" alt="VN" class="country-flag">
                                                        <span class="country-name">Việt Nam</span>
                                                        <span class="country-code">+84</span>
                                                    </div>
                                                    <div class="country-option" data-code="+1" data-country="US">
                                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a4/Flag_of_the_United_States.svg/2000px-Flag_of_the_United_States.svg.png" alt="US" class="country-flag">
                                                        <span class="country-name">United States</span>
                                                        <span class="country-code">+1</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="text" name="phone" class="form__input phone-number-input" required 
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div>
                                        <label>Email <span class="required">*Bắt buộc</span></label>
                                        <input type="email" name="email" class="form__input" required 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="section">
                                <h3>2. Phương thức giao hàng</h3>
                                <div class="shipping-methods">
                                    <button type="button" class="store-btn <?php echo ($_POST['delivery_method'] ?? 'store') === 'store' ? 'active' : ''; ?>">Cửa hàng</button>
                                    <button type="button" class="delivery-btn <?php echo ($_POST['delivery_method'] ?? '') === 'delivery' ? 'active' : ''; ?>">Giao hàng</button>
                                </div>
                                
                                <!-- Giao diện cho phương thức Cửa hàng -->
                                <div class="store-pickup-container" <?php echo ($_POST['delivery_method'] ?? 'store') !== 'store' ? 'style="display: none;"' : ''; ?>>
                                    <input type="hidden" name="delivery_method" value="store" class="delivery-method-input">
                                    <div class="form-row">
                                        <div>
                                            <label>Ngày lấy hàng <span class="required">*Bắt buộc</span></label>
                                            <input type="date" name="pickup_date" class="form__input" required 
                                                   value="<?php echo htmlspecialchars($_POST['pickup_date'] ?? ''); ?>">
                                        </div>
                                        <div>
                                            <label>Thời gian lấy <span class="required">*Bắt buộc</span></label>
                                            <select name="pickup_time" class="form__input" required>
                                                <option value="">Chọn thời gian</option>
                                                <option value="8:00-9:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '8:00-9:00') ? 'selected' : ''; ?>>8:00 - 9:00</option>
                                                <option value="9:00-10:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '9:00-10:00') ? 'selected' : ''; ?>>9:00 - 10:00</option>
                                                <option value="10:00-11:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '10:00-11:00') ? 'selected' : ''; ?>>10:00 - 11:00</option>
                                                <option value="11:00-12:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '11:00-12:00') ? 'selected' : ''; ?>>11:00 - 12:00</option>
                                                <option value="12:00-13:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '12:00-13:00') ? 'selected' : ''; ?>>12:00 - 13:00</option>
                                                <option value="13:00-14:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '13:00-14:00') ? 'selected' : ''; ?>>13:00 - 14:00</option>
                                                <option value="14:00-15:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '14:00-15:00') ? 'selected' : ''; ?>>14:00 - 15:00</option>
                                                <option value="15:00-16:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '15:00-16:00') ? 'selected' : ''; ?>>15:00 - 16:00</option>
                                                <option value="16:00-17:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '16:00-17:00') ? 'selected' : ''; ?>>16:00 - 17:00</option>
                                                <option value="17:00-18:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '17:00-18:00') ? 'selected' : ''; ?>>17:00 - 18:00</option>
                                                <option value="18:00-19:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '18:00-19:00') ? 'selected' : ''; ?>>18:00 - 19:00</option>
                                                <option value="19:00-20:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '19:00-20:00') ? 'selected' : ''; ?>>19:00 - 20:00</option>
                                                <option value="20:00-21:00" <?php echo (isset($_POST['pickup_time']) && $_POST['pickup_time'] === '20:00-21:00') ? 'selected' : ''; ?>>20:00 - 21:00</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="store-location">
                                        <h4>Chọn cửa hàng</h4>
                                        <div class="store-options">
                                            <?php foreach ($stores as $index => $store): ?>
                                                <div class="store-option <?php echo ($_POST['store'] ?? '') === $store['id'] ? 'selected' : ''; ?>">
                                                    <input type="radio" id="<?php echo $store['id']; ?>" name="store" value="<?php echo $store['id']; ?>" 
                                                           <?php echo ($index === 0 || ($_POST['store'] ?? '') === $store['id']) ? 'checked' : ''; ?>>
                                                    <span class="custom-radio"></span>
                                                    <div class="store-info">
                                                        <div class="store-name"><?php echo htmlspecialchars($store['name']); ?></div>
                                                        <div class="store-address"><?php echo htmlspecialchars($store['address']); ?></div>
                                                        <div class="store-hours"><?php echo htmlspecialchars($store['hours']); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Giao diện cho phương thức Giao hàng -->
                                <div class="delivery-container" <?php echo ($_POST['delivery_method'] ?? 'store') !== 'delivery' ? 'style="display: none;"' : ''; ?>>
                                    <input type="hidden" name="delivery_method" value="delivery" class="delivery-method-input">
                                    <div class="form-row">
                                        <div>
                                            <label>Ngày giao hàng <span class="required">*Bắt buộc</span></label>
                                            <input type="date" name="delivery_date" class="form__input" 
                                                   value="<?php echo htmlspecialchars($_POST['delivery_date'] ?? ''); ?>">
                                        </div>
                                        <div>
                                            <label>Thời gian giao <span class="required">*Bắt buộc</span></label>
                                            <select name="delivery_time" class="form__input">
                                                <option value="">Chọn thời gian</option>
                                                <option value="8:00-9:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '8:00-9:00') ? 'selected' : ''; ?>>8:00 - 9:00</option>
                                                <option value="9:00-10:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '9:00-10:00') ? 'selected' : ''; ?>>9:00 - 10:00</option>
                                                <option value="10:00-11:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '10:00-11:00') ? 'selected' : ''; ?>>10:00 - 11:00</option>
                                                <option value="11:00-12:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '11:00-12:00') ? 'selected' : ''; ?>>11:00 - 12:00</option>
                                                <option value="12:00-13:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '12:00-13:00') ? 'selected' : ''; ?>>12:00 - 13:00</option>
                                                <option value="13:00-14:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '13:00-14:00') ? 'selected' : ''; ?>>13:00 - 14:00</option>
                                                <option value="14:00-15:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '14:00-15:00') ? 'selected' : ''; ?>>14:00 - 15:00</option>
                                                <option value="15:00-16:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '15:00-16:00') ? 'selected' : ''; ?>>15:00 - 16:00</option>
                                                <option value="16:00-17:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '16:00-17:00') ? 'selected' : ''; ?>>16:00 - 17:00</option>
                                                <option value="17:00-18:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '17:00-18:00') ? 'selected' : ''; ?>>17:00 - 18:00</option>
                                                <option value="18:00-19:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '18:00-19:00') ? 'selected' : ''; ?>>18:00 - 19:00</option>
                                                <option value="19:00-20:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '19:00-20:00') ? 'selected' : ''; ?>>19:00 - 20:00</option>
                                                <option value="20:00-21:00" <?php echo (isset($_POST['delivery_time']) && $_POST['delivery_time'] === '20:00-21:00') ? 'selected' : ''; ?>>20:00 - 21:00</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div>
                                            <label>Thành phố/Tỉnh <span class="required">*Bắt buộc</span></label>
                                            <select name="city" id="city-select" class="form__input" onchange="updateDeliveryShippingFee()">
                                                <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                                <option value="ho-chi-minh" data-fee="15000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ho-chi-minh') ? 'selected' : ''; ?>>TP. Hồ Chí Minh</option>
                                                <option value="ha-noi" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ha-noi') ? 'selected' : ''; ?>>Hà Nội</option>
                                                <option value="da-nang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'da-nang') ? 'selected' : ''; ?>>Đà Nẵng</option>
                                                <option value="can-tho" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'can-tho') ? 'selected' : ''; ?>>Cần Thơ</option>
                                                <option value="hai-phong" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'hai-phong') ? 'selected' : ''; ?>>Hải Phòng</option>
                                                <option value="binh-duong" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'binh-duong') ? 'selected' : ''; ?>>Bình Dương</option>
                                                <option value="dong-nai" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'dong-nai') ? 'selected' : ''; ?>>Đồng Nai</option>
                                                <option value="long-an" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'long-an') ? 'selected' : ''; ?>>Long An</option>
                                                <option value="ba-ria-vung-tau" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ba-ria-vung-tau') ? 'selected' : ''; ?>>Bà Rịa - Vũng Tàu</option>
                                                <option value="an-giang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'an-giang') ? 'selected' : ''; ?>>An Giang</option>
                                                <option value="bac-giang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'bac-giang') ? 'selected' : ''; ?>>Bắc Giang</option>
                                                <option value="bac-kan" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'bac-kan') ? 'selected' : ''; ?>>Bắc Kạn</option>
                                                <option value="bac-lieu" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'bac-lieu') ? 'selected' : ''; ?>>Bạc Liêu</option>
                                                <option value="bac-ninh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'bac-ninh') ? 'selected' : ''; ?>>Bắc Ninh</option>
                                                <option value="ben-tre" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ben-tre') ? 'selected' : ''; ?>>Bến Tre</option>
                                                <option value="binh-dinh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'binh-dinh') ? 'selected' : ''; ?>>Bình Định</option>
                                                <option value="binh-phuoc" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'binh-phuoc') ? 'selected' : ''; ?>>Bình Phước</option>
                                                <option value="binh-thuan" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'binh-thuan') ? 'selected' : ''; ?>>Bình Thuận</option>
                                                <option value="ca-mau" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ca-mau') ? 'selected' : ''; ?>>Cà Mau</option>
                                                <option value="cao-bang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'cao-bang') ? 'selected' : ''; ?>>Cao Bằng</option>
                                                <option value="dak-lak" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'dak-lak') ? 'selected' : ''; ?>>Đắk Lắk</option>
                                                <option value="dak-nong" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'dak-nong') ? 'selected' : ''; ?>>Đắk Nông</option>
                                                <option value="dien-bien" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'dien-bien') ? 'selected' : ''; ?>>Điện Biên</option>
                                                <option value="dong-thap" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'dong-thap') ? 'selected' : ''; ?>>Đồng Tháp</option>
                                                <option value="gia-lai" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'gia-lai') ? 'selected' : ''; ?>>Gia Lai</option>
                                                <option value="ha-giang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ha-giang') ? 'selected' : ''; ?>>Hà Giang</option>
                                                <option value="ha-nam" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ha-nam') ? 'selected' : ''; ?>>Hà Nam</option>
                                                <option value="ha-tinh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ha-tinh') ? 'selected' : ''; ?>>Hà Tĩnh</option>
                                                <option value="hai-duong" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'hai-duong') ? 'selected' : ''; ?>>Hải Dương</option>
                                                <option value="hau-giang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'hau-giang') ? 'selected' : ''; ?>>Hậu Giang</option>
                                                <option value="hoa-binh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'hoa-binh') ? 'selected' : ''; ?>>Hòa Bình</option>
                                                <option value="hung-yen" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'hung-yen') ? 'selected' : ''; ?>>Hưng Yên</option>
                                                <option value="khanh-hoa" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'khanh-hoa') ? 'selected' : ''; ?>>Khánh Hòa</option>
                                                <option value="kien-giang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'kien-giang') ? 'selected' : ''; ?>>Kiên Giang</option>
                                                <option value="kon-tum" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'kon-tum') ? 'selected' : ''; ?>>Kon Tum</option>
                                                <option value="lai-chau" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'lai-chau') ? 'selected' : ''; ?>>Lai Châu</option>
                                                <option value="lam-dong" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'lam-dong') ? 'selected' : ''; ?>>Lâm Đồng</option>
                                                <option value="lang-son" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'lang-son') ? 'selected' : ''; ?>>Lạng Sơn</option>
                                                <option value="lao-cai" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'lao-cai') ? 'selected' : ''; ?>>Lào Cai</option>
                                                <option value="nam-dinh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'nam-dinh') ? 'selected' : ''; ?>>Nam Định</option>
                                                <option value="nghe-an" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'nghe-an') ? 'selected' : ''; ?>>Nghệ An</option>
                                                <option value="ninh-binh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ninh-binh') ? 'selected' : ''; ?>>Ninh Bình</option>
                                                <option value="ninh-thuan" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'ninh-thuan') ? 'selected' : ''; ?>>Ninh Thuận</option>
                                                <option value="phu-tho" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'phu-tho') ? 'selected' : ''; ?>>Phú Thọ</option>
                                                <option value="phu-yen" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'phu-yen') ? 'selected' : ''; ?>>Phú Yên</option>
                                                <option value="quang-binh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'quang-binh') ? 'selected' : ''; ?>>Quảng Bình</option>
                                                <option value="quang-nam" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'quang-nam') ? 'selected' : ''; ?>>Quảng Nam</option>
                                                <option value="quang-ngai" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'quang-ngai') ? 'selected' : ''; ?>>Quảng Ngãi</option>
                                                <option value="quang-ninh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'quang-ninh') ? 'selected' : ''; ?>>Quảng Ninh</option>
                                                <option value="quang-tri" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'quang-tri') ? 'selected' : ''; ?>>Quảng Trị</option>
                                                <option value="soc-trang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'soc-trang') ? 'selected' : ''; ?>>Sóc Trăng</option>
                                                <option value="son-la" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'son-la') ? 'selected' : ''; ?>>Sơn La</option>
                                                <option value="tay-ninh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'tay-ninh') ? 'selected' : ''; ?>>Tây Ninh</option>
                                                <option value="thai-binh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'thai-binh') ? 'selected' : ''; ?>>Thái Bình</option>
                                                <option value="thai-nguyen" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'thai-nguyen') ? 'selected' : ''; ?>>Thái Nguyên</option>
                                                <option value="thanh-hoa" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'thanh-hoa') ? 'selected' : ''; ?>>Thanh Hóa</option>
                                                <option value="thua-thien-hue" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'thua-thien-hue') ? 'selected' : ''; ?>>Thừa Thiên Huế</option>
                                                <option value="tien-giang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'tien-giang') ? 'selected' : ''; ?>>Tiền Giang</option>
                                                <option value="tra-vinh" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'tra-vinh') ? 'selected' : ''; ?>>Trà Vinh</option>
                                                <option value="tuyen-quang" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'tuyen-quang') ? 'selected' : ''; ?>>Tuyên Quang</option>
                                                <option value="vinh-long" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'vinh-long') ? 'selected' : ''; ?>>Vĩnh Long</option>
                                                <option value="vinh-phuc" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'vinh-phuc') ? 'selected' : ''; ?>>Vĩnh Phúc</option>
                                                <option value="yen-bai" data-fee="30000" <?php echo (isset($_POST['city']) && $_POST['city'] === 'yen-bai') ? 'selected' : ''; ?>>Yên Bái</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label>Quận/Huyện <span class="required">*Bắt buộc</span></label>
                                            <input type="text" name="district" class="form__input" 
                                                   value="<?php echo htmlspecialchars($_POST['district'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div>
                                            <label>Số nhà/Đường <span class="required">*Bắt buộc</span></label>
                                            <input type="text" name="address" class="form__input" 
                                                   value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                                        </div>
                                        <div>
                                            <label>Mã Zip <span class="required">*Bắt buộc</span></label>
                                            <input type="text" name="zipcode" class="form__input" 
                                                   value="<?php echo htmlspecialchars($_POST['zipcode'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="section">
                                <h3>3. Phương thức thanh toán</h3>
                                <div class="payment-options-container">
                                    <div class="payment-option-wrapper">
                                        <button type="button" class="payment-option" data-method="mastercard">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="Mastercard">
                                        </button>
                                        <span class="payment-label">Mastercard</span>
                                    </div>
                                    <div class="payment-option-wrapper">
                                        <button type="button" class="payment-option active" data-method="visa">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/1200px-Visa_Inc._logo.svg.png" alt="Visa">
                                        </button>
                                        <span class="payment-label">Visa</span>
                                    </div>
                                    <div class="payment-option-wrapper">
                                        <button type="button" class="payment-option" data-method="momo">
                                            <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png" alt="Momo">
                                        </button>
                                        <span class="payment-label">Momo E-wallet</span>
                                    </div>
                                    <div class="payment-option-wrapper">
                                        <button type="button" class="payment-option" data-method="cod">
                                            <img src="https://cdn-icons-png.flaticon.com/512/1554/1554401.png" alt="COD">
                                        </button>
                                        <span class="payment-label">COD</span>
                                    </div>
                                </div>
                                <input type="hidden" name="payment_method" value="visa" id="selected-payment-method">
                                
                                <!-- Hidden inputs for coupon information -->
                                <input type="hidden" name="coupon_code" id="coupon-code-input" value="">
                                <input type="hidden" name="discount_amount" id="discount-amount-input" value="0">
                            </div>
                        </form>
                    </section>

                    <aside class="order-summary">
                        <h3>Đơn hàng của bạn</h3>
                        
                        <div class="cart-items-container">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item" data-price="<?php echo $item['product_price']; ?>" data-id="<?php echo $item['id']; ?>">
                                    <img src="<?php echo $item['product_image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                                    <div class="cart-item-details">
                                        <div class="item-title"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="item-info">
                                            <span class="item-info-label">Số lượng</span>
                                            <div class="qty-controls">
                                                <button type="button" class="qty-btn" aria-label="Giảm" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                                <span class="qty-number"><?php echo $item['quantity']; ?></span>
                                                <button type="button" class="qty-btn" aria-label="Tăng" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                                            </div>
                                            <a href="#" class="remove-link" onclick="removeItem(<?php echo $item['id']; ?>)">Xóa</a>
                                        </div>
                                        <div class="item-price"><?php echo number_format($item['product_price'] * $item['quantity'], 0, ',', '.'); ?> đ</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="shipping-fee">
                            <span>Phí vận chuyển:</span>
                            <span class="fee-value" id="shipping-fee-display">
                                <?php echo $shipping_fee > 0 ? number_format($shipping_fee, 0, ',', '.') . ' đ' : 'Miễn phí'; ?>
                            </span>
                        </div>
                        <div class="total">
                            <span>Tổng tiền:</span>
                            <span class="total-value" id="total-display"><?php echo number_format($final_total, 0, ',', '.'); ?> vnd</span>
                        </div>
                        <div class="terms">
                            <label class="check-box" for="terms-checkbox">
                                <input type="checkbox" id="terms-checkbox" name="terms_checkbox" required 
                                       <?php echo isset($_POST['terms_checkbox']) ? 'checked' : ''; ?>>
                                <span class="checkbox-custom"></span>
                                <span>Tôi đồng ý với <a href="terms.php" target="_blank">Điều khoản và dịch vụ</a></span>
                            </label>
                        </div>
                        
                        <!-- Payment Button -->
                        <div class="payment-container">
                            <div class="payment-left-side">
                                <div class="payment-card">
                                    <div class="payment-card-line"></div>
                                    <div class="payment-buttons"></div>
                                </div>
                                <div class="payment-post">
                                    <div class="payment-post-line"></div>
                                    <div class="payment-screen">
                                        <div class="payment-dollar">VND</div>
                                    </div>
                                    <div class="payment-numbers"></div>
                                    <div class="payment-numbers-line2"></div>
                                </div>
                            </div>
                            <div class="payment-right-side">
                                <button type="submit" name="submit_payment" form="payment-form" class="payment-new">Tiến hành thanh toán</button>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!--=============== FOOTER ===============-->
    <footer class="footer">
        <div class="footer__container container">
            <div class="footer__row">
                <div class="footer__column">
                    <a href="index.php" class="footer__logo">
                        <img src="./assets/img/logo_red.svg" alt="Sunkissed" class="footer__logo-img">
                    </a>
                    
                    <h4 class="footer__heading">LIÊN HỆ</h4>
                    
                    <p class="footer__info">
                        Địa chỉ: 279 Nguyễn Tri Phương, Quận 10, Tp. Hồ Chí Minh
                    </p>
                    <p class="footer__info">
                        Điện thoại: +84 0335993276
                    </p>
                    <p class="footer__info">
                        Giờ hoạt động: 8:00 - 21:00, Thứ 2 - Thứ 7
                    </p>
                    
                    <div class="footer__social">
                        <h4 class="footer__social-title">Follow ngay</h4>
                        <div class="footer__social-links">
                            <a href="#" class="footer__social-link">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="footer__social-link">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="footer__social-link">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="footer__social-link">
                                <i class="fab fa-pinterest-p"></i>
                            </a>
                            <a href="#" class="footer__social-link">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="footer__column">
                    <h3 class="footer__title">Thông Tin</h3>
                    <ul class="footer__links">
                        <li><a href="aboutus.php" class="footer__link">Về chúng tôi</a></li>
                        <li><a href="#" class="footer__link">Thông tin giao hàng</a></li>
                        <li><a href="#" class="footer__link">Chính sách</a></li>
                        <li><a href="#" class="footer__link">Điều khoản & Điều kiện</a></li>
                        <li><a href="contact.php" class="footer__link">Liên hệ chúng tôi</a></li>
                        <li><a href="#" class="footer__link">Trung tâm hỗ trợ</a></li>
                    </ul>
                </div>
                
                <div class="footer__column">
                    <h3 class="footer__title">Tài khoản</h3>
                    <ul class="footer__links">
                        <li><a href="login-register.php" class="footer__link">Đăng nhập</a></li>
                        <li><a href="cart.php" class="footer__link">Xem giỏ hàng</a></li>
                        <li><a href="wishlist.php" class="footer__link">Wishlist</a></li>
                        <li><a href="#" class="footer__link">Theo dõi đơn hàng</a></li>
                        <li><a href="#" class="footer__link">Trợ giúp</a></li>
                        <li><a href="#" class="footer__link">Đặt hàng</a></li>
                    </ul>
                </div>
                
                <div class="footer__column">
                    <h3 class="footer__title">Cổng thanh toán bảo mật</h3>
                    <div class="footer__payment">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/1200px-Visa_Inc._logo.svg.png" alt="Visa" class="payment-logo">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="Mastercard" class="payment-logo">
                        <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png" alt="Momo" class="payment-logo">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer__bottom">
            <div class="container">
                <div class="footer__bottom-content">
                    <p class="copyright">© <?php echo date('Y'); ?> Sunkissed. All right reserved</p>
                    <p class="designer">Thiết kế bởi Nhóm 5 Coop.</p>
                </div>
            </div>
        </div>
    </footer>

    <!--=============== MAIN JS ===============-->
    <script src="assets/js/main.js"></script>

    <script>
        // Biến để theo dõi trạng thái đang xử lý
        let isProcessing = false;
        
        // Biến lưu trữ thông tin giảm giá và phí vận chuyển
        let currentCoupon = null;
        let currentShippingFee = 15000; // Mặc định TP.HCM
        
        // Hàm hiển thị loading
        function showLoading() {
            const loadingIndicator = document.getElementById('loading-indicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'flex';
            }
        }
        
        // Hàm ẩn loading
        function hideLoading() {
            const loadingIndicator = document.getElementById('loading-indicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
        }
        
        // Hàm cập nhật phí vận chuyển
        function updateShippingFee() {
            const citySelect = document.getElementById('city-select');
            if (!citySelect) return;
            
            const selectedOption = citySelect.options[citySelect.selectedIndex];
            
            if (selectedOption && selectedOption.value) {
                // Lấy phí vận chuyển từ data-fee
                currentShippingFee = parseInt(selectedOption.getAttribute('data-fee')) || 15000;
                
                // Cập nhật hiển thị phí vận chuyển
                const shippingFeeElement = document.getElementById('shipping-fee');
                if (shippingFeeElement) {
                    shippingFeeElement.textContent = new Intl.NumberFormat('vi-VN').format(currentShippingFee);
                }
                
                // Hiển thị thông báo
                const cityName = selectedOption.text;
                showNotification(`Đã cập nhật phí vận chuyển cho ${cityName}: ${new Intl.NumberFormat('vi-VN').format(currentShippingFee)}₫`, 'success');
                
                // Tính lại tổng với mã giảm giá nếu có
                recalculateTotal();
            } else {
                showNotification('Vui lòng chọn Tỉnh/Thành phố', 'error');
            }
        }

        // Hàm áp dụng mã giảm giá
        function applyCoupon() {
            const couponSelect = document.getElementById('coupon-select');
            if (!couponSelect) return;
            
            const selectedCoupon = couponSelect.value;
            
            if (!selectedCoupon) {
                showNotification('Vui lòng chọn mã giảm giá', 'error');
                return;
            }
            
            // Lấy subtotal hiện tại
            const subtotalElement = document.getElementById('cart-subtotal');
            if (!subtotalElement) return;
            
            const subtotalText = subtotalElement.textContent;
            const subtotal = parseInt(subtotalText.replace(/[^\d]/g, '')) || 0;
            
            let discountAmount = 0;
            let discountDescription = '';
            
            switch (selectedCoupon) {
                case 'GIAIKHATHE':
                    // Giảm 10% phí vận chuyển, tối đa 10.000đ
                    discountAmount = Math.min(currentShippingFee * 0.1, 10000);
                    discountDescription = 'Giảm 10% phí vận chuyển (tối đa 10.000đ)';
                    break;
                    
                case 'GIAIKHAT':
                    // Giảm 15% giá trị đơn hàng
                    discountAmount = subtotal * 0.15;
                    discountDescription = 'Giảm 15% giá trị đơn hàng';
                    break;
            }
            
            // Lưu thông tin mã giảm giá
            currentCoupon = {
                code: selectedCoupon,
                type: selectedCoupon === 'GIAIKHATHE' ? 'shipping' : 'order',
                discount: discountAmount,
                description: discountDescription
            };
            
            // Hiển thị thông tin mã giảm giá đã áp dụng
            const couponNameElement = document.getElementById('coupon-name');
            const couponDiscountElement = document.getElementById('coupon-discount-amount');
            const appliedCouponElement = document.getElementById('applied-coupon');
            
            if (couponNameElement) {
                couponNameElement.textContent = `${selectedCoupon}: ${discountDescription}`;
            }
            if (couponDiscountElement) {
                couponDiscountElement.textContent = new Intl.NumberFormat('vi-VN').format(Math.round(discountAmount));
            }
            if (appliedCouponElement) {
                appliedCouponElement.style.display = 'block';
            }
            
            // Hiển thị dòng giảm giá trong bảng tổng
            const discountRow = document.getElementById('discount-row');
            const discountAmountElement = document.getElementById('discount-amount');
            
            if (discountRow) {
                discountRow.style.display = 'table-row';
            }
            if (discountAmountElement) {
                discountAmountElement.textContent = '- ' + new Intl.NumberFormat('vi-VN').format(Math.round(discountAmount));
            }
            
            // Tính lại tổng
            recalculateTotal();
            
            // Reset dropdown
            couponSelect.value = '';
            
            showNotification('Đã áp dụng mã giảm giá thành công!', 'success');
        }

        // Hàm xóa mã giảm giá
        function removeCoupon() {
            currentCoupon = null;
            
            // Ẩn thông tin mã giảm giá
            const appliedCouponElement = document.getElementById('applied-coupon');
            const discountRow = document.getElementById('discount-row');
            
            if (appliedCouponElement) {
                appliedCouponElement.style.display = 'none';
            }
            if (discountRow) {
                discountRow.style.display = 'none';
            }
            
            // Tính lại tổng
            recalculateTotal();
            
            showNotification('Đã xóa mã giảm giá', 'success');
        }

        // Hàm tính lại tổng tiền
        function recalculateTotal() {
            // Lấy tổng giá trị giỏ hàng
            let subtotal = 0;
            const cartItems = document.querySelectorAll('.cart-item');
            cartItems.forEach(item => {
                const price = parseInt(item.getAttribute('data-price')) || 0;
                const quantity = parseInt(item.querySelector('.qty-number').textContent) || 1;
                subtotal += price * quantity;
            });
            
            // Tính giảm giá nếu có
            let discountAmount = 0;
            if (currentCoupon) {
                if (currentCoupon.code === 'GIAIKHATHE') {
                    // Giảm 10% phí vận chuyển, tối đa 10.000đ
                    discountAmount = Math.min(currentShippingFee * 0.1, 10000);
                } else if (currentCoupon.code === 'GIAIKHAT') {
                    // Giảm 15% giá trị đơn hàng
                    discountAmount = subtotal * 0.15;
                }
                    currentCoupon.discount = discountAmount;
            }
            
            // Tính tổng cuối cùng
            const total = subtotal + currentShippingFee - discountAmount;
            
            // Cập nhật hiển thị tổng
            const totalDisplay = document.getElementById('total-display');
            if (totalDisplay) {
                totalDisplay.textContent = new Intl.NumberFormat('vi-VN').format(Math.round(total)) + ' vnd';
            }
        }

        // Hàm tăng số lượng
        function increaseQuantity(productId) {
            if (isProcessing) return;
            
            const input = document.getElementById(`quantity-${productId}`);
            if (!input) return;
            
            const currentValue = parseInt(input.value) || 0;
            const newValue = currentValue + 1;
            
            updateQuantityInstant(productId, newValue);
        }

        // Hàm giảm số lượng
        function decreaseQuantity(productId) {
            if (isProcessing) return;
            
            const input = document.getElementById(`quantity-${productId}`);
            if (!input) return;
            
            const currentValue = parseInt(input.value) || 0;
            
            if (currentValue > 1) {
                const newValue = currentValue - 1;
                updateQuantityInstant(productId, newValue);
            }
        }

        // Hàm cập nhật số lượng ngay lập tức
        function updateQuantityInstant(productId, quantity) {
            if (isProcessing) return;
            
            quantity = parseInt(quantity);
            if (quantity < 1) {
                quantity = 1;
            }
            
            isProcessing = true;
            showLoading();
            
            // Cập nhật UI ngay lập tức
            const input = document.getElementById(`quantity-${productId}`);
            if (input) {
                input.value = quantity;
            }
            updateItemSubtotal(productId);
            
            // Gửi request đến server để lưu vào database
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật tổng giỏ hàng từ server
                    updateCartTotalsFromServer();
                    // Cập nhật số lượng trên header
                    updateCartCount();
                    
                    // Hiển thị thông báo ngắn
                    showNotification('Đã cập nhật số lượng', 'success');
                } else {
                    // Nếu lỗi, revert lại giá trị cũ
                    location.reload();
                    showNotification(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                location.reload();
                showNotification('Có lỗi xảy ra', 'error');
            })
            .finally(() => {
                isProcessing = false;
                hideLoading();
            });
        }

        // Override hàm updateCartTotalsFromServer để tính cả giảm giá
        function updateCartTotalsFromServer() {
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_cart_items'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const subtotal = parseInt(data.data.cart_total) || 0;
                    
                    // Cập nhật hiển thị subtotal
                    const subtotalElement = document.getElementById('cart-subtotal');
                    if (subtotalElement) {
                        subtotalElement.textContent = new Intl.NumberFormat('vi-VN').format(subtotal);
                    }
                    
                    // Tính lại tổng với phí vận chuyển và giảm giá
                    recalculateTotal();
                }
            })
            .catch(error => {
                console.error('Error updating totals:', error);
            });
        }

        // Hàm cập nhật subtotal cho từng sản phẩm
        function updateItemSubtotal(productId) {
            const quantityInput = document.getElementById(`quantity-${productId}`);
            if (!quantityInput) return;
            
            const quantity = parseInt(quantityInput.value) || 0;
            
            // Lấy giá sản phẩm
            const productElement = document.querySelector(`[data-product-id="${productId}"]`);
            if (!productElement) return;
            
            const priceElement = productElement.querySelector('.w-r__price');
            if (!priceElement) return;
            
            const price = parseInt(priceElement.getAttribute('data-price')) || 0;
            
            // Tính subtotal
            const subtotal = price * quantity;
            
            // Cập nhật hiển thị subtotal
            const subtotalElement = document.getElementById(`subtotal-${productId}`);
            if (subtotalElement) {
                subtotalElement.textContent = new Intl.NumberFormat('vi-VN').format(subtotal);
            }
        }

        // Các hàm khác giữ nguyên...
        function removeItemInstant(productId) {
            // Code cũ giữ nguyên
        }

        function clearCartInstant() {
            // Code cũ giữ nguyên
        }

        function updateCartCount() {
            // Code cũ giữ nguyên
        }

        function checkEmptyCart() {
            // Code cũ giữ nguyên
        }

        function viewProduct(productId) {
            window.location.href = `product-detail.php?id=${productId}`;
        }

        function showNotification(message, type) {
            // Xóa thông báo cũ nếu có
            const existingNotification = document.querySelector('.toast-notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = `toast-notification ${type}`;
            notification.textContent = message;
            
            // Định nghĩa màu sắc cho từng loại thông báo
            let backgroundColor, textColor;
            switch(type) {
                case 'success':
                    backgroundColor = '#4CAF50';
                    textColor = 'white';
                    break;
                case 'error':
                    backgroundColor = '#f44336';
                    textColor = 'white';
                    break;
                case 'warning':
                    backgroundColor = '#ff9800';
                    textColor = 'white';
                    break;
                case 'info':
                    backgroundColor = '#2196F3';
                    textColor = 'white';
                    break;
                default:
                    backgroundColor = '#333';
                    textColor = 'white';
            }
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                background: ${backgroundColor};
                color: ${textColor};
                border-radius: 8px;
                z-index: 10000;
                opacity: 0;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                font-size: 14px;
                font-weight: 500;
                max-width: 350px;
                word-wrap: break-word;
                border-left: 4px solid rgba(255,255,255,0.3);
            `;
            
            // Thêm icon dựa trên type
            let icon = '';
            switch(type) {
                case 'success':
                    icon = '✅ ';
                    break;
                case 'error':
                    icon = '❌ ';
                    break;
                case 'warning':
                    icon = '⚠️ ';
                    break;
                case 'info':
                    icon = 'ℹ️ ';
                    break;
            }
            notification.textContent = icon + message;
            
            document.body.appendChild(notification);
            
            // Hiển thị
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateY(0)';
            }, 100);
            
            // Tự động ẩn notification
            const hideTimeout = type === 'info' ? 4000 : 3000; // Info hiển thị lâu hơn
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, hideTimeout);
            
            // Cho phép click để đóng
            notification.addEventListener('click', function() {
                this.style.opacity = '0';
                this.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    if (this.parentNode) {
                        this.remove();
                    }
                }, 300);
            });
            
            // Thêm cursor pointer
            notification.style.cursor = 'pointer';
        }

        function updateInitialPriceFormat() {
            // Code cũ giữ nguyên
        }

        // Khởi tạo khi trang load
        document.addEventListener('DOMContentLoaded', function() {
            // Thêm id cho form
            const paymentForm = document.querySelector('form[method="POST"]');
            if (paymentForm) {
                paymentForm.id = 'payment-form';
                
                // Thêm xử lý form submission với validation
                paymentForm.addEventListener('submit', function(e) {
                    if (!validateFormBeforeSubmit()) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Hiển thị loading
                    showLoading();
                    showNotification('Đang xử lý thanh toán...', 'info');
                });
            }
            
            // Thêm validation cho payment button
            const paymentButton = document.querySelector('button[name="submit_payment"]');
            if (paymentButton) {
                paymentButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (validateFormBeforeSubmit()) {
                        // Hiển thị confirmation
                        if (confirm('Bạn có chắc chắn muốn tiến hành thanh toán?')) {
                            showLoading();
                            showNotification('Đang xử lý thanh toán, vui lòng đợi...', 'info');
                            // Submit form
                            paymentForm.submit();
                        }
                    }
                });
            }

            // Validation function
            function validateFormBeforeSubmit() {
                const errors = [];
                
                // Kiểm tra thông tin khách hàng
                const firstName = document.querySelector('input[name="first_name"]').value.trim();
                const lastName = document.querySelector('input[name="last_name"]').value.trim();
                const phone = document.querySelector('input[name="phone"]').value.trim();
                const email = document.querySelector('input[name="email"]').value.trim();
                const termsAccepted = document.querySelector('input[name="terms_checkbox"]').checked;
                
                if (!firstName) errors.push('Vui lòng nhập tên');
                if (!lastName) errors.push('Vui lòng nhập họ và tên lót');
                if (!phone) errors.push('Vui lòng nhập số điện thoại');
                if (!email) errors.push('Vui lòng nhập email');
                if (!termsAccepted) errors.push('Vui lòng đồng ý với điều khoản dịch vụ');
                
                // Kiểm tra email format
                if (email && !isValidEmail(email)) {
                    errors.push('Email không hợp lệ');
                }
                
                // Kiểm tra phương thức giao hàng
                const deliveryMethod = getCurrentDeliveryMethod();
                
                if (deliveryMethod === 'store') {
                    const pickupDate = document.querySelector('input[name="pickup_date"]').value;
                    const pickupTime = document.querySelector('select[name="pickup_time"]').value;
                    const selectedStore = document.querySelector('input[name="store"]:checked');
                    
                    if (!pickupDate) errors.push('Vui lòng chọn ngày lấy hàng');
                    if (!pickupTime) errors.push('Vui lòng chọn thời gian lấy hàng');
                    if (!selectedStore) errors.push('Vui lòng chọn cửa hàng');
                } else if (deliveryMethod === 'delivery') {
                    const deliveryDate = document.querySelector('input[name="delivery_date"]').value;
                    const deliveryTime = document.querySelector('select[name="delivery_time"]').value;
                    const city = document.querySelector('select[name="city"]').value;
                    const district = document.querySelector('input[name="district"]').value.trim();
                    const address = document.querySelector('input[name="address"]').value.trim();
                    const zipcode = document.querySelector('input[name="zipcode"]').value.trim();
                    
                    if (!deliveryDate) errors.push('Vui lòng chọn ngày giao hàng');
                    if (!deliveryTime) errors.push('Vui lòng chọn thời gian giao hàng');
                    if (!city) errors.push('Vui lòng chọn tỉnh/thành phố');
                    if (!district) errors.push('Vui lòng nhập quận/huyện');
                    if (!address) errors.push('Vui lòng nhập địa chỉ');
                    if (!zipcode) errors.push('Vui lòng nhập mã ZIP');
                }
                
                // Kiểm tra giỏ hàng có sản phẩm không
                const cartItems = document.querySelectorAll('.cart-item');
                if (cartItems.length === 0) {
                    errors.push('Giỏ hàng trống, vui lòng thêm sản phẩm');
                }
                
                // Hiển thị lỗi nếu có
                if (errors.length > 0) {
                    let errorMessage = 'Vui lòng kiểm tra lại thông tin:\n';
                    errors.forEach((error, index) => {
                        errorMessage += `${index + 1}. ${error}\n`;
                    });
                    
                    showNotification('Thông tin chưa đầy đủ!', 'error');
                    alert(errorMessage);
                    
                    // Highlight first error field
                    highlightErrorField(errors[0]);
                    
                    return false;
                }
                
                return true;
            }
            
            // Hàm kiểm tra email hợp lệ
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            // Hàm lấy phương thức giao hàng hiện tại
            function getCurrentDeliveryMethod() {
                const storeBtn = document.querySelector('.store-btn');
                const deliveryBtn = document.querySelector('.delivery-btn');
                
                if (storeBtn && storeBtn.classList.contains('active')) {
                    return 'store';
                } else if (deliveryBtn && deliveryBtn.classList.contains('active')) {
                    return 'delivery';
                }
                
                return 'store'; // mặc định
            }
            
            // Hàm highlight field có lỗi
            function highlightErrorField(errorMessage) {
                // Remove previous highlights
                document.querySelectorAll('.validation-error').forEach(el => {
                    el.classList.remove('validation-error');
                });
                
                // Highlight specific field based on error message
                if (errorMessage.includes('tên')) {
                    const field = errorMessage.includes('họ') ? 
                        document.querySelector('input[name="last_name"]') : 
                        document.querySelector('input[name="first_name"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('điện thoại')) {
                    const field = document.querySelector('input[name="phone"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('email')) {
                    const field = document.querySelector('input[name="email"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('điều khoản')) {
                    const field = document.querySelector('input[name="terms_checkbox"]');
                    if (field) {
                        field.parentElement.classList.add('validation-error');
                        // Scroll to terms
                        field.parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else if (errorMessage.includes('ngày lấy hàng')) {
                    const field = document.querySelector('input[name="pickup_date"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('thời gian lấy hàng')) {
                    const field = document.querySelector('select[name="pickup_time"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('cửa hàng')) {
                    const storeOptions = document.querySelectorAll('.store-option');
                    storeOptions.forEach(option => option.classList.add('validation-error'));
                } else if (errorMessage.includes('ngày giao hàng')) {
                    const field = document.querySelector('input[name="delivery_date"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('thời gian giao hàng')) {
                    const field = document.querySelector('select[name="delivery_time"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('tỉnh/thành phố')) {
                    const field = document.querySelector('select[name="city"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('quận/huyện')) {
                    const field = document.querySelector('input[name="district"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('địa chỉ')) {
                    const field = document.querySelector('input[name="address"]');
                    if (field) field.classList.add('validation-error');
                } else if (errorMessage.includes('ZIP')) {
                    const field = document.querySelector('input[name="zipcode"]');
                    if (field) field.classList.add('validation-error');
                }
            }
            
            // Thêm event listeners để remove validation error khi user input
            const allInputs = document.querySelectorAll('input, select');
            allInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('validation-error');
                    if (this.parentElement) {
                        this.parentElement.classList.remove('validation-error');
                    }
                });
                
                input.addEventListener('change', function() {
                    this.classList.remove('validation-error');
                    if (this.parentElement) {
                        this.parentElement.classList.remove('validation-error');
                    }
                });
            });
            
            // Xử lý chọn phương thức giao hàng
            const storeBtn = document.querySelector('.store-btn');
            const deliveryBtn = document.querySelector('.delivery-btn');
            const storeContainer = document.querySelector('.store-pickup-container');
            const deliveryContainer = document.querySelector('.delivery-container');
            
            if (storeBtn && deliveryBtn) {
                storeBtn.addEventListener('click', function() {
                    storeBtn.classList.add('active');
                    deliveryBtn.classList.remove('active');
                    storeContainer.style.display = 'block';
                    deliveryContainer.style.display = 'none';
                    
                    // Cập nhật input hidden
                    const storeInput = storeContainer.querySelector('.delivery-method-input');
                    if (storeInput) storeInput.value = 'store';
                    
                    // Disable required fields cho delivery
                    deliveryContainer.querySelectorAll('input[required]').forEach(input => {
                        input.removeAttribute('required');
                    });
                    
                    // Enable required fields cho store
                    storeContainer.querySelectorAll('input').forEach(input => {
                        if (input.name === 'pickup_date' || input.name === 'pickup_time') {
                            input.setAttribute('required', 'required');
                        }
                    });
                    
                    // Cập nhật phí vận chuyển
                    document.getElementById('shipping-fee-display').textContent = 'Miễn phí';
                    currentShippingFee = 0;
                    recalculateTotal();
                });
                
                deliveryBtn.addEventListener('click', function() {
                    deliveryBtn.classList.add('active');
                    storeBtn.classList.remove('active');
                    deliveryContainer.style.display = 'block';
                    storeContainer.style.display = 'none';
                    
                    // Cập nhật input hidden
                    const deliveryInput = deliveryContainer.querySelector('.delivery-method-input');
                    if (deliveryInput) deliveryInput.value = 'delivery';
                    
                    // Enable required fields cho delivery
                    deliveryContainer.querySelectorAll('input').forEach(input => {
                        if (['delivery_date', 'delivery_time', 'city', 'district', 'address', 'zipcode'].includes(input.name)) {
                            input.setAttribute('required', 'required');
                        }
                    });
                    
                    // Disable required fields cho store
                    storeContainer.querySelectorAll('input[required]').forEach(input => {
                        input.removeAttribute('required');
                    });
                    
                    // Cập nhật phí vận chuyển dựa trên tỉnh/thành phố đã chọn
                    const citySelect = document.getElementById('city-select');
                    if (citySelect && citySelect.value) {
                        const selectedOption = citySelect.options[citySelect.selectedIndex];
                        currentShippingFee = parseInt(selectedOption.getAttribute('data-fee')) || 30000;
                    } else {
                        // Mặc định nếu chưa chọn tỉnh/thành phố
                        currentShippingFee = 30000;
                    }
                    
                    document.getElementById('shipping-fee-display').textContent = new Intl.NumberFormat('vi-VN').format(currentShippingFee) + ' đ';
                    recalculateTotal();
                });
            }
            
            // Xử lý click chọn cửa hàng
            const storeOptions = document.querySelectorAll('.store-option');
            storeOptions.forEach(option => {
                // Validate thông tin giao hàng
                const deliveryDate = document.querySelector('input[name="delivery_date"]').value;
                option.addEventListener('click', function() {
                    // Bỏ selected từ tất cả options
                    storeOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Thêm selected cho option được click
                    this.classList.add('selected');
                    
                    // Check radio button tương ứng
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                    }
                });
            });
            
            // Xử lý click chọn phương thức thanh toán
            const paymentOptions = document.querySelectorAll('.payment-option');
            const selectedPaymentInput = document.getElementById('selected-payment-method');
            
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Bỏ active từ tất cả options
                    paymentOptions.forEach(opt => opt.classList.remove('active'));
                    
                    // Thêm active cho option được click
                    this.classList.add('active');
                    
                    // Cập nhật giá trị input hidden
                    const method = this.getAttribute('data-method');
                    if (selectedPaymentInput) {
                        selectedPaymentInput.value = method;
                    }
                    
                    // Thêm hiệu ứng
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 100);
                });
            });
            
            // Xử lý country dropdown
            const countrySelector = document.getElementById('country-selector');
            const countryDropdown = document.getElementById('country-dropdown');
            
            if (countrySelector && countryDropdown) {
                countrySelector.addEventListener('click', function(e) {
                    e.stopPropagation();
                    countryDropdown.classList.toggle('show');
                });
                
                // Đóng dropdown khi click bên ngoài
                document.addEventListener('click', function() {
                    countryDropdown.classList.remove('show');
                });
                
                // Xử lý chọn country
                const countryOptions = countryDropdown.querySelectorAll('.country-option');
                countryOptions.forEach(option => {
                    option.addEventListener('click', function() {
                        const code = this.getAttribute('data-code');
                        const flag = this.querySelector('.country-flag').src;
                        
                        // Cập nhật button
                        countrySelector.querySelector('.country-flag').src = flag;
                        countrySelector.querySelector('span:nth-child(2)').textContent = code;
                        
                        // Đóng dropdown
                        countryDropdown.classList.remove('show');
                    });
                });
            }
            
            // Set mặc định TP.HCM và phí vận chuyển 15.000đ
            const citySelect = document.getElementById('city-select');
            if (citySelect) {
                citySelect.value = 'ho-chi-minh';
                currentShippingFee = 15000;
                const shippingFeeElement = document.getElementById('shipping-fee');
                if (shippingFeeElement) {
                    shippingFeeElement.textContent = '15.000';
                }
            }
            
            // Tính tổng ban đầu
            setTimeout(() => {
                recalculateTotal();
            }, 100);
            
            // Lưu thông tin vào sessionStorage khi chuyển trang thanh toán
            const checkoutBtn = document.querySelector('a[href="payment.php"]');
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Kiểm tra đã chọn tỉnh/thành phố chưa
                    const citySelect = document.getElementById('city-select');
                    if (!citySelect || !citySelect.value) {
                        showNotification('Vui lòng chọn Tỉnh/Thành phố để tính phí vận chuyển', 'error');
                        return;
                    }
                    
                    // Lưu thông tin vào sessionStorage
                    const shippingInfo = {
                        city: citySelect.value,
                        cityName: citySelect.options[citySelect.selectedIndex].text,
                        shippingFee: currentShippingFee,
                        coupon: currentCoupon
                    };
                    
                    sessionStorage.setItem('shippingInfo', JSON.stringify(shippingInfo));
                    
                    // Chuyển trang
                    window.location.href = 'payment.php';
                });
            }
            
            // Xử lý thay đổi thời gian dựa trên ngày và cửa hàng được chọn
            function updateTimeSlots() {
                const selectedStore = document.querySelector('input[name="store"]:checked');
                const pickupDate = document.querySelector('input[name="pickup_date"]').value;
                const pickupTimeSelect = document.querySelector('select[name="pickup_time"]');
                
                if (!pickupDate || !pickupTimeSelect) return;
                
                // Kiểm tra xem ngày được chọn có phải là ngày trong quá khứ không
                const selectedDate = new Date(pickupDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    showNotification('Vui lòng chọn ngày từ hôm nay trở đi', 'error');
                    document.querySelector('input[name="pickup_date"]').value = '';
                    return;
                }
                
                // Reset select options
                const defaultOptions = `
                    <option value="">Chọn thời gian</option>
                    <option value="8:00-9:00">8:00 - 9:00</option>
                    <option value="9:00-10:00">9:00 - 10:00</option>
                    <option value="10:00-11:00">10:00 - 11:00</option>
                    <option value="11:00-12:00">11:00 - 12:00</option>
                    <option value="12:00-13:00">12:00 - 13:00</option>
                    <option value="13:00-14:00">13:00 - 14:00</option>
                    <option value="14:00-15:00">14:00 - 15:00</option>
                    <option value="15:00-16:00">15:00 - 16:00</option>
                    <option value="16:00-17:00">16:00 - 17:00</option>
                    <option value="17:00-18:00">17:00 - 18:00</option>
                    <option value="18:00-19:00">18:00 - 19:00</option>
                    <option value="19:00-20:00">19:00 - 20:00</option>
                    <option value="20:00-21:00">20:00 - 21:00</option>
                `;
                pickupTimeSelect.innerHTML = defaultOptions;
                
                // Kiểm tra ngày có phải Chủ Nhật không (cho cửa hàng 1)
                if (selectedStore && selectedStore.value === 'store1') {
                    const dayOfWeek = selectedDate.getDay();
                    if (dayOfWeek === 0) { // Chủ Nhật
                        pickupTimeSelect.innerHTML = '<option value="">Cửa hàng đóng cửa Chủ Nhật</option>';
                        pickupTimeSelect.disabled = true;
                        showNotification('Cửa hàng chi nhánh 1 đóng cửa vào Chủ Nhật', 'warning');
                        return;
                    } else {
                        pickupTimeSelect.disabled = false;
                    }
                }
                
                // Nếu là ngày hôm nay, disable các khung giờ đã qua
                if (selectedDate.toDateString() === today.toDateString()) {
                    const currentHour = new Date().getHours();
                    const currentMinute = new Date().getMinutes();
                    const options = pickupTimeSelect.querySelectorAll('option');
                    
                    options.forEach(option => {
                        if (option.value) {
                            const startHour = parseInt(option.value.split(':')[0]);
                            // Thêm 1 giờ buffer để khách hàng có thời gian chuẩn bị
                            if (startHour <= currentHour + 1) {
                                option.disabled = true;
                                option.textContent = option.textContent + ' (Không khả dụng)';
                            }
                        }
                    });
                    
                    // Nếu tất cả slots đều disabled (quá muộn trong ngày)
                    const availableOptions = pickupTimeSelect.querySelectorAll('option:not(:disabled)');
                    if (availableOptions.length <= 1) { // Chỉ có option "Chọn thời gian"
                        showNotification('Đã quá muộn để đặt hàng trong ngày hôm nay. Vui lòng chọn ngày mai.', 'warning');
                    }
                }
            }
            
            // Lắng nghe sự kiện thay đổi ngày
            const pickupDateInput = document.querySelector('input[name="pickup_date"]');
            if (pickupDateInput) {
                pickupDateInput.addEventListener('change', updateTimeSlots);
                
                // Set min date là ngày hôm nay
                const today = new Date().toISOString().split('T')[0];
                pickupDateInput.min = today;
                
                // Set max date là 30 ngày từ hôm nay
                const maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + 30);
                pickupDateInput.max = maxDate.toISOString().split('T')[0];
            }
            
            // Update lại event listener cho store options
            const existingStoreOptions = document.querySelectorAll('.store-option');
            existingStoreOptions.forEach(option => {
                const existingClickHandler = option.onclick;
                option.addEventListener('click', function() {
                    // Gọi handler cũ nếu có
                    if (existingClickHandler) existingClickHandler.call(this);
                    
                    // Update time slots khi chọn cửa hàng khác
                    setTimeout(updateTimeSlots, 100);
                });
            });
            
            // Cũng xử lý cho delivery date
            const deliveryDateInput = document.querySelector('input[name="delivery_date"]');
            if (deliveryDateInput) {
                const today = new Date().toISOString().split('T')[0];
                deliveryDateInput.min = today;
                
                const maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + 30);
                deliveryDateInput.max = maxDate.toISOString().split('T')[0];
                
                deliveryDateInput.addEventListener('change', function() {
                    const selectedDate = new Date(this.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (selectedDate < today) {
                        showNotification('Vui lòng chọn ngày từ hôm nay trở đi', 'error');
                        this.value = '';
                    }
                });
            }
            
            // Set mặc định TP.HCM nếu chưa có giá trị
            const citySelectElement = document.getElementById('city-select');
            if (citySelectElement && !citySelectElement.value) {
                citySelectElement.value = 'ho-chi-minh';
                
                // Nếu đang ở phương thức giao hàng, cập nhật phí vận chuyển
                const deliveryBtn = document.querySelector('.delivery-btn');
                if (deliveryBtn && deliveryBtn.classList.contains('active')) {
                    currentShippingFee = 15000;
                    document.getElementById('shipping-fee-display').textContent = '15.000 đ';
                    recalculateTotal();
                }
            }
            
            // Lấy thông tin từ sessionStorage nếu có (từ trang cart)
            const shippingInfo = sessionStorage.getItem('shippingInfo');
            if (shippingInfo) {
                const info = JSON.parse(shippingInfo);
                if (info.city && citySelectElement) {
                    citySelectElement.value = info.city;
                    currentShippingFee = info.shippingFee || 15000;
                    
                    // Áp dụng mã giảm giá nếu có
                    if (info.coupon) {
                        currentCoupon = info.coupon;
                        // Hiển thị thông tin mã giảm giá
                        showNotification(`Đã áp dụng mã giảm giá ${info.coupon.code}`, 'success');
                    }
                    
                    // Cập nhật hiển thị
                    if (document.querySelector('.delivery-btn.active')) {
                        document.getElementById('shipping-fee-display').textContent = new Intl.NumberFormat('vi-VN').format(currentShippingFee) + ' đ';
                        recalculateTotal();
                    }
                }
                
                // Xóa thông tin từ sessionStorage sau khi sử dụng
                sessionStorage.removeItem('shippingInfo');
            }
        });

        // Hàm cập nhật phí vận chuyển cho phương thức giao hàng
        function updateDeliveryShippingFee() {
            const deliveryBtn = document.querySelector('.delivery-btn');
            
            // Chỉ cập nhật nếu đang chọn phương thức giao hàng
            if (!deliveryBtn || !deliveryBtn.classList.contains('active')) {
                return;
            }
            
            const citySelect = document.getElementById('city-select');
            if (!citySelect) return;
            
            const selectedOption = citySelect.options[citySelect.selectedIndex];
            
            if (selectedOption && selectedOption.value) {
                // Lấy phí vận chuyển từ data-fee
                currentShippingFee = parseInt(selectedOption.getAttribute('data-fee')) || 15000;
                
                // Cập nhật hiển thị phí vận chuyển
                const shippingFeeDisplay = document.getElementById('shipping-fee-display');
                if (shippingFeeDisplay) {
                    shippingFeeDisplay.textContent = new Intl.NumberFormat('vi-VN').format(currentShippingFee) + ' đ';
                }
                
                // Hiển thị thông báo
                const cityName = selectedOption.text;
                showNotification(`Phí vận chuyển cho ${cityName}: ${new Intl.NumberFormat('vi-VN').format(currentShippingFee)}₫`, 'info');
                
                // Tính lại tổng
                recalculateTotal();
            }
        }
    </script>

    <style>
        /* Additional styles to match payment.html */
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert ul {
            margin: 10px 0 0 20px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-cart h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .back-arrow {
            color: #ff6b35;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .back-arrow:hover {
            color: #e55a2b;
        }
        
        .required {
            color: #ff6b35;
            font-size: 12px;
        }
        
        .phone-input-container {
            display: flex;
            gap: 5px;
        }
        
        .phone-country-code {
            position: relative;
        }
        
        .phone-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            cursor: pointer;
        }
        
        .country-flag {
            width: 20px;
            height: 15px;
        }
        
        .country-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .country-dropdown.show {
            display: block;
        }
        
        .country-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            cursor: pointer;
        }
        
        .country-option:hover {
            background-color: #f5f5f5;
        }
        
        .shipping-methods {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .store-btn, .delivery-btn {
            padding: 10px 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .store-btn.active, .delivery-btn.active {
            border-color: #26551D;
            background-color: #26551D;
            color: white;
        }
        
        .store-options {
            display: grid;
            gap: 20px;
        }
        
        .store-option {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            background-color: #fff;
        }
        
        .store-option:hover {
            border-color: #26551D;
            background-color: rgba(38, 85, 29, 0.03);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .store-option.selected {
            border-color: #26551D;
            background-color: rgba(38, 85, 29, 0.05);
            box-shadow: 0 4px 16px rgba(38, 85, 29, 0.15);
        }
        
        .store-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .custom-radio {
            width: 24px;
            height: 24px;
            border: 2px solid #d0d0d0;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            margin-right: 20px;
            flex-shrink: 0;
            transition: all 0.3s ease;
            background-color: #fff;
        }
        
        .store-option:hover .custom-radio {
            border-color: #26551D;
            box-shadow: 0 0 0 4px rgba(38, 85, 29, 0.1);
        }
        
        .store-option.selected .custom-radio {
            border-color: #26551D;
            background-color: #26551D;
        }
        
        .store-option.selected .custom-radio::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: white;
            animation: radioCheck 0.3s ease;
        }
        
        @keyframes radioCheck {
            0% {
                transform: translate(-50%, -50%) scale(0);
            }
            50% {
                transform: translate(-50%, -50%) scale(1.2);
            }
            100% {
                transform: translate(-50%, -50%) scale(1);
            }
        }
        
        .store-info {
            flex: 1;
        }
        
        .store-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
            font-size: 16px;
        }
        
        .store-address {
            color: #666;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .store-hours {
            color: #888;
            font-size: 13px;
        }
        
        .store-option.selected .store-name {
            color: #26551D;
        }
        
        /* Payment Options Styles */
        .payment-options-container {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 25px 20px;
            background-color: #fff;
            border-radius: 12px;
        }
        
        .payment-option-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        
        .payment-option {
            width: 75%;
            height: 60px;
            padding: 8px 10px;
            background-color: #ffffff;
            border: 1.6px solid #e6e6e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 5px;
            position: relative;
            overflow: hidden;
        }
        
        .payment-option img {
            max-height: 32px;
            max-width: 80%;
            object-fit: contain;
            transition: transform 0.3s ease;
            filter: grayscale(0);
        }
        
        .payment-option:hover {
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }
        
        .payment-option:hover img {
            transform: scale(1.08);
        }
        
        .payment-option.active {
            border-color: #26551D;
            border-width: 1.6px;
            box-shadow: none;
            background-color: #ffffff;
            transform: translateY(0);
        }
        
        .payment-option.active img {
            transform: scale(1.1);
            filter: brightness(1.05);
        }
        
        /* Subtle animation for active state */
        @keyframes subtlePulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .payment-option.active {
            animation: subtlePulse 2s ease-in-out infinite;
        }
        
        /* Click effect */
        .payment-option:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
        
        .payment-label {
            font-size: 0.8rem;
            color: #555;
            margin-top: 8px;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .payment-option.active ~ .payment-label {
            color: #26551D;
            font-weight: 600;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .store-options {
                gap: 15px;
            }
            
            .store-option {
                padding: 15px;
            }
            
            .custom-radio {
                width: 20px;
                height: 20px;
                margin-right: 15px;
            }
            
            .payment-options-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .payment-option {
                width: 100%;
                height: 55px;
            }
            
            .payment-option img {
                max-height: 28px;
            }
        }
        
        /* Style for select dropdown */
        select.form__input {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
            padding-right: 40px;
            cursor: pointer;
        }
        
        select.form__input:focus {
            border-color: #26551D;
            box-shadow: 0 0 0 3px rgba(38, 85, 29, 0.1);
        }
        
        select.form__input option {
            padding: 10px;
        }
        
        /* Disabled state for unavailable time slots */
        select.form__input option:disabled {
            color: #999;
            background-color: #f5f5f5;
        }
        
        /* Time slot warning */
        .time-slot-warning {
            font-size: 12px;
            color: #ff6b35;
            margin-top: 5px;
            display: none;
        }
        
        /* Loading indicator */
        .loading-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
            font-size: 16px;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #ff6b35;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Toast notification */
        .toast-notification {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Thêm vào phần <style> trong file */
        /* Validation error highlight */
        .validation-error {
            border-color: #ff6b35 !important;
            box-shadow: 0 0 10px rgba(255, 107, 53, 0.3) !important;
            animation: errorPulse 1s ease-in-out 3;
        }

        @keyframes errorPulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
            100% {
                transform: scale(1);
            }
        }

        /* Style cải thiện cho thông báo lỗi */
        .payment-container:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 20px rgba(38, 85, 29, 0.15);
        }

        .payment-container:active {
            transform: scale(0.98);
        }

        /* Thêm cursor pointer cho payment container */
        .payment-container {
            cursor: pointer;
        }

        .payment-container .payment-new {
            cursor: pointer;
            pointer-events: none; /* Để click event đi qua container */
        }
    </style>
</body>
</html>
