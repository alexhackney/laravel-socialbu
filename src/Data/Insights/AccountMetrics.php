<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data\Insights;

final readonly class AccountMetrics
{
    /**
     * @param  array<string, array<TimeSeriesPoint>>  $metrics
     */
    public function __construct(
        public int $accountId,
        public array $metrics,
    ) {}

    public static function fromArray(array $data): self
    {
        $metrics = [];

        foreach ($data['metrics'] ?? [] as $name => $points) {
            $metrics[$name] = array_map(
                fn (array $point) => TimeSeriesPoint::fromArray($point),
                $points
            );
        }

        return new self(
            accountId: (int) ($data['account_id'] ?? $data['accountId'] ?? 0),
            metrics: $metrics,
        );
    }

    public function toArray(): array
    {
        $metrics = [];

        foreach ($this->metrics as $name => $points) {
            $metrics[$name] = array_map(
                fn (TimeSeriesPoint $point) => $point->toArray(),
                $points
            );
        }

        return [
            'account_id' => $this->accountId,
            'metrics' => $metrics,
        ];
    }

    /**
     * Get the time series for a specific metric.
     *
     * @return array<TimeSeriesPoint>|null
     */
    public function metric(string $name): ?array
    {
        return $this->metrics[$name] ?? null;
    }
}
