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

    public function test_throws_exception_for_empty_merchant_id(): void
    {
        $this->expectException(NovaPayException::class);
        $this->expectExceptionMessage('Merchant ID cannot be empty');

        new NovaPay(
            merchantId: '',
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
        $this->assertTrue($novaPay->isSandbox());
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
        $this->assertFalse($novaPay->isSandbox());
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

    public function test_throws_exception_for_invalid_private_key(): void
    {
        $this->expectException(NovaPayException::class);
        $this->expectExceptionMessage('Private key not found');

        new NovaPay(
            merchantId: 'test_merchant',
            privateKey: '/nonexistent/path/to/key.pem',
            publicKey: $this->getTestPublicKey(),
            sandbox: true
        );
    }

    public function test_get_merchant_id(): void
    {
        $merchantId = 'test_merchant_123';
        $novaPay = new NovaPay(
            merchantId: $merchantId,
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestPublicKey(),
            sandbox: true
        );

        $this->assertEquals($merchantId, $novaPay->getMerchantId());
    }

    public function test_create_session_throws_exception(): void
    {
        $sessionData = $this->getTestSessionData();

        $this->expectException(NovaPayException::class);
        $this->novaPay->createSession($sessionData);
    }

    public function test_expire_session_throws_exception(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);
        $this->novaPay->expireSession($sessionId);
    }

    public function test_add_payment_with_direct_charge_throws_exception(): void
    {
        $sessionId = 'test_session_123';
        $paymentData = ['use_hold' => false, 'amount' => 100.00];

        $this->expectException(NovaPayException::class);
        $this->novaPay->addPayment($sessionId, $paymentData);
    }

    public function test_add_payment_with_hold_throws_exception(): void
    {
        $sessionId = 'test_session_123';
        $paymentData = ['use_hold' => true, 'amount' => 250.00];

        $this->expectException(NovaPayException::class);
        $this->novaPay->addPayment($sessionId, $paymentData);
    }

    public function test_add_payment_with_delivery_throws_exception(): void
    {
        $sessionId = 'test_session_123';
        $paymentData = [
            'amount' => 1000.00,
            'use_hold' => true,
            'delivery' => $this->getTestDeliveryData()
        ];

        $this->expectException(NovaPayException::class);
        $this->novaPay->addPayment($sessionId, $paymentData);
    }

    public function test_add_payment_with_products_throws_exception(): void
    {
        $sessionId = 'test_session_123';
        $paymentData = [
            'amount' => 150.00,
            'use_hold' => false,
            'products' => [
                [
                    'description' => 'Test Product',
                    'count' => 2,
                    'price' => 75.00
                ]
            ]
        ];

        $this->expectException(NovaPayException::class);
        $this->novaPay->addPayment($sessionId, $paymentData);
    }

    public function test_complete_hold_without_amount_throws_exception(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);
        $this->novaPay->completeHold($sessionId);
    }

    public function test_complete_hold_with_partial_amount_throws_exception(): void
    {
        $sessionId = 'test_session_123';
        $amount = 50.00;

        $this->expectException(NovaPayException::class);
        $this->novaPay->completeHold($sessionId, $amount);
    }

    public function test_confirm_delivery_throws_exception(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);
        $this->novaPay->confirmDelivery($sessionId);
    }

    public function test_print_express_waybill_throws_exception(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);
        $this->novaPay->printExpressWaybill($sessionId);
    }

    public function test_void_payment_throws_exception(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);
        $this->novaPay->void($sessionId);
    }

    public function test_get_status_throws_exception(): void
    {
        $sessionId = 'test_session_123';

        $this->expectException(NovaPayException::class);
        $this->novaPay->getStatus($sessionId);
    }

    public function test_get_delivery_price_throws_exception(): void
    {
        $deliveryData = [
            'recipient_city' => 'db5c88d0-391c-11dd-90d9-001a92567626',
            'recipient_warehouse' => '1692286e-e1c2-11e3-8c4a-0050568002cf',
            'volume_weight' => 0.001,
            'weight' => 0.5,
            'amount' => 100.00
        ];

        $this->expectException(NovaPayException::class);
        $this->novaPay->getDeliveryPrice($deliveryData);
    }

    public function test_init_frames_throws_exception(): void
    {
        $framesData = [
            'callback_url' => 'https://example.com/callback',
            'amount' => 100.45,
            'delivery' => [
                'volume_weight' => 0.0004,
                'weight' => 0.1
            ]
        ];

        $this->expectException(NovaPayException::class);
        $this->novaPay->initFrames($framesData);
    }

    public function test_create_checkout_session_throws_exception(): void
    {
        $checkoutData = [
            'callback_url' => 'https://example.com/callback',
            'client_phone' => '+380982850620',
            'create_express_waybill' => true,
            'delivery' => [
                'volume_weight' => 0.0004,
                'weight' => 0.1
            ]
        ];

        $this->expectException(NovaPayException::class);
        $this->novaPay->createCheckoutSession($checkoutData);
    }

    public function test_add_checkout_payment_throws_exception(): void
    {
        $sessionId = 'test_session_123';
        $paymentData = [
            'amount' => 544.25,
            'external_id' => 'order_123',
            'products' => [
                [
                    'description' => 'Test Product',
                    'count' => 1,
                    'price' => 544.25
                ]
            ]
        ];

        $this->expectException(NovaPayException::class);
        $this->novaPay->addCheckoutPayment($sessionId, $paymentData);
    }

    public function test_export_operations_throws_exception(): void
    {
        $exportData = [
            'date_range' => [
                'start' => '2024-01-01',
                'end' => '2024-01-31'
            ],
            'format' => 'CSV',
            'recepient_email' => 'test@example.com'
        ];

        $this->expectException(NovaPayException::class);
        $this->novaPay->exportOperations($exportData);
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
            $this->getTestMerchantPublicKey()
        );

        $validSignature = $signature->createSignature($callbackData);

        // For callback verification, we need to create a NovaPay instance that uses
        // the merchant's key pair (since we're testing merchant's signature verification)
        $testNovaPay = new NovaPay(
            merchantId: $this->getTestMerchantId(),
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestMerchantPublicKey(),
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

    public function test_get_curl_command(): void
    {
        $endpoint = '/session';
        $data = [
            'external_id' => 'test_123',
            'amount' => 100.00
        ];

        $curlCommand = $this->novaPay->getCurlCommand($endpoint, $data);

        $this->assertIsString($curlCommand);
        $this->assertStringContainsString('curl -X POST', $curlCommand);
        $this->assertStringContainsString($this->getTestMerchantId(), $curlCommand);
        $this->assertStringContainsString('x-sign:', $curlCommand);
        $this->assertStringContainsString('/v1/session', $curlCommand);
    }

    /**
     * @param array<string, mixed> $sessionData
     */
    #[DataProvider('sessionDataProvider')]
    public function test_handles_different_session_data_structures(array $sessionData): void
    {
        $this->expectException(NovaPayException::class);
        $this->novaPay->createSession($sessionData);
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public static function sessionDataProvider(): array
    {
        return [
            'minimal data' => [[
                'client_phone' => '+380982850620'
            ]],
            'full data' => [[
                'client_first_name' => 'Test',
                'client_last_name' => 'User',
                'client_patronymic' => 'Testovich',
                'client_phone' => '+380982850620',
                'client_email' => 'test@example.com',
                'callback_url' => 'https://example.com/callback',
                'success_url' => 'https://example.com/success',
                'fail_url' => 'https://example.com/fail',
                'success_redirect_timeout' => 5,
                'metadata' => '{"order_id": "123"}'
            ]],
            'with extra fields' => [[
                'client_phone' => '+380982850620',
                'callback_url' => 'https://example.com/callback',
                'custom_field' => 'custom_value',
                'metadata' => '{"key": "value"}'
            ]]
        ];
    }

    public function test_all_methods_throw_exception_in_test_environment(): void
    {
        $testMethods = [
            fn () => $this->novaPay->createSession(['client_phone' => '+380982850620']),
            fn () => $this->novaPay->expireSession('session_123'),
            fn () => $this->novaPay->addPayment('session_123', ['amount' => 100]),
            fn () => $this->novaPay->completeHold('session_123'),
            fn () => $this->novaPay->confirmDelivery('session_123'),
            fn () => $this->novaPay->void('session_123'),
            fn () => $this->novaPay->getStatus('session_123'),
            fn () => $this->novaPay->getDeliveryPrice([
                'recipient_city' => 'city_id',
                'recipient_warehouse' => 'warehouse_id',
                'volume_weight' => 0.001,
                'weight' => 0.5,
                'amount' => 100.00
            ]),
            fn () => $this->novaPay->initFrames(['callback_url' => 'https://test.com', 'amount' => 100]),
            fn () => $this->novaPay->createCheckoutSession(['callback_url' => 'https://test.com']),
            fn () => $this->novaPay->addCheckoutPayment('session_123', ['amount' => 100]),
            fn () => $this->novaPay->exportOperations([
                'date_range' => ['start' => '2024-01-01', 'end' => '2024-01-31'],
                'recepient_email' => 'test@example.com'
            ])
        ];

        foreach ($testMethods as $index => $method) {
            try {
                $method();
                $this->fail("Method at index {$index} should throw NovaPayException");
            } catch (NovaPayException $e) {
                // Expected - all methods should throw exceptions in test environment
                $this->assertInstanceOf(NovaPayException::class, $e);
            }
        }
    }

    public function test_build_url_adds_version_and_endpoint(): void
    {
        $curlCommand = $this->novaPay->getCurlCommand('/test', []);

        // Check that URL contains both API version and endpoint
        $this->assertStringContainsString('/v1/test', $curlCommand);
    }

    public function test_merchant_id_numeric_string_works(): void
    {
        $merchantId = '0'; // This should work (not empty)

        $novaPay = new NovaPay(
            merchantId: $merchantId,
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestPublicKey(),
            sandbox: true
        );

        $this->assertEquals('0', $novaPay->getMerchantId());
    }

    public function test_signature_class_initialization(): void
    {
        // Test that signature class is properly initialized
        $callbackData = ['test' => 'data'];
        $result = $this->novaPay->verifyCallback($callbackData, 'invalid_signature');

        // Should not throw exception, just return false
        $this->assertFalse($result);
    }

    public function test_passphrase_is_passed_to_signature(): void
    {
        $passphrase = 'test_passphrase';

        // This should not throw exception if passphrase handling works correctly
        $novaPay = new NovaPay(
            merchantId: 'test',
            privateKey: $this->getTestPrivateKey(),
            publicKey: $this->getTestPublicKey(),
            sandbox: true,
            passphrase: $passphrase
        );

        $this->assertInstanceOf(NovaPay::class, $novaPay);
    }

}
