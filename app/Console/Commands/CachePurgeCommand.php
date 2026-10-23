<?php

declare(strict_types=1);

namespace App\Console\Commands;

use PrestoWorld\Core\Cache\CacheManager;
use Witals\Framework\Console\Command;

/**
 * cache:purge [type] — xoá cache từ CLI.
 *
 * type: page|fragment|parse|hooks|framework|all (mặc định: all)
 */
class CachePurgeCommand extends Command
{
    protected string $name = 'cache:purge';
    protected string $description = 'Purge application caches (page, fragment, parse, hooks, framework, all)';

    /**
     * @param list<string> $args
     */
    public function handle(array $args): int
    {
        $type = $args[0] ?? 'all';

        try {
            $result = CacheManager::purge($type);
        } catch (\InvalidArgumentException $e) {
            $this->line($e->getMessage());
            $this->line('Types: page, fragment, parse, hooks, framework, all');

            return 1;
        }

        $this->info(sprintf('Purged [%s]: %s', $type, json_encode($result['purged'])));

        return 0;
    }
}
