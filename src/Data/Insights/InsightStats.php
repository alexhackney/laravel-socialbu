<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data\Insights;

final readonly class InsightStats
{
    public function __construct(
        public int $unreadFeeds,
        public int $userAutomations,
        public int $userPendingPosts,
        public int $userFailedPosts,
        public int $inactiveAccounts,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            unreadFeeds: (int) ($data['unreadFeeds'] ?? $data['unread_feeds'] ?? 0),
            userAutomations: (int) ($data['userAutomations'] ?? $data['user_automations'] ?? 0),
            userPendingPosts: (int) ($data['userPendingPosts'] ?? $data['user_pending_posts'] ?? 0),
            userFailedPosts: (int) ($data['userFailedPosts'] ?? $data['user_failed_posts'] ?? 0),
            inactiveAccounts: (int) ($data['inactiveAccounts'] ?? $data['inactive_accounts'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'unreadFeeds' => $this->unreadFeeds,
            'userAutomations' => $this->userAutomations,
            'userPendingPosts' => $this->userPendingPosts,
            'userFailedPosts' => $this->userFailedPosts,
            'inactiveAccounts' => $this->inactiveAccounts,
        ];
    }

    /**
     * Check if there are any issues requiring attention.
     */
    public function hasIssues(): bool
    {
        return $this->userFailedPosts > 0 || $this->inactiveAccounts > 0;
    }
}
