<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Security;

use Tibezh\NovapayPhp\Exceptions\SignatureException;

/**
 * Class for handling RSA signatures with optional passphrase support
 */
class Signature
{
    private string $privateKey;
    private string $publicKey;
    private ?string $passphrase;

    /**
     * @param string $privateKey Private key content
     * @param string $publicKey Public key content
     * @param string|null $passphrase Passphrase for encrypted private key
     */
    public function __construct(string $privateKey, string $publicKey, ?string $passphrase = null)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        $this->passphrase = $passphrase;
    }

    /**
     * Create signature for request data
     *
     * @param array<string, mixed> $data
     */
    public function createSignature(array $data): string
    {
        // Remove x-sign field if present
        unset($data['x-sign']);

        // Convert to JSON (as expected by NovaPay)
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE);

        $privateKeyResource = $this->loadPrivateKey();

        if (!$privateKeyResource) {
            throw new SignatureException('Invalid private key or incorrect passphrase');
        }

        $signature = '';
        $success = openssl_sign($dataString, $signature, $privateKeyResource, OPENSSL_ALGO_SHA1);

        if (!$success) {
            throw new SignatureException('Failed to create signature');
        }

        return base64_encode($signature);
    }

    /**
     * Verify signature from NovaPay callback
     *
     * @param array<string, mixed> $data
     */
    public function verifySignature(array $data, string $signature): bool
    {
        // Remove x-sign field if present
        unset($data['x-sign']);

        // Convert to JSON for verification
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE);
        $signatureDecoded = base64_decode($signature);

        $publicKeyResource = openssl_pkey_get_public($this->publicKey);
        if (!$publicKeyResource) {
            throw new SignatureException('Invalid public key');
        }

        $result = openssl_verify($dataString, $signatureDecoded, $publicKeyResource, OPENSSL_ALGO_SHA1);

        return $result === 1;
    }

    /**
     * Load private key with optional passphrase
     *
     * @return \OpenSSLAsymmetricKey|false
     */
    private function loadPrivateKey()
    {
        if ($this->passphrase !== null && $this->passphrase !== '') {
            // Load encrypted private key with passphrase
            return openssl_pkey_get_private($this->privateKey, $this->passphrase);
        } else {
            // Load unencrypted private key
            return openssl_pkey_get_private($this->privateKey);
        }
    }

    /**
     * Validate private key and passphrase combination
     *
     * @throws SignatureException
     */
    public function validatePrivateKey(): bool
    {
        $privateKeyResource = $this->loadPrivateKey();

        if (!$privateKeyResource) {
            if ($this->passphrase !== null) {
                throw new SignatureException('Invalid private key or incorrect passphrase');
            } else {
                throw new SignatureException('Invalid private key');
            }
        }

        return true;
    }

    /**
     * Check if private key is encrypted
     */
    public function isPrivateKeyEncrypted(): bool
    {
        // Try to load without passphrase
        $resource = openssl_pkey_get_private($this->privateKey);

        if ($resource === false) {
            // Check if it's due to encryption
            $errors = [];
            while ($error = openssl_error_string()) {
                $errors[] = $error;
            }

            // Look for encryption-related error messages
            $errorString = implode(' ', $errors);
            if (str_contains($errorString, 'bad decrypt')
              || str_contains($errorString, 'PEM routines')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate test signature to validate configuration
     */
    public function createTestSignature(): array
    {
        $testData = [
          'test' => true,
          'timestamp' => time(),
          'merchant_id' => 'test_merchant',
          'amount' => 100.00,
        ];

        $signature = $this->createSignature($testData);

        return [
          'signature' => $signature,
          'data' => $testData,
        ];
    }

}
