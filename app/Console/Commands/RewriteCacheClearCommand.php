<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use App\Http\RewriteRuleCache;

class RewriteCacheClearCommand extends Command
{
    protected string $name = 'rewrite:clear';
    protected string $description = 'Clear the rewrite rules cache';

    /**
     * @param list<string> $args
     */
    public function handle(array $args): int
    {
        /** @var \Witals\Framework\Application $app */
        $app = $this->app;
        $cachePath = $app->storagePath('framework/cache/rewrite-rules.json');
        $cache = new RewriteRuleCache($cachePath);

        if ($cache->isValid()) {
            $cache->clear();
            $this->info('Rewrite rules cache cleared.');
        } else {
            $this->line('Rewrite rules cache is empty or invalid.');
        }

        return 0;
    }
}
