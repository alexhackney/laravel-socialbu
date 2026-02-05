<?php

declare(strict_types=1);

namespace Hei\SocialBu\Facades;

use Hei\SocialBu\Builders\PostBuilder;
use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\Post;
use Hei\SocialBu\Resources\AccountResource;
use Hei\SocialBu\Resources\MediaResource;
use Hei\SocialBu\Resources\PostResource;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PostResource posts()
 * @method static AccountResource accounts()
 * @method static MediaResource media()
 * @method static PostBuilder create()
 * @method static Post publish(string $content, ?string $mediaPath = null)
 * @method static bool isConfigured()
 * @method static array<int> getAccountIds()
 * @method static array get(string $endpoint, array $query = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array patch(string $endpoint, array $data = [])
 * @method static array delete(string $endpoint)
 *
 * @see \Hei\SocialBu\Client\SocialBuClient
 */
class SocialBu extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SocialBuClientInterface::class;
    }
}
