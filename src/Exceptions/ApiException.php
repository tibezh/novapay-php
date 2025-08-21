<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Exceptions;

use Exception;

/**
 * API request exception
 */
class ApiException extends NovaPayException
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $responseData;

    /**
     * @param string $message
     * @param int $code
     * @param array<string, mixed>|null $responseData
     */
    public function __construct(string $message, int $code = 0, ?array $responseData = null)
    {
        parent::__construct($message, $code);
        $this->responseData = $responseData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
