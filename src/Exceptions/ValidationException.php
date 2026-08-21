<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Throwable;

class ValidationException extends SocialBuException
{
    /**
     * @param  array<string, list<string>>  $errors  Field name to the messages against it.
     * @param  array<array-key, mixed>|null  $response
     * @param  array<string, mixed>|null  $request
     */
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
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return array_merge(parent::context(), [
            'errors' => $this->errors,
        ]);
    }
}
