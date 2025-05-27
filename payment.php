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

// Giả lập user_id
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Lấy thông tin giỏ hàng từ DATABASE
$cart_items = $cart->getItems($user_id);
$cart_total = $cart->getTotal($user_id);
$cart_count = $cart->getItemCount($user_id);
$shipping_fee = 0; // Miễn phí vận chuyển mặc định

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
    if ($delivery_method === 'delivery') {
        $delivery_date = $_POST['delivery_date'] ?? '';
        $delivery_time = $_POST['delivery_time'] ?? '';
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
        
        $shipping_fee = 25000; // Phí giao hàng
    } else {
        $pickup_date = $_POST['pickup_date'] ?? '';
        $pickup_time = $_POST['pickup_time'] ?? '';
        $selected_store = $_POST['store'] ?? '';
        
        if (empty($pickup_date)) $errors[] = 'Ngày lấy hàng không được để trống';
        if (empty($pickup_time)) $errors[] = 'Thời gian lấy hàng không được để trống';
        if (empty($selected_store)) $errors[] = 'Vui lòng chọn cửa hàng';
    }
    
    // Nếu không có lỗi, xử lý đơn hàng
    if (empty($errors)) {
        try {
            $order = new Order($db);
            
            $customer_info = [
                'name' => $first_name . ' ' . $last_name,
                'email' => $email,
                'phone' => $phone,
                'address' => $delivery_method === 'delivery' ? 
                    "$address, $district, $city, $zipcode" : 'Nhận tại cửa hàng'
            ];
            
            $total_amount = $cart_total + $shipping_fee;
            
            $order_id = $order->createOrder($user_id, $customer_info, $cart_items, $total_amount);
            
            // Clear cart after successful order
            $cart->clearCart($user_id);
            
            // Redirect to confirmation page
        header('Location: order_confirmation.php?order_id=' . $order_id);
        exit;
            
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
$final_total = $cart_total + $shipping_fee;
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
                        <form method="POST">
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
                                            <input type="text" name="pickup_time" class="form__input" placeholder="13h-16h" required 
                                                   value="<?php echo htmlspecialchars($_POST['pickup_time'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="store-location">
                                        <h4>Chọn cửa hàng</h4>
                                        <div class="store-options">
                                            <?php foreach ($stores as $index => $store): ?>
                                                <div class="store-option">
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
                                            <input type="text" name="delivery_time" class="form__input" placeholder="13h-16h" 
                                                   value="<?php echo htmlspecialchars($_POST['delivery_time'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div>
                                            <label>Thành phố/Tỉnh <span class="required">*Bắt buộc</span></label>
                                            <input type="text" name="city" class="form__input" 
                                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
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
        // Add form id to main form
        document.querySelector('.payment-form form').id = 'payment-form';
        
        document.addEventListener('DOMContentLoaded', function() {
            // Country dropdown functionality
            const countrySelector = document.getElementById('country-selector');
            const countryDropdown = document.getElementById('country-dropdown');
            const countryOptions = document.querySelectorAll('.country-option');
            
            if (countrySelector) {
                // Toggle dropdown
                countrySelector.addEventListener('click', function(e) {
                    e.stopPropagation();
                    countryDropdown.classList.toggle('show');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.phone-country-code')) {
                        countryDropdown.classList.remove('show');
                    }
                });
                
                // Select country option
                countryOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const code = this.getAttribute('data-code');
                        const flag = this.querySelector('.country-flag').src;
                        
                        const codeSpan = countrySelector.querySelector('span:first-of-type');
                        if (codeSpan) {
                            codeSpan.textContent = code;
                        }
                        const flagImg = countrySelector.querySelector('.country-flag');
                        if (flagImg) {
                            flagImg.src = flag;
                        }
                        
                        countryDropdown.classList.remove('show');
                    });
                });
            }
            
            // Xử lý chuyển đổi giữa phương thức Cửa hàng và Giao hàng
            const storeBtn = document.querySelector('.store-btn');
            const deliveryBtn = document.querySelector('.delivery-btn');
            const storeContainer = document.querySelector('.store-pickup-container');
            const deliveryContainer = document.querySelector('.delivery-container');
            const shippingFeeDisplay = document.getElementById('shipping-fee-display');
            const totalDisplay = document.getElementById('total-display');
            
            if (storeBtn && deliveryBtn) {
                storeBtn.addEventListener('click', function() {
                    storeBtn.classList.add('active');
                    deliveryBtn.classList.remove('active');
                    storeContainer.style.display = 'block';
                    deliveryContainer.style.display = 'none';
                    
                    // Update delivery method
                    storeContainer.querySelector('.delivery-method-input').disabled = false;
                    deliveryContainer.querySelector('.delivery-method-input').disabled = true;
                    
                    // Update shipping fee
                    updateShippingFee(0);
                });
                
                deliveryBtn.addEventListener('click', function() {
                    deliveryBtn.classList.add('active');
                    storeBtn.classList.remove('active');
                    deliveryContainer.style.display = 'block';
                    storeContainer.style.display = 'none';
                    
                    // Update delivery method
                    deliveryContainer.querySelector('.delivery-method-input').disabled = false;
                    storeContainer.querySelector('.delivery-method-input').disabled = true;
                    
                    // Update shipping fee
                    updateShippingFee(25000);
                });
            }
            
            // Xử lý phương thức thanh toán
            const paymentOptions = document.querySelectorAll('.payment-option');
            const paymentMethodInput = document.getElementById('selected-payment-method');
            
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    paymentOptions.forEach(o => o.classList.remove('active'));
                    this.classList.add('active');
                    
                    const method = this.getAttribute('data-method');
                    paymentMethodInput.value = method;
                });
            });
            
            // Make entire store option clickable
            const storeOptions = document.querySelectorAll('.store-option');
            
            storeOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    storeOptions.forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    this.classList.add('selected');
                    
                    if (e.target !== radio) {
                        e.preventDefault();
                    }
                });
            });
            
            // Initialize selected state for pre-checked radio
            storeOptions.forEach(option => {
                const radio = option.querySelector('input[type="radio"]');
                if (radio.checked) {
                    option.classList.add('selected');
                }
            });

            // Xử lý tăng giảm số lượng trực tiếp trên giao diện
            setupQuantityControls();
        });
        
        function setupQuantityControls() {
            // Xử lý các nút tăng giảm số lượng
            const minusBtns = document.querySelectorAll('.qty-btn[aria-label="Giảm"]');
            const plusBtns = document.querySelectorAll('.qty-btn[aria-label="Tăng"]');
            
            minusBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const cartItem = this.closest('.cart-item');
                    const qtyNumberEl = cartItem.querySelector('.qty-number');
                    const itemPriceEl = cartItem.querySelector('.item-price');
                    const unitPrice = parseInt(cartItem.getAttribute('data-price'));
                    const itemId = cartItem.getAttribute('data-id');
                    
                    let qty = parseInt(qtyNumberEl.textContent);
                    if (qty > 1) {
                        qty--;
                        qtyNumberEl.textContent = qty;
                        
                        // Cập nhật giá tiền của sản phẩm
                        const newPrice = qty * unitPrice;
                        itemPriceEl.textContent = formatPrice(newPrice) + ' đ';
                        
                        // Cập nhật tổng tiền
                        updateTotalPrice();
                        
                        // Gửi request cập nhật database
                        updateQuantityInDatabase(itemId, qty);
                    }
                });
            });
            
            plusBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const cartItem = this.closest('.cart-item');
                    const qtyNumberEl = cartItem.querySelector('.qty-number');
                    const itemPriceEl = cartItem.querySelector('.item-price');
                    const unitPrice = parseInt(cartItem.getAttribute('data-price'));
                    const itemId = cartItem.getAttribute('data-id');
                    
                    let qty = parseInt(qtyNumberEl.textContent);
                    qty++;
                    qtyNumberEl.textContent = qty;
                    
                    // Cập nhật giá tiền của sản phẩm
                    const newPrice = qty * unitPrice;
                    itemPriceEl.textContent = formatPrice(newPrice) + ' đ';
                    
                    // Cập nhật tổng tiền
                    updateTotalPrice();
                    
                    // Gửi request cập nhật database
                    updateQuantityInDatabase(itemId, qty);
                });
            });
            
            // Xử lý nút xóa sản phẩm
            const removeLinks = document.querySelectorAll('.remove-link');
            
            removeLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const cartItem = this.closest('.cart-item');
                    const itemId = cartItem.getAttribute('data-id');
                    
                    if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
                        // Xóa element khỏi giao diện ngay lập tức
                        cartItem.remove();
                        
                        // Cập nhật tổng tiền
                        updateTotalPrice();
                        
                        // Gửi request xóa khỏi database
                        removeItemFromDatabase(itemId);
                        
                        // Kiểm tra nếu giỏ hàng trống
                        checkEmptyCart();
                    }
                });
            });
        }
        
        function formatPrice(price) {
            return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        function updateTotalPrice() {
            let total = 0;
            const cartItems = document.querySelectorAll('.cart-item');
            
            cartItems.forEach(item => {
                const qty = parseInt(item.querySelector('.qty-number').textContent);
                const unitPrice = parseInt(item.getAttribute('data-price'));
                total += qty * unitPrice;
            });
            
            // Cập nhật tổng tiền với phí vận chuyển
            const shippingFee = getCurrentShippingFee();
            const finalTotal = total + shippingFee;
            
            document.getElementById('total-display').textContent = formatPrice(finalTotal) + ' vnd';
        }
        
        function getCurrentShippingFee() {
            const deliveryBtn = document.querySelector('.delivery-btn');
            return deliveryBtn && deliveryBtn.classList.contains('active') ? 25000 : 0;
        }
        
        function updateShippingFee(fee) {
            const shippingFeeDisplay = document.getElementById('shipping-fee-display');
            const totalDisplay = document.getElementById('total-display');
            
            shippingFeeDisplay.textContent = fee > 0 ? formatPrice(fee) + ' đ' : 'Miễn phí';
            
            // Cập nhật tổng tiền với phí vận chuyển mới
            updateTotalPrice();
        }
        
        function updateQuantityInDatabase(itemId, newQuantity) {
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity_direct&item_id=${itemId}&new_quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Lỗi cập nhật database:', data.message);
                    // Có thể hiển thị thông báo lỗi cho user nếu cần
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        function removeItemFromDatabase(itemId) {
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_item&item_id=${itemId}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Lỗi xóa sản phẩm:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        function checkEmptyCart() {
            const cartItems = document.querySelectorAll('.cart-item');
            if (cartItems.length === 0) {
                // Hiển thị thông báo giỏ hàng trống hoặc redirect
                const container = document.querySelector('.payment__container');
                container.innerHTML = `
                    <div class="empty-cart" style="text-align: center; padding: 80px 20px;">
                        <h2>Giỏ hàng của bạn đang trống</h2>
                        <p>Vui lòng thêm sản phẩm vào giỏ hàng trước khi thanh toán.</p>
                        <a href="shop.php" class="btn" style="display: inline-block; padding: 12px 24px; background: #ff6b35; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px;">Tiếp tục mua sắm</a>
                    </div>
                `;
            }
        }
        
        // Các function cũ để tương thích (deprecated)
        function updateQuantity(itemId, change) {
            console.warn('updateQuantity function is deprecated. Use direct quantity controls.');
        }
        
        function removeItem(itemId) {
            console.warn('removeItem function is deprecated. Use remove link click handler.');
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
            gap: 30px;
        }
        
        .store-option {
            border: 1.6px solid #ddd;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            transition: border-color 0.3s ease, background-color 0.2s ease;
            cursor: pointer;
            position: relative;
        }
        
        .store-option:hover {
            border-color: #26551D;
            background-color: rgba(38, 85, 29, 0.05);
        }
        
        .store-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .custom-radio {
            width: 18px;
            height: 18px;
            border: 1.6px solid #e0e0e0;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            margin-right: 15px;
            flex-shrink: 0;
        }
    </style>
</body>
</html> 