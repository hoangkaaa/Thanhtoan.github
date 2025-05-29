<?php
// PayPal Configuration - PRODUCTION READY VERSION

// Debug mode
if (!defined('PAYPAL_DEBUG')) {
    define('PAYPAL_DEBUG', true);
}

// Test mode - Keep in sandbox for testing
define('PAYPAL_TEST_MODE', true);

// Credentials
define('PAYPAL_CLIENT_ID', 'AWwE9QxUut8lxWsPpK31uZZIQQcomkkpxwH8gUjobkYcD0qNUf9115mHBCzo2BIB_hdAjB7h51rPGU2c');
define('PAYPAL_CLIENT_SECRET', 'EKn_jhKnKJcUuD_i7FdUhDKoyZHiVYA_47qiVZMBH8dZ3VprXOPQvOIfuQN_lLaluqDtCNV-tb5SHvTe');

// URLs
if (PAYPAL_TEST_MODE) {
    define('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com');
} else {
    define('PAYPAL_BASE_URL', 'https://api-m.paypal.com');
}

// Currency
define('PAYPAL_CURRENCY', 'USD');
define('VND_TO_USD_RATE', 0.000041);

// FIXED: Use ngrok for development or replace with your domain
// 🔥 OPTION 1: Use ngrok (recommended for testing)
// First install ngrok: https://ngrok.com/download
// Then run: ngrok http 80
// Copy the https URL (e.g., https://abc123.ngrok.io)

$protocol = 'https://';
$host = 'abc123.ngrok.io'; // 🔥 Replace with your ngrok URL or domain

// 🔥 OPTION 2: For live server, use your real domain
// $host = 'yourwebsite.com';

$baseUrl = $protocol . $host;

define('PAYPAL_RETURN_URL', $baseUrl . '/paypal_return.php');
define('PAYPAL_CANCEL_URL', $baseUrl . '/payment.php?cancelled=1');

// Validate URLs
if (strpos(PAYPAL_RETURN_URL, 'localhost') !== false) {
    die('❌ ERROR: PayPal không chấp nhận localhost URLs. Vui lòng dùng ngrok hoặc domain thực!');
}
?> 