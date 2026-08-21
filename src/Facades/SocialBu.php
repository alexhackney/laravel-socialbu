<?php

declare(strict_types=1);

namespace Hei\SocialBu\Facades;

use Hei\SocialBu\Builders\PostBuilder;
use Hei\SocialBu\Client\SocialBuClient;
use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\Post;
use Hei\SocialBu\Resources\AccountResource;
use Hei\SocialBu\Resources\InsightsResource;
use Hei\SocialBu\Resources\MediaResource;
use Hei\SocialBu\Resources\PostResource;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PostResource posts()
 * @method static AccountResource accounts()
 * @method static MediaResource media()
 * @method static InsightsResource insights()
 * @method static PostBuilder create()
 * @method static Post publish(string $content, ?string $mediaPath = null)
 * @method static bool isConfigured()
 * @method static array<int> getAccountIds()
 * @method static array<array-key, mixed> get(string $endpoint, array<string, mixed> $query = [])
 * @method static array<array-key, mixed> post(string $endpoint, array<string, mixed> $data = [])
 * @method static array<array-key, mixed> patch(string $endpoint, array<string, mixed> $data = [])
 * @method static array<array-key, mixed> delete(string $endpoint)
 *
 * @see SocialBuClient
 */
class SocialBu extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SocialBuClientInterface::class;
    }
}
