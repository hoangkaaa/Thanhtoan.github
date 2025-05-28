<?php
// Cấu hình kết nối database
class Database {
    private $host = 'localhost';
    private $db_name = 'sunkissed_shop';
    private $username = 'root';
    private $password = '';
    private $conn;

    // Kết nối database
    public function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}

// Class để quản lý sản phẩm
class Product {
    private $conn;
    private $table = 'products';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Lấy thông tin sản phẩm theo ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lấy tất cả sản phẩm
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Class để quản lý giỏ hàng trong database
class Cart {
    private $conn;
    private $table = 'cart_items';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Thêm sản phẩm vào giỏ hàng
    public function addItem($user_id, $product_id, $name, $price, $image, $category, $variant, $quantity) {
        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        $check_query = "SELECT id, quantity FROM " . $this->table . " 
                       WHERE user_id = :user_id AND product_id = :product_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':product_id', $product_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Nếu có, cập nhật số lượng
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + $quantity;
            
            $update_query = "UPDATE " . $this->table . " 
                           SET quantity = :quantity 
                           WHERE id = :id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':quantity', $new_quantity);
            $update_stmt->bindParam(':id', $row['id']);
            
            return $update_stmt->execute();
        } else {
            // Nếu chưa có, thêm mới
            $insert_query = "INSERT INTO " . $this->table . " 
                           (user_id, product_id, product_name, product_price, product_image, 
                            product_category, product_variant, quantity, created_at) 
                           VALUES (:user_id, :product_id, :product_name, :product_price, 
                                   :product_image, :product_category, :product_variant, :quantity, NOW())";
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $user_id);
            $insert_stmt->bindParam(':product_id', $product_id);
            $insert_stmt->bindParam(':product_name', $name);
            $insert_stmt->bindParam(':product_price', $price);
            $insert_stmt->bindParam(':product_image', $image);
            $insert_stmt->bindParam(':product_category', $category);
            $insert_stmt->bindParam(':product_variant', $variant);
            $insert_stmt->bindParam(':quantity', $quantity);
            
            return $insert_stmt->execute();
        }
    }
    
    // Lấy các sản phẩm trong giỏ hàng của user
    public function getItems($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Cập nhật số lượng sản phẩm
    public function updateQuantity($user_id, $product_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($user_id, $product_id);
        }
        
        $query = "UPDATE " . $this->table . " 
                 SET quantity = :quantity 
                 WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        
        return $stmt->execute();
    }
    
    // Xóa sản phẩm khỏi giỏ hàng
    public function removeItem($user_id, $product_id) {
        $query = "DELETE FROM " . $this->table . " 
                 WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        
        return $stmt->execute();
    }
    
    // Đếm số lượng sản phẩm trong giỏ hàng
    public function getItemCount($user_id) {
        $query = "SELECT SUM(quantity) as total FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? $result['total'] : 0;
    }
    
    // Tính tổng giá trị giỏ hàng
    public function getTotal($user_id) {
        $query = "SELECT SUM(product_price * quantity) as total FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? $result['total'] : 0;
    }
    
    // Xóa toàn bộ giỏ hàng
    public function clearCart($user_id) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    public function updateQuantityDirect($item_id, $new_quantity) {
        try {
            $query = "UPDATE cart_items SET quantity = :quantity WHERE id = :item_id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':quantity', $new_quantity);
            $stmt->bindParam(':item_id', $item_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Cart updateQuantityDirect error: " . $e->getMessage());
            return false;
        }
    }

    public function removeItemDirect($item_id) {
        try {
            $query = "DELETE FROM cart_items WHERE id = :item_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':item_id', $item_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Cart removeItemDirect error: " . $e->getMessage());
            return false;
        }
    }
}

