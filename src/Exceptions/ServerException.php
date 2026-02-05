<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Throwable;

class ServerException extends SocialBuException
{
    public function __construct(
        string $message = 'Server error occurred.',
        int $statusCode = 500,
        ?array $response = null,
        ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $response, $request, $previous);
    }
}
