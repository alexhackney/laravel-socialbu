<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data\Insights;

final readonly class TopPost
{
    /**
     * @param  array<array<string, mixed>>|null  $attachments
     * @param  array<PostInsight>  $insights
     */
    public function __construct(
        public int $id,
        public string $content,
        public int $accountId,
        public string $accountType,
        public string $type,
        public ?array $attachments,
        public ?string $publishAt,
        public ?string $publishedAt,
        public bool $published,
        public ?string $permalink,
        public array $insights,
    ) {}

    public static function fromArray(array $data): self
    {
        $insights = array_map(
            fn (array $item) => PostInsight::fromArray($item),
            $data['insights'] ?? []
        );

        return new self(
            id: (int) ($data['id'] ?? 0),
            content: $data['content'] ?? '',
            accountId: (int) ($data['account_id'] ?? $data['accountId'] ?? 0),
            accountType: $data['account_type'] ?? $data['accountType'] ?? '',
            type: $data['type'] ?? '',
            attachments: $data['attachments'] ?? null,
            publishAt: $data['publish_at'] ?? $data['publishAt'] ?? null,
            publishedAt: $data['published_at'] ?? $data['publishedAt'] ?? null,
            published: (bool) ($data['published'] ?? false),
            permalink: $data['permalink'] ?? null,
            insights: $insights,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'content' => $this->content,
            'account_id' => $this->accountId,
            'account_type' => $this->accountType,
            'type' => $this->type,
            'attachments' => $this->attachments,
            'publish_at' => $this->publishAt,
            'published_at' => $this->publishedAt,
            'published' => $this->published,
            'permalink' => $this->permalink,
            'insights' => array_map(fn (PostInsight $i) => $i->toArray(), $this->insights),
        ], fn ($value) => $value !== null);
    }

    /**
     * Get the value for a specific insight type.
     */
    public function insightValue(string $type): int|float|null
    {
        foreach ($this->insights as $insight) {
            if ($insight->type === $type) {
                return $insight->value;
            }
        }

        return null;
    }
}
