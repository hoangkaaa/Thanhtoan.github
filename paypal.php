<?php
// PayPal Payment - FIXED VERSION
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config
require_once 'config/paypal_config.php';

// Enhanced logging
function writeLog($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $log = date('Y-m-d H:i:s') . " - " . $message . "\n";
    @file_put_contents($logDir . '/paypal.log', $log, FILE_APPEND);
}

try {
    writeLog("=== PayPal Payment Started ===");
    
    // Check payment info in session
    if (!isset($_SESSION['payment_info'])) {
        throw new Exception('Không tìm thấy thông tin thanh toán');
    }
    
    $paymentInfo = $_SESSION['payment_info'];
    $amountVND = floatval($paymentInfo['total_amount']);
    
    // Convert VND to USD
    $amountUSD = $amountVND * VND_TO_USD_RATE;
    $amountUSD = max(1.00, round($amountUSD, 2)); // Minimum $1
    
    writeLog("Amount: $amountVND VND = $amountUSD USD");
    
    // STEP 1: Get Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Accept-Language: en_US'
    ));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    writeLog("Token response code: $httpCode");
    
    if ($httpCode != 200) {
        writeLog("Token response: $response");
        throw new Exception("Cannot get PayPal access token");
    }
    
    $tokenData = json_decode($response, true);
    if (!isset($tokenData['access_token'])) {
        throw new Exception("No access token in response");
    }
    
    $accessToken = $tokenData['access_token'];
    writeLog("Got access token successfully");
    
    // STEP 2: Create Order - FIXED URLs
    $orderData = array(
        'intent' => 'CAPTURE',
        'purchase_units' => array(
            array(
                'amount' => array(
                    'currency_code' => 'USD',
                    'value' => number_format($amountUSD, 2, '.', '')
                ),
                'description' => 'Sunkissed Store Order #' . time()
            )
        ),
        'application_context' => array(
            'brand_name' => 'Sunkissed Store',
            'landing_page' => 'LOGIN',
            'user_action' => 'PAY_NOW',
            'return_url' => PAYPAL_RETURN_URL,
            'cancel_url' => PAYPAL_CANCEL_URL
        )
    );
    
    writeLog("Order data: " . json_encode($orderData));
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'PayPal-Request-Id: ' . uniqid()
    ));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    writeLog("Order response code: $httpCode");
    
    if ($httpCode != 201) {
        writeLog("Order response: $response");
        throw new Exception("Cannot create PayPal order: HTTP $httpCode");
    }
    
    $orderResponse = json_decode($response, true);
    if (!isset($orderResponse['id'])) {
        throw new Exception("No order ID in response");
    }
    
    // Store order ID in session
    $_SESSION['paypal_order_id'] = $orderResponse['id'];
    writeLog("Order created: " . $orderResponse['id']);
    
    // Find approval URL
    $approvalUrl = null;
    foreach ($orderResponse['links'] as $link) {
        if ($link['rel'] == 'approve') {
            $approvalUrl = $link['href'];
            break;
        }
    }
    
    if (!$approvalUrl) {
        throw new Exception("No approval URL found");
    }
    
    writeLog("Redirecting to: $approvalUrl");
    
    // Redirect to PayPal
    header('Location: ' . $approvalUrl);
    exit();
    
} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
    
    $_SESSION['payment_error'] = true;
    $_SESSION['error_message'] = 'Lỗi PayPal: ' . $e->getMessage();
    
    header('Location: payment.php');
    exit();
}
?> 