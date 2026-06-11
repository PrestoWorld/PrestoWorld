<?php

declare(strict_types=1);

namespace PrestoWorld\Hooks\Dispatchers;

use Witals\Framework\Application;
use PrestoWorld\Contracts\Hooks\Dispatchers\ActionDispatcherInterface;

class SyncDispatcher implements ActionDispatcherInterface
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected array $actionCounts = [];

    public function dispatch(string $hook, array $hookData, array $args): void
    {
        $this->incrementRunCount($hook);
        
        $this->app->make(\PrestoWorld\Hooks\HookManager::class)
            ->executeDispatchedAction($hookData, $args);
    }
    
    public function getRunCount(string $hook): int
    {
        return $this->actionCounts[$hook] ?? 0;
    }

    public function incrementRunCount(string $hook): void
    {
        if (!isset($this->actionCounts[$hook])) {
            $this->actionCounts[$hook] = 0;
        }
        $this->actionCounts[$hook]++;
    }

    public function flush(): void
    {
        $this->actionCounts = [];
    }
}
