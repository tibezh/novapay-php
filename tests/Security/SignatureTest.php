<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Tests\Security;

use Tibezh\NovapayPhp\Security\Signature;
use Tibezh\NovapayPhp\Exceptions\SignatureException;
use Tibezh\NovapayPhp\Tests\TestCase;

class SignatureTest extends TestCase
{
    private Signature $signature;

    protected function setUp(): void
    {
        parent::setUp();

        // Use merchant's key pair for signature testing (not NovaPay's public key)
        $this->signature = new Signature(
            $this->getTestPrivateKey(),
            $this->getTestMerchantPublicKey()
        );
    }

    public function test_can_create_signature(): void
    {
        $data = [
          'merchant_id' => 'test_merchant',
          'amount' => 100.50,
          'currency' => 'UAH'
        ];

        $signature = $this->signature->createSignature($data);

        $this->assertIsString($signature);
        $this->assertNotEmpty($signature);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+=*$/', $signature); // Base64 pattern
    }

    public function test_can_verify_own_signature(): void
    {
        $data = [
          'session_id' => 'test_session_123',
          'status' => 'paid',
          'amount' => 250.00
        ];

        $signature = $this->signature->createSignature($data);
        $isValid = $this->signature->verifySignature($data, $signature);

        $this->assertTrue($isValid);
    }

    public function test_signature_verification_fails_for_tampered_data(): void
    {
        $originalData = [
          'amount' => 100.00,
          'currency' => 'UAH'
        ];

        $signature = $this->signature->createSignature($originalData);

        // Tamper with the data
        $tamperedData = [
          'amount' => 1000.00, // Changed amount
          'currency' => 'UAH'
        ];

        $isValid = $this->signature->verifySignature($tamperedData, $signature);

        $this->assertFalse($isValid);
    }

    public function test_signature_verification_fails_for_invalid_signature(): void
    {
        $data = [
          'amount' => 100.00,
          'currency' => 'UAH'
        ];

        $invalidSignature = base64_encode('invalid_signature_data');
        $isValid = $this->signature->verifySignature($data, $invalidSignature);

        $this->assertFalse($isValid);
    }

    public function test_array_to_string_conversion(): void
    {
        $data = [
          'z_last' => 'value_z',
          'a_first' => 'value_a',
          'b_middle' => 'value_b'
        ];

        $signature1 = $this->signature->createSignature($data);

        // Same data, different order
        $dataReordered = [
          'b_middle' => 'value_b',
          'z_last' => 'value_z',
          'a_first' => 'value_a'
        ];

        $signature2 = $this->signature->createSignature($dataReordered);

        $this->assertEquals($signature1, $signature2);
    }

    public function test_handles_nested_arrays(): void
    {
        $data = [
          'merchant_id' => 'test',
          'delivery' => [
            'weight' => 0.5,
            'city' => 'kyiv'
          ]
        ];

        $signature = $this->signature->createSignature($data);

        $this->assertIsString($signature);
        $this->assertNotEmpty($signature);
    }

    public function test_handles_boolean_values(): void
    {
        $data = [
          'use_hold' => true,
          'test_mode' => false,
          'amount' => 100
        ];

        $signature = $this->signature->createSignature($data);
        $isValid = $this->signature->verifySignature($data, $signature);

        $this->assertTrue($isValid);
    }

    public function test_handles_null_values(): void
    {
        $data = [
          'amount' => 100,
          'description' => null,
          'callback_url' => 'https://example.com'
        ];

        $signature = $this->signature->createSignature($data);
        $isValid = $this->signature->verifySignature($data, $signature);

        $this->assertTrue($isValid);
    }

    public function test_removes_x_sign_field_from_data(): void
    {
        $data = [
          'amount' => 100,
          'x-sign' => 'should_be_removed'
        ];

        $signature = $this->signature->createSignature($data);

        // Verify that x-sign field doesn't affect signature
        $dataWithoutSign = ['amount' => 100];
        $signatureWithoutSign = $this->signature->createSignature($dataWithoutSign);

        $this->assertEquals($signature, $signatureWithoutSign);
    }

    public function test_throws_exception_for_invalid_private_key(): void
    {
        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Invalid private key');

        $invalidSignature = new Signature('invalid_private_key', $this->getTestPublicKey());
        $invalidSignature->createSignature(['test' => 'data']);
    }

    public function test_throws_exception_for_invalid_public_key(): void
    {
        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Invalid public key');

        $invalidSignature = new Signature($this->getTestPrivateKey(), 'invalid_public_key');
        $invalidSignature->verifySignature(['test' => 'data'], 'test_signature');
    }

    public function test_consistent_signatures_for_same_data(): void
    {
        $data = [
          'merchant_id' => 'test_merchant',
          'amount' => 100.50,
          'currency' => 'UAH'
        ];

        $signature1 = $this->signature->createSignature($data);
        $signature2 = $this->signature->createSignature($data);

        $this->assertEquals($signature1, $signature2);
    }

    public function test_different_signatures_for_different_data(): void
    {
        $data1 = ['amount' => 100];
        $data2 = ['amount' => 200];

        $signature1 = $this->signature->createSignature($data1);
        $signature2 = $this->signature->createSignature($data2);

        $this->assertNotEquals($signature1, $signature2);
    }
}
