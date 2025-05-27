<?php
// Include database nếu cần
require_once 'config/database.php';

// Lấy dữ liệu sản phẩm nổi bật từ database
$database = new Database();
$db = $database->connect();
$product = new Product($db);

// Lấy sản phẩm nổi bật
$featured_products = $product->getAll(); // Hoặc method getFeatured()
?>

<!-- Copy nội dung từ index.html và chỉnh sửa thành động --> 