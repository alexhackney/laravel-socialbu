<?php

declare(strict_types=1);

namespace Hei\SocialBu\Tests;

use Hei\SocialBu\SocialBuServiceProvider;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SocialBuServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('socialbu.token', 'test-token');
        $app['config']->set('socialbu.account_ids', [123, 456]);
        $app['config']->set('socialbu.base_url', 'https://socialbu.com/api/v1');
    }

    protected function fixture(string $path): array
    {
        $content = file_get_contents(__DIR__.'/Fixtures/'.$path);

        return json_decode($content, true);
    }
}
