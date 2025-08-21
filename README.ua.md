# NovaPay PHP Library

PHP бібліотека для інтеграції з платіжною системою NovaPay. Підтримує прямі платежі, платежі з утриманням коштів та надійні покупки з доставкою Нова Пошта.

[![Latest Version](https://img.shields.io/packagist/v/tibezh/novapay-php.svg)](https://packagist.org/packages/tibezh/novapay-php)
[![License](https://img.shields.io/packagist/l/tibezh/novapay-php.svg)](https://github.com/tibezh/novapay-php/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/tibezh/novapay-php.svg)](https://packagist.org/packages/tibezh/novapay-php)

[English version](README.md) | [Документація](https://github.com/tibezh/novapay-php/wiki)

## Особливості

- ✅ Пряме списання коштів
- ✅ Списання після підтвердження продавцем (hold)
- ✅ Надійна покупка з інтеграцією Нова Пошта
- ✅ RSA підписи для безпеки
- ✅ Підтримка sandbox та production середовищ
- ✅ Валідація callback запитів
- ✅ PHP 8.3+ сумісність

## Встановлення

```bash
composer require tibezh/novapay-php
```

## Швидкий старт

### 1. Ініціалізація клієнта

```php
<?php

use Tibezh\NovapayPhp\NovaPay;

// Ініціалізація для sandbox
$novaPay = new NovaPay(
    merchantId: 'your_merchant_id',
    privateKey: '/path/to/your/private.key', // або безпосередньо вміст ключа
    publicKey: '-----BEGIN PUBLIC KEY-----...', // публічний ключ NovaPay
    sandbox: true // false для production
);
```

### 2. Пряме списання

```php
// Створення сесії
$session = $novaPay->createSession([
    'external_id' => 'order_123',
    'amount' => 100.50,
    'currency' => 'UAH',
    'description' => 'Оплата замовлення #123',
    'success_url' => 'https://yoursite.com/success',
    'fail_url' => 'https://yoursite.com/fail',
    'callback_url' => 'https://yoursite.com/callback'
]);

// Додавання платежу (пряме списання)
$payment = $novaPay->addPayment($session['session_id'], [
    'use_hold' => false
]);

// Перенаправлення користувача на сторінку оплати
header('Location: ' . $payment['checkout_url']);
```

### 3. Списання після підтвердження (Hold)

```php
// Створення сесії та платежу з hold
$session = $novaPay->createSession([
    'external_id' => 'order_124',
    'amount' => 250.00,
    'currency' => 'UAH',
    'description' => 'Оплата з підтвердженням',
    'success_url' => 'https://yoursite.com/success',
    'fail_url' => 'https://yoursite.com/fail',
    'callback_url' => 'https://yoursite.com/callback'
]);

$payment = $novaPay->addPayment($session['session_id'], [
    'use_hold' => true
]);

// Після підтвердження замовлення - списати кошти
$result = $novaPay->completeHold($session['session_id']);

// Або часткове списання
$result = $novaPay->completeHold($session['session_id'], 150.00);
```

### 4. Надійна покупка (з доставкою Нова Пошта)

```php
$session = $novaPay->createSession([
    'external_id' => 'order_125',
    'amount' => 500.00,
    'currency' => 'UAH',
    'description' => 'Надійна покупка',
    'success_url' => 'https://yoursite.com/success',
    'fail_url' => 'https://yoursite.com/fail',
    'callback_url' => 'https://yoursite.com/callback'
]);

$payment = $novaPay->addPayment($session['session_id'], [
    'use_hold' => true,
    'delivery' => [
        'volume_weight' => 0.001, // Д/100*Ш/100*В/100
        'weight' => 0.5, // в кг
        'recipient_city' => 'db5c88d0-391c-11dd-90d9-001a92567626', // UUID міста
        'recipient_warehouse' => '1692286e-e1c2-11e3-8c4a-0050568002cf' // UUID відділення
    ]
]);

// Після доставки товару - підтвердити угоду
$result = $novaPay->confirmDelivery($session['session_id']);
// У відповіді буде номер ТТН: $result['ttn']
```

### 5. Перевірка статусу платежу

```php
$status = $novaPay->getStatus($session['session_id']);
echo "Статус: " . $status['status']; // paid, holded, failed, etc.
```

### 6. Скасування/повернення

```php
// Скасування hold або повернення оплаченого платежу
$result = $novaPay->void($session['session_id']);
```

### 7. Обробка callback запитів

```php
// callback.php
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$signature = $_SERVER['HTTP_X_SIGN'] ?? '';

// Перевірка підпису
if ($novaPay->verifyCallback($data, $signature)) {
    // Підпис валідний, обробляємо дані
    $sessionId = $data['session_id'];
    $status = $data['status'];
    
    // Оновлюємо статус замовлення в базі даних
    updateOrderStatus($sessionId, $status);
    
    http_response_code(200);
    echo 'OK';
} else {
    // Невалідний підпис
    http_response_code(400);
    echo 'Invalid signature';
}
```

## Тестування

### Тестові картки

| PAN | EXP | CVV | Статус |
|-----|-----|-----|--------|
| 5269610000007956 | 05/24 | 755 | Активна |
| 4134170000013005 | 11/24 | 704 | Активна |
| 4134170000013088 | 11/24 | 045 | Втрачена |

### Тестові відділення Нова Пошта

```php
// Одесса, відділення №1
'recipient_city' => 'db5c88d0-391c-11dd-90d9-001a92567626',
'recipient_warehouse' => '1692286e-e1c2-11e3-8c4a-0050568002cf'

// Київ, відділення №4
'recipient_city' => '7b422fc5-e1b8-11e3-8c4a-0050568002cf',
'recipient_warehouse' => '8d5a980d-391c-11dd-90d9-001a92567626'
```

**Увага**: Тестові операції до 500 грн не вимагають підтвердження OTP.

## Генерація RSA ключів

```bash
# Генерація приватного ключа (2048 біт)
openssl genrsa -out private.key 2048

# Генерація публічного ключа
openssl rsa -in private.key -pubout -out public.key
```

Публічний ключ потрібно передати технічним спеціалістам NovaPay.

## Перехід на production

1. Змініть `sandbox: false` при ініціалізації
2. Використовуйте production URL: `https://api-ecom.novapay.ua/v1`
3. Отримайте production `merchant_id` від NovaPay
4. Використовуйте production публічний ключ NovaPay

### Production публічний ключ NovaPay

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

## Обробка помилок

```php
use Tibezh\NovapayPhp\Exceptions\NovaPayException;
use Tibezh\NovapayPhp\Exceptions\SignatureException;

try {
    $session = $novaPay->createSession($sessionData);
} catch (SignatureException $e) {
    // Помилка з підписом
    echo "Помилка підпису: " . $e->getMessage();
} catch (NovaPayException $e) {
    // Загальна помилка NovaPay
    echo "Помилка NovaPay: " . $e->getMessage();
}
```

## API методи

| Метод | Опис | Параметри |
|-------|------|-----------|
| `createSession()` | Створення платіжної сесії | Масив даних сесії |
| `addPayment()` | Додавання платежу до сесії | ID сесії, дані платежу |
| `completeHold()` | Завершення hold платежу | ID сесії, сума (опціонально) |
| `confirmDelivery()` | Підтвердження доставки | ID сесії |
| `void()` | Скасування/повернення | ID сесії |
| `getStatus()` | Отримання статусу | ID сесії |
| `verifyCallback()` | Перевірка підпису callback | Масив даних, підпис |

## Конфігурація

### Змінні середовища

Створіть файл `.env`:

```env
NOVAPAY_MERCHANT_ID=your_merchant_id
NOVAPAY_PRIVATE_KEY_PATH=/path/to/private.key
NOVAPAY_SANDBOX=true
```

### Laravel Service Provider (опціонально)

```php
// config/novapay.php
return [
    'merchant_id' => env('NOVAPAY_MERCHANT_ID'),
    'private_key' => env('NOVAPAY_PRIVATE_KEY_PATH'),
    'sandbox' => env('NOVAPAY_SANDBOX', true),
    'public_key' => env('NOVAPAY_PUBLIC_KEY', '-----BEGIN PUBLIC KEY-----...')
];
```

## Системні вимоги

- PHP >= 8.3
- ext-openssl
- ext-curl
- ext-json

## Розробка

```bash
# Встановлення залежностей
composer install

# Запуск тестів
composer test

# Виправлення стилю коду
composer lint-fix
```

## Внесок у розробку

1. Зробіть fork репозиторію
2. Створіть feature branch (`git checkout -b feature/amazing-feature`)
3. Закомітьте зміни (`git commit -m 'Add amazing feature'`)
4. Push у branch (`git push origin feature/amazing-feature`)
5. Відкрийте Pull Request

## Список змін

Дивіться [CHANGELOG.md](CHANGELOG.md) для деталей.

## Ліцензія

Цей проєкт ліцензований під MIT License - дивіться файл [LICENSE](LICENSE) для деталей.

## Підтримка

- [Документація NovaPay](https://novapay.ua/)
- [GitHub Issues](https://github.com/tibezh/novapay-php/issues)
- [Packagist](https://packagist.org/packages/tibezh/novapay-php)

## Приклади

Більше прикладів використання можна знайти в папці `examples/`.

---

Зроблено з ❤️ для української електронної комерції
