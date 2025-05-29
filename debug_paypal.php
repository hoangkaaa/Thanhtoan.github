<?php
// Quick PayPal Debug
require_once 'config/paypal_config.php';

echo "<h2>PayPal Debug Info</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "cURL Enabled: " . (function_exists('curl_init') ? 'Yes' : 'No') . "\n";
echo "SSL Enabled: " . (extension_loaded('openssl') ? 'Yes' : 'No') . "\n";
echo "\nPayPal Config:\n";
echo "Mode: " . (PAYPAL_TEST_MODE ? 'SANDBOX' : 'LIVE') . "\n";
echo "Client ID: " . substr(PAYPAL_CLIENT_ID, 0, 20) . "...\n";
echo "Return URL: " . PAYPAL_RETURN_URL . "\n";
echo "\nTesting API Connection...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . '/v1/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) echo "cURL Error: $error\n";

if ($httpCode === 200) {
    echo "✅ SUCCESS! PayPal API is working.\n";
    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
        echo "Access Token: " . substr($data['access_token'], 0, 30) . "...\n";
    }
} else {
    echo "❌ FAILED! Check credentials.\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
}
echo "</pre>";
?> 