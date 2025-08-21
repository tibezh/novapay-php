<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Tests\Exceptions;

use Tibezh\NovapayPhp\Exceptions\NovaPayException;
use Tibezh\NovapayPhp\Exceptions\SignatureException;
use Tibezh\NovapayPhp\Exceptions\ApiException;
use Tibezh\NovapayPhp\Tests\TestCase;

class ExceptionsTest extends TestCase
{
    public function test_novapay_exception_is_base_exception(): void
    {
        $exception = new NovaPayException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function test_novapay_exception_with_code(): void
    {
        $exception = new NovaPayException('Test message', 500);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    public function test_signature_exception_extends_novapay_exception(): void
    {
        $exception = new SignatureException('Invalid signature');

        $this->assertInstanceOf(NovaPayException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Invalid signature', $exception->getMessage());
    }

    public function test_signature_exception_with_code(): void
    {
        $exception = new SignatureException('Signature verification failed', 403);

        $this->assertEquals('Signature verification failed', $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
    }

    public function test_api_exception_extends_novapay_exception(): void
    {
        $exception = new ApiException('API error');

        $this->assertInstanceOf(NovaPayException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('API error', $exception->getMessage());
    }

    public function test_api_exception_with_response_data(): void
    {
        $responseData = [
          'error' => 'INVALID_REQUEST',
          'message' => 'Invalid request parameters',
          'details' => ['field' => 'amount', 'error' => 'required']
        ];

        $exception = new ApiException('Request failed', 400, $responseData);

        $this->assertEquals('Request failed', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertEquals($responseData, $exception->getResponseData());
    }

    public function test_api_exception_without_response_data(): void
    {
        $exception = new ApiException('Network error', 500);

        $this->assertEquals('Network error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertNull($exception->getResponseData());
    }

    public function test_api_exception_with_null_response_data(): void
    {
        $exception = new ApiException('Server error', 500, null);

        $this->assertEquals('Server error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertNull($exception->getResponseData());
    }

    public function test_api_exception_with_empty_response_data(): void
    {
        $exception = new ApiException('Empty response', 204, []);

        $this->assertEquals('Empty response', $exception->getMessage());
        $this->assertEquals(204, $exception->getCode());
        $this->assertEquals([], $exception->getResponseData());
    }

    public function test_exception_inheritance_chain(): void
    {
        $signatureException = new SignatureException('Signature error');
        $apiException = new ApiException('API error');

        // Test that both extend NovaPayException
        $this->assertInstanceOf(NovaPayException::class, $signatureException);
        $this->assertInstanceOf(NovaPayException::class, $apiException);

        // Test that both can be caught as NovaPayException
        try {
            throw $signatureException;
        } catch (NovaPayException $e) {
            $this->assertInstanceOf(SignatureException::class, $e);
        }

        try {
            throw $apiException;
        } catch (NovaPayException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function test_exceptions_can_be_serialized(): void
    {
        $responseData = ['error' => 'test', 'code' => 400];
        $exception = new ApiException('Test error', 400, $responseData);

        $serialized = serialize($exception);
        $unserialized = unserialize($serialized);

        $this->assertEquals($exception->getMessage(), $unserialized->getMessage());
        $this->assertEquals($exception->getCode(), $unserialized->getCode());
        $this->assertEquals($exception->getResponseData(), $unserialized->getResponseData());
    }

    public function test_exception_messages_are_preserved(): void
    {
        $longMessage = 'This is a very long error message that contains detailed information about what went wrong during the API request processing and should be preserved completely without any truncation or modification';

        $exception = new NovaPayException($longMessage);

        $this->assertEquals($longMessage, $exception->getMessage());
    }

    public function test_exceptions_with_previous_exception(): void
    {
        $previousException = new \RuntimeException('Previous error');
        $novaPayException = new NovaPayException('NovaPay error', 0, $previousException);

        $this->assertEquals('NovaPay error', $novaPayException->getMessage());
        $this->assertSame($previousException, $novaPayException->getPrevious());
    }
}
