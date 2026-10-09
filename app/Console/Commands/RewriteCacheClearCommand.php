<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use App\Http\RewriteRuleCache;

class RewriteCacheClearCommand extends Command
{
    protected string $name = 'rewrite:clear';
    protected string $description = 'Clear the rewrite rules cache';

    public function handle(array $args): int
    {
        $cachePath = $this->app->storagePath('framework/cache/rewrite-rules.json');
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
