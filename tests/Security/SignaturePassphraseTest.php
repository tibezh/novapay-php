<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Tests\Security;

use Tibezh\NovapayPhp\Security\Signature;
use Tibezh\NovapayPhp\Utils\KeyGenerator;
use Tibezh\NovapayPhp\Exceptions\SignatureException;
use Tibezh\NovapayPhp\Tests\TestCase;

class SignaturePassphraseTest extends TestCase
{
    private string $encryptedPrivateKey;
    private string $publicKey;
    private string $passphrase;

    protected function setUp(): void
    {
        parent::setUp();

        // Generate encrypted key pair for testing
        $this->passphrase = 'test_passphrase_2024!';
        $keyPair = KeyGenerator::generateKeyPair(2048, $this->passphrase);

        $this->encryptedPrivateKey = $keyPair['private_key'];
        $this->publicKey = $keyPair['public_key'];
    }

    public function test_can_create_signature_with_encrypted_key(): void
    {
        $signature = new Signature($this->encryptedPrivateKey, $this->publicKey, $this->passphrase);

        $data = [
            'merchant_id' => 'test_merchant',
            'amount' => 100.50,
            'currency' => 'UAH'
        ];

        $signatureString = $signature->createSignature($data);

        $this->assertIsString($signatureString);
        $this->assertNotEmpty($signatureString);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+=*$/', $signatureString);
    }

    public function test_can_verify_signature_with_encrypted_key(): void
    {
        $signature = new Signature($this->encryptedPrivateKey, $this->publicKey, $this->passphrase);

        $data = [
            'session_id' => 'test_session_123',
            'status' => 'paid',
            'amount' => 250.00
        ];

        $signatureString = $signature->createSignature($data);
        $isValid = $signature->verifySignature($data, $signatureString);

        $this->assertTrue($isValid);
    }

    public function test_fails_with_wrong_passphrase(): void
    {
        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Invalid private key or incorrect passphrase');

        $signature = new Signature($this->encryptedPrivateKey, $this->publicKey, 'wrong_passphrase');

        $signature->createSignature(['test' => 'data']);
    }

    public function test_fails_with_no_passphrase_for_encrypted_key(): void
    {
        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Invalid private key or incorrect passphrase');

        $signature = new Signature($this->encryptedPrivateKey, $this->publicKey, null);

        $signature->createSignature(['test' => 'data']);
    }

    public function test_works_with_unencrypted_key(): void
    {
        // Generate unencrypted key pair
        $keyPair = KeyGenerator::generateKeyPair(2048); // No passphrase
        $unencryptedPrivateKey = $keyPair['private_key'];
        $publicKey = $keyPair['public_key'];

        $signature = new Signature($unencryptedPrivateKey, $publicKey, null);

        $data = ['test' => 'data', 'amount' => 100];
        $signatureString = $signature->createSignature($data);
        $isValid = $signature->verifySignature($data, $signatureString);

        $this->assertTrue($isValid);
    }

    public function test_validate_private_key_method(): void
    {
        $signature = new Signature($this->encryptedPrivateKey, $this->publicKey, $this->passphrase);

        $isValid = $signature->validatePrivateKey();
        $this->assertTrue($isValid);
    }

    public function test_validate_private_key_fails_with_wrong_passphrase(): void
    {
        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Invalid private key or incorrect passphrase');

        $signature = new Signature($this->encryptedPrivateKey, $this->publicKey, 'wrong_passphrase');
        $signature->validatePrivateKey();
    }

    public function test_is_private_key_encrypted_detection(): void
    {
        $signature = new Signature($this->encryptedPrivateKey, $this->publicKey, $this->passphrase);

        $isEncrypted = $signature->isPrivateKeyEncrypted();
        $this->assertTrue($isEncrypted);
    }

    public function test_is_private_key_encrypted_detection_unencrypted(): void
    {
        // Generate unencrypted key pair
        $keyPair = KeyGenerator::generateKeyPair(2048); // No passphrase
        $unencryptedPrivateKey = $keyPair['private_key'];

        $signature = new Signature($unencryptedPrivateKey, $this->publicKey, null);

        $isEncrypted = $signature->isPrivateKeyEncrypted();
        $this->assertFalse($isEncrypted);
    }

    public function test_create_test_signature(): void
    {
        $signature = new Signature($this->encryptedPrivateKey, $this->publicKey, $this->passphrase);

        $testResult = $signature->createTestSignature();

        $this->assertArrayHasKey('signature', $testResult);
        $this->assertArrayHasKey('data', $testResult);
        $this->assertIsString($testResult['signature']);
        $this->assertIsArray($testResult['data']);

        // Verify the test signature
        $isValid = $signature->verifySignature($testResult['data'], $testResult['signature']);
        $this->assertTrue($isValid);
    }

    public function test_signature_consistency_with_encrypted_key(): void
    {
        $signature = new Signature($this->encryptedPrivateKey, $this->publicKey, $this->passphrase);

        $data = [
            'merchant_id' => 'test_merchant',
            'amount' => 100.50,
            'currency' => 'UAH'
        ];

        $signature1 = $signature->createSignature($data);
        $signature2 = $signature->createSignature($data);

        // Should be consistent
        $this->assertEquals($signature1, $signature2);
    }

    public function test_different_passphrases_produce_same_signature(): void
    {
        // This test verifies that the same key with different passphrases
        // (if they decrypt to the same key) produces the same signature

        // First, decrypt the key and re-encrypt with different passphrase
        $decryptedKey = KeyGenerator::decryptPrivateKey($this->encryptedPrivateKey, $this->passphrase);
        $newPassphrase = 'different_passphrase_123!';
        $reencryptedKey = KeyGenerator::encryptPrivateKey($decryptedKey, $newPassphrase);

        $signature1 = new Signature($this->encryptedPrivateKey, $this->publicKey, $this->passphrase);
        $signature2 = new Signature($reencryptedKey, $this->publicKey, $newPassphrase);

        $data = ['test' => 'data', 'amount' => 100];

        $sig1 = $signature1->createSignature($data);
        $sig2 = $signature2->createSignature($data);

        // Should produce the same signature since it's the same key
        $this->assertEquals($sig1, $sig2);
    }

    public function test_empty_passphrase_handled_correctly(): void
    {
        // Empty string passphrase should be treated as no passphrase
        $keyPair = KeyGenerator::generateKeyPair(2048); // Unencrypted
        $unencryptedKey = $keyPair['private_key'];

        $signature = new Signature($unencryptedKey, $keyPair['public_key'], '');

        $data = ['test' => 'data'];
        $signatureString = $signature->createSignature($data);

        $this->assertIsString($signatureString);
        $this->assertNotEmpty($signatureString);
    }

    public function test_null_passphrase_handled_correctly(): void
    {
        // null passphrase should work with unencrypted keys
        $keyPair = KeyGenerator::generateKeyPair(2048); // Unencrypted
        $unencryptedKey = $keyPair['private_key'];

        $signature = new Signature($unencryptedKey, $keyPair['public_key'], null);

        $data = ['test' => 'data'];
        $signatureString = $signature->createSignature($data);

        $this->assertIsString($signatureString);
        $this->assertNotEmpty($signatureString);
    }

}
