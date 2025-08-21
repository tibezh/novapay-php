<?php

/**
 * Simple Hold Payment Example
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tibezh\NovapayPhp\NovaPay;
use Tibezh\NovapayPhp\Exceptions\NovaPayException;

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
    $novaPay = new NovaPay(
        merchantId: $config['merchant_id'],
        privateKey: $config['private_key'],
        publicKey: $config['public_key'],
        sandbox: $config['sandbox']
    );

    echo "Creating hold payment...\n";

    // Step 1: Create session
    $session = $novaPay->createSession([
      'external_id' => 'booking_' . time(),
      'amount' => 250.00,
      'currency' => 'UAH',
      'description' => 'Hotel booking',
      'success_url' => 'https://yoursite.com/success',
      'fail_url' => 'https://yoursite.com/fail',
      'callback_url' => 'https://yoursite.com/callback'
    ]);

    echo "Session created: {$session['session_id']}\n";

    // Step 2: Add hold payment
    $payment = $novaPay->addPayment($session['session_id'], [
      'use_hold' => true
    ]);

    echo "Payment URL: {$payment['checkout_url']}\n";
    echo "After customer pays, funds will be held.\n\n";

    // Later: Complete the hold (charge the funds)
    echo "To complete the payment later, use:\n";
    echo "\$novaPay->completeHold('{$session['session_id']}');\n\n";

    // Or cancel the hold
    echo "To cancel the hold, use:\n";
    echo "\$novaPay->void('{$session['session_id']}');\n";

} catch (NovaPayException $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
