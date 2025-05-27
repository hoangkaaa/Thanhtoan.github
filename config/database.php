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
    public function addItem($user_id, $product_id, $quantity) {
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
                           (user_id, product_id, quantity) 
                           VALUES (:user_id, :product_id, :quantity)";
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $user_id);
            $insert_stmt->bindParam(':product_id', $product_id);
            $insert_stmt->bindParam(':quantity', $quantity);
            
            return $insert_stmt->execute();
        }
    }
    
    // Lấy các sản phẩm trong giỏ hàng của user
    public function getItems($user_id) {
        $query = "SELECT c.*, p.name, p.price, p.image, p.category, p.variant 
                 FROM " . $this->table . " c 
                 JOIN products p ON c.product_id = p.id 
                 WHERE c.user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 