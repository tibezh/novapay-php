<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp;

use Tibezh\NovapayPhp\Security\Signature;
use Tibezh\NovapayPhp\Exceptions\NovaPayException;
use Tibezh\NovapayPhp\Exceptions\ApiException;

/**
 * Main NovaPay client class
 */
final class NovaPay
{

    private const string SANDBOX_URL = 'https://api-qecom.novapay.ua';
    private const string PRODUCTION_URL = 'https://api-ecom.novapay.ua';
    private const string API_VERSION = '/v1';

    private string $merchantId;
    private string $privateKey;
    private ?string $passphrase;
    private string $publicKey;
    private string $baseUrl;
    private Signature $signature;

    /**
     * @param string $merchantId Merchant identifier
     * @param string $privateKey Path to private key file or key content
     * @param string $publicKey NovaPay public key content
     * @param bool $sandbox Use sandbox environment
     * @param string|null $passphrase Passphrase for private key (if encrypted)
     */
    public function __construct(
        string $merchantId,
        string $privateKey,
        string $publicKey,
        bool $sandbox = true,
        ?string $passphrase = null
    ) {

        if ($merchantId === '') {
            throw new NovaPayException('Merchant ID cannot be empty');
        }

        $this->merchantId = $merchantId;
        $this->passphrase = $passphrase;

        $privateKey = $this->loadKey($privateKey);
        if (!$privateKey) {
            throw new NovaPayException('Private key not found');
        }
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        $this->baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;

        // Pass passphrase to Signature class
        $this->signature = new Signature($this->privateKey, $this->publicKey, $this->passphrase);
    }

    /**
     * Create payment session
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function createSession(array $data): array
    {
        $data['merchant_id'] = $this->merchantId;
        return $this->makeJsonRequest('/session', $data);
    }

    /**
     * Expire checkout session
     *
     * @param string $sessionId
     *
     * @return array<string, mixed>
     */
    public function expireSession(string $sessionId): array
    {
        return $this->makeJsonRequest('/expire', [
          'merchant_id' => $this->merchantId,
          'session_id' => $sessionId
        ]);
    }

    /**
     * Add payment to session
     *
     * @param string $sessionId
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function addPayment(string $sessionId, array $data): array
    {
        $data['merchant_id'] = $this->merchantId;
        $data['session_id'] = $sessionId;
        return $this->makeJsonRequest('/payment', $data);
    }

    /**
     * Complete hold payment
     *
     * @return array<string, mixed>
     */
    public function completeHold(string $sessionId, ?float $amount = null): array
    {
        $data = [
          'merchant_id' => $this->merchantId,
          'session_id' => $sessionId
        ];

        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        return $this->makeJsonRequest('/complete-hold', $data);
    }

    /**
     * Confirm delivery for secure purchase
     *
     * @return array<string, mixed>
     */
    public function confirmDelivery(string $sessionId): array
    {
        return $this->makeJsonRequest('/confirm-delivery-hold', [
          'merchant_id' => $this->merchantId,
          'session_id' => $sessionId
        ]);
    }

    /**
     * Print express waybill
     *
     * @return string Binary PDF content
     */
    public function printExpressWaybill(string $sessionId): string
    {
        $data = [
          'merchant_id' => $this->merchantId,
          'session_id' => $sessionId
        ];

        return $this->makeBinaryRequest('/print-express-waybill', $data);
    }

    /**
     * Void (cancel/refund) session
     *
     * @return array<string, mixed>
     */
    public function void(string $sessionId): array
    {
        return $this->makeJsonRequest('/void', [
          'merchant_id' => $this->merchantId,
          'session_id' => $sessionId
        ]);
    }

    /**
     * Get payment status
     *
     * @return array<string, mixed>
     */
    public function getStatus(string $sessionId): array
    {
        return $this->makeJsonRequest('/get-status', [
          'merchant_id' => $this->merchantId,
          'session_id' => $sessionId
        ]);
    }

    /**
     * Get delivery price
     *
     * @param array<string, mixed> $deliveryData
     *
     * @return array<string, mixed>
     */
    public function getDeliveryPrice(array $deliveryData): array
    {
        $deliveryData['merchant_id'] = $this->merchantId;
        return $this->makeJsonRequest('/delivery-price', $deliveryData);
    }

