<?php
// File xử lý các hành động liên quan đến giỏ hàng
session_start();

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Hàm thêm sản phẩm vào giỏ hàng
function addToCart($productId, $name, $price, $image, $category, $variant, $quantity = 1) {
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = [
            'id' => $productId,
            'name' => $name,
            'price' => $price,
            'image' => $image,
            'category' => $category,
            'variant' => $variant,
            'quantity' => $quantity
        ];
    }
}

// Hàm cập nhật số lượng sản phẩm
function updateQuantity($productId, $quantity) {
    if (isset($_SESSION['cart'][$productId])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        }
        return true;
    }
    return false;
}

// Hàm xóa sản phẩm khỏi giỏ hàng
function removeFromCart($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    return false;
}

// Hàm tính tổng tiền giỏ hàng
function getCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}

// Hàm đếm số lượng sản phẩm trong giỏ hàng
function getCartItemCount() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}

// Xử lý AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'add_to_cart':
            $productId = $_POST['product_id'];
            $name = $_POST['name'];
            $price = $_POST['price'];
            $image = $_POST['image'];
            $category = $_POST['category'];
            $variant = $_POST['variant'];
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            
            addToCart($productId, $name, $price, $image, $category, $variant, $quantity);
            $response['success'] = true;
            $response['message'] = 'Sản phẩm đã được thêm vào giỏ hàng';
            break;
            
        case 'update_quantity':
            $productId = $_POST['product_id'];
            $quantity = intval($_POST['quantity']);
            
            if (updateQuantity($productId, $quantity)) {
                $response['success'] = true;
                $response['message'] = 'Đã cập nhật số lượng';
                $response['cart_total'] = getCartTotal();
            }
            break;
            
        case 'remove_item':
            $productId = $_POST['product_id'];
            
            if (removeFromCart($productId)) {
                $response['success'] = true;
                $response['message'] = 'Đã xóa sản phẩm khỏi giỏ hàng';
                $response['cart_total'] = getCartTotal();
            }
            break;
            
        case 'update_cart':
            $quantities = json_decode($_POST['quantities'], true);
            
            foreach ($quantities as $productId => $quantity) {
                updateQuantity($productId, intval($quantity));
            }
            
            $response['success'] = true;
            $response['message'] = 'Đã cập nhật giỏ hàng';
            $response['cart_total'] = getCartTotal();
            break;
            
        case 'clear_cart':
            $_SESSION['cart'] = [];
            $response['success'] = true;
            $response['message'] = 'Đã xóa toàn bộ giỏ hàng';
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Xử lý GET request để lấy số lượng giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_cart_count') {
    header('Content-Type: application/json');
    echo json_encode(['count' => getCartItemCount()]);
    exit;
}
?> 