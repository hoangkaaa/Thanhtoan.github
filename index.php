<?php
// Khởi tạo session
session_start();

// Include các file cần thiết
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->connect();

// Khởi tạo class Product
$product = new Product($db);

// Dữ liệu mẫu cho sản phẩm (trong thực tế sẽ lấy từ database)
$products = [
    [
        'id' => 1,
        'name' => 'Hương Cam',
        'category' => 'Tropical',
        'price' => 35000,
        'original_price' => 55000,
        'rating' => 4.3,
        'stock_status' => 'in',
        'discount' => 'yes',
        'discount_percent' => 15,
        'image' => 'assets/img/td_p1_bg.png',
        'link' => 'details_HuongCam.html',
        'date' => '2024-06-15'
    ],
    [
        'id' => 2,
        'name' => 'Hương Socola',
        'category' => 'Trà Sữa',
        'price' => 30000,
        'original_price' => 50000,
        'rating' => 4.5,
        'stock_status' => 'in',
        'discount' => 'yes',
        'discount_percent' => 15,
        'image' => 'assets/img/mt_p1_bg.png',
        'link' => 'details_HuongCam.html',
        'date' => '2024-09-16'
    ],
    [
        'id' => 3,
        'name' => 'Vị Sài Gòn',
        'category' => 'Cà Phê',
        'price' => 35000,
        'original_price' => 55000,
        'rating' => 4.2,
        'stock_status' => 'in',
        'discount' => 'yes',
        'discount_percent' => 15,
        'image' => 'assets/img/cf_p1_bg.png',
        'link' => 'details_HuongCam.html',
        'date' => '2024-11-12'
    ],
    [
        'id' => 4,
        'name' => 'Hương Chanh',
        'category' => 'Trà Thảo Mộc',
        'price' => 25000,
        'original_price' => 35000,
        'rating' => 4.6,
        'stock_status' => 'in',
        'discount' => 'yes',
        'discount_percent' => 0,
        'image' => 'assets/img/ht_p1_bg.png',
        'link' => 'details_HuongCam.html',
        'date' => '2024-11-10'
    ],
    [
        'id' => 5,
        'name' => 'Hương Lựu Đỏ',
        'category' => 'Kombucha',
        'price' => 35000,
        'original_price' => 50000,
        'rating' => 4.4,
        'stock_status' => 'in',
        'discount' => 'yes',
        'discount_percent' => 15,
        'image' => 'assets/img/kom_p1_bg.png',
        'link' => 'details_HuongCam.html',
        'date' => '2024-11-11'
    ],
    [
        'id' => 6,
        'name' => 'Hương Chanh Dây',
        'category' => 'Mocktail',
        'price' => 23000,
        'original_price' => 30000,
        'rating' => 4.3,
        'stock_status' => 'almost',
        'discount' => 'yes',
        'discount_percent' => 22,
        'image' => 'assets/img/sm_p1_bg.png',
        'link' => 'details_HuongCam.html',
        'date' => '2024-06-15'
    ],
    [
        'id' => 7,
        'name' => 'Hương Dứa',
        'category' => 'Tropical',
        'price' => 35000,
        'original_price' => 0,
        'rating' => 4.2,
        'stock_status' => 'out',
        'discount' => 'yes',
        'discount_percent' => 10,
        'image' => 'assets/img/td_p2_bg.png',
        'link' => 'details_HuongDua.html',
        'date' => '2023-11-18'
    ],
    [
        'id' => 8,
        'name' => 'Hương Bạc Hà',
        'category' => 'Nước Tăng Lực',
        'price' => 25000,
        'original_price' => 0,
        'rating' => 4.3,
        'stock_status' => 'out',
        'discount' => 'no',
        'discount_percent' => 0,
        'image' => 'assets/img/ed_p1_bg.png',
        'link' => 'details_HuongDua.html',
        'date' => '2024-09-19'
    ],
    [
        'id' => 9,
        'name' => 'Hương Cam Quế',
        'category' => 'Trà Thảo Mộc',
        'price' => 35000,
        'original_price' => 0,
        'rating' => 4.5,
        'stock_status' => 'out',
        'discount' => 'yes',
        'discount_percent' => 12,
        'image' => 'assets/img/ht_p2_bg.png',
        'link' => 'details_HuongDua.html',
        'date' => '2024-11-15'
    ],
    [
        'id' => 10,
        'name' => 'Hương Việt Quất',
        'category' => 'Sữa',
        'price' => 35000,
        'original_price' => 55000,
        'rating' => 4.2,
        'stock_status' => 'in',
        'discount' => 'yes',
        'discount_percent' => 20,
        'image' => 'assets/img/mi_p2_bg.png',
        'link' => 'details_HuongDua.html',
        'date' => '2024-12-06'
    ],
    [
        'id' => 11,
        'name' => 'Hương Xoài',
        'category' => 'Trà Thảo Mộc',
        'price' => 35000,
        'original_price' => 55000,
        'rating' => 4.1,
        'stock_status' => 'in',
        'discount' => 'yes',
        'discount_percent' => 15,
        'image' => 'assets/img/ht_p3_bg.png',
        'link' => 'details_HuongDua.html',
        'date' => '2023-11-06'
    ],
    [
        'id' => 12,
        'name' => 'Hương Dưa Hấu',
        'category' => 'Tropical',
        'price' => 33000,
        'original_price' => 0,
        'rating' => 4.9,
        'stock_status' => 'in',
        'discount' => 'no',
        'discount_percent' => 0,
        'image' => 'assets/img/td_p3_bg.png',
        'link' => 'details_HuongDua.html',
        'date' => '2024-04-06'
    ]
];

