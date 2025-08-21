<?php

declare(strict_types=1);

namespace Tibezh\NovapayPhp\Exceptions;

use Exception;

/**
 * API request exception
 */
class ApiException extends NovaPayException
{
  private ?array $responseData;

  public function __construct(string $message, int $code = 0, ?array $responseData = null)
  {
    parent::__construct($message, $code);
    $this->responseData = $responseData;
  }

  public function getResponseData(): ?array
  {
    return $this->responseData;
  }
}
