<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config/database.php';
require_once 'config/momo_config.php';

// Start logging
$logFile = __DIR__ . '/logs/momo_ipn.log';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog('Received MoMo IPN request');
writeLog('POST data: ' . print_r($_POST, true));

try {
    // Get the raw POST data
    $rawData = file_get_contents('php://input');
    writeLog('Raw data: ' . $rawData);
    
    // Parse the JSON data
    $momoResponse = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
    }
    
    // Verify the signature
    $rawHash = "accessKey=" . MOMO_ACCESS_KEY .
               "&amount=" . $momoResponse['amount'] .
               "&extraData=" . $momoResponse['extraData'] .
               "&message=" . $momoResponse['message'] .
               "&orderId=" . $momoResponse['orderId'] .
               "&orderInfo=" . $momoResponse['orderInfo'] .
               "&orderType=" . $momoResponse['orderType'] .
               "&partnerCode=" . $momoResponse['partnerCode'] .
               "&payType=" . $momoResponse['payType'] .
               "&requestId=" . $momoResponse['requestId'] .
               "&responseTime=" . $momoResponse['responseTime'] .
               "&resultCode=" . $momoResponse['resultCode'] .
               "&transId=" . $momoResponse['transId'];

    $signature = hash_hmac('sha256', $rawHash, MOMO_SECRET_KEY);
    
    if ($signature !== $momoResponse['signature']) {
        throw new Exception('Invalid signature');
    }
    
    // Connect to database
    $database = new Database();
    $db = $database->connect();
    
    // Update order status based on resultCode
    if ($momoResponse['resultCode'] == 0) {
        // Payment successful
        $sql = "UPDATE orders SET 
                payment_status = 'completed',
                momo_trans_id = :transId,
                updated_at = NOW()
                WHERE order_id = :orderId";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':transId' => $momoResponse['transId'],
            ':orderId' => $momoResponse['orderId']
        ]);
        
        writeLog('Order ' . $momoResponse['orderId'] . ' payment completed');
    } else {
        // Payment failed
        $sql = "UPDATE orders SET 
                payment_status = 'failed',
                payment_message = :message,
                updated_at = NOW()
                WHERE order_id = :orderId";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':message' => $momoResponse['message'],
            ':orderId' => $momoResponse['orderId']
        ]);
        
        writeLog('Order ' . $momoResponse['orderId'] . ' payment failed: ' . $momoResponse['message']);
    }
    
    // Return success response to MoMo
    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'message' => 'Processed successfully',
        'orderId' => $momoResponse['orderId']
    ]);
    
} catch (Exception $e) {
    writeLog('Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 