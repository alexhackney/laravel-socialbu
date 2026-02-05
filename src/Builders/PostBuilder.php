<?php

declare(strict_types=1);

namespace Hei\SocialBu\Builders;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\Post;
use Hei\SocialBu\Exceptions\ValidationException;

class PostBuilder
{
    private string $content = '';

    /** @var array<string> */
    private array $mediaPaths = [];

    /** @var array<int> */
    private array $accountIds = [];

    private ?string $publishAt = null;

    private bool $draft = false;

    private ?string $postbackUrl = null;

    public function __construct(
        private readonly SocialBuClientInterface $client,
    ) {}

    /**
     * Set the post content.
     */
    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Add media to the post.
     *
     * Can be called multiple times to add multiple media files.
     * Supports local file paths and remote URLs.
     */
    public function media(string $path): self
    {
        $this->mediaPaths[] = $path;

        return $this;
    }

    /**
     * Set the target account IDs.
     *
     * Can be called with variadic int arguments or an array.
     *
     * @param  int|array<int>  ...$accountIds
     */
    public function to(int|array ...$accountIds): self
    {
        foreach ($accountIds as $id) {
            if (is_array($id)) {
                $this->accountIds = array_merge($this->accountIds, $id);
            } else {
                $this->accountIds[] = $id;
            }
        }

        return $this;
    }

    /**
     * Schedule the post for a future time.
     */
    public function scheduledAt(CarbonInterface|DateTimeInterface|string $datetime): self
    {
        if ($datetime instanceof CarbonInterface) {
            $this->publishAt = $datetime->toDateTimeString();
        } elseif ($datetime instanceof DateTimeInterface) {
            $this->publishAt = $datetime->format('Y-m-d H:i:s');
        } else {
            $this->publishAt = $datetime;
        }

        return $this;
    }

    /**
     * Alias for scheduledAt().
     */
    public function schedule(CarbonInterface|DateTimeInterface|string $datetime): self
    {
        return $this->scheduledAt($datetime);
    }

    /**
     * Save as draft instead of publishing.
     */
    public function asDraft(): self
    {
        $this->draft = true;

        return $this;
    }

    /**
     * Set the postback URL for status webhooks.
     */
    public function withPostbackUrl(string $url): self
    {
        $this->postbackUrl = $url;

        return $this;
    }

    /**
     * Validate and return the payload without sending.
     *
     * @throws ValidationException
     */
    public function dryRun(): array
    {
        $this->validate();

        return $this->buildPayload();
    }

    /**
     * Send the post.
     *
     * @throws ValidationException
     */
    public function send(): Post
    {
        $this->validate();

        // Upload media if any
        $attachments = $this->uploadMedia();

        // Create the post
        return $this->client->posts()->create(
            content: $this->content,
            accountIds: $this->resolveAccountIds(),
            publishAt: $this->publishAt,
            attachments: $attachments,
            draft: $this->draft,
            postbackUrl: $this->postbackUrl,
        );
    }

    /**
     * Validate the builder state.
     *
     * @throws ValidationException
     */
    private function validate(): void
    {
        $errors = [];

        if (trim($this->content) === '') {
            $errors['content'] = ['Content is required.'];
        }

        $accountIds = $this->resolveAccountIds();
        if (empty($accountIds)) {
            $errors['accounts'] = ['At least one account ID is required.'];
        }

        if (! empty($errors)) {
            throw new ValidationException('Validation failed.', $errors);
        }
    }

    /**
     * Resolve account IDs, using defaults if none specified.
     *
     * @return array<int>
     */
    private function resolveAccountIds(): array
    {
        if (! empty($this->accountIds)) {
            return array_values(array_unique($this->accountIds));
        }

        return $this->client->getAccountIds();
    }

    /**
     * Upload all media files and return attachment tokens.
     *
     * @return array<array{upload_token: string}>|null
     */
    private function uploadMedia(): ?array
    {
        if (empty($this->mediaPaths)) {
            return null;
        }

        $attachments = [];

        foreach ($this->mediaPaths as $path) {
            $upload = $this->client->media()->upload($path);
            $attachments[] = $upload->toAttachment();
        }

        return $attachments;
    }

    /**
     * Build the payload that would be sent.
     */
    private function buildPayload(): array
    {
        return array_filter([
            'content' => $this->content,
            'accounts' => $this->resolveAccountIds(),
            'publish_at' => $this->publishAt,
            'media_paths' => $this->mediaPaths ?: null,
            'draft' => $this->draft ?: null,
            'postback_url' => $this->postbackUrl,
        ], fn ($value) => $value !== null);
    }
}