    /**
     * Initialize frames payment
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function initFrames(array $data): array
    {
        $data['merchant_id'] = $this->merchantId;
        return $this->makeJsonRequest('/frames/init', $data);
    }

    /**
     * Create checkout session
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function createCheckoutSession(array $data): array
    {
        $data['merchant_id'] = $this->merchantId;
        return $this->makeJsonRequest('/checkout/session', $data);
    }

    /**
     * Add checkout payment
     *
     * @param string $sessionId
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function addCheckoutPayment(string $sessionId, array $data): array
    {
        $data['merchant_id'] = $this->merchantId;
        $data['session_id'] = $sessionId;
        return $this->makeJsonRequest('/checkout/payment', $data);
    }

    /**
     * Export operations
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function exportOperations(array $data): array
    {
        $data['merchant_id'] = $this->merchantId;
        return $this->makeJsonRequest('/export-operations', $data);
    }

    /**
     * Verify callback signature
     *
     * @param array<string, mixed> $data
     */
    public function verifyCallback(array $data, string $signature): bool
    {
        return $this->signature->verifySignature($data, $signature);
    }

    /**
     * Make JSON request to NovaPay API
     *
     * @param string $endpoint
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function makeJsonRequest(string $endpoint, array $data): array
    {
        $result = $this->makeRequest($endpoint, $data);
        if (!is_array($result)) {
            throw new NovaPayException('Expected array response for JSON request');
        }
        return $result;
    }

    /**
     * Make binary request to NovaPay API
     *
     * @param string $endpoint
     * @param array<string, mixed> $data
     *
     * @return string
     */
    private function makeBinaryRequest(string $endpoint, array $data, string $contentType = 'text/plain'): string
    {
        $result = $this->makeRequest($endpoint, $data, $contentType);
        if (!is_string($result)) {
            throw new NovaPayException('Expected string response for binary request');
        }
        return $result;
    }

    /**
     * Make HTTP request to NovaPay API
     *
     * @param string $endpoint
     * @param array<string, mixed> $data
     * @param string $expectedContentType Expected response content type
     *
     * @return array<string, mixed>|string
     */
    private function makeRequest(string $endpoint, array $data, string $expectedContentType = 'application/json')
    {
        $url = $this->buildUrl($endpoint);
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
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        if (curl_error($ch)) {
            throw new NovaPayException('CURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        if (($httpCode < 200 || $httpCode >= 300) && is_string($contentType)) {
            $errorData = null;
            if (str_contains($contentType, 'application/json')) {
                $errorData = json_decode((string) $response, true);
            }

            // Create an enhanced API exception
            if ($errorData && is_array($errorData)) {
                throw ApiException::fromApiResponse($errorData, $httpCode);
            } else {
                throw new NovaPayException('HTTP error ' . $httpCode . ': ' . $response);
            }
        }

        // For binary responses (like PDF)
        if ($expectedContentType === 'text/plain') {
            $stringResponse = (string) $response;
            if (empty($stringResponse)) {
                throw new NovaPayException('Empty response for binary request');
            }
            return $stringResponse;
        }

        // For JSON responses
        $decodedResponse = json_decode((string) $response, true);
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new NovaPayException('Invalid JSON response: ' . json_last_error_msg());
        }

        return $decodedResponse;
    }

    /**
     * Load private key from file or string
     */
    private function loadKey(string $key): ?string
    {
        if (file_exists($key)) {
            $contents = file_get_contents($key);
            return is_string($contents) ? $contents : null;
        }

        if (str_contains($key, 'BEGIN') && str_contains($key, 'END')) {
            return $key;
        }

        if (str_contains($key, '/') || str_contains($key, '\\')) {
            return null;
        }

        return $key;
    }

    /**
     * Generate a cURL command for making a POST request with the provided endpoint and data.
     *
     * @param string $endpoint The API endpoint the request is targeting.
     * @param array<string, mixed> $data The data to be included in the request payload.
     *
     * @return string The formatted cURL command as a string.
     */
    public function getCurlCommand(string $endpoint, array $data): string
    {
        $data['merchant_id'] = $this->merchantId;
        $url = $this->buildUrl($endpoint);
        $signature = $this->signature->createSignature($data);
        $json = json_encode($data);

        return "curl -X POST '{$url}' \\\n" .
          "  -H 'Content-Type: application/json' \\\n" .
          "  -H 'x-sign: {$signature}' \\\n" .
          "  -d '{$json}' \\\n" .
          "  --verbose \\\n" .
          "  --include \\\n" .
          '  --write-out "\\n\\n=== TIMING INFO ===\\nHTTP: %{http_code}\\nTime: %{time_total}s\\nSize: %{size_download}b\\n"';
    }

    /**
     * Get merchant ID
     */
    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    /**
     * Check if running in sandbox mode
     */
    public function isSandbox(): bool
    {
        return $this->baseUrl === self::SANDBOX_URL;
    }

    /**
     * Builds a full URL by appending the endpoint to the base URL and API version.
     *
     * @param string $endpoint The API endpoint to be appended.
     *
     * @return string The constructed full URL.
     */
    private function buildUrl(string $endpoint): string
    {
        return $this->baseUrl . self::API_VERSION . $endpoint;
    }

}
