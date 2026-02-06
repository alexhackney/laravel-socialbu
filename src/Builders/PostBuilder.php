<?php

declare(strict_types=1);

namespace Hei\SocialBu\Builders;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\Account;
use Hei\SocialBu\Data\Post;
use Hei\SocialBu\Exceptions\ValidationException;

class PostBuilder
{
    private string $content = '';

    /** @var array<string> */
    private array $mediaPaths = [];

    /** @var array<int> */
    private array $accountIds = [];

    /** @var array<Account> */
    private array $accounts = [];

    private ?string $publishAt = null;

    private bool $draft = false;

    private ?string $postbackUrl = null;

    private ?array $options = null;

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
     * Set target accounts with pre-fetched Account objects.
     *
     * When accounts are provided this way, send() will use them for
     * capability validation without making additional API calls.
     * Account IDs are also derived from the objects automatically.
     */
    public function toAccounts(Account ...$accounts): self
    {
        foreach ($accounts as $account) {
            $this->accounts[$account->id] = $account;
            $this->accountIds[] = $account->id;
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
     * Set platform-specific options (e.g. Reddit title, TikTok privacy).
     */
    public function withOptions(array $options): self
    {
        $this->options = $options;

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
        $this->validateAccountCapabilities();

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
            options: $this->options,
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
     * Validate content and media against each target account's capabilities.
     *
     * @throws ValidationException
     */
    private function validateAccountCapabilities(): void
    {
        $accountIds = $this->resolveAccountIds();
        $errors = [];

        foreach ($accountIds as $accountId) {
            $account = $this->accounts[$accountId] ?? $this->client->accounts()->get($accountId);

            // Check content length
            if ($account->postMaxLength !== null) {
                $length = mb_strlen($this->content);
                if ($length > $account->postMaxLength) {
                    $errors['content'][] = "Content ({$length} chars) exceeds limit for {$account->name} (max {$account->postMaxLength}).";
                }
            }

            // Check media required
            if ($account->requiresMedia() && empty($this->mediaPaths)) {
                $errors['media'][] = "{$account->name} requires at least one media attachment.";
            }

            // Check max attachments
            if ($account->maxAttachments !== null && count($this->mediaPaths) > $account->maxAttachments) {
                $errors['attachments'][] = "Too many attachments for {$account->name} (max {$account->maxAttachments}, got ".count($this->mediaPaths).').';
            }
        }

        if (! empty($errors)) {
            throw new ValidationException('Account capability validation failed.', $errors);
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
     * Build the payload that would be sent to the API.
     *
     * Note: Media files listed in pending_uploads would be uploaded
     * and replaced with existing_attachments tokens when send() is called.
     */
    private function buildPayload(): array
    {
        return array_filter([
            'content' => $this->content,
            'accounts' => $this->resolveAccountIds(),
            'publish_at' => $this->publishAt,
            'pending_uploads' => $this->mediaPaths ?: null,
            'draft' => $this->draft ?: null,
            'postback_url' => $this->postbackUrl,
            'options' => $this->options,
        ], fn ($value) => $value !== null);
    }
}
