<?php

declare(strict_types=1);

namespace Hei\SocialBu\Testing;

use Generator;
use Hei\SocialBu\Builders\PostBuilder;
use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\Account;
use Hei\SocialBu\Data\MediaUpload;
use Hei\SocialBu\Data\PaginatedResponse;
use Hei\SocialBu\Data\Post;
use Hei\SocialBu\Resources\AccountResource;
use Hei\SocialBu\Resources\MediaResource;
use Hei\SocialBu\Resources\PostResource;
use PHPUnit\Framework\Assert;
use Throwable;

class FakeSocialBu implements SocialBuClientInterface
{
    /** @var array<array<string, mixed>> */
    private array $published = [];

    /** @var array<array<string, mixed>> */
    private array $uploads = [];

    private ?Throwable $throwOnPublish = null;

    private ?Throwable $throwOnUpload = null;

    /** @var array<Account> */
    private array $fakeAccounts = [];

    /** @var array<Post> */
    private array $fakePosts = [];

    private int $nextPostId = 1;

    private int $nextUploadId = 1;

    /**
     * Create and bind a fake instance.
     */
    public static function fake(): self
    {
        $fake = new self;

        app()->instance(SocialBuClientInterface::class, $fake);

        return $fake;
    }

    /**
     * Set accounts to return from the API.
     *
     * @param  array<Account|array>  $accounts
     */
    public function withAccounts(array $accounts): self
    {
        $this->fakeAccounts = array_map(
            fn ($a) => $a instanceof Account ? $a : Account::fromArray($a),
            $accounts
        );

        return $this;
    }

    /**
     * Set posts to return from the API.
     *
     * @param  array<Post|array>  $posts
     */
    public function withPosts(array $posts): self
    {
        $this->fakePosts = array_map(
            fn ($p) => $p instanceof Post ? $p : Post::fromArray($p),
            $posts
        );

        return $this;
    }

    /**
     * Throw an exception on the next publish attempt.
     */
    public function throwOnPublish(Throwable $exception): self
    {
        $this->throwOnPublish = $exception;

        return $this;
    }

    /**
     * Throw an exception on the next upload attempt.
     */
    public function throwOnUpload(Throwable $exception): self
    {
        $this->throwOnUpload = $exception;

        return $this;
    }

    /**
     * Get all published posts.
     *
     * @return array<array<string, mixed>>
     */
    public function getPublished(): array
    {
        return $this->published;
    }

    /**
     * Get all uploads.
     *
     * @return array<array<string, mixed>>
     */
    public function getUploads(): array
    {
        return $this->uploads;
    }

    /**
     * Assert that content was published.
     */
    public function assertPublished(string $content): void
    {
        $found = collect($this->published)->contains(fn ($p) => str_contains($p['content'], $content));

        Assert::assertTrue($found, "Expected content '{$content}' was not published.");
    }

    /**
     * Assert the number of posts published.
     */
    public function assertPublishedCount(int $count): void
    {
        Assert::assertCount($count, $this->published, "Expected {$count} posts, got ".count($this->published));
    }

    /**
     * Assert content was published to specific accounts.
     *
     * @param  array<int>  $accountIds
     */
    public function assertPublishedTo(array $accountIds): void
    {
        $found = collect($this->published)->contains(function ($p) use ($accountIds) {
            $publishedTo = $p['accounts'] ?? [];
            sort($publishedTo);
            sort($accountIds);

            return $publishedTo === $accountIds;
        });

        Assert::assertTrue($found, 'No post was published to accounts: '.implode(', ', $accountIds));
    }

    /**
     * Assert nothing was published.
     */
    public function assertNothingPublished(): void
    {
        Assert::assertEmpty($this->published, 'Expected no posts, but '.count($this->published).' were published.');
    }

    /**
     * Assert media was uploaded.
     */
    public function assertUploaded(string $path): void
    {
        $found = collect($this->uploads)->contains(fn ($u) => $u['path'] === $path);

        Assert::assertTrue($found, "Expected upload of '{$path}' was not found.");
    }

    /**
     * Assert the number of files uploaded.
     */
    public function assertUploadedCount(int $count): void
    {
        Assert::assertCount($count, $this->uploads, "Expected {$count} uploads, got ".count($this->uploads));
    }

    // SocialBuClientInterface implementation

    public function posts(): PostResource
    {
        return new FakePostResource($this);
    }

    public function accounts(): AccountResource
    {
        return new FakeAccountResource($this);
    }

    public function media(): MediaResource
    {
        return new FakeMediaResource($this);
    }

    public function create(): PostBuilder
    {
        return new PostBuilder($this);
    }

