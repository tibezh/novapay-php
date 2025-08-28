<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Utils;

use Tibezh\NovapayPhp\Exceptions\NovaPayException;

/**
 * Utility class for RSA key generation with optional encryption
 */
class KeyGenerator
{
    /**
     * Generate RSA key pair
     *
     * @param int $keySize Key size in bits (default: 2048)
     * @param string|null $passphrase Optional passphrase to encrypt private key
     * @param string $cipher Cipher for private key encryption (default: AES-256-CBC)
     *
     * @return array{private_key: string, public_key: string, encrypted: bool}
     */
    public static function generateKeyPair(
        int $keySize = 2048,
        ?string $passphrase = null,
        string $cipher = 'AES-256-CBC'
    ): array {
        if ($keySize < 2048) {
            throw new NovaPayException('Key size must be at least 2048 bits for security');
        }

        $config = [
          'digest_alg' => 'sha256',
          'private_key_bits' => $keySize,
          'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Add encryption configuration if passphrase is provided
        if ($passphrase !== null && $passphrase !== '') {
            $config['encrypt_key'] = true;
            $config['encrypt_key_cipher'] = constant('OPENSSL_CIPHER_' . str_replace('-', '_', strtoupper($cipher)));
        }

        $resource = openssl_pkey_new($config);
        if (!$resource) {
            throw new NovaPayException('Failed to generate RSA key pair: ' . openssl_error_string());
        }

        // Export private key with optional passphrase
        $exportSuccess = openssl_pkey_export($resource, $privateKey, $passphrase);
        if (!$exportSuccess) {
            throw new NovaPayException('Failed to export private key: ' . openssl_error_string());
        }

        // Export public key
        $keyDetails = openssl_pkey_get_details($resource);
        if (!$keyDetails) {
            throw new NovaPayException('Failed to export public key: ' . openssl_error_string());
        }

        return [
          'private_key' => $privateKey,
          'public_key' => $keyDetails['key'],
          'encrypted' => $passphrase !== null && $passphrase !== '',
        ];
    }

    /**
     * Encrypt existing private key with passphrase
     *
     * @param string $privateKey Existing private key content
     * @param string $passphrase Passphrase for encryption
     * @param string $cipher Cipher for encryption
     *
     * @return string Encrypted private key
     */
    public static function encryptPrivateKey(
        string $privateKey,
        string $passphrase,
        string $cipher = 'AES-256-CBC'
    ): string {
        if (empty($passphrase)) {
            throw new NovaPayException('Passphrase cannot be empty');
        }

        // Load existing private key
        $resource = openssl_pkey_get_private($privateKey);
        if (!$resource) {
            throw new NovaPayException('Invalid private key: ' . openssl_error_string());
        }

        // Re-export with encryption
        $exportSuccess = openssl_pkey_export(
            $resource,
            $encryptedKey,
            $passphrase,
            [
              'encrypt_key' => true,
              'encrypt_key_cipher' => constant('OPENSSL_CIPHER_' . str_replace('-', '_', strtoupper($cipher))),
            ]
        );

        if (!$exportSuccess) {
            throw new NovaPayException('Failed to encrypt private key: ' . openssl_error_string());
        }

        return $encryptedKey;
    }

    /**
     * Decrypt private key (remove passphrase protection)
     *
     * @param string $encryptedPrivateKey Encrypted private key content
     * @param string $passphrase Passphrase for decryption
     *
     * @return string Decrypted private key
     */
    public static function decryptPrivateKey(string $encryptedPrivateKey, string $passphrase): string
    {
        // Load encrypted private key with passphrase
        $resource = openssl_pkey_get_private($encryptedPrivateKey, $passphrase);
        if (!$resource) {
            throw new NovaPayException('Failed to decrypt private key. Check passphrase: ' . openssl_error_string());
        }

        // Export without encryption
        $exportSuccess = openssl_pkey_export($resource, $decryptedKey);
        if (!$exportSuccess) {
            throw new NovaPayException('Failed to export decrypted private key: ' . openssl_error_string());
        }

        return $decryptedKey;
    }

    /**
     * Validate private key and passphrase combination
     *
     * @param string $privateKey Private key content
     * @param string|null $passphrase Optional passphrase
     *
     * @return bool True if valid
     */
    public static function validatePrivateKey(string $privateKey, ?string $passphrase = null): bool
    {
        $resource = $passphrase ?
          openssl_pkey_get_private($privateKey, $passphrase) :
          openssl_pkey_get_private($privateKey);

        return $resource !== false;
    }

    /**
     * Check if private key is encrypted (requires passphrase)
     *
     * @param string $privateKey Private key content
     *
     * @return bool True if encrypted
     */
    public static function isPrivateKeyEncrypted(string $privateKey): bool
    {
        // Try to load without passphrase
        $resource = openssl_pkey_get_private($privateKey);

        if ($resource === false) {
            // Check if it's due to encryption by examining error messages
            $errorMessages = [];
            while ($error = openssl_error_string()) {
                $errorMessages[] = $error;
            }

            $errorString = implode(' ', $errorMessages);

            // Common error patterns for encrypted keys
            $encryptionPatterns = [
              'bad decrypt',
              'PEM routines',
              'nested asn1 error',
              'asn1 encoding routines',
              'bad base64 decode'
            ];

            foreach ($encryptionPatterns as $pattern) {
                if (stripos($errorString, $pattern) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get key information
     *
     * @param string $privateKey Private key content
     * @param string|null $passphrase Optional passphrase
     *
     * @return array{bits: int, type: string, encrypted: bool}
     */
    public static function getKeyInfo(string $privateKey, ?string $passphrase = null): array
    {
        $resource = $passphrase ?
          openssl_pkey_get_private($privateKey, $passphrase) :
          openssl_pkey_get_private($privateKey);

        if (!$resource) {
            throw new NovaPayException('Cannot load private key. Check key format and passphrase.');
        }

        $details = openssl_pkey_get_details($resource);
        if (!$details) {
            throw new NovaPayException('Cannot get key details: ' . openssl_error_string());
        }

        return [
          'bits' => $details['bits'],
          'type' => $details['type'] === OPENSSL_KEYTYPE_RSA ? 'RSA' : 'Other',
          'encrypted' => self::isPrivateKeyEncrypted($privateKey),
        ];
    }

    /**
     * Generate strong passphrase
     *
     * @param int $length Passphrase length
     * @param bool $includeSpecial Include special characters
     *
     * @return string Generated passphrase
     */
    public static function generatePassphrase(int $length = 32, bool $includeSpecial = true): string
    {
        if ($length < 12) {
            throw new NovaPayException('Passphrase must be at least 12 characters long');
        }

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($includeSpecial) {
            $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        }

        $passphrase = '';
        $maxIndex = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $passphrase .= $chars[random_int(0, $maxIndex)];
        }

        return $passphrase;
    }

}
