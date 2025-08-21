<?php


/**
 * Simple Secure Purchase Example
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

  echo "Creating secure purchase with delivery...\n";

  // Step 1: Create session
  $session = $novaPay->createSession([
    'external_id' => 'order_' . time(),
    'amount' => 1200.00,
    'currency' => 'UAH',
    'description' => 'Laptop with delivery',
    'success_url' => 'https://yoursite.com/success',
    'fail_url' => 'https://yoursite.com/fail',
    'callback_url' => 'https://yoursite.com/callback'
  ]);

  echo "Session created: {$session['session_id']}\n";

  // Step 2: Add secure purchase with delivery
  $payment = $novaPay->addPayment($session['session_id'], [
    'use_hold' => true,
    'delivery' => [
      'volume_weight' => 0.014, // 50*35*8 cm = 0.014 m³
      'weight' => 2.5,
      'recipient_city' => '7b422fc5-e1b8-11e3-8c4a-0050568002cf', // Kyiv
      'recipient_warehouse' => '8d5a980d-391c-11dd-90d9-001a92567626' // Warehouse #4
    ]
  ]);

  echo "Payment URL: {$payment['checkout_url']}\n";
  echo "After customer pays, funds will be held.\n\n";

  // Later: Confirm delivery (creates shipping label)
  echo "To confirm delivery and create TTN, use:\n";
  echo "\$result = \$novaPay->confirmDelivery('{$session['session_id']}');\n";
  echo "// TTN will be in \$result['ttn']\n";

} catch (NovaPayException $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
