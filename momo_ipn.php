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

/**
 * ★★★ FILE XỬ LÝ THÔNG BÁO KẾT QUẢ THANH TOÁN TỪ MOMO (IPN) ★★★
 * File này được MoMo gọi tự động sau khi khách hàng thanh toán xong
 * Để cập nhật trạng thái đơn hàng trong database
 */
writeLog('Received MoMo IPN request');
writeLog('POST data: ' . print_r($_POST, true));

try {
    /**
     * NHẬN DỮ LIỆU TỪ MOMO API
     * MoMo sẽ gửi kết quả thanh toán dưới dạng JSON
     */
    // Lấy dữ liệu raw từ request body
    $rawData = file_get_contents('php://input');
    writeLog('Raw data: ' . $rawData);
    
    // Parse dữ liệu JSON từ MoMo
    $momoResponse = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
    }
    
    /**
     * XÁC THỰC CHỮ KÝ ĐIỆN TỬ TỪ MOMO
     * Đảm bảo dữ liệu gửi đến thực sự từ MoMo, không bị giả mạo
     */
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

    // Tạo signature để so sánh với signature từ MoMo
    $signature = hash_hmac('sha256', $rawHash, MOMO_SECRET_KEY);
    
    // Kiểm tra tính hợp lệ của signature
    if ($signature !== $momoResponse['signature']) {
        throw new Exception('Invalid signature');
    }
    
    // Kết nối database
    $database = new Database();
    $db = $database->connect();
    
    /**
     * CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG DỰA TRÊN KẾT QUẢ THANH TOÁN
     * resultCode = 0: Thanh toán thành công
     * resultCode != 0: Thanh toán thất bại
     */
    if ($momoResponse['resultCode'] == 0) {
        // Thanh toán thành công - cập nhật trạng thái đơn hàng
        $sql = "UPDATE orders SET 
                payment_status = 'completed',
                momo_trans_id = :transId,
                updated_at = NOW()
                WHERE order_id = :orderId";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':transId' => $momoResponse['transId'], // Lưu mã giao dịch MoMo
            ':orderId' => $momoResponse['orderId']
        ]);
        
        writeLog('Order ' . $momoResponse['orderId'] . ' payment completed');
    } else {
        // Thanh toán thất bại - cập nhật trạng thái và lý do
        $sql = "UPDATE orders SET 
                payment_status = 'failed',
                payment_message = :message,
                updated_at = NOW()
                WHERE order_id = :orderId";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':message' => $momoResponse['message'], // Lưu lý do thất bại
            ':orderId' => $momoResponse['orderId']
        ]);
        
        writeLog('Order ' . $momoResponse['orderId'] . ' payment failed: ' . $momoResponse['message']);
    }
    
    /**
     * TRẢ VỀ RESPONSE CHO MOMO
     * MoMo cần nhận được response để biết đã xử lý thành công
     */
    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'message' => 'Processed successfully',
        'orderId' => $momoResponse['orderId']
    ]);
    
} catch (Exception $e) {
    writeLog('Error: ' . $e->getMessage());
    
    // Trả về lỗi cho MoMo
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 