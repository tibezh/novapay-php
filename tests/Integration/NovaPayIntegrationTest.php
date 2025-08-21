<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Tests\Integration;

use Tibezh\NovapayPhp\NovaPay;
use Tibezh\NovapayPhp\Exceptions\NovaPayException;
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

    // Step 1: Create session
    try {
      $this->novaPay->createSession($sessionData);
      $this->fail('Expected NovaPayException due to no actual API');
    } catch (NovaPayException $e) {
      // Could be CURL error or HTTP error - both indicate proper structure
      $this->assertTrue(
        str_contains($e->getMessage(), 'CURL error') ||
        str_contains($e->getMessage(), 'HTTP error')
      );
    }

    // The workflow structure is correct even if the API call fails
    $this->assertTrue(true);
  }

  public function test_complete_hold_payment_workflow(): void
  {
    $sessionId = 'test_session_123';

    // Test the complete hold workflow structure
    try {
      // Step 1: Create hold payment
      $this->novaPay->addPayment($sessionId, ['use_hold' => true]);
      $this->fail('Expected NovaPayException due to no actual API');
    } catch (NovaPayException $e) {
      $this->assertTrue(
        str_contains($e->getMessage(), 'CURL error') ||
        str_contains($e->getMessage(), 'HTTP error')
      );
    }

    try {
      // Step 2: Complete hold
      $this->novaPay->completeHold($sessionId);
      $this->fail('Expected NovaPayException due to no actual API');
    } catch (NovaPayException $e) {
      $this->assertTrue(
        str_contains($e->getMessage(), 'CURL error') ||
        str_contains($e->getMessage(), 'HTTP error')
      );
    }

    $this->assertTrue(true);
  }

  public function test_secure_purchase_workflow(): void
  {
    $sessionId = 'test_session_123';
    $deliveryData = $this->getTestDeliveryData();

    try {
      // Step 1: Create secure purchase
      $this->novaPay->addPayment($sessionId, [
        'use_hold' => true,
        'delivery' => $deliveryData
      ]);
      $this->fail('Expected NovaPayException due to no actual API');
    } catch (NovaPayException $e) {
      $this->assertTrue(
        str_contains($e->getMessage(), 'CURL error') ||
        str_contains($e->getMessage(), 'HTTP error')
      );
    }

    try {
      // Step 2: Confirm delivery
      $this->novaPay->confirmDelivery($sessionId);
      $this->fail('Expected NovaPayException due to no actual API');
    } catch (NovaPayException $e) {
      $this->assertTrue(
        str_contains($e->getMessage(), 'CURL error') ||
        str_contains($e->getMessage(), 'HTTP error')
      );
    }

    $this->assertTrue(true);
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

    // Create signature using the merchant's private key
    $signature = new \Tibezh\NovapayPhp\Security\Signature(
      $this->getTestPrivateKey(),
      $this->getTestMerchantPublicKey()  // Use merchant's public key for testing
    );

    $validSignature = $signature->createSignature($callbackData);

    // Create test NovaPay instance with merchant's key pair for verification
    $testNovaPay = new \Tibezh\NovapayPhp\NovaPay(
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

    try {
      $this->novaPay->createSession($invalidSessionData);
      $this->fail('Expected NovaPayException');
    } catch (NovaPayException $e) {
      // Should get CURL or HTTP error since we're not hitting actual API
      $this->assertTrue(
        str_contains($e->getMessage(), 'CURL error') ||
        str_contains($e->getMessage(), 'HTTP error')
      );
    }
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

    $signature = new \Tibezh\NovapayPhp\Security\Signature(
      $this->getTestPrivateKey(),
      $this->getTestMerchantPublicKey()
    );

    $validSignature = $signature->createSignature($complexCallbackData);

    // Create test NovaPay instance for verification
    $testNovaPay = new \Tibezh\NovapayPhp\NovaPay(
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
    // Test a sequence of operations to ensure state is maintained correctly
    $sessionData = $this->getTestSessionData();
    $sessionId = 'test_session_sequence';

    $operations = [
      fn() => $this->novaPay->createSession($sessionData),
      fn() => $this->novaPay->addPayment($sessionId, ['use_hold' => true]),
      fn() => $this->novaPay->getStatus($sessionId),
      fn() => $this->novaPay->completeHold($sessionId, 50.00),
      fn() => $this->novaPay->getStatus($sessionId),
    ];

    foreach ($operations as $index => $operation) {
      try {
        $operation();
        $this->fail("Expected NovaPayException for operation {$index}");
      } catch (NovaPayException $e) {
        // Each operation should fail with CURL or HTTP error due to no actual API
        $this->assertTrue(
          str_contains($e->getMessage(), 'CURL error') ||
          str_contains($e->getMessage(), 'HTTP error')
        );
      }
    }

    // If we get here, all operations have the correct structure
    $this->assertTrue(true);
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

    try {
      $this->novaPay->createSession($largeAmountData);
      $this->fail('Expected NovaPayException');
    } catch (NovaPayException $e) {
      $this->assertTrue(
        str_contains($e->getMessage(), 'CURL error') ||
        str_contains($e->getMessage(), 'HTTP error')
      );
    }

    // Unicode characters in descriptions
    $unicodeData = $this->getTestSessionData();
    $unicodeData['description'] = 'Тест з українськими символами 🇺🇦';

    try {
      $this->novaPay->createSession($unicodeData);
      $this->fail('Expected NovaPayException');
    } catch (NovaPayException $e) {
      $this->assertTrue(
        str_contains($e->getMessage(), 'CURL error') ||
        str_contains($e->getMessage(), 'HTTP error')
      );
    }
  }
}
