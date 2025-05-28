<?php
// MoMo Payment Configuration

// Test Environment
define('MOMO_TEST_MODE', true);

if (MOMO_TEST_MODE) {
    define('MOMO_PARTNER_CODE', 'MOMOBKUN20180529');
    define('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j');
    define('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa');
    define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
} else {
    // Production Environment - Replace with your actual production credentials
    define('MOMO_PARTNER_CODE', 'YOUR_PRODUCTION_PARTNER_CODE');
    define('MOMO_ACCESS_KEY', 'YOUR_PRODUCTION_ACCESS_KEY');
    define('MOMO_SECRET_KEY', 'YOUR_PRODUCTION_SECRET_KEY');
    define('MOMO_ENDPOINT', 'https://payment.momo.vn/v2/gateway/api/create');
}

// Common Configuration
define('MOMO_PARTNER_NAME', 'Sunkissed');
define('MOMO_STORE_ID', 'SunkissedStore');
define('MOMO_LANG', 'vi');
define('MOMO_REQUEST_TYPE', 'captureWallet');

// Get base URL for redirects
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . $domain . dirname(dirname($_SERVER['PHP_SELF']));

define('MOMO_REDIRECT_URL', $baseUrl . '/payment_return.php');
define('MOMO_IPN_URL', $baseUrl . '/momo_ipn.php'); 