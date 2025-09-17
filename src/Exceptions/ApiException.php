<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Exceptions;

/**
 * API request exception with enhanced error handling
 */
class ApiException extends NovaPayException
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $responseData;

    /**
     * @var string|null
     */
    private ?string $requestId;

    /**
     * @var string|null
     */
    private ?string $errorCode;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $validationErrors = null;

    /**
     * @param string $message
     * @param int $code HTTP status code
     * @param array<string, mixed>|null $responseData
     * @param string|null $requestId Request UUID from API
     * @param string|null $errorCode Error code from API
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?array $responseData = null,
        ?string $requestId = null,
        ?string $errorCode = null
    ) {
        parent::__construct($message, $code);
        $this->responseData = $responseData;
        $this->requestId = $requestId;
        $this->errorCode = $errorCode;
        $this->parseValidationErrors();
    }

    /**
     * Create ApiException from NovaPay error response
     *
     * @param array<string, mixed> $errorData
     * @param int $httpCode
     *
     * @return ApiException
     */
    public static function fromApiResponse(array $errorData, int $httpCode): self
    {
        $message = $errorData['error'] ?? 'Unknown API error';
        $requestId = $errorData['uuid'] ?? null;
        $errorCode = $errorData['code'] ?? null;

        return new self($message, $httpCode, $errorData, $requestId, $errorCode);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * @return string|null
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getValidationErrors(): ?array
    {
        return $this->validationErrors;
    }

    /**
     * Check if this is a validation error
     */
    public function isValidationError(): bool
    {
        return $this->responseData !== null
            && isset($this->responseData['type'])
            && $this->responseData['type'] === 'validation';
    }

    /**
     * Check if this is a processing error
     */
    public function isProcessingError(): bool
    {
        return $this->responseData !== null
            && isset($this->responseData['type'])
            && $this->responseData['type'] === 'processing';
    }

    /**
     * Get a formatted error message for display
     */
    public function getDisplayMessage(): string
    {
        if ($this->isValidationError() && $this->validationErrors) {
            $messages = [];
            foreach ($this->validationErrors as $error) {
                $messages[] = $error['message'] ?? 'Validation error';
            }
            return implode('; ', $messages);
        }

        return $this->getMessage();
    }

    /**
     * Get detailed error information
     *
     * @return array<string, mixed>
     */
    public function getDetailedInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'request_id' => $this->requestId,
            'error_code' => $this->errorCode,
            'type' => $this->getErrorType(),
            'validation_errors' => $this->validationErrors,
            'response_data' => $this->responseData
        ];
    }

    /**
     * Get error type from response
     */
    public function getErrorType(): ?string
    {
        return $this->responseData['type'] ?? null;
    }

    /**
     * Parse validation errors from response data
     */
    private function parseValidationErrors(): void
    {
        if ($this->responseData !== null
            && isset($this->responseData['errors'])
            && is_array($this->responseData['errors'])) {
            $this->validationErrors = $this->responseData['errors'];
        }
    }

    /**
     * Convert to array for logging
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'request_id' => $this->requestId,
            'error_code' => $this->errorCode,
            'type' => $this->getErrorType(),
            'validation_errors' => $this->validationErrors,
            'response_data' => $this->responseData
        ];
    }

}
