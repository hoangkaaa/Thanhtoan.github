<?php
// PayPal Configuration - FIXED VERSION

// Debug mode
if (!defined('PAYPAL_DEBUG')) {
    define('PAYPAL_DEBUG', true);
}

// Test mode
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

// FIXED: Return URLs - Use ngrok or proper domain for development
// For local development, use ngrok or replace with your actual domain
$protocol = 'http://';
$host = 'localhost';
$baseUrl = $protocol . $host;

define('PAYPAL_RETURN_URL', $baseUrl . '/paypal_return.php');
define('PAYPAL_CANCEL_URL', $baseUrl . '/payment.php?cancelled=1');
?> 