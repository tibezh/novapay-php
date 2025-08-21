<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp;

use Tibezh\NovapayPhp\Security\Signature;
use Tibezh\NovapayPhp\Exceptions\NovaPayException;

/**
 * Main NovaPay client class
 */
class NovaPay
{
    private const SANDBOX_URL = 'https://api-qecom.novapay.ua/v1';
    private const PRODUCTION_URL = 'https://api-ecom.novapay.ua/v1';

    private string $merchantId;
    private string $privateKey;
    private string $publicKey;
    private string $baseUrl;
    private Signature $signature;

    /**
     * @param string $merchantId Merchant identifier
     * @param string $privateKey Path to private key file or key content
     * @param string $publicKey NovaPay public key content
     * @param bool $sandbox Use sandbox environment
     */
    public function __construct(
        string $merchantId,
        string $privateKey,
        string $publicKey,
        bool $sandbox = true
    ) {
        $this->merchantId = $merchantId;
        $this->privateKey = $this->loadKey($privateKey);
        $this->publicKey = $publicKey;
        $this->baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
        $this->signature = new Signature($this->privateKey, $this->publicKey);
    }

    /**
     * Create payment session
     */
    public function createSession(array $data): array
    {
        $data['merchant_id'] = $this->merchantId;
        return $this->makeRequest('/session', $data);
    }

    /**
     * Add payment to session
     */
    public function addPayment(string $sessionId, array $data): array
    {
        $data['session_id'] = $sessionId;
        return $this->makeRequest('/payment', $data);
    }

    /**
     * Complete hold payment
     */
    public function completeHold(string $sessionId, ?float $amount = null): array
    {
        $data = ['session_id' => $sessionId];
        if ($amount !== null) {
            $data['amount'] = $amount;
        }
        return $this->makeRequest('/complete-hold', $data);
    }

    /**
     * Confirm delivery for secure purchase
     */
    public function confirmDelivery(string $sessionId): array
    {
        return $this->makeRequest('/confirm-delivery-hold', [
          'session_id' => $sessionId
        ]);
    }

    /**
     * Void (cancel/refund) payment
     */
    public function void(string $sessionId): array
    {
        return $this->makeRequest('/void', [
          'session_id' => $sessionId
        ]);
    }

    /**
     * Get payment status
     */
    public function getStatus(string $sessionId): array
    {
        return $this->makeRequest('/get-status', [
          'session_id' => $sessionId
        ]);
    }

    /**
     * Verify callback signature
     */
    public function verifyCallback(array $data, string $signature): bool
    {
        return $this->signature->verifySignature($data, $signature);
    }

    /**
     * Make HTTP request to NovaPay API
     */
    private function makeRequest(string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint;
        $signature = $this->signature->createSignature($data);

        $headers = [
          'Content-Type: application/json',
          'x-sign: ' . $signature
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
          CURLOPT_URL => $url,
          CURLOPT_POST => true,
          CURLOPT_POSTFIELDS => json_encode($data),
          CURLOPT_HTTPHEADER => $headers,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_SSL_VERIFYPEER => true,
          CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new NovaPayException('CURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new NovaPayException(
                'HTTP error ' . $httpCode . ': ' . ($decodedResponse['message'] ?? $response)
            );
        }

        return $decodedResponse;
    }

    /**
     * Load private key from file or string
     */
    private function loadKey(string $key): string
    {
        if (file_exists($key)) {
            return file_get_contents($key);
        }
        return $key;
    }
}
