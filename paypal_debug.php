<?php
session_start();
require_once 'config/paypal_config.php';

echo "<h1>PayPal Debug Tool</h1>";
echo "<pre>";

// 1. Check environment
echo "=== ENVIRONMENT CHECK ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "cURL: " . (function_exists('curl_init') ? 'Enabled' : 'DISABLED!') . "\n";
echo "JSON: " . (function_exists('json_decode') ? 'Enabled' : 'DISABLED!') . "\n";
echo "SSL: " . (extension_loaded('openssl') ? 'Enabled' : 'DISABLED!') . "\n\n";

// 2. Check config
echo "=== CONFIGURATION ===\n";
echo "Mode: " . (PAYPAL_TEST_MODE ? 'SANDBOX' : 'PRODUCTION') . "\n";
echo "Client ID: " . PAYPAL_CLIENT_ID . "\n";
echo "Client Secret: " . substr(PAYPAL_CLIENT_SECRET, 0, 10) . "...\n";
echo "Base URL: " . PAYPAL_BASE_URL . "\n";
echo "Return URL: " . PAYPAL_RETURN_URL . "\n";
echo "Cancel URL: " . PAYPAL_CANCEL_URL . "\n\n";

// 3. Test access token
echo "=== TESTING ACCESS TOKEN ===\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . '/v1/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Accept-Language: en_US'
));
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: " . substr($response, 0, 500) . "\n\n";

$tokenData = json_decode($response, true);
$accessToken = isset($tokenData['access_token']) ? $tokenData['access_token'] : null;

if ($accessToken) {
    echo "✅ Access Token obtained successfully!\n";
    echo "Token: " . substr($accessToken, 0, 30) . "...\n\n";
    
    // 4. Test create order
    echo "=== TESTING CREATE ORDER ===\n";
    
    $orderData = array(
        'intent' => 'CAPTURE',
        'purchase_units' => array(
            array(
                'amount' => array(
                    'currency_code' => 'USD',
                    'value' => '1.00'
                ),
                'description' => 'Test Order'
            )
        ),
        'application_context' => array(
            'brand_name' => 'Test Store',
            'return_url' => PAYPAL_RETURN_URL,
            'cancel_url' => PAYPAL_CANCEL_URL
        )
    );
    
    echo "Order Data: " . json_encode($orderData, JSON_PRETTY_PRINT) . "\n\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'PayPal-Request-Id: ' . uniqid()
    ));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($error) {
        echo "cURL Error: $error\n";
    }
    echo "Response:\n" . $response . "\n\n";
    
    if ($httpCode == 201) {
        echo "✅ Order created successfully!\n";
        $order = json_decode($response, true);
        if (isset($order['links'])) {
            foreach ($order['links'] as $link) {
                if ($link['rel'] == 'approve') {
                    echo "Approval URL: " . $link['href'] . "\n";
                }
            }
        }
    } else {
        echo "❌ Failed to create order!\n";
        echo "Please check the response above for error details.\n";
    }
} else {
    echo "❌ Failed to get access token!\n";
    echo "Response details:\n";
    print_r($tokenData);
}

echo "</pre>";

// Add test payment button
if ($accessToken) {
    $_SESSION['payment_info'] = array(
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'phone' => '0123456789',
        'delivery_method' => 'store',
        'user_id' => 1,
        'cart_items' => array(
            array('product_id' => 1, 'name' => 'Test Product', 'price' => 100000, 'quantity' => 1)
        ),
        'cart_total' => 100000,
        'shipping_fee' => 0,
        'discount_amount' => 0,
        'total_amount' => 100000
    );
    
    echo '<hr>';
    echo '<h3>Test Payment:</h3>';
    echo '<button onclick="window.location.href=\'paypal.php\'" style="padding: 10px 20px; background: #0070ba; color: white; border: none; border-radius: 5px; cursor: pointer;">Test PayPal Payment</button>';
}
?> 