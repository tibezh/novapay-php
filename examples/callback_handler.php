<?php

/**
 * Simple Callback Handler
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tibezh\NovapayPhp\NovaPay;

// Configuration
$config = [
  'merchant_id' => 'test_merchant_123',
  'private_key' => __DIR__ . '/keys/private.key',
  'public_key' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtJjoMALd2ywDYK0BCUVS
8hTgkSS6InosHMLe9SC6DLV20ouJggZvBt42X0VlqqN+PvE9xEMnIUW6FDC06D+8
CkfZuYBkt7mFDykeZXhhfWEj94LaoWCc1EvotgZ2y9KxjCNsefRTloctNB5F63dx
TLReatz/dhuSxxPIuuZQYdLBXbfUkxSE4XKb5rREiqBdCpfj1mZ3AliYy9GsmA11
u+n8x2ocCBed6P4WdBnpRuctRU6ed1s7IZu6e1slIlNeyAb7XCEanfK3PisTZcvv
XvN6stL3XuICuOpfVAtyGzzIq2J1h2Ha2ydJY2l1MmmvzyNu/PPZF5WzQ0k08PJU
rwIDAQAB
-----END PUBLIC KEY-----',
  'sandbox' => true
];

try {
    // Initialize NovaPay
    $novaPay = new NovaPay(
        merchantId: $config['merchant_id'],
        privateKey: $config['private_key'],
        publicKey: $config['public_key'],
        sandbox: $config['sandbox']
    );

    // Get callback data
    $input = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_SIGN'] ?? '';

    if (empty($input) || empty($signature)) {
        http_response_code(400);
        echo 'Bad Request';
        exit;
    }

    $data = json_decode($input, true);
    if (!$data) {
        http_response_code(400);
        echo 'Invalid JSON';
        exit;
    }

    // Verify signature
    if (!$novaPay->verifyCallback($data, $signature)) {
        http_response_code(403);
        echo 'Invalid signature';
        exit;
    }

    // Process the callback
    $sessionId = $data['session_id'];
    $orderId = $data['external_id'];
    $status = $data['status'];
    $amount = $data['amount'] ?? null;

    // Log the callback (replace with your logging)
    error_log("Payment callback: Order {$orderId}, Status: {$status}, Amount: {$amount}");

    // Update order status based on payment status
    switch ($status) {
        case 'paid':
            // Payment successful - fulfill order
            error_log("Order {$orderId} paid successfully");
            break;

        case 'holded':
            // Funds held - waiting for confirmation
            error_log("Order {$orderId} payment held");
            break;

        case 'failed':
            // Payment failed
            error_log("Order {$orderId} payment failed");
            break;

        case 'expired':
            // Payment session expired
            error_log("Order {$orderId} payment expired");
            break;

        case 'voided':
            // Payment cancelled/refunded
            error_log("Order {$orderId} payment voided");
            break;
    }

    // Return success response
    http_response_code(200);
    echo 'OK';

} catch (Exception $e) {
    error_log('Callback error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error';
}
