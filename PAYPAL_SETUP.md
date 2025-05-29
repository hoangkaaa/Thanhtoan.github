# Hướng dẫn thiết lập PayPal

## 1. Tạo tài khoản PayPal Developer

1. Truy cập https://developer.paypal.com/
2. Đăng ký hoặc đăng nhập bằng tài khoản PayPal của bạn
3. Tạo một ứng dụng mới (Create App)

## 2. Lấy thông tin xác thực

Sau khi tạo app, bạn sẽ nhận được:
- **Client ID**: ID ứng dụng
- **Client Secret**: Khóa bí mật

## 3. Cấu hình file config/paypal_config.php

Thay thế các giá trị sau trong file `config/paypal_config.php`:

```php
// Sandbox (Testing)
define('PAYPAL_CLIENT_ID', 'YOUR_SANDBOX_CLIENT_ID_HERE');
define('PAYPAL_CLIENT_SECRET', 'YOUR_SANDBOX_CLIENT_SECRET_HERE');

// Production (Live)
define('PAYPAL_CLIENT_ID', 'YOUR_PRODUCTION_CLIENT_ID_HERE');
define('PAYPAL_CLIENT_SECRET', 'YOUR_PRODUCTION_CLIENT_SECRET_HERE');
```

## 4. Tạo tài khoản Test (Sandbox)

1. Trong PayPal Developer Dashboard, vào **Sandbox > Accounts**
2. Tạo tài khoản Business và Personal để test
3. Sử dụng thông tin đăng nhập này để test trên https://sandbox.paypal.com

## 5. Cập nhật tỷ giá

Cập nhật tỷ giá VND/USD trong file config:
```php
define('VND_TO_USD_RATE', 0.00004); // Cập nhật tỷ giá hiện tại
```

## 6. Test Integration

1. Đặt `PAYPAL_TEST_MODE` = `true` trong config
2. Sử dụng tài khoản sandbox để test
3. Kiểm tra logs trong thư mục `logs/`

## 7. Go Live

1. Đặt `PAYPAL_TEST_MODE` = `false`
2. Thay thế Client ID và Secret bằng production credentials
3. Test kỹ lưỡng trước khi public 

## Tài khoản: sb-bsovm43131848@personal.example.com
## Mật khẩu: CkZ4ae,=