    public function publish(string $content, ?string $mediaPath = null): Post
    {
        $builder = $this->create()->content($content);

        if ($mediaPath !== null) {
            $builder->media($mediaPath);
        }

        return $builder->send();
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getAccountIds(): array
    {
        return [1, 2, 3];
    }

    public function get(string $endpoint, array $query = []): array
    {
        return [];
    }

    public function post(string $endpoint, array $data = []): array
    {
        return [];
    }

    public function patch(string $endpoint, array $data = []): array
    {
        return [];
    }

    public function delete(string $endpoint): array
    {
        return [];
    }

    // Internal methods for fake resources

    /**
     * @internal
     */
    public function recordPublish(array $data): Post
    {
        if ($this->throwOnPublish) {
            $e = $this->throwOnPublish;
            $this->throwOnPublish = null;

            throw $e;
        }

        $this->published[] = $data;

        return Post::fromArray(array_merge($data, [
            'id' => $this->nextPostId++,
            'status' => $data['draft'] ?? false ? 'draft' : ($data['publish_at'] ?? null ? 'scheduled' : 'published'),
            'account_ids' => $data['accounts'] ?? [],
            'created_at' => now()->toDateTimeString(),
        ]));
    }

    /**
     * @internal
     */
    public function recordUpload(string $path): MediaUpload
    {
        if ($this->throwOnUpload) {
            $e = $this->throwOnUpload;
            $this->throwOnUpload = null;

            throw $e;
        }

        $id = $this->nextUploadId++;

        $this->uploads[] = [
            'path' => $path,
            'id' => $id,
        ];

        return new MediaUpload(
            uploadToken: "fake-token-{$id}",
            key: "uploads/fake-{$id}",
            url: "https://fake-cdn.example.com/fake-{$id}",
            secureKey: "fake-secure-{$id}",
            mimeType: 'image/jpeg',
            name: basename($path),
        );
    }

    /**
     * @internal
     *
     * @return array<Account>
     */
    public function getFakeAccounts(): array
    {
        return $this->fakeAccounts;
    }

    /**
     * @internal
     *
     * @return array<Post>
     */
    public function getFakePosts(): array
    {
        return $this->fakePosts;
    }
}

/**
 * @internal
 */
class FakePostResource extends PostResource
{
    public function __construct(
        private readonly FakeSocialBu $fake,
    ) {}

    public function list(?string $type = null, int $page = 1, int $perPage = 15): array
    {
        return $this->fake->getFakePosts();
    }

    public function get(int $postId): Post
    {
        foreach ($this->fake->getFakePosts() as $post) {
            if ($post->id === $postId) {
                return $post;
            }
        }

        return Post::fromArray(['id' => $postId, 'content' => 'Fake post', 'created_at' => now()]);
    }

    public function create(
        string $content,
        array $accountIds,
        ?string $publishAt = null,
        ?array $attachments = null,
        bool $draft = false,
        ?string $postbackUrl = null,
        ?array $options = null,
    ): Post {
        return $this->fake->recordPublish([
            'content' => $content,
            'accounts' => $accountIds,
            'publish_at' => $publishAt,
            'attachments' => $attachments,
            'draft' => $draft,
            'postback_url' => $postbackUrl,
            'options' => $options,
        ]);
    }

    public function update(int $postId, array $data): bool
    {
        return true;
    }

    public function delete(int $postId): bool
    {
        return true;
    }

    public function paginate(?string $type = null, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $posts = $this->fake->getFakePosts();

        return new PaginatedResponse(
            items: $posts,
            currentPage: $page,
            lastPage: 1,
            perPage: $perPage,
            total: count($posts),
        );
    }

    public function lazy(?string $type = null, int $perPage = 15): Generator
    {
        foreach ($this->fake->getFakePosts() as $post) {
            yield $post;
        }
    }

    public function all(?string $type = null, int $perPage = 50): array
    {
        return $this->fake->getFakePosts();
    }
}

/**
 * @internal
 */
class FakeAccountResource extends AccountResource
{
    public function __construct(
        private readonly FakeSocialBu $fake,
    ) {}

    public function list(?string $type = null, int $page = 1, int $perPage = 15): array
    {
        return $this->fake->getFakeAccounts();
    }

    public function get(int $accountId): Account
    {
        foreach ($this->fake->getFakeAccounts() as $account) {
            if ($account->id === $accountId) {
                return $account;
            }
        }

        return Account::fromArray(['id' => $accountId, 'name' => 'Fake Account', 'type' => 'facebook']);
    }

    public function paginate(?string $type = null, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $accounts = $this->fake->getFakeAccounts();

        return new PaginatedResponse(
            items: $accounts,
            currentPage: $page,
            lastPage: 1,
            perPage: $perPage,
            total: count($accounts),
        );
    }

    public function lazy(?string $type = null, int $perPage = 15): Generator
    {
        foreach ($this->fake->getFakeAccounts() as $account) {
            yield $account;
        }
    }

    public function all(?string $type = null, int $perPage = 50): array
    {
        return $this->fake->getFakeAccounts();
    }
}

/**
 * @internal
 */
class FakeMediaResource extends MediaResource
{
    public function __construct(
        private readonly FakeSocialBu $fake,
    ) {}

    public function upload(string $path): MediaUpload
    {
        return $this->fake->recordUpload($path);
    }
}
