<?php

declare(strict_types=1);

namespace PrestoWorld\Hooks\Dispatchers;

use Witals\Framework\Application;
use PrestoWorld\Contracts\Hooks\Dispatchers\ActionDispatcherInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Server as SwooleServer;

class SwooleTaskDispatcher implements ActionDispatcherInterface
{
    protected Application $app;
    protected $server;

    public function __construct(Application $app, $server)
    {
        $this->app = $app;
        $this->server = $server;
    }

    public function dispatch(string $hook, array $hookData, array $args): void
    {
        $this->incrementRunCount($hook);
        
        if ($this->server instanceof SwooleHttpServer || $this->server instanceof SwooleServer) {
            // Push to Task Worker
            $this->server->task([
                'type' => 'hook_action',
                'hook' => $hook,
                'hook_data' => $hookData,
                'args' => $args
            ]);
            return;
        }

        // Fallback to sync if server is not capable
        $this->app->make(\PrestoWorld\Hooks\HookManager::class)
            ->executeDispatchedAction($hookData, $args);
    }
    
    protected array $actionCounts = [];

    public function getRunCount(string $hook): int
    {
        // Note: Counting in Swoole Task Dispatcher might be tricky across processes
        // Ideally should use Atomic or Table for accurate counts.
        // For now, local process count.
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
