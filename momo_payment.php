<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'config/momo_config.php';

// Simple logging
function writeLog($message) {
    $logFile = __DIR__ . '/logs/momo_payment.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function execPostRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data))
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'result' => $result,
        'httpCode' => $httpCode,
        'error' => $error
    ];
}

try {
    writeLog('Starting MoMo payment process');
    
    // Check payment info from session
    if (!isset($_SESSION['payment_info'])) {
        throw new Exception('Không tìm thấy thông tin thanh toán');
    }

    $payment_info = $_SESSION['payment_info'];
    $amount = $payment_info['total_amount'];
    
    writeLog('Payment amount: ' . $amount);

    if ($amount <= 0) {
        throw new Exception('Số tiền không hợp lệ');
    }

    // Generate order ID
    $orderId = time();
    $requestId = time();
    $orderInfo = "Thanh toan don hang Sunkissed #" . $orderId;
    $extraData = "";

    writeLog('Order ID: ' . $orderId);
    writeLog('Order Info: ' . $orderInfo);

    // Create signature
    $rawHash = "accessKey=" . MOMO_ACCESS_KEY .
        "&amount=" . $amount .
        "&extraData=" . $extraData .
        "&ipnUrl=" . MOMO_IPN_URL .
        "&orderId=" . $orderId .
        "&orderInfo=" . $orderInfo .
        "&partnerCode=" . MOMO_PARTNER_CODE .
        "&redirectUrl=" . MOMO_REDIRECT_URL .
        "&requestId=" . $requestId .
        "&requestType=" . MOMO_REQUEST_TYPE;

    $signature = hash_hmac('sha256', $rawHash, MOMO_SECRET_KEY);
    
    writeLog('Signature created successfully');

    // Prepare request data
    $data = [
        'partnerCode' => MOMO_PARTNER_CODE,
        'partnerName' => MOMO_PARTNER_NAME,
        'storeId' => MOMO_STORE_ID,
        'requestId' => $requestId,
        'amount' => $amount,
        'orderId' => $orderId,
        'orderInfo' => $orderInfo,
        'redirectUrl' => MOMO_REDIRECT_URL,
        'ipnUrl' => MOMO_IPN_URL,
        'lang' => MOMO_LANG,
        'extraData' => $extraData,
        'requestType' => MOMO_REQUEST_TYPE,
        'signature' => $signature
    ];

    writeLog('Request data prepared: ' . json_encode($data));

    // Send request to MoMo
    $response = execPostRequest(MOMO_ENDPOINT, json_encode($data));
    
    writeLog('MoMo response: ' . $response['result']);

    if ($response['error']) {
        throw new Exception('Lỗi kết nối: ' . $response['error']);
    }

    if ($response['httpCode'] != 200) {
        throw new Exception('Lỗi HTTP: ' . $response['httpCode']);
    }

    $jsonResult = json_decode($response['result'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Lỗi JSON: ' . json_last_error_msg());
    }

    // Check if we have payment URL
    if (isset($jsonResult['payUrl']) && !empty($jsonResult['payUrl'])) {
        writeLog('Payment URL received, redirecting...');
        header('Location: ' . $jsonResult['payUrl']);
        exit;
    } else {
        writeLog('No payUrl in response: ' . print_r($jsonResult, true));
        throw new Exception('Không nhận được URL thanh toán từ MoMo');
    }

} catch (Exception $e) {
    writeLog('Error: ' . $e->getMessage());
    
    // Save error and redirect back
    $_SESSION['payment_error'] = true;
    $_SESSION['error_message'] = $e->getMessage();
    
    header('Location: payment.php');
    exit;
} 