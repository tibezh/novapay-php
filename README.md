# NovaPay PHP Library

PHP library for integration with NovaPay payment system. Supports direct payments, hold payments, and secure purchases with Nova Poshta delivery integration.

[![Latest Version](https://img.shields.io/packagist/v/tibezh/novapay-php.svg)](https://packagist.org/packages/tibezh/novapay-php)
[![License](https://img.shields.io/packagist/l/tibezh/novapay-php.svg)](https://github.com/tibezh/novapay-php/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/tibezh/novapay-php.svg)](https://packagist.org/packages/tibezh/novapay-php)

[Українська версія](README.ua.md) | [Documentation](https://github.com/tibezh/novapay-php/wiki)

## Features

- ✅ Direct payment processing
- ✅ Hold payments with merchant confirmation
- ✅ Secure purchases with Nova Poshta delivery integration
- ✅ RSA signature security
- ✅ Sandbox and production environment support
- ✅ Callback request validation
- ✅ PHP 8.1+ compatibility

## Installation

```bash
composer require tibezh/novapay-php
```

## Quick Start

### 1. Initialize Client

```php
<?php

use Tibezh\NovapayPhp\NovaPay;

// Initialize for sandbox
$novaPay = new NovaPay(
    merchantId: 'your_merchant_id',
    privateKey: '/path/to/your/private.key', // or key content directly
    publicKey: '-----BEGIN PUBLIC KEY-----...', // NovaPay public key
    sandbox: true // false for production
);
```

### 2. Direct Payment

```php
// Create session
$session = $novaPay->createSession([
    'external_id' => 'order_123',
    'amount' => 100.50,
    'currency' => 'UAH',
    'description' => 'Payment for order #123',
    'success_url' => 'https://yoursite.com/success',
    'fail_url' => 'https://yoursite.com/fail',
    'callback_url' => 'https://yoursite.com/callback'
]);

// Add payment (direct charge)
$payment = $novaPay->addPayment($session['session_id'], [
    'use_hold' => false
]);

// Redirect user to payment page
header('Location: ' . $payment['checkout_url']);
```

### 3. Hold Payment (with merchant confirmation)

```php
// Create session and payment with hold
$session = $novaPay->createSession([
    'external_id' => 'order_124',
    'amount' => 250.00,
    'currency' => 'UAH',
    'description' => 'Payment with confirmation',
    'success_url' => 'https://yoursite.com/success',
    'fail_url' => 'https://yoursite.com/fail',
    'callback_url' => 'https://yoursite.com/callback'
]);

$payment = $novaPay->addPayment($session['session_id'], [
    'use_hold' => true
]);

// After order confirmation - charge the funds
$result = $novaPay->completeHold($session['session_id']);

// Or partial charge
$result = $novaPay->completeHold($session['session_id'], 150.00);
```

### 4. Secure Purchase (with Nova Poshta delivery)

```php
$session = $novaPay->createSession([
    'external_id' => 'order_125',
    'amount' => 500.00,
    'currency' => 'UAH',
    'description' => 'Secure purchase',
    'success_url' => 'https://yoursite.com/success',
    'fail_url' => 'https://yoursite.com/fail',
    'callback_url' => 'https://yoursite.com/callback'
]);

$payment = $novaPay->addPayment($session['session_id'], [
    'use_hold' => true,
    'delivery' => [
        'volume_weight' => 0.001, // L/100*W/100*H/100
        'weight' => 0.5, // in kg
        'recipient_city' => 'db5c88d0-391c-11dd-90d9-001a92567626', // City UUID
        'recipient_warehouse' => '1692286e-e1c2-11e3-8c4a-0050568002cf' // Warehouse UUID
    ]
]);

// After goods delivery - confirm the deal
$result = $novaPay->confirmDelivery($session['session_id']);
// Response will contain tracking number: $result['ttn']
```

### 5. Check Payment Status

```php
$status = $novaPay->getStatus($session['session_id']);
echo "Status: " . $status['status']; // paid, holded, failed, etc.
```

### 6. Cancel/Refund

```php
// Cancel hold or refund paid payment
$result = $novaPay->void($session['session_id']);
```

### 7. Handle Callback Requests

```php
// callback.php
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$signature = $_SERVER['HTTP_X_SIGN'] ?? '';

// Verify signature
if ($novaPay->verifyCallback($data, $signature)) {
    // Signature is valid, process data
    $sessionId = $data['session_id'];
    $status = $data['status'];
    
    // Update order status in database
    updateOrderStatus($sessionId, $status);
    
    http_response_code(200);
    echo 'OK';
} else {
    // Invalid signature
    http_response_code(400);
    echo 'Invalid signature';
}
```

## Testing

### Test Cards

| PAN | EXP | CVV | Status |
|-----|-----|-----|--------|
| 5269610000007956 | 05/24 | 755 | Active |
| 4134170000013005 | 11/24 | 704 | Active |
| 4134170000013088 | 11/24 | 045 | Lost |

### Test Nova Poshta Warehouses

```php
// Odessa, warehouse №1
'recipient_city' => 'db5c88d0-391c-11dd-90d9-001a92567626',
'recipient_warehouse' => '1692286e-e1c2-11e3-8c4a-0050568002cf'

// Kyiv, warehouse №4
'recipient_city' => '7b422fc5-e1b8-11e3-8c4a-0050568002cf',
'recipient_warehouse' => '8d5a980d-391c-11dd-90d9-001a92567626'
```

**Note**: Test operations up to 500 UAH don't require OTP confirmation.

## RSA Key Generation

```bash
# Generate private key (2048 bit)
openssl genrsa -out private.key 2048

# Generate public key
openssl rsa -in private.key -pubout -out public.key
```

Send your public key to NovaPay technical specialists.

## Production Setup

1. Set `sandbox: false` in initialization
2. Use production URL: `https://api-ecom.novapay.ua/v1`
3. Get production `merchant_id` from NovaPay
4. Use production NovaPay public key

### Production NovaPay Public Key

```
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtJjoMALd2ywDYK0BCUVS
8hTgkSS6InosHMLe9SC6DLV20ouJggZvBt42X0VlqqN+PvE9xEMnIUW6FDC06D+8
CkfZuYBkt7mFDykeZXhhfWEj94LaoWCc1EvotgZ2y9KxjCNsefRTloctNB5F63dx
TLReatz/dhuSxxPIuuZQYdLBXbfUkxSE4XKb5rREiqBdCpfj1mZ3AliYy9GsmA11
u+n8x2ocCBed6P4WdBnpRuctRU6ed1s7IZu6e1slIlNeyAb7XCEanfK3PisTZcvv
XvN6stL3XuICuOpfVAtyGzzIq2J1h2Ha2ydJY2l1MmmvzyNu/PPZF5WzQ0k08PJU
rwIDAQAB
-----END PUBLIC KEY-----
```

## Error Handling

```php
use Tibezh\NovapayPhp\Exceptions\NovaPayException;
use Tibezh\NovapayPhp\Exceptions\SignatureException;

try {
    $session = $novaPay->createSession($sessionData);
} catch (SignatureException $e) {
    // Signature error
    echo "Signature error: " . $e->getMessage();
} catch (NovaPayException $e) {
    // General NovaPay error
    echo "NovaPay error: " . $e->getMessage();
}
```

## API Methods

| Method | Description | Parameters |
|--------|-------------|------------|
| `createSession()` | Create payment session | Session data array |
| `addPayment()` | Add payment to session | Session ID, payment data |
| `completeHold()` | Complete hold payment | Session ID, amount (optional) |
| `confirmDelivery()` | Confirm delivery for secure purchase | Session ID |
| `void()` | Cancel/refund payment | Session ID |
| `getStatus()` | Get payment status | Session ID |
| `verifyCallback()` | Verify callback signature | Data array, signature |

## Configuration

### Environment Variables

Create `.env` file:

```env
NOVAPAY_MERCHANT_ID=your_merchant_id
NOVAPAY_PRIVATE_KEY_PATH=/path/to/private.key
NOVAPAY_SANDBOX=true
```

### Laravel Service Provider (optional)

```php
// config/novapay.php
return [
    'merchant_id' => env('NOVAPAY_MERCHANT_ID'),
    'private_key' => env('NOVAPAY_PRIVATE_KEY_PATH'),
    'sandbox' => env('NOVAPAY_SANDBOX', true),
    'public_key' => env('NOVAPAY_PUBLIC_KEY', '-----BEGIN PUBLIC KEY-----...')
];
```

## System Requirements

- PHP >= 8.1
- ext-openssl
- ext-curl
- ext-json

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Fix code style
composer cs-fix
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- [NovaPay Documentation](https://novapay.ua/)
- [GitHub Issues](https://github.com/tibezh/novapay-php/issues)
- [Packagist](https://packagist.org/packages/tibezh/novapay-php)

## Examples

More usage examples can be found in the `examples/` directory (coming soon).

---

Made with ❤️ for Ukrainian e-commerce