// Xử lý filter và sort
$filtered_products = $products;

// Lấy tham số từ URL
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$discount_filter = isset($_GET['discount']) ? $_GET['discount'] : '';
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';
$price_filter = isset($_GET['price']) ? intval($_GET['price']) : 55;
$date_filter = isset($_GET['date']) ? intval($_GET['date']) : '';
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Apply filters
if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($search_query) {
        return stripos($product['name'], $search_query) !== false ||
               stripos($product['category'], $search_query) !== false;
    });
}

if (!empty($category_filter)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($category_filter) {
        return $product['category'] === $category_filter;
    });
}

if (!empty($discount_filter)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($discount_filter) {
        return $product['discount'] === $discount_filter;
    });
}

if (!empty($stock_filter)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($stock_filter) {
        return $product['stock_status'] === $stock_filter;
    });
}

if ($price_filter) {
    $filtered_products = array_filter($filtered_products, function($product) use ($price_filter) {
        return ($product['price'] / 1000) <= $price_filter;
    });
}

if (!empty($date_filter)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($date_filter) {
        $product_date = new DateTime($product['date']);
        $current_date = new DateTime();
        $interval = $current_date->diff($product_date);
        $months_diff = ($interval->y * 12) + $interval->m;
        return $months_diff <= $date_filter;
    });
}

// Apply sorting
if (!empty($sort_option)) {
    usort($filtered_products, function($a, $b) use ($sort_option) {
        switch ($sort_option) {
            case 'rating-asc':
                return $a['rating'] <=> $b['rating'];
            case 'rating-desc':
                return $b['rating'] <=> $a['rating'];
            case 'price-asc':
                return $a['price'] <=> $b['price'];
            case 'price-desc':
                return $b['price'] <=> $a['price'];
            case 'date-newest':
                return strtotime($b['date']) <=> strtotime($a['date']);
            case 'date-oldest':
                return strtotime($a['date']) <=> strtotime($b['date']);
            default:
                return 0;
        }
    });
}

// Pagination
$products_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_products = count($filtered_products);
$total_pages = ceil($total_products / $products_per_page);
$offset = ($current_page - 1) * $products_per_page;
$products_on_page = array_slice($filtered_products, $offset, $products_per_page);

