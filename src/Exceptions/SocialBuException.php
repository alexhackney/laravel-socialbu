<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Exception;
use Throwable;

class SocialBuException extends Exception
{
    public function __construct(
        string $message,
        protected int $statusCode = 0,
        protected ?array $response = null,
        protected ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

    public function getRequest(): ?array
    {
        return $this->request;
    }

    /**
     * Get context array for logging.
     */
    public function context(): array
    {
        return array_filter([
            'status_code' => $this->statusCode,
            'response' => $this->response,
            'request' => $this->request,
        ]);
    }
}
