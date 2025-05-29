<?php
// PayPal Return - SIMPLEST VERSION WITHOUT CLASS
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/paypal_config.php';
require_once 'config/database.php';

function writeLog($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $log = date('Y-m-d H:i:s') . " - " . $message . "\n";
    @file_put_contents($logDir . '/paypal_return.log', $log, FILE_APPEND);
}

try {
    writeLog("=== PayPal Return Started ===");
    
    // Check if cancelled
    if (isset($_GET['cancelled'])) {
        $_SESSION['payment_error'] = true;
        $_SESSION['error_message'] = 'Bạn đã hủy thanh toán PayPal';
        header('Location: payment.php');
        exit();
    }
    
    // Get parameters
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    $payerId = isset($_GET['PayerID']) ? $_GET['PayerID'] : '';
    $orderId = isset($_SESSION['paypal_order_id']) ? $_SESSION['paypal_order_id'] : '';
    
    writeLog("Token: $token, PayerID: $payerId, OrderID: $orderId");
    
    if (empty($token) || empty($orderId)) {
        throw new Exception('Missing PayPal parameters');
    }
    
    // STEP 1: Get Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200) {
        throw new Exception('Cannot get access token');
    }
    
    $tokenData = json_decode($response, true);
    $accessToken = isset($tokenData['access_token']) ? $tokenData['access_token'] : '';
    
    if (empty($accessToken)) {
        throw new Exception('No access token');
    }
    
    // STEP 2: Capture Payment
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . '/v2/checkout/orders/' . $orderId . '/capture');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $captureResponse = curl_exec($ch);
    $captureHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    writeLog("Capture response code: $captureHttpCode");
    
    if ($captureHttpCode !== 201) {
        throw new Exception('Capture failed');
    }
    
    $captureData = json_decode($captureResponse, true);
    
    if ($captureData['status'] !== 'COMPLETED') {
        throw new Exception('Payment not completed');
    }
    
    // Create order
    if (!isset($_SESSION['payment_info'])) {
        throw new Exception('No payment info found');
    }
    
    $paymentInfo = $_SESSION['payment_info'];
    
    // Database connection
    $database = new Database();
    $db = $database->connect();
    $cart = new Cart($db);
    $order = new Order($db);
    
    // Customer info
    $customerInfo = [
        'name' => $paymentInfo['first_name'] . ' ' . $paymentInfo['last_name'],
        'email' => $paymentInfo['email'],
        'phone' => $paymentInfo['phone'],
        'address' => 'PayPal Payment',
        'city' => '',
        'district' => '',
        'zipcode' => ''
    ];
    
    // Order details
    $orderDetails = [
        'delivery_method' => $paymentInfo['delivery_method'] ?? 'store',
        'payment_method' => 'paypal',
        'store_id' => null,
        'pickup_date' => null,
        'pickup_time' => null,
        'delivery_date' => null,
        'delivery_time' => null,
        'city' => null,
        'district' => null,
        'zipcode' => null,
        'subtotal' => $paymentInfo['cart_total'],
        'shipping_fee' => $paymentInfo['shipping_fee'] ?? 0,
        'discount_amount' => $paymentInfo['discount_amount'] ?? 0,
        'coupon_code' => null,
        'total_amount' => $paymentInfo['total_amount'],
        'paypal_order_id' => $orderId,
        'payment_status' => 'paid'
    ];
    
    $result = $order->createOrder(
        $paymentInfo['user_id'],
        $customerInfo,
        $paymentInfo['cart_items'],
        $orderDetails
    );
    
    if (!$result['success']) {
        throw new Exception('Failed to create order');
    }
    
    // Clear cart
    $cart->clearCart($paymentInfo['user_id']);
    
    // Success
    $_SESSION['payment_success'] = true;
    $_SESSION['success_message'] = 'Thanh toán thành công! Mã đơn: ' . $result['order_code'];
    
    // Cleanup
    unset($_SESSION['payment_info']);
    unset($_SESSION['paypal_order_id']);
    unset($_SESSION['paypal_amount']);
    
    writeLog("Success! Order: " . $result['order_code']);
    
    header('Location: order_confirmation.php?order_id=' . $result['order_id']);
    exit;
    
} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
    
    $_SESSION['payment_error'] = true;
    $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
    
    header('Location: payment.php');
    exit;
}
?> 