// Đếm số lượng sản phẩm trong giỏ hàng
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}

// Hàm tạo URL với parameters
function buildUrl($params = []) {
    $current_params = $_GET;
    $merged_params = array_merge($current_params, $params);
    $merged_params = array_filter($merged_params); // Remove empty values
    return '?' . http_build_query($merged_params);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!--=============== FLATICON ===============-->
    <link rel="icon" href="./assets/img/logo_hinh_red.svg" type="image/png">
    <link
      rel="stylesheet"
      href="https://cdn-uicons.flaticon.com/2.0.0/uicons-regular-straight/css/uicons-regular-straight.css"
    />

    <!--=============== SWIPER CSS ===============-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
    />

    <!--=============== CSS ===============-->
    <link rel="stylesheet" href="./assets/css/style_shop.css" />
    <link rel="stylesheet" href="./assets/css/breakpoint.css" />

    <title>Sunkissed - Nước đóng lon</title>
  </head>
  <body>
   <!--=============== HEADER ===============-->
   <header class="header">
    <div class="header__top">
      <div class="header__container container">
        <div class="header__contact">
          <span>(+84)335993276</span>
          <span></span>
        </div>
        <p class="header__alert-news">
         Giảm 15% trên mọi đơn hàng từ 20-24/12
        </p>
        <a href="login-register.html" class="header__top-action">
         Đăng nhập/ Đăng kí
        </a>
      </div>
    </div>

<!-- Navigation -->
<nav class="nav container">
  <a href="index.php" class="nav__logo">
    <img class="nav__logo-img" src="assets/img/logo_red.svg" alt="website logo" />
  </a>
  <div class="nav__menu" id="nav-menu">
    <div class="nav__menu-top">
      <a href="index.php" class="nav__menu-logo">
        <img src="./assets/img/logo_red.svg" alt="">
      </a>
      <div class="nav__close" id="nav-close">
        <i class="fi fi-rs-cross-small"></i>
      </div>
    </div>
    <ul class="nav__list">
      <li class="nav__item">
        <a href="index.php" class="nav__link">Trang chủ</a>
      </li>
      <li class="nav__item">
        <a href="shop.php" class="nav__link active-link">Cửa hàng</a>
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

    <!-- Hộp tìm kiếm -->
    <div class="header__search">
      <form method="GET" action="shop.php">
        <input type="text" 
               name="search" 
               placeholder="Tìm kiếm sản phẩm" 
               class="form__input" 
               value="<?php echo htmlspecialchars($search_query); ?>" />
        <!-- Preserve other filters -->
        <?php if ($category_filter): ?>
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
        <?php endif; ?>
        <?php if ($discount_filter): ?>
          <input type="hidden" name="discount" value="<?php echo htmlspecialchars($discount_filter); ?>">
        <?php endif; ?>
        <?php if ($stock_filter): ?>
          <input type="hidden" name="stock" value="<?php echo htmlspecialchars($stock_filter); ?>">
        <?php endif; ?>
        <?php if ($price_filter != 55): ?>
          <input type="hidden" name="price" value="<?php echo $price_filter; ?>">
        <?php endif; ?>
        <?php if ($date_filter): ?>
          <input type="hidden" name="date" value="<?php echo $date_filter; ?>">
        <?php endif; ?>
        <?php if ($sort_option): ?>
          <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_option); ?>">
        <?php endif; ?>
      </form>
    </div>
  </div>

    <!-- Wishlist và Giỏ hàng -->
    <div class="header__user-actions">
      <a href="wishlist.php" class="header__action-btn" title="Wishlist">
        <img src="assets/img/icon-heart.svg" alt="" />
        <span class="count">0</span>
      </a>
      <a href="cart.php" class="header__action-btn" title="Cart">
        <img src="assets/img/icon-cart.svg" alt="" />
        <span class="count"><?php echo $cart_count; ?></span>
      </a>
      <div class="header__action-btn nav__toggle" id="nav-toggle">
        <img src="./assets//img/menu-burger.svg" alt="">
      </div>
    </div>
  </nav>
  </header>

    <!--=============== MAIN ===============-->
    <main class="main">
        <!-- Hiển thị thông báo hủy thanh toán -->
        <?php if (isset($_SESSION['payment_cancelled']) && isset($_SESSION['cancel_message'])): ?>
            <div id="payment-alert" class="alert alert-info container" style="background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 20px auto; border-radius: 5px; max-width: 800px; transition: opacity 0.5s ease-out;">
                <div style="display: flex; align-items: center;">
                    <i class="fi fi-rs-info" style="margin-right: 10px; font-size: 18px;"></i>
                    <span><?php echo htmlspecialchars($_SESSION['cancel_message']); ?></span>
                </div>
            </div>
            <script>
                // Tự động ẩn thông báo sau 4 giây
                setTimeout(function() {
                    const alert = document.getElementById('payment-alert');
                    if (alert) {
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.remove();
                        }, 500); // Đợi animation fade-out hoàn thành
                    }
                }, 4000); // 4 giây
            </script>
            <?php 
            unset($_SESSION['payment_cancelled']);
            unset($_SESSION['cancel_message']);
            ?>
        <?php endif; ?>

      <!--=============== ĐƯỜNG DẪN HIỆN TẠI ===============-->
      <section class="breadcrumb">
        <ul class="breadcrumb__list flex container">
          <li><a href="index.php" class="breadcrumb__link">Trang chủ</a></li>
          <li><span class="breadcrumb__link">></span></li>
          <li><span class="breadcrumb__link">Cửa hàng</span></li>
        </ul>
      </section>

      <!--=============== LỌC SẢN PHẨM ===============-->
      <div class="filter-toggle-container">
        <button id="toggle-filter-button" class="btn">Hiển thị bộ lọc</button>
      </div>
      <section class="filter container" id="filter-section">
        <div class="filter__content">
        <form id="filter-form" method="GET" action="shop.php">
          <!-- Preserve search query -->
          <?php if ($search_query): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
          <?php endif; ?>
          
          <!-- Loại sản phẩm -->
          <div class="filter__group">
            <label for="categories">Loại sản phẩm:</label>
            <select id="categories" name="category">
              <option value="">Tất cả</option>
              <option value="Tropical" <?php echo $category_filter === 'Tropical' ? 'selected' : ''; ?>>Tropical</option>
              <option value="Trà Sữa" <?php echo $category_filter === 'Trà Sữa' ? 'selected' : ''; ?>>Trà Sữa</option>
              <option value="Cà Phê" <?php echo $category_filter === 'Cà Phê' ? 'selected' : ''; ?>>Cà Phê</option>
              <option value="Trà Thảo Mộc" <?php echo $category_filter === 'Trà Thảo Mộc' ? 'selected' : ''; ?>>Trà Thảo Mộc</option>
              <option value="Kombucha" <?php echo $category_filter === 'Kombucha' ? 'selected' : ''; ?>>Kombucha</option>
              <option value="Mocktail" <?php echo $category_filter === 'Mocktail' ? 'selected' : ''; ?>>Mocktail</option>
              <option value="Nước Tăng Lực" <?php echo $category_filter === 'Nước Tăng Lực' ? 'selected' : ''; ?>>Nước Tăng Lực</option>
              <option value="Sữa" <?php echo $category_filter === 'Sữa' ? 'selected' : ''; ?>>Sữa</option>
            </select>
          </div>
      
          <!-- Giảm giá -->
          <div class="filter__group">
            <label for="discount">Giảm giá:</label>
            <select id="discount" name="discount">
              <option value="">Tất cả</option>
              <option value="yes" <?php echo $discount_filter === 'yes' ? 'selected' : ''; ?>>Đang giảm giá</option>
              <option value="no" <?php echo $discount_filter === 'no' ? 'selected' : ''; ?>>Không giảm giá</option>
            </select>
          </div>
      
          <!-- Trạng thái -->
          <div class="filter__group">
            <label for="stock">Trạng thái:</label>
            <select id="stock" name="stock">
              <option value="">Tất cả</option>
              <option value="in" <?php echo $stock_filter === 'in' ? 'selected' : ''; ?>>Còn hàng</option>
              <option value="almost" <?php echo $stock_filter === 'almost' ? 'selected' : ''; ?>>Sắp hết</option>
            </select>
          </div>
      
          <!-- Khoảng giá -->
          <div class="filter__group">
            <label for="price">Khoảng giá:</label>
            <input type="range" 
                   id="price" 
                   name="price" 
                   min="23" 
                   max="55" 
                   step="2" 
                   value="<?php echo $price_filter; ?>" />
            <span id="price-value"><?php echo $price_filter; ?>.000 vnđ</span>
          </div>
      
          <!-- Ngày sản xuất -->
          <div class="filter__group">
            <label for="date">Ngày sản xuất:</label>
            <select id="date" name="date">
              <option value="">Tất cả</option>
              <option value="1" <?php echo $date_filter === 1 ? 'selected' : ''; ?>>1 tháng gần đây</option>
              <option value="3" <?php echo $date_filter === 3 ? 'selected' : ''; ?>>3 tháng gần đây</option>
              <option value="6" <?php echo $date_filter === 6 ? 'selected' : ''; ?>>6 tháng gần đây</option>
              <option value="12" <?php echo $date_filter === 12 ? 'selected' : ''; ?>>1 năm gần đây</option>
            </select>
          </div>
      
          <!-- Nút lọc -->
          <div class="filter__actions">
            <button type="submit">Lọc</button>
            <a href="shop.php" class="btn">Đặt lại</a>
          </div>
        </form>
      </div>
      </section>

      <!--=============== SẢN PHẨM ===============-->
      <section class="products container section--lg">
        <!--=============== SẮP XẾP SẢN PHẨM ===============-->
      <div class="sort">
        <label for="sort-options">Sắp xếp theo:</label>
        <select id="sort-options" name="sort-options" onchange="applySorting(this.value)">
          <option value="">Chọn tiêu chí</option>
          <option value="rating-asc" <?php echo $sort_option === 'rating-asc' ? 'selected' : ''; ?>>Đánh giá (Thấp → Cao)</option>
          <option value="rating-desc" <?php echo $sort_option === 'rating-desc' ? 'selected' : ''; ?>>Đánh giá (Cao → Thấp)</option>
          <option value="price-asc" <?php echo $sort_option === 'price-asc' ? 'selected' : ''; ?>>Giá (Thấp → Cao)</option>
          <option value="price-desc" <?php echo $sort_option === 'price-desc' ? 'selected' : ''; ?>>Giá (Cao → Thấp)</option>
          <option value="date-newest" <?php echo $sort_option === 'date-newest' ? 'selected' : ''; ?>>Ngày sản xuất (Mới nhất)</option>
          <option value="date-oldest" <?php echo $sort_option === 'date-oldest' ? 'selected' : ''; ?>>Ngày sản xuất (Cũ nhất)</option>
        </select>
      </div>
        
        <!--TỔNG SẢN PHẨM TÌM ĐƯỢC-->
        <p class="total__products">Đã tìm thấy <span><?php echo $total_products; ?></span> sản phẩm phù hợp với bạn!</p>
        
        <!--LƯỚI SẢN PHẨM-->  
        <div class="products__container grid">
          <?php if (empty($products_on_page)): ?>
            <div class="no-products">
              <p>Không tìm thấy sản phẩm nào phù hợp với tiêu chí lọc của bạn.</p>
              <a href="shop.php" class="btn">Xem tất cả sản phẩm</a>
            </div>
          <?php else: ?>
            <?php foreach ($products_on_page as $product): ?>
              <div class="product__item" 
                   data-stock="<?php echo $product['stock_status']; ?>" 
                   data-categories="<?php echo htmlspecialchars($product['category']); ?>" 
                   data-price="<?php echo $product['price'] / 1000; ?>" 
                   data-rating="<?php echo $product['rating']; ?>" 
                   data-discount="<?php echo $product['discount']; ?>" 
                   data-date="<?php echo $product['date']; ?>">
                <!--ẢNH BANNER SẢN PHẨM-->
                <div class="product__banner">
                  <a href="<?php echo $product['link']; ?>" class="product__images">
                    <img
                      src="<?php echo $product['image']; ?>"
                      alt="<?php echo htmlspecialchars($product['name']); ?>"
                      class="product__img default"
                    />
                    <!--ẢNH BANNER SẢN PHẨM (SAU KHI HOVER)-->
                    <img
                      src="<?php echo $product['image']; ?>"
                      alt="<?php echo htmlspecialchars($product['name']); ?>"
                      class="product__img hover" 
                    />
                  </a>
                  <!--CÁC HÀNH ĐỘNG VỚI SẢN PHẨM-->
                  <div class="product__actions">
                    <a href="#" class="action__btn" aria-label="Xem Nhanh" onclick="quickView(<?php echo $product['id']; ?>)">
                      <i class="fi fi-rs-eye"></i>
                    </a>
                    <a href="#" class="action__btn" aria-label="Thêm vào Wishlist" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                      <i class="fi fi-rs-heart"></i>
                    </a>
                  </div>
                  
                  <?php if ($product['stock_status'] === 'out'): ?>
                    <div class="product__badge out-of-stock">Hết hàng</div>
                  <?php elseif ($product['discount'] === 'yes' && $product['discount_percent'] > 0): ?>
                    <div class="product__badge <?php 
                      if ($product['discount_percent'] >= 20) echo 'light-blue';
                      elseif ($product['discount_percent'] >= 15) echo '';
                      elseif ($product['discount_percent'] >= 10) echo 'light-green';
                      else echo 'light-orange';
                    ?>">
                      -<?php echo $product['discount_percent']; ?>%
                    </div>
                  <?php endif; ?>
                </div>
                
                <div class="product__content">
                  <span class="product__category"><?php echo htmlspecialchars($product['category']); ?></span>
                  <a href="<?php echo $product['link']; ?>">
                    <h3 class="product__title"><?php echo htmlspecialchars($product['name']); ?></h3>
                  </a>
                  <div class="product__rating">
                    <?php 
                    $rating = $product['rating'];
                    $full_stars = floor($rating);
                    $half_star = ($rating - $full_stars) >= 0.5;
                    
                    for ($i = 1; $i <= 5; $i++): 
                      if ($i <= $full_stars): ?>
                        <i class="fi fi-rs-star"></i>
                      <?php elseif ($i == $full_stars + 1 && $half_star): ?>
                        <i class="fi fi-rs-star-half"></i>
                      <?php else: ?>
                        <i class="fi fi-rs-star" style="opacity: 0.3;"></i>
                      <?php endif;
                    endfor; ?>
                    <span id="product-rating-text"><?php echo $rating; ?></span>
                  </div>
                  
                  <div class="product__price flex">
                    <?php if ($product['stock_status'] === 'out'): ?>
                      <span class="new__price">Hết hàng</span>
                    <?php else: ?>
                      <span class="new__price"><?php echo number_format($product['price']); ?> vnđ</span>
                      <?php if ($product['original_price'] > 0 && $product['original_price'] > $product['price']): ?>
                        <span class="old__price"><?php echo number_format($product['original_price']); ?> vnđ</span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                  
                  <?php if ($product['stock_status'] !== 'out'): ?>
                    <a href="#" 
                       class="action__btn cart__btn"
                       aria-label="Thêm vào giỏ hàng"
                       onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo $product['image']; ?>', '<?php echo htmlspecialchars($product['category']); ?>')">
                      <i class="fi fi-rs-shopping-bag-add"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        
        <!--Danh sách các trang và nút chuyển trang-->
        <?php if ($total_pages > 1): ?>
          <ul class="pagination">
            <?php if ($current_page > 1): ?>
              <li><a href="<?php echo buildUrl(['page' => $current_page - 1]); ?>" class="pagination__link">‹</a></li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1): ?>
              <li><a href="<?php echo buildUrl(['page' => 1]); ?>" class="pagination__link">01</a></li>
              <?php if ($start_page > 2): ?>
                <li><span class="pagination__link">...</span></li>
              <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
              <li><a href="<?php echo buildUrl(['page' => $i]); ?>" 
                     class="pagination__link <?php echo $i === $current_page ? 'active' : ''; ?>">
                     <?php echo sprintf('%02d', $i); ?>
                  </a></li>
            <?php endfor; ?>
            
            <?php if ($end_page < $total_pages): ?>
              <?php if ($end_page < $total_pages - 1): ?>
                <li><span class="pagination__link">...</span></li>
              <?php endif; ?>
              <li><a href="<?php echo buildUrl(['page' => $total_pages]); ?>" class="pagination__link"><?php echo sprintf('%02d', $total_pages); ?></a></li>
            <?php endif; ?>
            
            <?php if ($current_page < $total_pages): ?>
              <li><a href="<?php echo buildUrl(['page' => $current_page + 1]); ?>" class="pagination__link">›</a></li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </section>
    </main>

    <!-- =============== FOOTER ===============-->
  <footer class="footer container">
    <div class="footer__container grid">
      <div class="footer__content">
        <a href="index.php" class="footer__logo">
          <img src="./assets/img/logo_red.svg" alt="" class="footer__logo-img" />
        </a>
        <h4 class="footer__subtitle">LIÊN HỆ</h4>
        <p class="footer__description">
          <span>Địa chỉ:</span> 279 Nguyễn Tri Phương, Quận 10, Tp. Hồ Chí Minh
        </p>
        <p class="footer__description">
          <span>Điện thoại:</span> +84 0335993276
        </p>
        <p class="footer__description">
          <span>Giờ hoạt động:</span> 8:00 - 21:00, Thứ 2 - Thứ 7
        </p>
        <div class="footer__social">
          <h4 class="footer__subtitle">Follow ngay</h4>
          <div class="footer__links flex">
            <a href="#">
              <img src="./assets/img/icon-facebook.svg" alt="" class="footer__social-icon" />
            </a>
            <a href="#">
              <img src="./assets/img/icon-twitter.svg" alt="" class="footer__social-icon" />
            </a>
            <a href="#">
              <img src="./assets/img/icon-instagram.svg" alt="" class="footer__social-icon" />
            </a>
            <a href="#">
              <img src="./assets/img/icon-pinterest.svg" alt="" class="footer__social-icon" />
            </a>
            <a href="#">
              <img src="./assets/img/icon-youtube.svg" alt="" class="footer__social-icon" />
            </a>
          </div>
        </div>
      </div>
      <div class="footer__content">
        <h3 class="footer__title">Thông Tin</h3>
        <ul class="footer__links">
          <li><a href="#" class="footer__link">Về chúng tôi</a></li>
          <li><a href="#" class="footer__link">Thông tin giao hàng</a></li>
          <li><a href="#" class="footer__link">Chính sách </a></li>
          <li><a href="#" class="footer__link">Điều khoản & Điều kiện</a></li>
          <li><a href="#" class="footer__link">Liên hệ chúng tôi</a></li>
          <li><a href="#" class="footer__link">Trung tâm hỗ trợ</a></li>
        </ul>
      </div>
      <div class="footer__content">
        <h3 class="footer__title">Tài khoản</h3>
        <ul class="footer__links">
          <li><a href="#" class="footer__link">Đăng nhập</a></li>
          <li><a href="cart.php" class="footer__link">Xem giỏ hàng</a></li>
          <li><a href="#" class="footer__link">Wishlist</a></li>
          <li><a href="#" class="footer__link">Theo dõi đơn hàng</a></li>
          <li><a href="#" class="footer__link">Trợ giúp</a></li>
          <li><a href="#" class="footer__link">Đặt hàng</a></li>
        </ul>
      </div>
      <div class="footer__content">
        <h3 class="footer__title">Cổng thanh toán bảo mật</h3>
        <img src="./assets/img/thanhtoan.svg" alt="" class="payment__img" />
      </div>
    </div>
    <div class="footer__bottom">
      <p class="copyright">&copy; <?php echo date('Y'); ?> Sunkissed. All right reserved</p>
      <span class="designer">Thiết kế bởi Nhóm 5 Coop.</span>
    </div>
  </footer> 

    <!--=============== SWIPER JS ===============-->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!--=============== MAIN JS ===============-->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/ct_main.js"></script>

    <!-- ======== JAVASCRIPT CHO PHP FUNCTIONS =========== -->
    <script>
      // Hàm áp dụng sắp xếp
      function applySorting(sortValue) {
        const url = new URL(window.location);
        if (sortValue) {
          url.searchParams.set('sort', sortValue);
        } else {
          url.searchParams.delete('sort');
        }
        url.searchParams.delete('page'); // Reset về trang 1 khi sort
        window.location.href = url.toString();
      }

      // Hàm thêm vào giỏ hàng
      function addToCart(productId, productName, price, image, category) {
        fetch('cart_actions.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=add_to_cart&product_id=${productId}&name=${encodeURIComponent(productName)}&price=${price}&image=${encodeURIComponent(image)}&category=${encodeURIComponent(category)}&variant=500ml&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Đã thêm sản phẩm vào giỏ hàng!');
            // Cập nhật số lượng giỏ hàng trong header
            updateCartCount();
          } else {
            alert('Có lỗi xảy ra: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng');
        });
      }

      // Hàm thêm vào wishlist
      function addToWishlist(productId) {
        // Implement wishlist functionality
        alert('Đã thêm vào wishlist! (Chức năng đang phát triển)');
      }

      // Hàm xem nhanh sản phẩm
      function quickView(productId) {
        // Implement quick view functionality
        alert('Xem nhanh sản phẩm ID: ' + productId + ' (Chức năng đang phát triển)');
      }

      // Hàm cập nhật số lượng giỏ hàng
      function updateCartCount() {
        fetch('cart_actions.php?action=get_cart_count')
        .then(response => response.json())
        .then(data => {
          const cartCountElement = document.querySelector('.header__action-btn[title="Cart"] .count');
          if (cartCountElement) {
            cartCountElement.textContent = data.count || 0;
          }
        })
        .catch(error => console.error('Error updating cart count:', error));
      }

      // Cập nhật giá trị hiển thị của thanh trượt
      document.getElementById("price").addEventListener("input", function (event) {
        document.getElementById("price-value").textContent = `${event.target.value}.000 vnđ`;
      });

      // Nút hiện thị/đóng bộ lọc
      document.getElementById("toggle-filter-button").addEventListener("click", function () {
        const filterSection = document.getElementById("filter-section");
        const filterContent = filterSection.querySelector(".filter__content");
      
        const isOpen = filterSection.classList.contains("open");
      
        if (isOpen) {
          filterSection.style.height = `${filterContent.scrollHeight}px`;
          setTimeout(() => {
            filterSection.style.height = "0";
            setTimeout(() => {
              filterSection.style.visibility = "hidden";
            }, 300);
          }, 10);
        } else {
          filterSection.style.visibility = "visible";
          filterSection.style.height = "0";
          setTimeout(() => {
            filterSection.style.height = `${filterContent.scrollHeight}px`;
          }, 10);
        }
      
        filterSection.classList.toggle("open");
        this.textContent = isOpen ? "Hiển thị bộ lọc" : "Ẩn bộ lọc";
      
        if (!isOpen) {
          setTimeout(() => {
            filterSection.style.height = "auto";
          }, 300);
        }
      });
    </script>
</body>
</html> 