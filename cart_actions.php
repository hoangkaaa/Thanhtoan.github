<?php
// File xử lý các hành động liên quan đến giỏ hàng với database - TỰ ĐỘNG LƯU

// Chỉ start session nếu chưa có session nào
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->connect();

// Khởi tạo Cart class
$cart = new Cart($db);

// Giả lập user_id (trong thực tế lấy từ session đăng nhập)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Xử lý AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    try {
        switch ($_POST['action']) {
            case 'add_to_cart':
                $product_id = $_POST['product_id'];
                $name = $_POST['name'];
                $price = $_POST['price'];
                $image = $_POST['image'];
                $category = $_POST['category'];
                $variant = isset($_POST['variant']) ? $_POST['variant'] : '500ml';
                $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
                
                if ($cart->addItem($user_id, $product_id, $name, $price, $image, $category, $variant, $quantity)) {
                    $response['success'] = true;
                    $response['message'] = 'Sản phẩm đã được thêm vào giỏ hàng';
                    $response['data']['cart_count'] = $cart->getItemCount($user_id);
                    $response['data']['cart_total'] = $cart->getTotal($user_id);
                } else {
                    $response['message'] = 'Có lỗi khi thêm sản phẩm vào giỏ hàng';
                }
                break;
                
            case 'update_quantity':
                $product_id = $_POST['product_id'];
                $quantity = intval($_POST['quantity']);
                
                if ($quantity <= 0) {
                    $response['message'] = 'Số lượng phải lớn hơn 0';
                    break;
                }
                
                if ($cart->updateQuantity($user_id, $product_id, $quantity)) {
                    $response['success'] = true;
                    $response['message'] = 'Đã cập nhật số lượng thành công';
                    $response['data']['cart_total'] = $cart->getTotal($user_id);
                    $response['data']['cart_count'] = $cart->getItemCount($user_id);
                } else {
                    $response['message'] = 'Có lỗi khi cập nhật số lượng';
                }
                break;
                
            case 'remove_item':
                $product_id = $_POST['product_id'];
                
                if ($cart->removeItem($user_id, $product_id)) {
                    $response['success'] = true;
                    $response['message'] = 'Đã xóa sản phẩm khỏi giỏ hàng';
                    $response['data']['cart_total'] = $cart->getTotal($user_id);
                    $response['data']['cart_count'] = $cart->getItemCount($user_id);
                } else {
                    $response['message'] = 'Có lỗi khi xóa sản phẩm';
                }
                break;
                
            case 'clear_cart':
                if ($cart->clearCart($user_id)) {
                    $response['success'] = true;
                    $response['message'] = 'Đã xóa toàn bộ giỏ hàng';
                    $response['data']['cart_total'] = 0;
                    $response['data']['cart_count'] = 0;
                } else {
                    $response['message'] = 'Có lỗi khi xóa giỏ hàng';
                }
                break;
                
            case 'get_cart_items':
                $items = $cart->getItems($user_id);
                $response['success'] = true;
                $response['data']['items'] = $items;
                $response['data']['cart_total'] = $cart->getTotal($user_id);
                $response['data']['cart_count'] = $cart->getItemCount($user_id);
                break;

            case 'update_quantity_direct':
                $item_id = (int)$_POST['item_id'];
                $new_quantity = (int)$_POST['quantity'];
                
                if ($new_quantity < 1) {
                    $response['success'] = false;
                    $response['message'] = 'Số lượng phải lớn hơn 0';
                    break;
                }
                
                try {
                    $success = $cart->updateQuantityDirect($item_id, $new_quantity);
                    
                    if ($success) {
                        $total = $cart->getTotal($user_id);
                        $count = $cart->getItemCount($user_id);
                        
                        $response['success'] = true;
                        $response['data']['total'] = $total;
                        $response['data']['count'] = $count;
                        $response['message'] = 'Cập nhật số lượng thành công';
                    } else {
                        $response['success'] = false;
                        $response['message'] = 'Không thể cập nhật số lượng';
                    }
                } catch (Exception $e) {
                    $response['success'] = false;
                    $response['message'] = 'Lỗi: ' . $e->getMessage();
                }
                break;

            case 'remove_item_direct':
                $item_id = (int)$_POST['item_id'];
                
                try {
                    $success = $cart->removeItemDirect($item_id);
                    
                    if ($success) {
                        $total = $cart->getTotal($user_id);
                        $count = $cart->getItemCount($user_id);
                        
                        $response['success'] = true;
                        $response['data']['total'] = $total;
                        $response['data']['count'] = $count;
                        $response['message'] = 'Xóa sản phẩm thành công';
                    } else {
                        $response['success'] = false;
                        $response['message'] = 'Không thể xóa sản phẩm';
                    }
                } catch (Exception $e) {
                    $response['success'] = false;
                    $response['message'] = 'Lỗi: ' . $e->getMessage();
                }
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Có lỗi hệ thống: ' . $e->getMessage();
        error_log('Cart error: ' . $e->getMessage());
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Xử lý GET request để lấy số lượng giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_cart_count') {
    try {
        $count = $cart->getItemCount($user_id);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => $count]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'count' => 0, 'message' => $e->getMessage()]);
    }
    exit;
}
?> 