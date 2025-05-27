<?php
// Khởi tạo session để quản lý giỏ hàng
session_start();

// Include database
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->connect();

// Khởi tạo Cart class
$cart = new Cart($db);

// Giả lập user_id (trong thực tế lấy từ session đăng nhập)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Lấy sản phẩm từ database
$cart_items = $cart->getItems($user_id);

// Tính tổng giỏ hàng
$subtotal = $cart->getTotal($user_id);
$shipping = 21000;
$total = $subtotal + $shipping;

// Helper function để định dạng giá tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// Helper function để lấy giá tiền dạng số
function getNumericPrice($price) {
    return intval(str_replace(['₫', ',', '.', ' '], '', $price));
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
  
        <!-- Hộp tìm kiếm -->
        <div class="header__search">
          <input type="text" placeholder="Tìm kiếm sản phẩm" class="form__input" />
        </div>
      </div>
  
        <!-- Wishlist và Giỏ hàng -->
        <div class="header__user-actions">
          <a href="wishlist.php" class="header__action-btn" title="Wishlist">
            <img src="assets/img/icon-heart.svg" alt="" />
            <span class="count">3</span>
          </a>
          <a href="cart.php" class="header__action-btn" title="Cart">
            <img src="assets/img/icon-cart.svg" alt="" />
            <span class="count" id="cart-count"><?php echo $cart->getItemCount($user_id); ?></span>
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
        <li><a href="index.php" class="breadcrumb__link">Trang chủ</a></li>
        <li><span class="breadcrumb__link">></span></li>
        <li><span class="breadcrumb__link">Giỏ hàng</span></li>
      </ul>
    </section>
    
    <!--=============== LOADING INDICATOR ===============-->
    <div id="loading-indicator" class="loading-indicator" style="display: none;">
      <div class="spinner"></div>
      <span>Đang cập nhật...</span>
    </div>
    
    <!--=============== GIỎ HÀNG ===============-->
    <div class="section__content giohang">
      <!-- Container -->
      <div class="container">
        <div class="giohang_title">GIỎ HÀNG CỦA BẠN</div>
        
        <?php if (empty($cart_items)): ?>
          <div class="empty-cart">
            <p>Giỏ hàng của bạn đang trống</p>
            <a href="shop.php" class="btn">Tiếp tục mua sắm</a>
          </div>
        <?php else: ?>
          <!-- Danh sách sản phẩm trong giỏ hàng -->
          <div id="cart-items-container">
          <?php foreach ($cart_items as $item): ?>
              <div class="product-list" data-product-id="<?php echo $item['product_id']; ?>">
              <div class="w-r__container">
                <div class="w-r__img-wrap">
                    <img src="<?php echo $item['product_image']; ?>" alt="Product Image">
                </div>
                <div class="w-r__info">
                    <div class="w-r__name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                  <div class="w-r__details">
                      <div class="w-r__category"><?php echo htmlspecialchars($item['product_category']); ?></div>
                      <div class="w-r__variant"><?php echo htmlspecialchars($item['product_variant']); ?></div>
                  </div>
                  <div class="w-r__price-wrapper">
                      <div class="w-r__price" data-price="<?php echo $item['product_price']; ?>">
                        <?php echo number_format($item['product_price'], 0, ',', '.'); ?>
                      </div>
                  </div>
                </div>
                <div class="w-r__wrap-2">
                  <!-- Điều chỉnh số lượng sản phẩm -->
                  <div class="quantity-control">
                      <button type="button" class="quantity-btn minus" onclick="decreaseQuantity(<?php echo $item['product_id']; ?>)">-</button>
                      <input type="number" 
                             id="quantity-<?php echo $item['product_id']; ?>" 
                           value="<?php echo $item['quantity']; ?>" 
                           min="1" 
                           max="99" 
                           class="quantity-input"
                             onchange="updateQuantityInstant(<?php echo $item['product_id']; ?>, this.value)">
                      <button type="button" class="quantity-btn plus" onclick="increaseQuantity(<?php echo $item['product_id']; ?>)">+</button>
                    </div>
                    
                    <!-- Subtotal cho từng sản phẩm -->
                    <div class="item-subtotal">
                      <span id="subtotal-<?php echo $item['product_id']; ?>">
                        <?php echo number_format($item['product_price'] * $item['quantity'], 0, ',', '.'); ?>
                      </span>
                    </div>
                    
                  <!-- Các nút hành động cho sản phẩm -->
                    <div class="product-actions">
                      <button type="button" class="w-r__link btn--e-transparent-platinum-b-2" 
                              onclick="viewProduct(<?php echo $item['product_id']; ?>)">Xem Sản Phẩm</button>
                      <button type="button" class="w-r__link btn--e-transparent-platinum-b-2 remove-btn" 
                              onclick="removeItemInstant(<?php echo $item['product_id']; ?>)">Xóa Sản Phẩm</button>
                    </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          </div>

          <!-- Các hành động của giỏ hàng -->
          <div class="cart__actions">
            <a href="shop.php" class="btn flex btn__md">
              <i class="fi-rs-shopping-bag"></i> Tiếp tục mua sắm
            </a>
            <button type="button" onclick="clearCartInstant()" class="btn flex btn__md">
              <i class="fi-rs-trash"></i> Xóa toàn bộ giỏ hàng
            </button>
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
                  <td><span class="cart__total-price" id="cart-subtotal"><?php echo number_format($subtotal, 0, ',', '.'); ?></span></td>
                </tr>
                <tr>
                  <td><span class="cart__total-title">Vận chuyển</span></td>
                  <td><span class="cart__total-price"><?php echo number_format($shipping, 0, ',', '.'); ?></span></td>
                </tr>
                <tr>
                  <td><span class="cart__total-title">Tổng</span></td>
                  <td><span class="cart__total-price" id="cart-total"><?php echo number_format($total, 0, ',', '.'); ?></span></td>
                </tr>
              </table>
              <!-- Nút thanh toán -->
              <a href="payment.php" class="btn flex btn--md">
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
        <a href="index.php" class="footer__logo">
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
  
  <!-- JavaScript cho các chức năng giỏ hàng - SỬA LỖI GIÁ TIỀN -->
  <script>
    // Biến để theo dõi trạng thái đang xử lý
    let isProcessing = false;
    
    // Hàm hiển thị loading
    function showLoading() {
        document.getElementById('loading-indicator').style.display = 'flex';
    }
    
    // Hàm ẩn loading
    function hideLoading() {
        document.getElementById('loading-indicator').style.display = 'none';
    }
    
    // Hàm tăng số lượng - TỰ ĐỘNG LƯU
    function increaseQuantity(productId) {
        if (isProcessing) return;
        
        const input = document.getElementById(`quantity-${productId}`);
        const currentValue = parseInt(input.value) || 0;
        const newValue = currentValue + 1;
        
        updateQuantityInstant(productId, newValue);
    }

    // Hàm giảm số lượng - TỰ ĐỘNG LƯU
    function decreaseQuantity(productId) {
        if (isProcessing) return;
        
        const input = document.getElementById(`quantity-${productId}`);
        const currentValue = parseInt(input.value) || 0;
        
        if (currentValue > 1) {
            const newValue = currentValue - 1;
            updateQuantityInstant(productId, newValue);
        }
    }

    // Hàm cập nhật số lượng ngay lập tức - LƯU VÀO DATABASE
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
        input.value = quantity;
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
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Nếu lỗi, revert lại giá trị cũ
            location.reload();
            showNotification('Có lỗi xảy ra', 'error');
        })
        .finally(() => {
            isProcessing = false;
            hideLoading();
        });
    }

    // Hàm xóa sản phẩm ngay lập tức - LƯU VÀO DATABASE
    function removeItemInstant(productId) {
        if (isProcessing) return;
        
        if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
            isProcessing = true;
            showLoading();
            
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_item&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Xóa sản phẩm khỏi DOM với animation
                    const productElement = document.querySelector(`[data-product-id="${productId}"]`);
                    if (productElement) {
                        productElement.style.transition = 'all 0.5s ease';
                        productElement.style.opacity = '0';
                        productElement.style.transform = 'translateX(-100%)';
                        
                        setTimeout(() => {
                            productElement.remove();
                            
                            // Kiểm tra nếu giỏ hàng trống
                            checkEmptyCart();
                        }, 500);
                    }
                    
                    // Cập nhật tổng giỏ hàng
                    updateCartTotalsFromServer();
                    // Cập nhật số lượng trên header
                    updateCartCount();
                    
                    showNotification('Đã xóa sản phẩm khỏi giỏ hàng', 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Có lỗi xảy ra', 'error');
            })
            .finally(() => {
                isProcessing = false;
                hideLoading();
            });
        }
    }

    // Hàm xóa toàn bộ giỏ hàng ngay lập tức
    function clearCartInstant() {
        if (isProcessing) return;
        
        if (confirm('Bạn có chắc chắn muốn xóa toàn bộ giỏ hàng?')) {
            isProcessing = true;
            showLoading();
            
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear_cart'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload trang để hiển thị giỏ hàng trống
                    location.reload();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Có lỗi xảy ra', 'error');
            })
            .finally(() => {
                isProcessing = false;
                hideLoading();
            });
        }
    }

    // Hàm cập nhật subtotal cho từng sản phẩm - KHÔNG THÊM ₫
    function updateItemSubtotal(productId) {
        const quantityInput = document.getElementById(`quantity-${productId}`);
        const quantity = parseInt(quantityInput.value) || 0;
        
        // Lấy giá sản phẩm
        const productElement = document.querySelector(`[data-product-id="${productId}"]`);
        const priceElement = productElement.querySelector('.w-r__price');
        const price = parseInt(priceElement.getAttribute('data-price'));
        
        // Tính subtotal
        const subtotal = price * quantity;
        
        // Cập nhật hiển thị subtotal - KHÔNG THÊM ₫ (CSS sẽ thêm)
        const subtotalElement = document.getElementById(`subtotal-${productId}`);
        if (subtotalElement) {
            subtotalElement.textContent = new Intl.NumberFormat('vi-VN').format(subtotal);
        }
    }

    // Hàm cập nhật tổng giỏ hàng từ server - KHÔNG THÊM ₫
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
                const shipping = 21000;
                const total = subtotal + shipping;
                
                // Cập nhật hiển thị - CHỈ 1 KÝ HIỆU ₫
                document.getElementById('cart-subtotal').textContent = new Intl.NumberFormat('vi-VN').format(subtotal);
                document.getElementById('cart-total').textContent = new Intl.NumberFormat('vi-VN').format(total);
            }
        })
        .catch(error => {
            console.error('Error updating totals:', error);
        });
    }

    // Hàm cập nhật số lượng sản phẩm trên header
    function updateCartCount() {
        fetch('cart_actions.php?action=get_cart_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('cart-count').textContent = data.count || 0;
            }
        })
        .catch(error => {
            console.error('Error updating cart count:', error);
        });
    }

    // Hàm kiểm tra giỏ hàng trống
    function checkEmptyCart() {
        const cartContainer = document.getElementById('cart-items-container');
        if (cartContainer && cartContainer.children.length === 0) {
            // Hiển thị thông báo giỏ hàng trống
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    }

    // Hàm xem sản phẩm
    function viewProduct(productId) {
        // Redirect đến trang chi tiết sản phẩm
        window.location.href = `product-detail.php?id=${productId}`;
    }

    // Hàm hiển thị thông báo ngắn
    function showNotification(message, type) {
        // Xóa thông báo cũ nếu có
        const existingNotification = document.querySelector('.toast-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = `toast-notification ${type}`;
        notification.textContent = message;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'success' ? '#4CAF50' : '#f44336'};
            color: white;
            border-radius: 6px;
            z-index: 10000;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 14px;
            max-width: 300px;
        `;
        
        document.body.appendChild(notification);
        
        // Hiển thị
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateY(0)';
        }, 100);
        
        // Ẩn sau 2 giây
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 2000);
    }

    // Hàm cập nhật định dạng giá tiền ban đầu - SỬA LỖI ₫₫₫
    function updateInitialPriceFormat() {
        // Cập nhật tất cả subtotal
        document.querySelectorAll('[id^="subtotal-"]').forEach(function(element) {
            let text = element.textContent;
            // Xóa tất cả ký hiệu ₫ cũ
            text = text.replace(/₫/g, '');
            const number = parseInt(text.replace(/[^\d]/g, ''));
            if (!isNaN(number)) {
                element.textContent = new Intl.NumberFormat('vi-VN').format(number);
            }
        });
        
        // Cập nhật tổng tiền
        const subtotalElement = document.getElementById('cart-subtotal');
        const totalElement = document.getElementById('cart-total');
        
        if (subtotalElement) {
            let subtotalText = subtotalElement.textContent;
            // Xóa tất cả ký hiệu ₫ cũ
            subtotalText = subtotalText.replace(/₫/g, '');
            const subtotalNumber = parseInt(subtotalText.replace(/[^\d]/g, ''));
            if (!isNaN(subtotalNumber)) {
                subtotalElement.textContent = new Intl.NumberFormat('vi-VN').format(subtotalNumber);
            }
        }
        
        if (totalElement) {
            let totalText = totalElement.textContent;
            // Xóa tất cả ký hiệu ₫ cũ
            totalText = totalText.replace(/₫/g, '');
            const totalNumber = parseInt(totalText.replace(/[^\d]/g, ''));
            if (!isNaN(totalNumber)) {
                totalElement.textContent = new Intl.NumberFormat('vi-VN').format(totalNumber);
            }
        }
        
        // Cập nhật giá sản phẩm đơn lẻ
        document.querySelectorAll('.w-r__price').forEach(function(element) {
            let text = element.textContent;
            // Xóa tất cả ký hiệu ₫ cũ
            text = text.replace(/₫/g, '');
            const number = parseInt(text.replace(/[^\d]/g, ''));
            if (!isNaN(number)) {
                element.textContent = new Intl.NumberFormat('vi-VN').format(number);
            }
        });
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Lắng nghe thay đổi trực tiếp trong input số lượng
        document.querySelectorAll('.quantity-input').forEach(function(input) {
            let timeout;
            
            input.addEventListener('input', function() {
                const productId = this.id.replace('quantity-', '');
                const quantity = parseInt(this.value) || 1;
                
                // Debounce để tránh gọi API quá nhiều
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    updateQuantityInstant(productId, quantity);
                }, 500);
            });
            
            // Ngăn không cho nhập số âm hoặc 0
            input.addEventListener('blur', function() {
                if (parseInt(this.value) < 1) {
                    this.value = 1;
                    const productId = this.id.replace('quantity-', '');
                    updateQuantityInstant(productId, 1);
                }
            });
        });
        
        // Cập nhật định dạng giá tiền ban đầu
        setTimeout(updateInitialPriceFormat, 100);
    });
  </script>
  
  <style>
    /* Thêm ký hiệu ₫ bằng CSS */
    .w-r__price::after,
    .item-subtotal span::after,
    .cart__total-price::after {
        content: '₫';
        margin-left: 2px;
    }

    /* Đảm bảo không có khoảng trắng thừa */
    .w-r__price,
    .item-subtotal span,
    .cart__total-price {
        display: inline-block;
        white-space: nowrap;
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
    
    /* Quantity control styles */
    .quantity-control {
        display: flex;
        align-items: center;
        gap: 5px;
        background: #f8f9fa;
        border-radius: 25px;
        padding: 5px;
    }
    
    .quantity-btn {
        background: #ff6b35;
        color: white;
        border: none;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: bold;
        transition: all 0.2s ease;
        user-select: none;
    }
    
    .quantity-btn:hover {
        background: #e55a2b;
        transform: scale(1.05);
    }
    
    .quantity-btn:active {
        transform: scale(0.95);
    }
    
    .quantity-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }
    
    .quantity-input {
        width: 60px;
        text-align: center;
        border: none;
        background: transparent;
        font-size: 16px;
        font-weight: bold;
        color: #2c3e50;
        padding: 5px;
    }
    
    .quantity-input:focus {
        outline: none;
        background: white;
        border-radius: 4px;
    }
    
    /* Product list styles */
    .product-list {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .product-list:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    
    .w-r__container {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .w-r__img-wrap {
        flex-shrink: 0;
        width: 100px;
        height: 100px;
        border-radius: 8px;
        overflow: hidden;
        background: #f8f9fa;
    }
    
    .w-r__img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .w-r__info {
        flex: 1;
        min-width: 0;
    }
    
    .w-r__name {
        font-size: 18px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 8px;
    }
    
    .w-r__details {
        display: flex;
        gap: 15px;
        margin-bottom: 10px;
    }
    
    .w-r__category, .w-r__variant {
        font-size: 14px;
        color: #7f8c8d;
        background: #ecf0f1;
        padding: 4px 8px;
        border-radius: 4px;
    }
    
    .w-r__price-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .w-r__price {
        font-size: 16px;
        font-weight: bold;
        color: #e74c3c;
    }
    
    .w-r__wrap-2 {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        min-width: 200px;
    }
    
    /* Item subtotal */
    .item-subtotal {
        font-size: 16px;
        font-weight: bold;
        color: #2c3e50;
        text-align: center;
    }
    
    /* Product actions */
    .product-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    
    .w-r__link {
        padding: 8px 16px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: white;
        color: #2c3e50;
        text-decoration: none;
        text-align: center;
        font-size: 14px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .w-r__link:hover {
        background: #ff6b35;
        color: white;
        border-color: #ff6b35;
    }
    
    /* Cart actions */
    .cart__actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 30px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    /* Toast notification */
    .toast-notification {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .w-r__container {
            flex-direction: column;
            text-align: center;
        }
        
        .w-r__details {
            justify-content: center;
        }
        
        .cart__actions {
            flex-direction: column;
            gap: 15px;
        }
        
        .product-actions {
            flex-direction: row;
        }
        
        .quantity-control {
            justify-content: center;
        }
    }
  </style>
</body>

</html> 