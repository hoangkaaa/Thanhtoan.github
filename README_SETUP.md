# Hướng dẫn thiết lập hệ thống thanh toán Sunkissed

## Cài đặt Database

### 1. Khởi động XAMPP
- Khởi động Apache và MySQL trong XAMPP Control Panel

### 2. Tạo Database
- Mở phpMyAdmin tại `http://localhost/phpmyadmin`
- Chạy script trong file `database_schema.sql` để tạo database và các bảng cần thiết

### 3. Cấu hình kết nối
- Kiểm tra file `config/database.php` 
- Đảm bảo thông tin kết nối database đúng:
  ```php
  private $host = 'localhost';
  private $db_name = 'sunkissed_shop';
  private $username = 'root';
  private $password = '';
  ```

## Tính năng đã thêm

### 1. Lưu thông tin khách hàng
- Tự động lưu thông tin khách hàng vào bảng `customers`
- Cập nhật thông tin nếu khách hàng đã tồn tại

### 2. Lưu đơn hàng hoàn chỉnh
- Lưu thông tin đơn hàng vào bảng `orders`
- Lưu chi tiết sản phẩm vào bảng `order_items`
- Tạo mã đơn hàng tự động (format: ORD + ngày + ID duy nhất)

### 3. Hỗ trợ mã giảm giá
- Áp dụng và lưu thông tin mã giảm giá
- Cập nhật số lần sử dụng mã giảm giá
- Tính toán giảm giá tự động

### 4. Lịch sử đơn hàng
- Theo dõi trạng thái đơn hàng
- Lưu lịch sử thay đổi trạng thái

### 5. Thông báo thành công
- Hiển thị thông báo khi thanh toán thành công
- Chuyển hướng đến trang xác nhận đơn hàng
- Animation và hiệu ứng trực quan

## Cấu trúc Database

### Bảng chính:
- `customers`: Thông tin khách hàng
- `orders`: Thông tin đơn hàng
- `order_items`: Chi tiết sản phẩm trong đơn hàng
- `cart_items`: Giỏ hàng
- `products`: Danh sách sản phẩm
- `coupons`: Mã giảm giá
- `stores`: Thông tin cửa hàng
- `order_history`: Lịch sử đơn hàng

### Dữ liệu mẫu có sẵn:
- 2 cửa hàng
- 2 mã giảm giá (GIAIKHATHE, GIAIKHAT)
- 5 sản phẩm mẫu

## Quy trình thanh toán

1. **Khách hàng thêm sản phẩm vào giỏ hàng**
   - Dữ liệu được lưu vào `cart_items`

2. **Điền thông tin thanh toán**
   - Validate thông tin bắt buộc
   - Tính phí vận chuyển theo địa chỉ

3. **Áp dụng mã giảm giá (nếu có)**
   - Kiểm tra tính hợp lệ
   - Tính toán giảm giá

4. **Xác nhận thanh toán**
   - Lưu thông tin khách hàng
   - Tạo đơn hàng mới
   - Lưu chi tiết sản phẩm
   - Cập nhật mã giảm giá
   - Xóa giỏ hàng
   - Chuyển đến trang xác nhận

5. **Hiển thị xác nhận**
   - Thông báo thành công
   - Chi tiết đơn hàng đầy đủ
   - Lịch sử trạng thái

## Tính năng nâng cao

### Quản lý trạng thái đơn hàng:
- `pending`: Chờ xác nhận
- `confirmed`: Đã xác nhận  
- `processing`: Đang xử lý
- `shipping`: Đang giao hàng
- `delivered`: Đã giao hàng
- `cancelled`: Đã hủy

### Trạng thái thanh toán:
- `pending`: Chờ thanh toán
- `paid`: Đã thanh toán
- `failed`: Thanh toán thất bại
- `refunded`: Đã hoàn tiền

## File quan trọng

### Backend:
- `config/database.php`: Class kết nối database và các model
- `payment.php`: Xử lý thanh toán
- `order_confirmation.php`: Trang xác nhận đơn hàng
- `cart_actions.php`: Xử lý giỏ hàng

### Database:
- `database_schema.sql`: Script tạo database

### Frontend:
- JavaScript trong `payment.php`: Xử lý tương tác người dùng
- CSS: Giao diện responsive và đẹp mắt

## Lưu ý

1. **Bảo mật**: 
   - Validate đầu vào
   - Sử dụng Prepared Statements
   - Escape output

2. **Performance**:
   - Index các cột quan trọng
   - Transaction để đảm bảo tính nhất quán

3. **User Experience**:
   - Thông báo rõ ràng
   - Loading indicators
   - Responsive design

## Troubleshooting

### Lỗi kết nối database:
- Kiểm tra XAMPP đã khởi động MySQL
- Kiểm tra tên database và user/password

### Lỗi tạo đơn hàng:
- Kiểm tra các bảng đã được tạo
- Kiểm tra dữ liệu đầu vào hợp lệ

### Lỗi hiển thị:
- Kiểm tra đường dẫn file CSS/JS
- Kiểm tra quyền truy cập file 