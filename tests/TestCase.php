<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected function getTestMerchantId(): string
    {
        return 'test_merchant_123';
    }

    protected function getTestPrivateKey(): string
    {
        // Generate test keys if they don't exist
        $this->ensureTestKeysExist();

        return file_get_contents($this->getTestKeysPath() . '/private.key');
    }

    protected function getTestPublicKey(): string
    {
        // For NovaPay public key (this is the real production key from docs)
        // This key is used for verifying callbacks FROM NovaPay TO merchant
        return '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtJjoMALd2ywDYK0BCUVS
8hTgkSS6InosHMLe9SC6DLV20ouJggZvBt42X0VlqqN+PvE9xEMnIUW6FDC06D+8
CkfZuYBkt7mFDykeZXhhfWEj94LaoWCc1EvotgZ2y9KxjCNsefRTloctNB5F63dx
TLReatz/dhuSxxPIuuZQYdLBXbfUkxSE4XKb5rREiqBdCpfj1mZ3AliYy9GsmA11
u+n8x2ocCBed6P4WdBnpRuctRU6ed1s7IZu6e1slIlNeyAb7XCEanfK3PisTZcvv
XvN6stL3XuICuOpfVAtyGzzIq2J1h2Ha2ydJY2l1MmmvzyNu/PPZF5WzQ0k08PJU
rwIDAQAB
-----END PUBLIC KEY-----';
    }

    protected function getTestMerchantPublicKey(): string
    {
        // For merchant's public key (generated automatically)
        // This key is used for testing merchant's signature creation/verification
        $this->ensureTestKeysExist();

        return file_get_contents($this->getTestKeysPath() . '/public.key');
    }

    protected function getTestSessionData(): array
    {
        return [
          'external_id' => 'test_order_' . time(),
          'amount' => 100.50,
          'currency' => 'UAH',
          'description' => 'Test payment',
          'success_url' => 'https://example.com/success',
          'fail_url' => 'https://example.com/fail',
          'callback_url' => 'https://example.com/callback'
        ];
    }

    protected function getTestDeliveryData(): array
    {
        return [
          'volume_weight' => 0.001,
          'weight' => 0.5,
          'recipient_city' => 'db5c88d0-391c-11dd-90d9-001a92567626',
          'recipient_warehouse' => '1692286e-e1c2-11e3-8c4a-0050568002cf'
        ];
    }

    protected function mockHttpResponse(array $responseData, int $httpCode = 200): string
    {
        return json_encode($responseData);
    }

    private function getTestKeysPath(): string
    {
        return __DIR__ . '/keys';
    }

    private function ensureTestKeysExist(): void
    {
        $keysPath = $this->getTestKeysPath();
        $privateKeyPath = $keysPath . '/private.key';
        $publicKeyPath = $keysPath . '/public.key';

        // Create keys directory if it doesn't exist
        if (!is_dir($keysPath)) {
            mkdir($keysPath, 0755, true);
        }

        // Generate keys if they don't exist
        if (!file_exists($privateKeyPath) || !file_exists($publicKeyPath)) {
            $this->generateTestKeys($privateKeyPath, $publicKeyPath);
        }
    }

    private function generateTestKeys(string $privateKeyPath, string $publicKeyPath): void
    {
        // Generate RSA key pair
        $config = [
          'digest_alg' => 'sha256',
          'private_key_bits' => 2048,
          'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);

        if (!$resource) {
            throw new \RuntimeException('Failed to generate RSA key pair for tests');
        }

        // Export private key
        openssl_pkey_export($resource, $privateKey);
        file_put_contents($privateKeyPath, $privateKey);

        // Export public key
        $keyDetails = openssl_pkey_get_details($resource);
        file_put_contents($publicKeyPath, $keyDetails['key']);
    }
}
