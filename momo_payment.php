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

/**
 * HÀM GỌI API MOMO - PHẦN QUAN TRỌNG NHẤT
 * Hàm này sử dụng cURL để gửi request POST đến API MoMo
 * @param string $url - URL endpoint của MoMo API
 * @param string $data - Dữ liệu JSON để gửi
 * @return array - Kết quả trả về từ API
 */
function execPostRequest($url, $data) {
    // Khởi tạo cURL session
    $ch = curl_init($url);
    
    // Cấu hình cURL để gửi POST request
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Dữ liệu gửi đi
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Trả về kết quả thay vì in ra
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json', // Header chỉ định loại dữ liệu JSON
        'Content-Length: ' . strlen($data)) // Độ dài dữ liệu
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout 30 giây
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // Timeout kết nối 30 giây
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bỏ qua verify SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Bỏ qua verify host SSL
    
    // Thực thi request và lấy kết quả
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Mã HTTP response
    $error = curl_error($ch); // Lỗi nếu có
    curl_close($ch); // Đóng cURL session
    
    return [
        'result' => $result,
        'httpCode' => $httpCode,
        'error' => $error
    ];
}

try {
    writeLog('Starting MoMo payment process');
    
    // Kiểm tra thông tin thanh toán từ session
    if (!isset($_SESSION['payment_info'])) {
        throw new Exception('Không tìm thấy thông tin thanh toán');
    }

    $payment_info = $_SESSION['payment_info'];
    $amount = $payment_info['total_amount'];
    
    writeLog('Payment amount: ' . $amount);

    if ($amount <= 0) {
        throw new Exception('Số tiền không hợp lệ');
    }

    // Tạo các ID duy nhất cho đơn hàng
    $orderId = time(); // ID đơn hàng dựa trên timestamp
    $requestId = time(); // ID request duy nhất
    $orderInfo = "Thanh toan don hang Sunkissed #" . $orderId; // Thông tin đơn hàng
    $extraData = ""; // Dữ liệu bổ sung (để trống)

    writeLog('Order ID: ' . $orderId);
    writeLog('Order Info: ' . $orderInfo);

    /**
     * TẠO CHỮ KÝ ĐIỆN TỬ (SIGNATURE) - BƯỚC QUAN TRỌNG BẢO MẬT
     * MoMo yêu cầu tạo signature từ các tham số để xác thực request
     */
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

    // Tạo signature bằng thuật toán HMAC SHA256
    $signature = hash_hmac('sha256', $rawHash, MOMO_SECRET_KEY);
    
    writeLog('Signature created successfully');

    /**
     * CHUẨN BỊ DỮ LIỆU GỬI ĐẾN API MOMO
     * Tất cả thông tin cần thiết để tạo link thanh toán
     */
    $data = [
        'partnerCode' => MOMO_PARTNER_CODE, // Mã đối tác
        'partnerName' => MOMO_PARTNER_NAME, // Tên đối tác
        'storeId' => MOMO_STORE_ID, // ID cửa hàng
        'requestId' => $requestId, // ID request
        'amount' => $amount, // Số tiền thanh toán
        'orderId' => $orderId, // ID đơn hàng
        'orderInfo' => $orderInfo, // Thông tin đơn hàng
        'redirectUrl' => MOMO_REDIRECT_URL, // URL redirect sau khi thanh toán
        'ipnUrl' => MOMO_IPN_URL, // URL nhận thông báo kết quả thanh toán
        'lang' => MOMO_LANG, // Ngôn ngữ
        'extraData' => $extraData, // Dữ liệu bổ sung
        'requestType' => MOMO_REQUEST_TYPE, // Loại request
        'signature' => $signature // Chữ ký điện tử
    ];

    writeLog('Request data prepared: ' . json_encode($data));

    /**
     * ★★★ ĐOẠN CODE CHÍNH GỌI API MOMO ★★★
     * Gửi request POST đến MoMo API để tạo link thanh toán
     */
    $response = execPostRequest(MOMO_ENDPOINT, json_encode($data));
    
    writeLog('MoMo response: ' . $response['result']);

    // Kiểm tra lỗi kết nối
    if ($response['error']) {
        throw new Exception('Lỗi kết nối: ' . $response['error']);
    }

    // Kiểm tra mã HTTP response
    if ($response['httpCode'] != 200) {
        throw new Exception('Lỗi HTTP: ' . $response['httpCode']);
    }

    // Parse JSON response từ MoMo
    $jsonResult = json_decode($response['result'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Lỗi JSON: ' . json_last_error_msg());
    }

    /**
     * XỬ LÝ KẾT QUẢ TRẢ VỀ TỪ MOMO API
     * Nếu thành công, MoMo sẽ trả về payUrl để redirect người dùng
     */
    if (isset($jsonResult['payUrl']) && !empty($jsonResult['payUrl'])) {
        writeLog('Payment URL received, redirecting...');
        // Redirect người dùng đến trang thanh toán MoMo
        header('Location: ' . $jsonResult['payUrl']);
        exit;
    } else {
        writeLog('No payUrl in response: ' . print_r($jsonResult, true));
        throw new Exception('Không nhận được URL thanh toán từ MoMo');
    }

} catch (Exception $e) {
    writeLog('Error: ' . $e->getMessage());
    
    // Lưu lỗi vào session và redirect về trang payment
    $_SESSION['payment_error'] = true;
    $_SESSION['error_message'] = $e->getMessage();
    
    header('Location: payment.php');
    exit;
} 