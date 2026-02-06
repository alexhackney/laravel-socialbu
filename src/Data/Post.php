<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data;

use Carbon\CarbonImmutable;

final readonly class Post
{
    /**
     * @param  array<int>  $accountIds
     * @param  array<array<string, mixed>>|null  $attachments
     */
    public function __construct(
        public int $id,
        public string $content,
        public string $status,
        public array $accountIds,
        public ?CarbonImmutable $publishAt,
        public ?array $attachments,
        public CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            content: $data['content'] ?? '',
            status: $data['status'] ?? self::deriveStatus($data),
            accountIds: self::parseAccountIds($data),
            publishAt: isset($data['publish_at'])
                ? CarbonImmutable::parse($data['publish_at'])
                : null,
            attachments: $data['attachments'] ?? null,
            createdAt: CarbonImmutable::parse($data['created_at'] ?? 'now'),
            updatedAt: isset($data['updated_at'])
                ? CarbonImmutable::parse($data['updated_at'])
                : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'content' => $this->content,
            'status' => $this->status,
            'account_ids' => $this->accountIds,
            'publish_at' => $this->publishAt?->toDateTimeString(),
            'attachments' => $this->attachments,
            'created_at' => $this->createdAt->toDateTimeString(),
            'updated_at' => $this->updatedAt?->toDateTimeString(),
        ], fn ($value) => $value !== null);
    }

    public function isScheduled(): bool
    {
        return $this->publishAt !== null && $this->publishAt->isFuture();
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Derive a status string from boolean flags when no `status` field is present.
     */
    private static function deriveStatus(array $data): string
    {
        if (! empty($data['draft'])) {
            return 'draft';
        }

        if (! empty($data['published'])) {
            return 'published';
        }

        if (isset($data['approved']) && $data['approved'] === false) {
            return 'awaiting_approval';
        }

        if (isset($data['publish_at'])) {
            return 'scheduled';
        }

        return 'draft';
    }

    /**
     * @return array<int>
     */
    private static function parseAccountIds(array $data): array
    {
        if (isset($data['account_ids'])) {
            return array_map('intval', $data['account_ids']);
        }

        if (isset($data['account_id'])) {
            return [(int) $data['account_id']];
        }

        if (isset($data['accounts'])) {
            return array_map(
                fn ($account) => is_array($account) ? (int) $account['id'] : (int) $account,
                $data['accounts']
            );
        }

        return [];
    }
}
