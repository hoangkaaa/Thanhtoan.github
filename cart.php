<?php
// Khởi tạo session để quản lý giỏ hàng
session_start();

// Dữ liệu mẫu cho giỏ hàng (trong thực tế sẽ lấy từ database)
$cart_items = [
    [
        'id' => 1,
        'name' => 'Hương Dứa',
        'category' => 'Tropical Drinks',
        'variant' => '500ml',
        'price' => 25000,
        'original_price' => 30000,
        'quantity' => 1,
        'image' => 'assets/img/td_p2_bg_large.png',
        'link' => 'details_HuongDua.html'
    ],
    [
        'id' => 2,
        'name' => 'Hương Chuối',
        'category' => 'Sữa',
        'variant' => '500ml',
        'price' => 35000,
        'original_price' => 35000,
        'quantity' => 1,
        'image' => 'assets/img/mi_p3_bg.png',
        'link' => 'category-link.html'
    ],
    [
        'id' => 3,
        'name' => 'Hương Cam',
        'category' => 'Tropical Drinks',
        'variant' => '330ml',
        'price' => 35000,
        'original_price' => 50000,
        'quantity' => 1,
        'image' => 'assets/img/td_p1_bg.png',
        'link' => 'details_HuongCam.html'
    ]
];

// Tính tổng giỏ hàng
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping = 21000;
$total = $subtotal + $shipping;

