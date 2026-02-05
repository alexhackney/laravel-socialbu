<?php

declare(strict_types=1);

namespace Hei\SocialBu\Commands;

use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Exceptions\SocialBuException;
use Illuminate\Console\Command;

class ListAccountsCommand extends Command
{
    protected $signature = 'socialbu:accounts
                            {--type= : Filter by account type (user, shared)}
                            {--json : Output as JSON}';

    protected $description = 'List all connected social accounts';

    public function handle(SocialBuClientInterface $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('SocialBu is not configured. Set SOCIALBU_TOKEN in your .env file.');

            return self::FAILURE;
        }

        try {
            $accounts = $client->accounts()->all(
                type: $this->option('type'),
            );

            if (empty($accounts)) {
                $this->info('No accounts found.');

                return self::SUCCESS;
            }

            if ($this->option('json')) {
                $this->line(json_encode(
                    array_map(fn ($a) => $a->toArray(), $accounts),
                    JSON_PRETTY_PRINT
                ));

                return self::SUCCESS;
            }

            $this->table(
                ['ID', 'Name', 'Type', 'Status', 'Username'],
                array_map(fn ($account) => [
                    $account->id,
                    $account->name,
                    $account->type,
                    $account->status,
                    $account->username ?? '-',
                ], $accounts)
            );

            $this->newLine();
            $this->info(sprintf('Total: %d account(s)', count($accounts)));

            return self::SUCCESS;
        } catch (SocialBuException $e) {
            $this->error('Failed to fetch accounts: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
