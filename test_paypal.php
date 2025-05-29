<?php
session_start();
require_once 'config/paypal_config.php';

// Set test payment info
$_SESSION['payment_info'] = [
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test@example.com',
    'phone' => '0123456789',
    'delivery_method' => 'store',
    'payment_method' => 'paypal',
    'user_id' => 1,
    'cart_items' => [
        [
            'product_id' => 1,
            'name' => 'Test Product',
            'price' => 100000,
            'quantity' => 1
        ]
    ],
    'cart_total' => 100000,
    'shipping_fee' => 0,
    'discount_amount' => 0,
    'total_amount' => 100000
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>PayPal Integration Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        button { padding: 10px 20px; background: #0070ba; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>PayPal Integration Test</h1>
    
    <div class="info">
        <h3>Configuration:</h3>
        <p><strong>Mode:</strong> <?php echo PAYPAL_TEST_MODE ? 'SANDBOX' : 'PRODUCTION'; ?></p>
        <p><strong>Client ID:</strong> <?php echo substr(PAYPAL_CLIENT_ID, 0, 20); ?>...</p>
        <p><strong>Base URL:</strong> <?php echo PAYPAL_BASE_URL; ?></p>
        <p><strong>Return URL:</strong> <?php echo PAYPAL_RETURN_URL; ?></p>
        <p><strong>Cancel URL:</strong> <?php echo PAYPAL_CANCEL_URL; ?></p>
    </div>
    
    <?php
    // Test API connection
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => PAYPAL_BASE_URL . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo '<div class="info success">✅ PayPal API connection successful!</div>';
            $data = json_decode($response, true);
            if (isset($data['access_token'])) {
                echo '<div class="info">Access token obtained: ' . substr($data['access_token'], 0, 20) . '...</div>';
            }
        } else {
            echo '<div class="info error">❌ PayPal API connection failed! HTTP Code: ' . $httpCode . '</div>';
            echo '<div class="info">Response: <pre>' . htmlspecialchars($response) . '</pre></div>';
        }
    } catch (Exception $e) {
        echo '<div class="info error">❌ Error: ' . $e->getMessage() . '</div>';
    }
    ?>
    
    <div class="info">
        <h3>Test Payment:</h3>
        <p>Amount: 100,000 VND (≈ $<?php echo number_format(100000 * VND_TO_USD_RATE, 2); ?> USD)</p>
        <button onclick="window.location.href='paypal.php'">Test PayPal Payment</button>
    </div>
    
    <div class="info">
        <h3>Debug Logs:</h3>
        <?php
        $logFile = __DIR__ . '/logs/paypal.log';
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $lastLogs = array_slice(explode("\n", $logs), -10);
            echo '<pre>' . htmlspecialchars(implode("\n", $lastLogs)) . '</pre>';
        } else {
            echo '<p>No logs found</p>';
        }
        ?>
    </div>
</body>
</html> 