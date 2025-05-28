<?php
// Hiển thị tất cả lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tạo file log riêng
$log_file = __DIR__ . '/payment_debug.log';
function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog('=== New Payment Session Started ===');

// Khởi tạo session và xử lý dữ liệu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include các file cần thiết
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->connect();

// Khởi tạo Cart class và Order class
$cart = new Cart($db);
$order = new Order($db);

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

// Thêm session token để tránh submit trùng
if (!isset($_SESSION['payment_token'])) {
    $_SESSION['payment_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['submit_payment']) || isset($_POST['save_session_only']))) {
    // Kiểm tra nếu chỉ cần lưu session (cho MoMo)
    $saveSessionOnly = isset($_POST['save_session_only']);
    
    if (!$saveSessionOnly) {
        // Kiểm tra payment token chỉ khi không phải save session only
        if (!isset($_POST['payment_token']) || $_POST['payment_token'] !== $_SESSION['payment_token']) {
            writeLog("Invalid or missing payment token. Redirecting to payment.php");
            header('Location: payment.php');
            exit;
        }
    }

    // Log để debug
    writeLog("=== PAYMENT FORM SUBMITTED ===");
    writeLog("Save session only: " . ($saveSessionOnly ? 'YES' : 'NO'));
    writeLog("POST data: " . print_r($_POST, true));
    writeLog("Session payment_info before processing: " . print_r($_SESSION['payment_info'] ?? 'NOT SET', true));
    
    try {
        // Validate form data
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $delivery_method = $_POST['delivery_method'] ?? 'store';
        $payment_method = $_POST['payment_method'] ?? 'visa';
        $terms_accepted = isset($_POST['terms_checkbox']) && $_POST['terms_checkbox'] == '1';
        
        writeLog("Extracted form data - First: $first_name, Last: $last_name, Phone: $phone, Email: $email, Payment: $payment_method");
        
        // Validation (ít nghiêm ngặt hơn cho save session only)
        if (!$saveSessionOnly) {
            if (empty($first_name)) $errors[] = 'Tên không được để trống';
            if (empty($last_name)) $errors[] = 'Họ và tên lót không được để trống';
            if (empty($phone)) $errors[] = 'Số điện thoại không được để trống';
            if (!preg_match('/^[0-9]{10,11}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ';
            if (empty($email)) $errors[] = 'Email không được để trống';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
            if (!$terms_accepted) $errors[] = 'Bạn phải đồng ý với điều khoản dịch vụ';
            if (empty($cart_items)) $errors[] = 'Giỏ hàng trống';
        }

        writeLog("Validation errors: " . print_r($errors, true));

        if ($saveSessionOnly || empty($errors)) {
            // Lưu thông tin vào session
            $_SESSION['payment_info'] = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
                'email' => $email,
                'delivery_method' => $delivery_method,
                'payment_method' => $payment_method,
                'user_id' => $user_id,
                'cart_items' => $cart_items,
                'cart_total' => $cart_total,
                'shipping_fee' => $shipping_fee,
                'discount_amount' => $discount_amount,
                'total_amount' => $cart_total + $shipping_fee - $discount_amount,
                'payment_token' => $_SESSION['payment_token'] ?? bin2hex(random_bytes(32))
            ];

            writeLog("Payment info saved to session: " . print_r($_SESSION['payment_info'], true));

            if ($saveSessionOnly) {
                // Chỉ trả về success cho AJAX request
                echo json_encode(['status' => 'success', 'message' => 'Session saved']);
                exit;
            }

            // Xóa token cũ và tạo token mới
            unset($_SESSION['payment_token']);
            $_SESSION['payment_token'] = bin2hex(random_bytes(32));

            // Redirect based on payment method
            if ($payment_method === 'momo') {
                writeLog("Redirecting to MoMo payment...");
                header('Location: momo_payment.php');
                exit;
            } else {
                writeLog("Processing other payment method: " . $payment_method);
                $errors[] = 'Phương thức thanh toán ' . $payment_method . ' chưa được tích hợp';
            }
        }
    } catch (Exception $e) {
        writeLog("Payment error: " . $e->getMessage());
        $errors[] = 'Có lỗi xảy ra: ' . $e->getMessage();
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
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <!-- Swiper JS -->
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    
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
        <?php if (!empty($errors) || isset($_SESSION['payment_error'])): ?>
            <div class="alert alert-error container">
                <h4>Có lỗi xảy ra:</h4>
                <ul>
                    <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['payment_error']) && isset($_SESSION['error_message'])): ?>
                        <li><?php echo htmlspecialchars($_SESSION['error_message']); ?></li>
                        <?php 
                        unset($_SESSION['payment_error']);
                        unset($_SESSION['error_message']);
                        ?>
                    <?php endif; ?>
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
                        <form id="payment-form" method="POST" action="payment.php">
                            <input type="hidden" name="payment_token" value="<?php echo $_SESSION['payment_token']; ?>">
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
                                                <label class="store-option <?php echo ($_POST['store'] ?? '') === $store['id'] ? 'selected' : ''; ?>" for="store-<?php echo $store['id']; ?>">
                                                    <input type="radio" 
                                                           id="store-<?php echo $store['id']; ?>" 
                                                           name="store" 
                                                           value="<?php echo $store['id']; ?>" 
                                                           <?php echo ($index === 0 || ($_POST['store'] ?? '') === $store['id']) ? 'checked' : ''; ?>>
                                                    <span class="custom-radio"></span>
                                                    <div class="store-info">
                                                        <div class="store-name"><?php echo htmlspecialchars($store['name']); ?></div>
                                                        <div class="store-address"><?php echo htmlspecialchars($store['address']); ?></div>
                                                        <div class="store-hours"><?php echo htmlspecialchars($store['hours']); ?></div>
                                                    </div>
                                                </label>
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
                            <label class="check-box" for="terms_checkbox">
                                <input type="checkbox" id="terms_checkbox" name="terms_checkbox" required 
                                       <?php echo isset($_POST['terms_checkbox']) ? 'checked' : ''; ?>>
                                <span class="checkbox-custom"></span>
                                <span>Tôi đồng ý với <a href="terms.php" target="_blank">Điều khoản và dịch vụ</a></span>
                            </label>
                        </div>
                        
                        <!-- Payment Button -->
                        <div class="payment-container" id="submit-payment-btn">
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
                            <div class="payment-right-side" >
                                <button type="button" name="submit_payment"  class="payment-new">Tiến hành thanh toán</button>
                                <input type="submit" name="submit_payment" id="hidden-submit" style="display: none;">
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
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing payment page...');
            
            const paymentForm = document.getElementById('payment-form');
            const paymentOptions = document.querySelectorAll('.payment-option');
            const selectedPaymentInput = document.getElementById('selected-payment-method');
            const submitBtn = document.getElementById('submit-payment-btn');

            // Xử lý chọn phương thức thanh toán
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const method = this.getAttribute('data-method');
                    console.log('Payment method selected:', method);
                    selectedPaymentInput.value = method;
                    
                    // Bỏ active tất cả options
                    paymentOptions.forEach(opt => opt.classList.remove('active'));
                    // Thêm active cho option được chọn
                    this.classList.add('active');
                });
            });

            // XỬ LÝ NÚT THANH TOÁN
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Payment button clicked!');
                    
                    if (validateFormBeforeSubmit()) {
                        const selectedMethod = selectedPaymentInput.value;
                        console.log('Selected payment method:', selectedMethod);
                        
                        // Lưu thông tin vào session trước khi chuyển hướng
                        const formData = new FormData();
                        formData.append('first_name', document.querySelector('input[name="first_name"]').value);
                        formData.append('last_name', document.querySelector('input[name="last_name"]').value);
                        formData.append('phone', document.querySelector('input[name="phone"]').value);
                        formData.append('email', document.querySelector('input[name="email"]').value);
                        formData.append('delivery_method', document.querySelector('.delivery-method-input').value);
                        formData.append('payment_method', selectedMethod);
                        formData.append('terms_checkbox', document.getElementById('terms_checkbox').checked ? '1' : '0');
                        formData.append('save_session_only', '1'); // Flag để chỉ lưu session
                        
                        if (selectedMethod === 'momo') {
                            showLoading();
                            showNotification('Đang chuyển đến trang thanh toán MoMo...', 'info');
                            
                            // Gửi dữ liệu để lưu vào session
                            fetch('payment.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(data => {
                                // Chuyển hướng đến MoMo
                                window.location.href = 'momo_payment.php';
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                hideLoading();
                                showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
                            });
                        } else {
                            showLoading();
                            showNotification('Đang xử lý thanh toán, vui lòng đợi...', 'info');
                            
                            // Submit form trực tiếp cho các phương thức thanh toán khác
                            document.getElementById('hidden-submit').click();
                        }
                    }
                });
            }
        });

        // Validation function
        function validateFormBeforeSubmit() {
            console.log('Starting validation...');
            const errors = [];
            
            // Kiểm tra thông tin khách hàng
            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            const phone = document.querySelector('input[name="phone"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const termsAccepted = document.getElementById('terms_checkbox').checked;
            
            if (!firstName) errors.push('Vui lòng nhập tên');
            if (!lastName) errors.push('Vui lòng nhập họ và tên lót');
            if (!phone) errors.push('Vui lòng nhập số điện thoại');
            if (!email) errors.push('Vui lòng nhập email');
            if (!termsAccepted) errors.push('Vui lòng đồng ý với điều khoản dịch vụ');
            
            // Kiểm tra email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                errors.push('Email không hợp lệ');
            }
            
            // Kiểm tra phương thức giao hàng
            const storeBtn = document.querySelector('.store-btn');
            const deliveryBtn = document.querySelector('.delivery-btn');
            const isStorePickup = storeBtn && storeBtn.classList.contains('active');
            
            if (isStorePickup) {
                const pickupDate = document.querySelector('input[name="pickup_date"]').value;
                const pickupTime = document.querySelector('select[name="pickup_time"]').value;
                const selectedStore = document.querySelector('input[name="store"]:checked');
                
                if (!pickupDate) errors.push('Vui lòng chọn ngày lấy hàng');
                if (!pickupTime) errors.push('Vui lòng chọn thời gian lấy hàng');
                if (!selectedStore) errors.push('Vui lòng chọn cửa hàng');
            } else {
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
            
            // Hiển thị lỗi nếu có
            if (errors.length > 0) {
                let errorMessage = 'Vui lòng kiểm tra lại thông tin:\n';
                errors.forEach((error, index) => {
                    errorMessage += `${index + 1}. ${error}\n`;
                });
                
                showNotification('Thông tin chưa đầy đủ!', 'error');
                alert(errorMessage);
                return false;
            }
            
            return true;
        }

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
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, type === 'info' ? 4000 : 3000);
            
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
    </script>

    <style>
        /* Ensure button is clickable */
        .payment-new {
            position: relative;
            z-index: 1000;
            pointer-events: auto !important;
        }
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
            pointer-events: auto !important;
            position: relative;
            z-index: 1000;
        }

        /* Store selection styles */
        .store-location {
            margin-top: 20px;
        }

        .store-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 15px;
        }

        .store-option {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            position: relative;
        }

        .store-option:hover {
            border-color: #26551D;
            background-color: rgba(38, 85, 29, 0.03);
            transform: translateY(-2px);
        }

        .store-option.selected {
            border-color: #26551D;
            background-color: rgba(38, 85, 29, 0.05);
        }

        .store-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .store-option .custom-radio {
            width: 20px;
            height: 20px;
            border: 2px solid #ccc;
            border-radius: 50%;
            margin-right: 15px;
            position: relative;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .store-option input[type="radio"]:checked + .custom-radio {
            border-color: #26551D;
            background: #26551D;
        }

        .store-option input[type="radio"]:checked + .custom-radio:after {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .store-info {
            flex: 1;
        }

        .store-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .store-address {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 3px;
        }

        .store-hours {
            font-size: 0.85em;
            color: #888;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .store-option {
                padding: 12px;
            }
            
            .store-name {
                font-size: 0.95em;
            }
            
            .store-address {
                font-size: 0.85em;
            }
            
            .store-hours {
                font-size: 0.8em;
            }
        }

        /* Delivery method tabs */
        .shipping-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .store-btn, .delivery-btn {
            padding: 12px 24px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 120px;
            position: relative;
            overflow: hidden;
        }

        .store-btn:hover, .delivery-btn:hover {
            border-color: #26551D;
            background-color: rgba(38, 85, 29, 0.03);
        }

        .store-btn.active, .delivery-btn.active {
            border-color: #26551D;
            background-color: #26551D;
            color: white;
        }

        /* Phone dropdown */
        .phone-country-code {
            position: relative;
            min-width: 100px;
        }

        .phone-dropdown-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .phone-dropdown-btn:hover {
            border-color: #26551D;
        }

        .country-dropdown {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            width: 200px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
        }

        .country-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .country-option:hover {
            background-color: rgba(38, 85, 29, 0.05);
        }

        .country-flag {
            width: 20px;
            height: 15px;
            object-fit: cover;
        }

        .country-name {
            font-size: 14px;
            color: #333;
        }

        .country-code {
            margin-left: auto;
            color: #666;
            font-size: 13px;
        }

        /* Delivery sections */
        .delivery-section {
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .delivery-section[style*="display: none"] {
            opacity: 0;
        }

        /* Smooth transitions */
        .store-pickup-container,
        .delivery-container {
            transition: all 0.3s ease;
        }
    </style>

    <script>
    // Add this to your existing JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Store selection handling
        const storeOptions = document.querySelectorAll('.store-option');
        
        storeOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                storeOptions.forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                this.classList.add('selected');
                // Check the radio input
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            });
        });
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing payment page...');
        
        // Delivery method switching
        const storeBtn = document.querySelector('.store-btn');
        const deliveryBtn = document.querySelector('.delivery-btn');
        const storeContainer = document.querySelector('.store-pickup-container');
        const deliveryContainer = document.querySelector('.delivery-container');
        
        if (storeBtn && deliveryBtn) {
            storeBtn.addEventListener('click', function() {
                console.log('Store button clicked');
                storeBtn.classList.add('active');
                deliveryBtn.classList.remove('active');
                storeContainer.style.display = 'block';
                deliveryContainer.style.display = 'none';
                document.querySelector('.delivery-method-input').value = 'store';
            });

            deliveryBtn.addEventListener('click', function() {
                console.log('Delivery button clicked');
                deliveryBtn.classList.add('active');
                storeBtn.classList.remove('active');
                deliveryContainer.style.display = 'block';
                storeContainer.style.display = 'none';
                document.querySelector('.delivery-method-input').value = 'delivery';
            });
        }

        // Phone country code dropdown
        const countrySelector = document.getElementById('country-selector');
        const countryDropdown = document.getElementById('country-dropdown');

        if (countrySelector && countryDropdown) {
            countrySelector.addEventListener('click', function(e) {
                e.stopPropagation();
                console.log('Country selector clicked');
                countryDropdown.style.display = countryDropdown.style.display === 'block' ? 'none' : 'block';
            });

            const countryOptions = document.querySelectorAll('.country-option');
            countryOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const code = this.getAttribute('data-code');
                    const flag = this.querySelector('.country-flag').src;
                    
                    // Update button content
                    countrySelector.querySelector('.country-flag').src = flag;
                    countrySelector.querySelector('span').textContent = code;
                    
                    // Hide dropdown
                    countryDropdown.style.display = 'none';
                });
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                if (countryDropdown) {
                    countryDropdown.style.display = 'none';
                }
            });
        }

        // Rest of your existing JavaScript code...
    });
    </script>
</body>
</html>
