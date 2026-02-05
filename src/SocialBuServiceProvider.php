<?php

declare(strict_types=1);

namespace Hei\SocialBu;

use Hei\SocialBu\Client\SocialBuClient;
use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Commands\GetPostCommand;
use Hei\SocialBu\Commands\ListAccountsCommand;
use Hei\SocialBu\Commands\TestPostCommand;
use Illuminate\Support\ServiceProvider;

class SocialBuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/socialbu.php',
            'socialbu'
        );

        $this->app->singleton(SocialBuClientInterface::class, function ($app) {
            $config = $app['config']['socialbu'];

            return new SocialBuClient(
                token: $config['token'] ?? null,
                accountIds: $config['account_ids'] ?? [],
                baseUrl: $config['base_url'] ?? 'https://socialbu.com/api/v1',
                timeout: $config['http']['timeout'] ?? 30,
                connectTimeout: $config['http']['connect_timeout'] ?? 10,
            );
        });

        $this->app->alias(SocialBuClientInterface::class, 'socialbu');
        $this->app->alias(SocialBuClientInterface::class, SocialBuClient::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/socialbu.php' => config_path('socialbu.php'),
        ], 'socialbu-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListAccountsCommand::class,
                TestPostCommand::class,
                GetPostCommand::class,
            ]);
        }

        $this->loadWebhookRoutes();
    }

    /**
     * Load webhook routes if enabled.
     */
    private function loadWebhookRoutes(): void
    {
        if (! config('socialbu.webhooks.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
    }
}
