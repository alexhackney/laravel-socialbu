<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data;

use Illuminate\Support\Collection;

final readonly class PaginatedResponse
{
    /**
     * @param  array<mixed>  $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}

    public static function fromArray(array $data, string $itemsKey = 'data'): self
    {
        $pagination = $data['pagination'] ?? $data['meta'] ?? $data;

        return new self(
            items: $data[$itemsKey] ?? $data['items'] ?? $data['data'] ?? [],
            currentPage: (int) ($pagination['currentPage'] ?? $pagination['current_page'] ?? 1),
            lastPage: (int) ($pagination['lastPage'] ?? $pagination['last_page'] ?? 1),
            perPage: (int) ($pagination['perPage'] ?? $pagination['per_page'] ?? 15),
            total: (int) ($pagination['total'] ?? count($data[$itemsKey] ?? $data['items'] ?? $data['data'] ?? [])),
        );
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function nextPage(): ?int
    {
        return $this->hasMorePages() ? $this->currentPage + 1 : null;
    }

    public function previousPage(): ?int
    {
        return $this->currentPage > 1 ? $this->currentPage - 1 : null;
    }

    public function isFirstPage(): bool
    {
        return $this->currentPage === 1;
    }

    public function isLastPage(): bool
    {
        return $this->currentPage === $this->lastPage;
    }

    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get items as a collection.
     *
     * @return Collection<int, mixed>
     */
    public function collect(): Collection
    {
        return collect($this->items);
    }

    /**
     * Map items through a callback.
     *
     * @template T
     *
     * @param  callable(mixed): T  $callback
     * @return array<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }
}
