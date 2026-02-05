<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Throwable;

class MediaUploadException extends SocialBuException
{
    public const STEP_SIGNED_URL = 'signed_url';

    public const STEP_S3_UPLOAD = 's3_upload';

    public const STEP_CONFIRMATION = 'confirmation';

    public function __construct(
        string $message,
        protected string $step,
        int $statusCode = 0,
        ?array $response = null,
        ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $response, $request, $previous);
    }

    /**
     * Create exception for a specific upload step.
     */
    public static function atStep(string $step, Throwable $previous): self
    {
        $messages = [
            self::STEP_SIGNED_URL => 'Failed to get signed URL for media upload.',
            self::STEP_S3_UPLOAD => 'Failed to upload media to storage.',
            self::STEP_CONFIRMATION => 'Failed to confirm media upload.',
        ];

        $statusCode = $previous instanceof SocialBuException
            ? $previous->getStatusCode()
            : 0;

        $response = $previous instanceof SocialBuException
            ? $previous->getResponse()
            : null;

        $request = $previous instanceof SocialBuException
            ? $previous->getRequest()
            : null;

        return new self(
            $messages[$step] ?? 'Media upload failed.',
            $step,
            $statusCode,
            $response,
            $request,
            $previous,
        );
    }

    /**
     * Get the step where the upload failed.
     */
    public function getStep(): string
    {
        return $this->step;
    }

    public function context(): array
    {
        return array_merge(parent::context(), [
            'step' => $this->step,
        ]);
    }
}
