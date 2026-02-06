<?php

declare(strict_types=1);

namespace Hei\SocialBu\Resources;

use DateTimeInterface;
use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\Insights\AccountMetrics;
use Hei\SocialBu\Data\Insights\InsightStats;
use Hei\SocialBu\Data\Insights\TimeSeriesPoint;
use Hei\SocialBu\Data\Insights\TopPost;

class InsightsResource
{
    public function __construct(
        private readonly SocialBuClientInterface $client,
    ) {}

    /**
     * Get dashboard stats for the authenticated user.
     */
    public function stats(): InsightStats
    {
        $response = $this->client->get('/insights/stats');

        return InsightStats::fromArray($response);
    }

    /**
     * Get post counts per day within a date range.
     *
     * @param  array<int>|null  $accounts
     * @return array<TimeSeriesPoint>
     */
    public function postCounts(
        string|DateTimeInterface $start,
        string|DateTimeInterface $end,
        ?array $accounts = null,
        ?string $postType = null,
        ?int $team = null,
    ): array {
        $query = array_filter([
            'start' => $this->formatDate($start),
            'end' => $this->formatDate($end),
            'accounts' => $accounts,
            'post_type' => $postType,
            'team' => $team,
        ], fn ($value) => $value !== null);

        $response = $this->client->get('/insights/posts/counts', $query);

        $items = $response['data'] ?? $response;

        if (! is_array($items) || empty($items)) {
            return [];
        }

        if (! array_is_list($items)) {
            return [];
        }

        return array_map(
            fn (array $point) => TimeSeriesPoint::fromArray($point),
            $items
        );
    }

    /**
     * Get metrics for posts within a date range.
     *
     * @param  array<string>  $metrics  Metric names (e.g. 'likes', 'comments', 'shares')
     * @param  array<int>|null  $accounts
     * @return array<string, array<TimeSeriesPoint>>
     */
    public function postMetrics(
        string|DateTimeInterface $start,
        string|DateTimeInterface $end,
        array $metrics,
        ?string $postType = null,
        ?array $accounts = null,
        ?int $team = null,
    ): array {
        $query = array_filter([
            'start' => $this->formatDate($start),
            'end' => $this->formatDate($end),
            'metrics' => implode(',', $metrics),
            'post_type' => $postType,
            'accounts' => $accounts,
            'team' => $team,
        ], fn ($value) => $value !== null);

        $response = $this->client->get('/insights/posts/metrics', $query);

        $data = $response['data'] ?? $response;

        // Empty response: {"data": []} returns a list, not a keyed object
        if (! is_array($data) || empty($data) || array_is_list($data)) {
            return [];
        }

        // The API may nest under "items" or return metrics directly
        $metricsData = $data['items'] ?? $data;

        $result = [];

        foreach ($metricsData as $name => $points) {
            if (! is_array($points)) {
                continue;
            }

            $result[$name] = array_map(
                fn (array $point) => TimeSeriesPoint::fromArray($point),
                $points
            );
        }

        return $result;
    }

    /**
     * Get top-performing posts within a date range.
     *
     * @param  array<string>  $metrics  Metric names to rank by (e.g. 'likes', 'comments')
     * @param  array<int>|null  $accounts
     * @return array<TopPost>
     */
    public function topPosts(
        string|DateTimeInterface $start,
        string|DateTimeInterface $end,
        array $metrics,
        ?array $accounts = null,
        ?int $team = null,
    ): array {
        $query = array_filter([
            'start' => $this->formatDate($start),
            'end' => $this->formatDate($end),
            'metrics' => implode(',', $metrics),
            'accounts' => $accounts,
            'team' => $team,
        ], fn ($value) => $value !== null);

        $response = $this->client->get('/insights/posts/top_posts', $query);

        $items = $response['data'] ?? $response;

        if (! is_array($items) || empty($items)) {
            return [];
        }

        if (! array_is_list($items)) {
            return [];
        }

        return array_map(
            fn (array $post) => TopPost::fromArray($post),
            $items
        );
    }

    /**
     * Get metrics for accounts within a date range.
     *
     * @param  array<string>  $metrics  Metric names (e.g. 'followers', 'total_views')
     * @param  array<int>|null  $accounts
     * @return array<AccountMetrics>
     */
    public function accountMetrics(
        string|DateTimeInterface $start,
        string|DateTimeInterface $end,
        array $metrics,
        ?array $accounts = null,
        ?bool $calculateGrowth = null,
        ?int $team = null,
    ): array {
        $query = array_filter([
            'start' => $this->formatDate($start),
            'end' => $this->formatDate($end),
            'metrics' => implode(',', $metrics),
            'accounts' => $accounts,
            'calculate_growth' => $calculateGrowth,
            'team' => $team,
        ], fn ($value) => $value !== null);

        $response = $this->client->get('/insights/accounts/metrics', $query);

        $items = $response['data'] ?? $response;

        if (! is_array($items) || empty($items)) {
            return [];
        }

        if (! array_is_list($items)) {
            return [];
        }

        return array_map(
            fn (array $account) => AccountMetrics::fromArray($account),
            $items
        );
    }

    /**
     * Format a date value for the API query string.
     */
    private function formatDate(string|DateTimeInterface $date): string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return $date;
    }
}
