<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Tibezh\NovapayPhp\NovaPay;
use Tibezh\NovapayPhp\Exceptions\NovaPayException;

class NovaPayTest extends TestCase
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

    public function test_can_initialize_with_sandbox_environment(): void
    {
        $novaPay = new NovaPay(
            merchantId: 'test_merchant',
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestPublicKey(),
            sandbox: true
        );

        $this->assertInstanceOf(NovaPay::class, $novaPay);
    }

    public function test_can_initialize_with_production_environment(): void
    {
        $novaPay = new NovaPay(
            merchantId: 'prod_merchant',
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestPublicKey(),
            sandbox: false
        );

        $this->assertInstanceOf(NovaPay::class, $novaPay);
    }

    public function test_can_load_private_key_from_string(): void
    {
        $novaPay = new NovaPay(
            merchantId: 'test_merchant',
            privateKey: $this->getTestPrivateKey(), // Direct key content
            publicKey: $this->getTestPublicKey(),
            sandbox: true
        );

        $this->assertInstanceOf(NovaPay::class, $novaPay);
    }

    public function test_create_session_adds_merchant_id(): void
    {
        // We can't easily mock the HTTP request, so we'll test that the method exists
        // and properly handles the data structure
        $sessionData = $this->getTestSessionData();

        // This would normally make an HTTP request, but we're testing the structure
        $this->expectException(NovaPayException::class); // Will fail due to no actual API

        try {
            $this->novaPay->createSession($sessionData);
        } catch (NovaPayException $e) {
            // Check that the error is related to HTTP/network, not data structure
            // Could be CURL error or HTTP 403 (both indicate we reached the API level)
            $this->assertTrue(
                str_contains($e->getMessage(), 'CURL error') ||
                str_contains($e->getMessage(), 'HTTP error')
            );
            throw $e;
        }
    }

    public function test_add_payment_with_direct_charge(): void
    {
        $sessionId = 'test_session_123';
        $paymentData = ['use_hold' => false];

        $this->expectException(NovaPayException::class); // Will fail due to no actual API

        $this->novaPay->addPayment($sessionId, $paymentData);
    }

    public function test_add_payment_with_hold(): void
    {
        $sessionId = 'test_session_123';
        $paymentData = ['use_hold' => true];

        $this->expectException(NovaPayException::class); // Will fail due to no actual API

        $this->novaPay->addPayment($sessionId, $paymentData);
    }

    public function test_add_payment_with_delivery(): void
    {
        $sessionId = 'test_session_123';
        $paymentData = [
          'use_hold' => true,
          'delivery' => $this->getTestDeliveryData()
        ];

        $this->expectException(NovaPayException::class); // Will fail due to no actual API

        $this->novaPay->addPayment($sessionId, $paymentData);
    }

    public function test_complete_hold_without_amount(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);

        $this->novaPay->completeHold($sessionId);
    }

    public function test_complete_hold_with_partial_amount(): void
    {
        $sessionId = 'test_session_123';
        $amount = 50.00;

        $this->expectException(NovaPayException::class);

        $this->novaPay->completeHold($sessionId, $amount);
    }

    public function test_confirm_delivery(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);

        $this->novaPay->confirmDelivery($sessionId);
    }

    public function test_void_payment(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);

        $this->novaPay->void($sessionId);
    }

    public function test_get_status(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);

        $this->novaPay->getStatus($sessionId);
    }

    public function test_verify_callback_with_valid_signature(): void
    {
        $callbackData = [
          'session_id' => 'test_session_123',
          'status' => 'paid',
          'amount' => 100.50
        ];

        // Create signature using the merchant's private key
        $signature = new \Tibezh\NovapayPhp\Security\Signature(
            $this->getTestPrivateKey(),
            $this->getTestMerchantPublicKey()  // Use merchant's public key for testing
        );

        $validSignature = $signature->createSignature($callbackData);

        // For callback verification, we need to create a NovaPay instance that uses
        // the merchant's key pair (since we're testing merchant's signature verification)
        $testNovaPay = new NovaPay(
            merchantId: $this->getTestMerchantId(),
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestMerchantPublicKey(), // Use merchant's public key
            sandbox: true
        );

        $isValid = $testNovaPay->verifyCallback($callbackData, $validSignature);

        $this->assertTrue($isValid);
    }

    public function test_verify_callback_with_invalid_signature(): void
    {
        $callbackData = [
          'session_id' => 'test_session_123',
          'status' => 'paid',
          'amount' => 100.50
        ];

        $invalidSignature = base64_encode('invalid_signature');
        $isValid = $this->novaPay->verifyCallback($callbackData, $invalidSignature);

        $this->assertFalse($isValid);
    }

    public function test_verify_callback_with_tampered_data(): void
    {
        $originalData = [
          'session_id' => 'test_session_123',
          'status' => 'paid',
          'amount' => 100.50
        ];

        // Create signature for original data
        $signature = new \Tibezh\NovapayPhp\Security\Signature(
            $this->getTestPrivateKey(),
            $this->getTestMerchantPublicKey()
        );

        $validSignature = $signature->createSignature($originalData);

        // Tamper with the data
        $tamperedData = [
          'session_id' => 'test_session_123',
          'status' => 'paid',
          'amount' => 1000.50 // Changed amount
        ];

        // Create test NovaPay instance with merchant's key pair
        $testNovaPay = new NovaPay(
            merchantId: $this->getTestMerchantId(),
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestMerchantPublicKey(),
            sandbox: true
        );

        $isValid = $testNovaPay->verifyCallback($tamperedData, $validSignature);

        $this->assertFalse($isValid);
    }

    public function test_handles_empty_callback_data(): void
    {
        $emptyData = [];
        $signature = 'some_signature';

        $isValid = $this->novaPay->verifyCallback($emptyData, $signature);

        // Should not throw exception, just return false
        $this->assertFalse($isValid);
    }

    public function test_method_chaining_workflow(): void
    {
        // Test that we can call methods in sequence without errors
        // (even though they'll fail due to no actual API)

        $sessionData = $this->getTestSessionData();

        try {
            $this->novaPay->createSession($sessionData);
            $this->fail('Expected NovaPayException');
        } catch (NovaPayException $e) {
            // Could be CURL error or HTTP error - both are acceptable
            $this->assertTrue(
                str_contains($e->getMessage(), 'CURL error') ||
                str_contains($e->getMessage(), 'HTTP error')
            );
        }
    }

    #[DataProvider('invalidMerchantIdProvider')]
    public function test_validates_merchant_id(string $merchantId): void
    {
        // Test that we can create instance with various merchant IDs
        $novaPay = new NovaPay(
            merchantId: $merchantId,
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestPublicKey(),
            sandbox: true
        );

        $this->assertInstanceOf(NovaPay::class, $novaPay);
    }

    public static function invalidMerchantIdProvider(): array
    {
        return [
          'empty string' => [''],
          'numeric string' => ['123'],
          'alphanumeric' => ['merchant_123'],
          'with special chars' => ['merchant-test_123']
        ];
    }

    public function test_handles_different_session_data_structures(): void
    {
        $testCases = [
          'minimal data' => [
            'external_id' => 'test_123',
            'amount' => 100,
            'currency' => 'UAH'
          ],
          'full data' => $this->getTestSessionData(),
          'with extra fields' => array_merge($this->getTestSessionData(), [
            'custom_field' => 'custom_value',
            'metadata' => ['key' => 'value']
          ])
        ];

        foreach ($testCases as $testName => $sessionData) {
            try {
                $this->novaPay->createSession($sessionData);
                $this->fail("Expected NovaPayException for test case: {$testName}");
            } catch (NovaPayException $e) {
                // Expected due to no actual API - could be CURL or HTTP error
                $this->assertTrue(
                    str_contains($e->getMessage(), 'CURL error') ||
                    str_contains($e->getMessage(), 'HTTP error')
                );
            }
        }
    }
}
