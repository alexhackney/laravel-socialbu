<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data\Insights;

final readonly class PostInsight
{
    public function __construct(
        public string $type,
        public int|float $value,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? '',
            value: $data['value'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
        ];
    }
}
