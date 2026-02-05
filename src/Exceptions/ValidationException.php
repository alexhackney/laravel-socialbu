<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Throwable;

class ValidationException extends SocialBuException
{
    public function __construct(
        string $message = 'Validation failed.',
        protected array $errors = [],
        ?array $response = null,
        ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 422, $response, $request, $previous);
    }

    /**
     * Get the validation errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function context(): array
    {
        return array_merge(parent::context(), [
            'errors' => $this->errors,
        ]);
    }
}
