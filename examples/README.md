# NovaPay Examples

Simple examples showing how to use the NovaPay PHP library.

## Files

- `config.php` - Configuration for sandbox and production
- `direct_payment.php` - Create direct payment (immediate charge)
- `hold_payment.php` - Create hold payment (charge later)
- `secure_purchase.php` - Create secure purchase with delivery
- `callback_handler.php` - Handle payment status callbacks

## Setup

1. **Generate RSA keys:**
```bash
openssl genrsa -out keys/private.key 2048
openssl rsa -in keys/private.key -pubout -out keys/public.key
```

2. **Update config:**
   Edit `config.php` with your merchant credentials.

3. **Run examples:**
```bash
php direct_payment.php
php hold_payment.php
php secure_purchase.php
```

## Test Cards (Sandbox)

| PAN | EXP | CVV | Status |
|-----|-----|-----|--------|
| 5269610000007956 | 05/24 | 755 | Active |
| 4134170000013005 | 11/24 | 704 | Active |
| 4134170000013088 | 11/24 | 045 | Lost (fails) |

## Test Delivery (Sandbox)

**Kyiv:**
- City: `7b422fc5-e1b8-11e3-8c4a-0050568002cf`
- Warehouse: `8d5a980d-391c-11dd-90d9-001a92567626`

**Odessa:**
- City: `db5c88d0-391c-11dd-90d9-001a92567626`
- Warehouse: `1692286e-e1c2-11e3-8c4a-0050568002cf`

## Usage

### Direct Payment
```php
$novaPay = new NovaPay($merchantId, $privateKey, $publicKey, true);
$session = $novaPay->createSession($sessionData);
$payment = $novaPay->addPayment($session['session_id'], ['use_hold' => false]);
// Redirect to $payment['checkout_url']
```

### Hold Payment
```php
$payment = $novaPay->addPayment($session['session_id'], ['use_hold' => true]);
// Later: $novaPay->completeHold($session['session_id']);
```

### Secure Purchase
```php
$payment = $novaPay->addPayment($session['session_id'], [
    'use_hold' => true,
    'delivery' => [
        'volume_weight' => 0.001,
        'weight' => 0.5,
        'recipient_city' => 'city_uuid',
        'recipient_warehouse' => 'warehouse_uuid'
    ]
]);
// Later: $novaPay->confirmDelivery($session['session_id']);
```

### Callback Handler
```php
$data = json_decode(file_get_contents('php://input'), true);
$signature = $_SERVER['HTTP_X_SIGN'];

if ($novaPay->verifyCallback($data, $signature)) {
    // Process $data['status']
    echo 'OK';
} else {
    http_response_code(403);
}
```
