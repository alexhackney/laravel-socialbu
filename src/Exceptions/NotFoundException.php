<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Throwable;

class NotFoundException extends SocialBuException
{
    /**
     * @param  array<array-key, mixed>|null  $response
     * @param  array<string, mixed>|null  $request
     */
    public function __construct(
        string $message = 'The requested resource was not found.',
        ?array $response = null,
        ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 404, $response, $request, $previous);
    }
}
