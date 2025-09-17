<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Tests\Exceptions;

use Exception;
use Tibezh\NovapayPhp\Exceptions\ApiException;
use Tibezh\NovapayPhp\Exceptions\NovaPayException;
use Tibezh\NovapayPhp\Tests\TestCase;

class ApiExceptionTest extends TestCase
{

    public function test_api_exception_extends_novapay_exception(): void
    {
        $exception = new ApiException('Test API error', 400);

        $this->assertInstanceOf(NovaPayException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function test_api_exception_basic_constructor(): void
    {
        $exception = new ApiException('Test error', 400);

        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertNull($exception->getResponseData());
        $this->assertNull($exception->getRequestId());
        $this->assertNull($exception->getErrorCode());
    }

    public function test_api_exception_with_full_parameters(): void
    {
        $responseData = [
            'error' => 'Invalid request',
            'code' => 'VALIDATION_ERROR',
            'uuid' => 'test-uuid-123'
        ];

        $exception = new ApiException(
            'Validation failed',
            400,
            $responseData,
            'test-uuid-123',
            'VALIDATION_ERROR'
        );

        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertEquals($responseData, $exception->getResponseData());
        $this->assertEquals('test-uuid-123', $exception->getRequestId());
        $this->assertEquals('VALIDATION_ERROR', $exception->getErrorCode());
    }

    public function test_from_api_response_factory_method(): void
    {
        $errorData = [
            'error' => 'Session not found',
            'uuid' => 'api-uuid-456',
            'code' => 'SessionNotFoundError',
            'type' => 'processing'
        ];

        $exception = ApiException::fromApiResponse($errorData, 404);

        $this->assertEquals('Session not found', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
        $this->assertEquals($errorData, $exception->getResponseData());
        $this->assertEquals('api-uuid-456', $exception->getRequestId());
        $this->assertEquals('SessionNotFoundError', $exception->getErrorCode());
        $this->assertEquals('processing', $exception->getErrorType());
    }

    public function test_from_api_response_with_missing_fields(): void
    {
        $errorData = [
            'some_field' => 'some_value'
        ];

        $exception = ApiException::fromApiResponse($errorData, 500);

        $this->assertEquals('Unknown API error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals($errorData, $exception->getResponseData());
        $this->assertNull($exception->getRequestId());
        $this->assertNull($exception->getErrorCode());
    }

    public function test_validation_error_detection(): void
    {
        $validationData = [
            'type' => 'validation',
            'errors' => [
                [
                    'instancePath' => '',
                    'schemaPath' => '#/required',
                    'keyword' => 'required',
                    'params' => ['missingProperty' => 'client_phone'],
                    'message' => 'обов\'язкове поле "client_phone"'
                ]
            ]
        ];

        $exception = ApiException::fromApiResponse($validationData, 400);

        $this->assertTrue($exception->isValidationError());
        $this->assertFalse($exception->isProcessingError());
        $this->assertNotNull($exception->getValidationErrors());
        $this->assertCount(1, $exception->getValidationErrors());
    }

    public function test_processing_error_detection(): void
    {
        $processingData = [
            'type' => 'processing',
            'error' => 'Session expired',
            'code' => 'SessionExpiredError'
        ];

        $exception = ApiException::fromApiResponse($processingData, 400);

        $this->assertTrue($exception->isProcessingError());
        $this->assertFalse($exception->isValidationError());
        $this->assertNull($exception->getValidationErrors());
    }

    public function test_get_display_message_for_validation_error(): void
    {
        $validationData = [
            'type' => 'validation',
            'errors' => [
                ['message' => 'Field A is required'],
                ['message' => 'Field B must be positive'],
                ['message' => 'Field C is invalid']
            ]
        ];

        $exception = ApiException::fromApiResponse($validationData, 400);

        $displayMessage = $exception->getDisplayMessage();

        $this->assertEquals('Field A is required; Field B must be positive; Field C is invalid', $displayMessage);
    }

    public function test_get_display_message_for_non_validation_error(): void
    {
        $errorData = [
            'type' => 'processing',
            'error' => 'Server error'
        ];

        $exception = ApiException::fromApiResponse($errorData, 500);

        $this->assertEquals('Server error', $exception->getDisplayMessage());
    }

    public function test_get_display_message_with_missing_validation_messages(): void
    {
        $validationData = [
            'type' => 'validation',
            'errors' => [
                ['field' => 'test'], // No message field
                ['message' => 'Valid message'],
                ['other_field' => 'value'] // No message field
            ]
        ];

        $exception = ApiException::fromApiResponse($validationData, 400);

        $displayMessage = $exception->getDisplayMessage();

        $this->assertEquals('Validation error; Valid message; Validation error', $displayMessage);
    }

    public function test_get_detailed_info(): void
    {
        $responseData = [
            'type' => 'processing',
            'error' => 'Payment failed',
            'code' => 'PAYMENT_FAILED',
            'uuid' => 'detail-uuid-789'
        ];

        $exception = ApiException::fromApiResponse($responseData, 402);

        $detailedInfo = $exception->getDetailedInfo();

        $expectedInfo = [
            'message' => 'Payment failed',
            'code' => 402,
            'request_id' => 'detail-uuid-789',
            'error_code' => 'PAYMENT_FAILED',
            'type' => 'processing',
            'validation_errors' => null,
            'response_data' => $responseData
        ];

        $this->assertEquals($expectedInfo, $detailedInfo);
    }

    public function test_to_array(): void
    {
        $exception = new ApiException(
            'Test error',
            400,
            ['key' => 'value'],
            'uuid-123',
            'TEST_ERROR'
        );

        $array = $exception->toArray();

        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('line', $array);
        $this->assertArrayHasKey('request_id', $array);
        $this->assertArrayHasKey('error_code', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('validation_errors', $array);
        $this->assertArrayHasKey('response_data', $array);

        $this->assertEquals('Test error', $array['message']);
        $this->assertEquals(400, $array['code']);
        $this->assertEquals('uuid-123', $array['request_id']);
        $this->assertEquals('TEST_ERROR', $array['error_code']);
    }

    public function test_validation_errors_parsing(): void
    {
        $responseWithErrors = [
            'type' => 'validation',
            'errors' => [
                [
                    'instancePath' => '/amount',
                    'schemaPath' => '#/properties/amount/minimum',
                    'keyword' => 'minimum',
                    'params' => ['limit' => 1],
                    'message' => 'must be >= 1'
                ],
                [
                    'instancePath' => '/client_phone',
                    'schemaPath' => '#/properties/client_phone/pattern',
                    'keyword' => 'pattern',
                    'params' => ['pattern' => '^\\+[0-9]{10,15}$'],
                    'message' => 'must match pattern'
                ]
            ]
        ];

        $exception = new ApiException('Validation failed', 400, $responseWithErrors);

        $validationErrors = $exception->getValidationErrors();

        $this->assertNotNull($validationErrors);
        $this->assertIsArray($validationErrors);
        $this->assertCount(2, $validationErrors);

        $this->assertArrayHasKey(0, $validationErrors);
        $this->assertArrayHasKey(1, $validationErrors);
        $this->assertArrayHasKey('message', $validationErrors[0]);
        $this->assertArrayHasKey('message', $validationErrors[1]);

        $this->assertEquals('must be >= 1', $validationErrors[0]['message']);
        $this->assertEquals('must match pattern', $validationErrors[1]['message']);
    }

    public function test_validation_errors_parsing_without_errors_array(): void
    {
        $responseWithoutErrors = [
            'type' => 'validation',
            'error' => 'Validation failed'
        ];

        $exception = new ApiException('Validation failed', 400, $responseWithoutErrors);

        $this->assertNull($exception->getValidationErrors());
    }

    public function test_exception_serialization(): void
    {
        $originalException = new ApiException(
            'Serialization test',
            400,
            ['test' => 'data'],
            'uuid-serialize',
            'SERIALIZE_ERROR'
        );

        $serialized = serialize($originalException);
        $unserialized = unserialize($serialized);

        $this->assertEquals($originalException->getMessage(), $unserialized->getMessage());
        $this->assertEquals($originalException->getCode(), $unserialized->getCode());
        $this->assertEquals($originalException->getResponseData(), $unserialized->getResponseData());
        $this->assertEquals($originalException->getRequestId(), $unserialized->getRequestId());
        $this->assertEquals($originalException->getErrorCode(), $unserialized->getErrorCode());
    }

    public function test_error_type_detection(): void
    {
        $unknownTypeData = [
            'type' => 'unknown_type',
            'error' => 'Unknown error type'
        ];

        $exception = ApiException::fromApiResponse($unknownTypeData, 500);

        $this->assertEquals('unknown_type', $exception->getErrorType());
        $this->assertFalse($exception->isValidationError());
        $this->assertFalse($exception->isProcessingError());
    }

    public function test_null_response_data_handling(): void
    {
        $exception = new ApiException('Null response test', 500, null);

        $this->assertNull($exception->getResponseData());
        $this->assertNull($exception->getErrorType());
        $this->assertNull($exception->getValidationErrors());
        $this->assertFalse($exception->isValidationError());
        $this->assertFalse($exception->isProcessingError());
        $this->assertEquals('Null response test', $exception->getDisplayMessage());
    }

}