// Xử lý các action từ form (update, remove items, etc.)
if ($_POST) {
    // Xử lý cập nhật số lượng, xóa sản phẩm, etc.
    // Code xử lý sẽ được thêm vào đây
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
  <link rel="stylesheet" href="assets/css/style_cart.css" />
  <link rel="stylesheet" href="assets/css/breakpoint.css" />

  <title>Sunkissed | Nước uống dinh dưỡng</title>
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

    <nav class="nav container">
      <a href="index.html" class="nav__logo">
        <img class="nav__logo-img" src="assets/img/logo_red.svg" alt="website logo" />
      </a>
      <div class="nav__menu" id="nav-menu">
        <div class="nav__menu-top">
          <a href="index.html" class="nav__menu-logo">
            <img src="./assets/img/logo_red.svg" alt="">
          </a>
          <div class="nav__close" id="nav-close">
            <i class="fi fi-rs-cross-small"></i>
          </div>
        </div>
        <ul class="nav__list">
          <li class="nav__item">
            <a href="index.html" class="nav__link ">Trang chủ</a>
          </li>
          <li class="nav__item">
            <a href="shop.html" class="nav__link">Cửa hàng</a>
          </li>
          <li class="nav__item">
            <a href="aboutus.html" class="nav__link">Về chúng tôi</a>
          </li>
          
          <li class="nav__item">
            <a href="lienhe.html" class="nav__link" >Liên hệ</a>
          </li>
          <li class="nav__item">
            <a href="accounts.html" class="nav__link">Tài khoản</a>
          </li>
        </ul>
  
        <!-- Hộp tìm kiếm -->
        <div class="header__search">
          <input type="text" placeholder="Tìm kiếm sản phẩm" class="form__input" />
        </div>
      </div>
  
        <!-- Wishlist và Giỏ hàng -->
        <div class="header__user-actions">
          <a href="wishlist.html" class="header__action-btn" title="Wishlist">
            <img src="assets/img/icon-heart.svg" alt="" />
            <span class="count">3</span>
          </a>
          <a href="cart.php" class="header__action-btn" title="Cart">
            <img src="assets/img/icon-cart.svg" alt="" />
            <span class="count"><?php echo count($cart_items); ?></span>
          </a>
          <div class="header__action-btn nav__toggle" id="nav-toggle">
            <img src="./assets//img/menu-burger.svg" alt="">
          </div>
        </div>
      </nav>
  </header>

  <!--=============== MAIN ===============-->
  <main class="main">
    <!--=============== BREADCRUMB ===============-->
    <section class="breadcrumb">
      <ul class="breadcrumb__list flex container">
        <li><a href="index.html" class="breadcrumb__link">Home</a></li>
        <li><span class="breadcrumb__link">></span></li>
        <li><span class="breadcrumb__link">Giỏ hàng</span></li>
      </ul>
    </section>
    
    <!--=============== GIỎ HÀNG ===============-->
    <div class="section__content giohang">
      <!-- Container -->
      <div class="container">
        <div class="giohang_title">GIỎ HÀNG CỦA BẠN</div>
        
        <?php if (empty($cart_items)): ?>
          <div class="empty-cart">
            <p>Giỏ hàng của bạn đang trống</p>
            <a href="shop.html" class="btn">Tiếp tục mua sắm</a>
          </div>
        <?php else: ?>
          <!-- Danh sách sản phẩm trong giỏ hàng -->
          <?php foreach ($cart_items as $item): ?>
            <div class="product-list" data-product-id="<?php echo $item['id']; ?>">
              <div class="w-r__container">
                <div class="w-r__img-wrap">
                  <a href="<?php echo $item['link']; ?>">
                    <img src="<?php echo $item['image']; ?>" alt="Product Image">
                  </a>
                </div>
                <div class="w-r__info">
                  <div class="w-r__name"><?php echo htmlspecialchars($item['name']); ?></div>
                  <div class="w-r__details">
                    <div class="w-r__category"><?php echo htmlspecialchars($item['category']); ?></div>
                    <div class="w-r__variant"><?php echo htmlspecialchars($item['variant']); ?></div>
                  </div>
                  <div class="w-r__price-wrapper">
                    <div class="w-r__price"><?php echo number_format($item['price']); ?>₫</div>
                    <?php if ($item['original_price'] > $item['price']): ?>
                      <div class="w-r__discount"><?php echo number_format($item['original_price']); ?>₫</div>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="w-r__wrap-2">
                  <!-- Điều chỉnh số lượng sản phẩm -->
                  <div class="quantity-control">
                    <input id="product-quantity-<?php echo $item['id']; ?>" 
                           aria-valuenow="<?php echo $item['quantity']; ?>" 
                           type="number" 
                           aria-label="Item Quantity" 
                           name="quantity[<?php echo $item['id']; ?>]"
                           value="<?php echo $item['quantity']; ?>" 
                           min="1" 
                           max="99" 
                           data-quantity-input 
                           class="quantity-input"
                           onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                  </div>
                  <!-- Các nút hành động cho sản phẩm -->
                  <a href="<?php echo $item['link']; ?>" class="w-r__link btn--e-transparent-platinum-b-2">Xem Sản Phẩm</a>
                  <button class="w-r__link btn--e-transparent-platinum-b-2" onclick="removeItem(<?php echo $item['id']; ?>)">Xóa Sản Phẩm</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Các hành động của giỏ hàng -->
          <div class="cart__actions">
            <button onclick="updateCart()" class="btn flex btn__md">
              <i class="fi-rs-shuffle"></i> Cập nhật giỏ hàng
            </button>
            <a href="shop.html" class="btn flex btn__md">
              <i class="fi-rs-shopping-bag"></i> Tiếp tục mua sắm
            </a>
          </div>

          <!-- Phân cách giữa các phần -->
          <div class="divider">
            <i class="fi fi-rs-fingerprint"></i>
          </div>

          <!-- Thông tin chi tiết về phí vận chuyển, mã giảm giá và tổng đơn hàng-->
          <div class="cart__group grid">
            <div>
              <!-- Phí vận chuyển -->
              <div class="cart__shippinp">
                <h3 class="section__title">Phí vận chuyển</h3>
                <form action="" method="POST" class="form grid">
                  <input type="text" name="city" class="form__input" placeholder="Tỉnh/Thành phố" />
                  <div class="form__group grid">
                    <input type="text" name="district" class="form__input" placeholder="Quận/Huyện" />
                    <input type="text" name="postal_code" class="form__input" placeholder="Mã bưu điện" />
                  </div>
                  <div class="form__btn">
                    <button type="submit" name="update_shipping" class="btn flex btn--sm">
                      <i class="fi-rs-shuffle"></i> Cập nhật
                    </button>
                  </div>
                </form>
              </div>

              <!-- Mã giảm giá -->
              <div class="cart__coupon">
                <h3 class="section__title">Mã giảm giá</h3>
                <form action="" method="POST" class="coupon__form form grid">
                  <div class="form__group grid">
                    <input type="text" name="coupon_code" class="form__input" placeholder="Nhập mã giảm giá" />
                    <div class="form__btn">
                      <button type="submit" name="apply_coupon" class="btn flex btn--sm">
                        <i class="fi-rs-label"></i> Áp dụng
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <!-- Tổng cộng đơn hàng -->
            <div class="cart__total">
              <h3 class="section__title">Tổng cộng</h3>
              <table class="cart__total-table">
                <tr>
                  <td><span class="cart__total-title">Tạm tính giỏ hàng</span></td>
                  <td><span class="cart__total-price"><?php echo number_format($subtotal); ?>₫</span></td>
                </tr>
                <tr>
                  <td><span class="cart__total-title">Vận chuyển</span></td>
                  <td><span class="cart__total-price"><?php echo number_format($shipping); ?>₫</span></td>
                </tr>
                <tr>
                  <td><span class="cart__total-title">Tổng</span></td>
                  <td><span class="cart__total-price"><?php echo number_format($total); ?>₫</span></td>
                </tr>
              </table>
              <!-- Nút thanh toán -->
              <a href="payment.html" class="btn flex btn--md">
                <i class="fi fi-rs-box-alt"></i> Tiến hành thanh toán
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!--=============== FOOTER ===============-->
  <footer class="footer ">
    <div class="footer__container grid">
      <div class="footer__content">
        <a href="index.html" class="footer__logo">
          <img src="./assets/img/logo_red.svg" alt="" class="footer__logo-img" />
        </a>
        <h4 class="footer__subtitle">Liên hệ</h4>
        <p class="footer__description">
          <span>Địa chỉ:</span> 279 Nguyễn Tri Phương, Quận 10, Tp. Hồ Chí Minh
        </p>
        <p class="footer__description">
          <span>Điện thoại:</span> +84 335993276
        </p>
        <p class="footer__description">
          <span>Giờ làm việc:</span> 8:00 - 21:00, Thứ 2 - Thứ 7
        </p>
        <div class="footer__social">
          <h4 class="footer__subtitle">Follow ngay</h4>
          <div class="footer__links flex">
            <a href="#">
              <img
                src="./assets/img/icon-facebook.svg"
                alt=""
                class="footer__social-icon"
              />
            </a>
            <a href="#">
              <img
                src="./assets/img/icon-instagram.svg"
                alt=""
                class="footer__social-icon"
              />
            </a>
            <a href="#">
              <img
                src="./assets/img/icon-youtube.svg"
                alt=""
                class="footer__social-icon"
              />
            </a>
          </div>
        </div>
      </div>
      <div class="footer__content">
        <h3 class="footer__title">Thông tin</h3>
        <ul class="footer__links">
          <li><a href="#" class="footer__link">Về chúng tôi</a></li>
          <li><a href="#" class="footer__link">Thông tin giao hàng</a></li>
          <li><a href="#" class="footer__link">Chính sách</a></li>
          <li><a href="#" class="footer__link">Điều khoản & Điều Kiện </a></li>
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
          <li><a href="#" class="footer__link">Đơn hàng</a></li>
        </ul>
      </div>
      <div class="footer__content">
        <h3 class="footer__title">Cổng thanh toán bảo mật</h3>
        <img
          src="./assets/img/thanhtoan.svg"
          alt=""
          class="payment__img"
        />
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
  <script src="assets/js/nhat_wishlist.js"></script>
  <script src="assets/js/ct_main.js"></script>
  
  <!-- JavaScript cho các chức năng PHP -->
  <script>
    // Hàm cập nhật số lượng sản phẩm
    function updateQuantity(productId, quantity) {
        // Gửi AJAX request để cập nhật số lượng
        fetch('cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_quantity&product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload trang để cập nhật tổng tiền
            }
        });
    }

    // Hàm xóa sản phẩm khỏi giỏ hàng
    function removeItem(productId) {
        if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_item&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    }

    // Hàm cập nhật toàn bộ giỏ hàng
    function updateCart() {
        const quantities = {};
        document.querySelectorAll('.quantity-input').forEach(input => {
            const productId = input.name.match(/\d+/)[0];
            quantities[productId] = input.value;
        });

        fetch('cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_cart&quantities=${JSON.stringify(quantities)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
  </script>
</body>

</html> 