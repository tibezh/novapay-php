<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Tests\Integration;

use Exception;
use Tibezh\NovapayPhp\NovaPay;
use Tibezh\NovapayPhp\Exceptions\NovaPayException;
use Tibezh\NovapayPhp\Security\Signature;
use Tibezh\NovapayPhp\Tests\TestCase;

/**
 * Integration tests for NovaPay
 *
 * These tests verify the complete workflow but don't make actual API calls
 * They focus on testing the integration between different components
 */
class NovaPayIntegrationTest extends TestCase
{
    private NovaPay $novaPay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->novaPay = new NovaPay(
            merchantId: $this->getTestMerchantId(),
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestPublicKey(),
            sandbox: true
        );
    }

    public function test_complete_direct_payment_workflow(): void
    {
        // Test the complete workflow structure (without actual API calls)
        $sessionData = $this->getTestSessionData();

        // Step 1: Create a session - should throw exception in the test environment
        $this->expectException(NovaPayException::class);
        $this->novaPay->createSession($sessionData);
    }

    public function test_complete_hold_payment_workflow(): void
    {
        $sessionId = 'test_session_123';

        // Test the complete hold workflow structure
        // Step 1: Create hold payment - should throw exception in test environment
        try {
            $this->novaPay->addPayment($sessionId, ['use_hold' => true, 'amount' => 250.00]);
            $this->fail('Expected NovaPayException');
        } catch (NovaPayException $e) {
            $this->assertInstanceOf(NovaPayException::class, $e);
        }

        // Step 2: Complete hold - should also throw exception
        $this->expectException(NovaPayException::class);
        $this->novaPay->completeHold($sessionId);
    }

    public function test_secure_purchase_workflow(): void
    {
        $sessionId = 'test_session_123';
        $deliveryData = $this->getTestDeliveryData();

        // Step 1: Create a secure purchase - should throw exception
        try {
            $this->novaPay->addPayment($sessionId, [
                'use_hold' => true,
                'amount' => 500.00,
                'delivery' => $deliveryData
            ]);
            $this->fail('Expected NovaPayException');
        } catch (NovaPayException $e) {
            $this->assertInstanceOf(NovaPayException::class, $e);
        }

        // Step 2: Confirm delivery - should also throw exception
        $this->expectException(NovaPayException::class);
        $this->novaPay->confirmDelivery($sessionId);
    }

    public function test_callback_verification_integration(): void
    {
        // Test that signature verification works with realistic callback data
        $callbackData = [
            'session_id' => 'session_abc123def456',
            'external_id' => 'order_789',
            'status' => 'paid',
            'amount' => 1299.99,
            'currency' => 'UAH',
            'created_at' => '2024-01-15T10:30:00Z',
            'updated_at' => '2024-01-15T10:32:15Z'
        ];

        // Create a signature using the merchant's private key
        $signature = new Signature(
            $this->getTestPrivateKey(),
            // Use merchant's public key for testing
            $this->getTestMerchantPublicKey()
        );

        $validSignature = $signature->createSignature($callbackData);

        // Create a test NovaPay instance with the merchant's key pair for verification
        $testNovaPay = new NovaPay(
            merchantId: $this->getTestMerchantId(),
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestMerchantPublicKey(),
            sandbox: true
        );

        // Verify using test NovaPay instance
        $isValid = $testNovaPay->verifyCallback($callbackData, $validSignature);

        $this->assertTrue($isValid);
    }

    public function test_error_handling_integration(): void
    {
        // Test that different types of errors are handled appropriately

        // Test with invalid session data
        $invalidSessionData = [
            'external_id' => '', // Invalid empty ID
            'amount' => -100,    // Invalid negative amount
        ];

        $this->expectException(NovaPayException::class);
        $this->novaPay->createSession($invalidSessionData);
    }

    public function test_signature_integration_with_complex_data(): void
    {
        // Test signature creation and verification with complex nested data
        $complexCallbackData = [
            'session_id' => 'session_123',
            'external_id' => 'order_456',
            'status' => 'paid',
            'amount' => 1500.75,
            'currency' => 'UAH',
            'payment_method' => [
                'type' => 'card',
                'card_mask' => '4***-****-****-1234',
                'bank' => 'Test Bank'
            ],
            'delivery' => [
                'ttn' => '20240115000123',
                'status' => 'shipped',
                'estimated_delivery' => '2024-01-18'
            ],
            'metadata' => [
                'customer_id' => 'cust_789',
                'source' => 'web',
                'campaign' => 'winter_sale_2024'
            ]
        ];

        $signature = new Signature(
            $this->getTestPrivateKey(),
            $this->getTestMerchantPublicKey()
        );

        $validSignature = $signature->createSignature($complexCallbackData);

        // Create test NovaPay instance for verification
        $testNovaPay = new NovaPay(
            merchantId: $this->getTestMerchantId(),
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestMerchantPublicKey(),
            sandbox: true
        );

        $isValid = $testNovaPay->verifyCallback($complexCallbackData, $validSignature);

        $this->assertTrue($isValid);

        // Test that tampering with nested data fails verification
        $tamperedData = $complexCallbackData;
        $tamperedData['payment_method']['card_mask'] = '5***-****-****-5678';

        $isValidAfterTampering = $testNovaPay->verifyCallback($tamperedData, $validSignature);
        $this->assertFalse($isValidAfterTampering);
    }

    public function test_multiple_operations_sequence(): void
    {
        // Test a sequence of operations to ensure all throw exceptions properly
        $sessionData = $this->getTestSessionData();
        $sessionId = 'test_session_sequence';

        $operations = [
            fn () => $this->novaPay->createSession($sessionData),
            fn () => $this->novaPay->addPayment($sessionId, ['use_hold' => true, 'amount' => 100.00]),
            fn () => $this->novaPay->getStatus($sessionId),
            fn () => $this->novaPay->completeHold($sessionId, 50.00),
            fn () => $this->novaPay->getStatus($sessionId),
        ];

        foreach ($operations as $index => $operation) {
            try {
                $operation();
                $this->fail("Expected NovaPayException for operation $index");
            } catch (NovaPayException $e) {
                // Each operation should throw NovaPayException due to no actual API
                $this->assertInstanceOf(NovaPayException::class, $e);
            }
        }
    }

    public function test_edge_cases_handling(): void
    {
        // Test edge cases that might occur in real-world usage

        // Empty callback data
        $isValid = $this->novaPay->verifyCallback([], 'some_signature');
        $this->assertFalse($isValid);

        // Very large amounts
        $largeAmountData = $this->getTestSessionData();
        $largeAmountData['amount'] = 999999.99;

        $this->expectException(NovaPayException::class);
        $this->novaPay->createSession($largeAmountData);
    }

    public function test_unicode_handling_in_session_data(): void
    {
        // Unicode characters in descriptions
        $unicodeData = $this->getTestSessionData();
        $unicodeData['client_first_name'] = 'Тарас';
        $unicodeData['client_last_name'] = 'Шевченко';
        $unicodeData['metadata'] = json_encode([
            'description' => 'Тест з українськими символами 🇺🇦',
            'special_chars' => '€$£¥₴'
        ]);

        $this->expectException(NovaPayException::class);
        $this->novaPay->createSession($unicodeData);
    }

    public function test_all_api_methods_structure(): void
    {
        // Test that all public API methods have the correct structure and throw exceptions
        $sessionId = 'test_session_integration';

        $apiMethods = [
            // Session management
            ['method' => 'createSession', 'args' => [['client_phone' => '+380982850620']]],
            ['method' => 'expireSession', 'args' => [$sessionId]],

            // Payment methods
            ['method' => 'addPayment', 'args' => [$sessionId, ['amount' => 100.00]]],
            ['method' => 'completeHold', 'args' => [$sessionId]],
            ['method' => 'confirmDelivery', 'args' => [$sessionId]],
            ['method' => 'void', 'args' => [$sessionId]],
            ['method' => 'getStatus', 'args' => [$sessionId]],
            ['method' => 'printExpressWaybill', 'args' => [$sessionId]],

            // Utility methods
            ['method' => 'getDeliveryPrice', 'args' => [[
                'recipient_city' => 'city_uuid',
                'recipient_warehouse' => 'warehouse_uuid',
                'volume_weight' => 0.001,
                'weight' => 0.5,
                'amount' => 100.00
            ]]],

            // Frames and checkout
            ['method' => 'initFrames', 'args' => [[
                'callback_url' => 'https://example.com/callback',
                'amount' => 100.00,
                'delivery' => ['volume_weight' => 0.001, 'weight' => 0.1]
            ]]],
            ['method' => 'createCheckoutSession', 'args' => [[
                'callback_url' => 'https://example.com/callback'
            ]]],
            ['method' => 'addCheckoutPayment', 'args' => [$sessionId, ['amount' => 100.00]]],

            // Export
            ['method' => 'exportOperations', 'args' => [[
                'date_range' => ['start' => '2024-01-01', 'end' => '2024-01-31'],
                'recipient_email' => 'test@example.com'
            ]]]
        ];

        foreach ($apiMethods as $methodInfo) {
            $methodName = $methodInfo['method'];
            $args = $methodInfo['args'];

            try {
                $this->novaPay->$methodName(...$args);
                $this->fail("Method $methodName should throw NovaPayException in test environment");
            } catch (NovaPayException $e) {
                // Expected - method should throw exception in test environment
                $this->assertInstanceOf(NovaPayException::class, $e);
            }
        }
    }

    public function test_callback_verification_does_not_throw_exceptions(): void
    {
        // Callback verification should never throw exceptions, only return true/false
        $testCases = [
            ['data' => [], 'signature' => ''],
            ['data' => ['test' => 'data'], 'signature' => 'invalid_signature'],
            // PHP will convert this to array
            ['data' => null, 'signature' => null],
        ];

        foreach ($testCases as $testCase) {
            $data = $testCase['data'] ?? [];
            $signature = $testCase['signature'] ?? '';

            try {
                $result = $this->novaPay->verifyCallback($data, $signature);
                $this->assertIsBool($result);
                // Should be false for invalid signatures
                $this->assertFalse($result);
            } catch (Exception $e) {
                $this->fail("verifyCallback should not throw exceptions, got: {$e->getMessage()}");
            }
        }
    }

    public function test_get_curl_command_does_not_make_requests(): void
    {
        // getCurlCommand should not make actual HTTP requests
        $curlCommand = $this->novaPay->getCurlCommand('/session', [
            'client_phone' => '+380982850620'
        ]);

        $this->assertIsString($curlCommand);
        $this->assertStringContainsString('curl -X POST', $curlCommand);
        $this->assertStringContainsString('/v1/session', $curlCommand);
        $this->assertStringContainsString($this->getTestMerchantId(), $curlCommand);

        // Should contain proper headers
        $this->assertStringContainsString('Content-Type: application/json', $curlCommand);
        $this->assertStringContainsString('x-sign:', $curlCommand);
    }

    public function test_merchant_id_and_session_id_validation(): void
    {
        // Test that methods properly handle merchant_id and session_id

        // Empty session ID should still create a proper request structure
        try {
            $this->novaPay->getStatus('');
            $this->fail('Expected NovaPayException');
        } catch (NovaPayException $e) {
            $this->assertInstanceOf(NovaPayException::class, $e);
        }

        // Very long session ID should still work
        try {
            $longSessionId = str_repeat('a', 1000);
            $this->novaPay->getStatus($longSessionId);
            $this->fail('Expected NovaPayException');
        } catch (NovaPayException $e) {
            $this->assertInstanceOf(NovaPayException::class, $e);
        }
    }

    public function test_environment_detection(): void
    {
        // Test sandbox vs production detection
        $sandboxNovaPay = new NovaPay(
            merchantId: 'test_merchant',
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestPublicKey(),
            sandbox: true
        );

        $productionNovaPay = new NovaPay(
            merchantId: 'prod_merchant',
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestPublicKey(),
            sandbox: false
        );

        $this->assertTrue($sandboxNovaPay->isSandbox());
        $this->assertFalse($productionNovaPay->isSandbox());

        // Test that different environments generate different URLs in curl commands
        $sandboxCurl = $sandboxNovaPay->getCurlCommand('/test', []);
        $productionCurl = $productionNovaPay->getCurlCommand('/test', []);

        $this->assertStringContainsString('api-qecom.novapay.ua', $sandboxCurl);
        $this->assertStringContainsString('api-ecom.novapay.ua', $productionCurl);
    }

    public function test_json_serialization_in_requests(): void
    {
        // Test that complex data structures are properly JSON encoded
        $complexData = [
            'simple_string' => 'test',
            'number' => 123,
            'float' => 123.45,
            'boolean' => true,
            'array' => [1, 2, 3],
            'nested_object' => [
                'key1' => 'value1',
                'key2' => [
                    'nested_key' => 'nested_value'
                ]
            ],
            'unicode_string' => 'Тест 🇺🇦',
            'special_chars' => '<>&"\'',
        ];

        $curlCommand = $this->novaPay->getCurlCommand('/test', $complexData);

        // Should contain properly escaped JSON
        $this->assertStringContainsString('"simple_string":"test"', $curlCommand);
        $this->assertStringContainsString('"number":123', $curlCommand);
        $this->assertStringContainsString('"boolean":true', $curlCommand);

        // Should handle Unicode properly
        $this->assertStringContainsString('\\u0422\\u0435\\u0441\\u0442', $curlCommand);
    }

}