// Class để quản lý đơn hàng - CẢI TIẾN
class Order {
    private $conn;
    private $table = 'orders';
    private $order_items_table = 'order_items';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tạo mã đơn hàng
    private function generateOrderCode() {
        return 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -5));
    }
    
    // Tạo đơn hàng mới - CẢI TIẾN
    public function createOrder($user_id, $customer_info, $cart_items, $order_details) {
        try {
            // Bắt đầu transaction
            $this->conn->beginTransaction();
            
            // Tạo mã đơn hàng
            $order_code = $this->generateOrderCode();
            
            // Lưu thông tin khách hàng
            $this->saveCustomerInfo($customer_info);
            
            // Cập nhật sử dụng mã giảm giá nếu có
            if (!empty($order_details['coupon_code'])) {
                $this->updateCouponUsage($order_details['coupon_code']);
            }
            
            // Thêm đơn hàng vào bảng orders với đầy đủ thông tin
            $order_query = "INSERT INTO " . $this->table . " 
                           (order_code, user_id, customer_name, customer_email, customer_phone, 
                            customer_address, delivery_method, payment_method, store_id,
                            pickup_date, pickup_time, delivery_date, delivery_time,
                            city, district, zipcode, subtotal, shipping_fee, discount_amount, 
                            total_amount, order_status, payment_status, notes, created_at) 
                           VALUES (:order_code, :user_id, :customer_name, :customer_email, :customer_phone, 
                                   :customer_address, :delivery_method, :payment_method, :store_id,
                                   :pickup_date, :pickup_time, :delivery_date, :delivery_time,
                                   :city, :district, :zipcode, :subtotal, :shipping_fee, :discount_amount,
                                   :total_amount, 'pending', 'pending', :notes, NOW())";
            
            $order_stmt = $this->conn->prepare($order_query);
            
            // Tạo notes với thông tin mã giảm giá
            $notes = '';
            if (!empty($order_details['coupon_code'])) {
                $notes = 'Mã giảm giá đã sử dụng: ' . $order_details['coupon_code'] . 
                        ' (Giảm: ' . number_format($order_details['discount_amount'], 0, ',', '.') . '₫)';
            }
            
            // Bind parameters
            $order_stmt->bindParam(':order_code', $order_code);
            $order_stmt->bindParam(':user_id', $user_id);
            $order_stmt->bindParam(':customer_name', $customer_info['name']);
            $order_stmt->bindParam(':customer_email', $customer_info['email']);
            $order_stmt->bindParam(':customer_phone', $customer_info['phone']);
            $order_stmt->bindParam(':customer_address', $customer_info['address']);
            $order_stmt->bindParam(':delivery_method', $order_details['delivery_method']);
            $order_stmt->bindParam(':payment_method', $order_details['payment_method']);
            $order_stmt->bindParam(':store_id', $order_details['store_id']);
            $order_stmt->bindParam(':pickup_date', $order_details['pickup_date']);
            $order_stmt->bindParam(':pickup_time', $order_details['pickup_time']);
            $order_stmt->bindParam(':delivery_date', $order_details['delivery_date']);
            $order_stmt->bindParam(':delivery_time', $order_details['delivery_time']);
            $order_stmt->bindParam(':city', $order_details['city']);
            $order_stmt->bindParam(':district', $order_details['district']);
            $order_stmt->bindParam(':zipcode', $order_details['zipcode']);
            $order_stmt->bindParam(':subtotal', $order_details['subtotal']);
            $order_stmt->bindParam(':shipping_fee', $order_details['shipping_fee']);
            $order_stmt->bindParam(':discount_amount', $order_details['discount_amount']);
            $order_stmt->bindParam(':total_amount', $order_details['total_amount']);
            $order_stmt->bindParam(':notes', $notes);
            
            $order_stmt->execute();
            
            // Lấy ID của đơn hàng vừa tạo
            $order_id = $this->conn->lastInsertId();
            
            // Thêm chi tiết đơn hàng vào bảng order_items
            $item_query = "INSERT INTO " . $this->order_items_table . " 
                          (order_id, product_id, product_name, product_price, quantity, subtotal) 
                          VALUES (:order_id, :product_id, :product_name, :product_price, :quantity, :subtotal)";
            
            $item_stmt = $this->conn->prepare($item_query);
            
            foreach ($cart_items as $item) {
                $subtotal = $item['product_price'] * $item['quantity'];
                
                $item_stmt->bindParam(':order_id', $order_id);
                $item_stmt->bindParam(':product_id', $item['product_id']);
                $item_stmt->bindParam(':product_name', $item['product_name']);
                $item_stmt->bindParam(':product_price', $item['product_price']);
                $item_stmt->bindParam(':quantity', $item['quantity']);
                $item_stmt->bindParam(':subtotal', $subtotal);
                
                $item_stmt->execute();
            }
            
            // Thêm lịch sử đơn hàng
            $this->addOrderHistory($order_id, 'pending', 'Đơn hàng được tạo');
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'order_id' => $order_id,
                'order_code' => $order_code,
                'message' => 'Đơn hàng đã được tạo thành công!'
            ];
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Cập nhật số lần sử dụng mã giảm giá
    private function updateCouponUsage($coupon_code) {
        $query = "UPDATE coupons SET used_count = used_count + 1 WHERE code = :code AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $coupon_code);
        $stmt->execute();
    }
    
    // Thêm lịch sử đơn hàng
    private function addOrderHistory($order_id, $status, $notes, $created_by = 'System') {
        $query = "INSERT INTO order_history (order_id, status, notes, created_by) VALUES (:order_id, :status, :notes, :created_by)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':created_by', $created_by);
        $stmt->execute();
    }
    
    // Kiểm tra mã giảm giá
    public function validateCoupon($coupon_code, $order_amount = 0) {
        $query = "SELECT * FROM coupons WHERE code = :code AND status = 'active' 
                 AND (usage_limit = 0 OR used_count < usage_limit)
                 AND (valid_from IS NULL OR valid_from <= NOW())
                 AND (valid_until IS NULL OR valid_until >= NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $coupon_code);
        $stmt->execute();
        
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn'];
        }
        
        if ($order_amount < $coupon['min_order_amount']) {
            return ['valid' => false, 'message' => 'Đơn hàng chưa đủ giá trị tối thiểu để sử dụng mã này'];
        }
        
        return ['valid' => true, 'coupon' => $coupon];
    }
    
    // Tính toán giảm giá
    public function calculateDiscount($coupon, $order_amount, $shipping_fee = 0) {
        $discount = 0;
        
        switch ($coupon['type']) {
            case 'fixed':
                $discount = $coupon['value'];
                break;
            case 'percentage':
                $discount = ($order_amount * $coupon['value']) / 100;
                if ($coupon['max_discount'] > 0) {
                    $discount = min($discount, $coupon['max_discount']);
                }
                break;
            case 'shipping':
                $discount = ($shipping_fee * $coupon['value']) / 100;
                if ($coupon['max_discount'] > 0) {
                    $discount = min($discount, $coupon['max_discount']);
                }
                break;
        }
        
        return $discount;
    }
    
    // Lưu thông tin khách hàng
    private function saveCustomerInfo($customer_info) {
        $check_query = "SELECT id FROM customers WHERE email = :email";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':email', $customer_info['email']);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            // Thêm khách hàng mới
            $insert_query = "INSERT INTO customers (email, first_name, last_name, phone, address, city, district, zipcode, created_at) 
                           VALUES (:email, :first_name, :last_name, :phone, :address, :city, :district, :zipcode, NOW())";
            $insert_stmt = $this->conn->prepare($insert_query);
            
            $names = explode(' ', $customer_info['name'], 2);
            $first_name = $names[0] ?? '';
            $last_name = $names[1] ?? '';
            
            $insert_stmt->bindParam(':email', $customer_info['email']);
            $insert_stmt->bindParam(':first_name', $first_name);
            $insert_stmt->bindParam(':last_name', $last_name);
            $insert_stmt->bindParam(':phone', $customer_info['phone']);
            $insert_stmt->bindParam(':address', $customer_info['address']);
            $insert_stmt->bindParam(':city', $customer_info['city']);
            $insert_stmt->bindParam(':district', $customer_info['district']);
            $insert_stmt->bindParam(':zipcode', $customer_info['zipcode']);
            
            $insert_stmt->execute();
        } else {
            // Cập nhật thông tin khách hàng hiện có
            $update_query = "UPDATE customers SET phone = :phone, address = :address, city = :city, 
                           district = :district, zipcode = :zipcode, updated_at = NOW() WHERE email = :email";
            $update_stmt = $this->conn->prepare($update_query);
            
            $update_stmt->bindParam(':phone', $customer_info['phone']);
            $update_stmt->bindParam(':address', $customer_info['address']);
            $update_stmt->bindParam(':city', $customer_info['city']);
            $update_stmt->bindParam(':district', $customer_info['district']);
            $update_stmt->bindParam(':zipcode', $customer_info['zipcode']);
            $update_stmt->bindParam(':email', $customer_info['email']);
            
            $update_stmt->execute();
        }
    }
    
    // Lấy thông tin đơn hàng
    public function getOrderById($order_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lấy chi tiết đơn hàng
    public function getOrderItems($order_id) {
        $query = "SELECT * FROM " . $this->order_items_table . " WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Lấy lịch sử đơn hàng
    public function getOrderHistory($order_id) {
        $query = "SELECT * FROM order_history WHERE order_id = :order_id ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Cập nhật trạng thái đơn hàng
    public function updateOrderStatus($order_id, $new_status, $notes = '', $updated_by = 'System') {
        try {
            $this->conn->beginTransaction();
            
            // Cập nhật trạng thái trong bảng orders
            $query = "UPDATE " . $this->table . " SET order_status = :status, updated_at = NOW() WHERE id = :order_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            
            // Thêm vào lịch sử
            $this->addOrderHistory($order_id, $new_status, $notes, $updated_by);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    // Cập nhật trạng thái thanh toán
    public function updatePaymentStatus($order_id, $payment_status, $notes = '', $updated_by = 'System') {
        try {
            $this->conn->beginTransaction();
            
            // Cập nhật trạng thái thanh toán
            $query = "UPDATE " . $this->table . " SET payment_status = :payment_status, updated_at = NOW() WHERE id = :order_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':payment_status', $payment_status);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            
            // Thêm vào lịch sử
            $history_notes = "Trạng thái thanh toán: $payment_status" . ($notes ? " - $notes" : "");
            $this->addOrderHistory($order_id, "payment_$payment_status", $history_notes, $updated_by);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}
?> 