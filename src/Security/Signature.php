<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Security;

use Tibezh\NovapayPhp\Exceptions\SignatureException;

/**
 * Class for handling RSA signatures
 */
class Signature
{
  private string $privateKey;
  private string $publicKey;

  public function __construct(string $privateKey, string $publicKey)
  {
    $this->privateKey = $privateKey;
    $this->publicKey = $publicKey;
  }

  /**
   * Create signature for request data
   */
  public function createSignature(array $data): string
  {
    $dataString = $this->arrayToString($data);

    $privateKeyResource = openssl_pkey_get_private($this->privateKey);
    if (!$privateKeyResource) {
      throw new SignatureException('Invalid private key');
    }

    $signature = '';
    $success = openssl_sign($dataString, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);

    if (!$success) {
      throw new SignatureException('Failed to create signature');
    }

    return base64_encode($signature);
  }

  /**
   * Verify signature from NovaPay callback
   */
  public function verifySignature(array $data, string $signature): bool
  {
    $dataString = $this->arrayToString($data);
    $signatureDecoded = base64_decode($signature);

    $publicKeyResource = openssl_pkey_get_public($this->publicKey);
    if (!$publicKeyResource) {
      throw new SignatureException('Invalid public key');
    }

    $result = openssl_verify($dataString, $signatureDecoded, $publicKeyResource, OPENSSL_ALGO_SHA256);

    return $result === 1;
  }

  /**
   * Convert array to string for signature
   */
  private function arrayToString(array $data): string
  {
    // Remove signature field if present
    unset($data['x-sign']);

    // Sort by keys
    ksort($data);

    // Convert to string
    $parts = [];
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $value = $this->arrayToString($value);
      } elseif (is_bool($value)) {
        $value = $value ? 'true' : 'false';
      } elseif ($value === null) {
        $value = '';
      } else {
        $value = (string) $value;
      }

      $parts[] = $key . '=' . $value;
    }

    return implode('&', $parts);
  }
}
