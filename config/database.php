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
}

// Class để quản lý đơn hàng
class Order {
    private $conn;
    private $table = 'orders';
    private $order_items_table = 'order_items';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tạo đơn hàng mới
    public function createOrder($user_id, $customer_info, $cart_items, $total_amount) {
        try {
            // Bắt đầu transaction
            $this->conn->beginTransaction();
            
            // Thêm đơn hàng vào bảng orders
            $order_query = "INSERT INTO " . $this->table . " 
                           (user_id, customer_name, customer_email, customer_phone, 
                            customer_address, total_amount, order_status, created_at) 
                           VALUES (:user_id, :customer_name, :customer_email, :customer_phone, 
                                   :customer_address, :total_amount, 'pending', NOW())";
            
            $order_stmt = $this->conn->prepare($order_query);
            $order_stmt->bindParam(':user_id', $user_id);
            $order_stmt->bindParam(':customer_name', $customer_info['name']);
            $order_stmt->bindParam(':customer_email', $customer_info['email']);
            $order_stmt->bindParam(':customer_phone', $customer_info['phone']);
            $order_stmt->bindParam(':customer_address', $customer_info['address']);
            $order_stmt->bindParam(':total_amount', $total_amount);
            
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
            
            // Xóa giỏ hàng sau khi đặt hàng thành công
            $this->clearCart($user_id);
            
            // Commit transaction
            $this->conn->commit();
            
            return $order_id;
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Xóa giỏ hàng sau khi đặt hàng
    private function clearCart($user_id) {
        $query = "DELETE FROM cart_items WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
}
?> 