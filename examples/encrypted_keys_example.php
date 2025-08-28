<?php

/**
 * Example of using NovaPay with encrypted private keys
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tibezh\NovapayPhp\NovaPay;
use Tibezh\NovapayPhp\Utils\KeyGenerator;
use Tibezh\NovapayPhp\Exceptions\NovaPayException;

// Example 1: Generate new encrypted key pair
echo "=== Example 1: Generate encrypted key pair ===\n";

try {
    // Generate strong passphrase
    $passphrase = KeyGenerator::generatePassphrase(24);
    echo "Generated passphrase: {$passphrase}\n";

    // Generate encrypted key pair
    $keyPair = KeyGenerator::generateKeyPair(2048, $passphrase);

    echo 'Private key encrypted: ' . ($keyPair['encrypted'] ? 'Yes' : 'No') . "\n";
    echo 'Private key length: ' . strlen($keyPair['private_key']) . " bytes\n";
    echo 'Public key length: ' . strlen($keyPair['public_key']) . " bytes\n";

    // Save keys to files
    file_put_contents(__DIR__ . '/keys/private_encrypted.key', $keyPair['private_key']);
    file_put_contents(__DIR__ . '/keys/public_encrypted.key', $keyPair['public_key']);
    file_put_contents(__DIR__ . '/keys/passphrase.txt', $passphrase);

    echo "Keys saved to files\n\n";

} catch (NovaPayException $e) {
    echo 'Error generating keys: ' . $e->getMessage() . "\n\n";
}

// Example 2: Use NovaPay with encrypted private key
echo "=== Example 2: Using NovaPay with encrypted key ===\n";

try {
    $merchant_id = 'test_merchant_123';
    $private_key_path = __DIR__ . '/keys/private_encrypted.key';
    $passphrase = trim(file_get_contents(__DIR__ . '/keys/passphrase.txt'));

    // NovaPay public key for sandbox
    $novapay_public_key = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtJjoMALd2ywDYK0BCUVS
8hTgkSS6InosHMLe9SC6DLV20ouJggZvBt42X0VlqqN+PvE9xEMnIUW6FDC06D+8
CkfZuYBkt7mFDykeZXhhfWEj94LaoWCc1EvotgZ2y9KxjCNsefRTloctNB5F63dx
TLReatz/dhuSxxPIuuZQYdLBXbfUkxSE4XKb5rREiqBdCpfj1mZ3AliYy9GsmA11
u+n8x2ocCBed6P4WdBnpRuctRU6ed1s7IZu6e1slIlNeyAb7XCEanfK3PisTZcvv
XvN6stL3XuICuOpfVAtyGzzIq2J1h2Ha2ydJY2l1MmmvzyNu/PPZF5WzQ0k08PJU
rwIDAQAB
-----END PUBLIC KEY-----';

    // Initialize NovaPay with encrypted private key
    $novaPay = new NovaPay(
        merchantId: $merchant_id,
        privateKey: $private_key_path,
        publicKey: $novapay_public_key,
        sandbox: true,
        passphrase: $passphrase  // <- This is the key addition!
    );

    echo "NovaPay client created with encrypted private key\n";

    // Test by creating a session
    $session_data = [
      'external_id' => 'encrypted_test_' . time(),
      'amount' => 100.50,
      'currency' => 'UAH',
      'description' => 'Test with encrypted key',
      'success_url' => 'https://example.com/success',
      'fail_url' => 'https://example.com/fail',
      'callback_url' => 'https://example.com/callback'
    ];

    $session = $novaPay->createSession($session_data);
    echo "Session created: {$session['session_id']}\n\n";

} catch (NovaPayException $e) {
    echo 'Error using encrypted key: ' . $e->getMessage() . "\n\n";
}

// Example 3: Validate existing keys
echo "=== Example 3: Key validation ===\n";

try {
    $private_key_content = file_get_contents(__DIR__ . '/keys/private_encrypted.key');
    $passphrase = trim(file_get_contents(__DIR__ . '/keys/passphrase.txt'));

    // Check if key is encrypted
    $isEncrypted = KeyGenerator::isPrivateKeyEncrypted($private_key_content);
    echo 'Key is encrypted: ' . ($isEncrypted ? 'Yes' : 'No') . "\n";

    // Validate with correct passphrase
    $isValid = KeyGenerator::validatePrivateKey($private_key_content, $passphrase);
    echo 'Key valid with passphrase: ' . ($isValid ? 'Yes' : 'No') . "\n";

    // Validate with wrong passphrase
    $isInvalid = KeyGenerator::validatePrivateKey($private_key_content, 'wrong_passphrase');
    echo 'Key valid with wrong passphrase: ' . ($isInvalid ? 'Yes' : 'No') . "\n";

    // Get key information
    $keyInfo = KeyGenerator::getKeyInfo($private_key_content, $passphrase);
    echo 'Key info: ' . json_encode($keyInfo, JSON_PRETTY_PRINT) . "\n\n";

} catch (NovaPayException $e) {
    echo 'Error validating key: ' . $e->getMessage() . "\n\n";
}

// Example 4: Encrypt existing unencrypted key
echo "=== Example 4: Encrypt existing key ===\n";

try {
    // First, let's create an unencrypted key for demonstration
    $unencryptedKeyPair = KeyGenerator::generateKeyPair(2048); // No passphrase
    $unencryptedPrivateKey = $unencryptedKeyPair['private_key'];

    echo "Generated unencrypted key\n";
    echo 'Is encrypted: ' . (KeyGenerator::isPrivateKeyEncrypted($unencryptedPrivateKey) ? 'Yes' : 'No') . "\n";

    // Now encrypt it
    $newPassphrase = 'my_secure_passphrase_2024!';
    $encryptedKey = KeyGenerator::encryptPrivateKey($unencryptedPrivateKey, $newPassphrase);

    echo "Key encrypted successfully\n";
    echo 'Is encrypted: ' . (KeyGenerator::isPrivateKeyEncrypted($encryptedKey) ? 'Yes' : 'No') . "\n";

    // Test the encrypted key
    $isValid = KeyGenerator::validatePrivateKey($encryptedKey, $newPassphrase);
    echo 'Encrypted key is valid: ' . ($isValid ? 'Yes' : 'No') . "\n\n";

} catch (NovaPayException $e) {
    echo 'Error encrypting key: ' . $e->getMessage() . "\n\n";
}

// Example 5: Working with environment variables
echo "=== Example 5: Using environment variables ===\n";

// You can store passphrase in environment variables for security
// export NOVAPAY_PASSPHRASE="your_secure_passphrase"
$env_passphrase = getenv('NOVAPAY_PASSPHRASE');

if ($env_passphrase) {
    echo "Passphrase found in environment variable\n";

    try {
        $novaPay = new NovaPay(
            merchantId: 'merchant_from_env',
            privateKey: '/path/to/encrypted/private.key',
            publicKey: $novapay_public_key,
            sandbox: true,
            passphrase: $env_passphrase
        );

        echo "NovaPay initialized with environment passphrase\n";

    } catch (NovaPayException $e) {
        echo 'Error with environment passphrase: ' . $e->getMessage() . "\n";
    }
} else {
    echo "No passphrase found in environment variable NOVAPAY_PASSPHRASE\n";
    echo "You can set it with: export NOVAPAY_PASSPHRASE=\"your_passphrase\"\n";
}

echo "\n=== Security Best Practices ===\n";
echo "1. Always use strong passphrases (generated with KeyGenerator::generatePassphrase())\n";
echo "2. Store passphrases in environment variables, not in code\n";
echo "3. Use encrypted keys in production environments\n";
echo "4. Regularly rotate your keys and passphrases\n";
echo "5. Keep backups of encrypted keys in secure locations\n";
echo "6. Never commit passphrases to version control\n";

// Example 6: Error handling for wrong passphrases
echo "\n=== Example 6: Error handling ===\n";

try {
    // Try to use wrong passphrase
    $wrong_novaPay = new NovaPay(
        merchantId: 'test_merchant',
        privateKey: __DIR__ . '/keys/private_encrypted.key',
        publicKey: $novapay_public_key,
        sandbox: true,
        passphrase: 'definitely_wrong_passphrase'
    );

} catch (NovaPayException $e) {
    echo 'Expected error with wrong passphrase: ' . $e->getMessage() . "\n";
}

// Clean up - remove test files (optional)
echo "\n=== Cleanup ===\n";
$test_files = [
  __DIR__ . '/keys/private_encrypted.key',
  __DIR__ . '/keys/public_encrypted.key',
  __DIR__ . '/keys/passphrase.txt'
];

foreach ($test_files as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo 'Deleted: ' . basename($file) . "\n";
    }
}

echo "\nExample completed!\n";
