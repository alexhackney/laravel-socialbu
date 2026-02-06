<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data\Insights;

final readonly class TimeSeriesPoint
{
    public function __construct(
        public string $date,
        public int|float $value,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            date: $data['date'] ?? '',
            value: $data['value'] ?? $data['count'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'value' => $this->value,
        ];
    }
}
