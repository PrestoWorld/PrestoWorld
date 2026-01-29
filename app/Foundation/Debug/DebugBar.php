<?php

declare(strict_types=1);

namespace App\Foundation\Debug;

use App\Foundation\Application;
use PrestoWorld\Theme\ThemeManager;

class DebugBar
{
    protected Application $app;
    protected float $startTime;
    protected array $queries = [];
    protected array $benchmarks = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->reset();
    }

    public function reset(): void
    {
        $this->startTime = microtime(true);
        $this->queries = [];
        $this->benchmarks = [];
    }

    public function startTimer(string $name): void
    {
        $this->benchmarks[$name] = microtime(true);
    }

    public function endTimer(string $name): float
    {
        if (!isset($this->benchmarks[$name])) {
            return 0;
        }
        $duration = microtime(true) - $this->benchmarks[$name];
        $this->benchmarks[$name] = $duration;
        return $duration;
    }

    public function logQuery(string $sql, float $time, array $bindings = []): void
    {
        $this->queries[] = [
            'sql' => $sql,
            'time' => $time,
            'bindings' => $bindings
        ];
    }

    public function render(): string
    {
        $totalTime = (microtime(true) - $this->startTime) * 1000;
        $memory = memory_get_peak_usage(true) / 1024 / 1024;
        $queryCount = count($this->queries);
        $queryTime = array_sum(array_column($this->queries, 'time')) * 1000;

        // Gather additional info
        $phpVersion = PHP_VERSION;
        $serverInfo = $_SERVER['SERVER_SOFTWARE'] ?? PHP_SAPI;
        
        $themeName = 'None';
        $themeEngine = 'None';
        $templateEngine = 'PHP'; // Default

        if ($this->app->has(ThemeManager::class)) {
            $themeManager = $this->app->make(ThemeManager::class);
            $activeTheme = $themeManager->getActiveTheme();
            if ($activeTheme) {
                $themeName = $activeTheme->getName();
                $engine = $activeTheme->getEngine();
                $themeEngine = ucfirst($activeTheme->getType());
                $templateEngine = $engine->getTemplateEngineName();
            }
        }

        // Current context (WordPress focused)
        $context = $GLOBALS['__presto_current_context'] ?? 'None';
        
        if ($this->app->has('current_context')) {
            $context = $this->app->make('current_context');
        }

        if ($context === 'None' && function_exists('get_queried_object')) {
            try {
                $obj = get_queried_object();
                if ($obj) {
                    if (isset($obj->title)) {
                        $context = ($obj->type ?? 'Post') . ": " . $obj->title;
                    } elseif (isset($obj->post_title)) {
                        $context = "Post: " . $obj->post_title;
                    } elseif (isset($obj->name)) {
                        $context = "Term: " . $obj->name;
                    } elseif (is_object($obj)) {
                        $context = get_class($obj);
                    } else {
                        $context = (string)$obj;
                    }
                }
            } catch (\Throwable $e) {
                $context = 'Error';
            }
        }

        // WordPress Admin Screen Detection
        $wpScreen = null;
        if (isset($GLOBALS['__presto_admin_context'])) {
            $data = $GLOBALS['__presto_admin_context'];
            $wpScreen = ($data['driver'] ?? 'wp') . ':' . ($data['screen'] ?? 'unknown');
        }
        $html = "
        <!-- PrestoWorld Debug Bar -->
        <style>
            #pw-debug-bar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 35px;
                background: #1e293b;
                color: #f8fafc;
                font-family: 'Inter', system-ui, sans-serif;
                font-size: 11px;
                display: flex;
                align-items: center;
                padding: 0 15px;
                border-top: 1px solid #334155;
                z-index: 1000000;
                box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
                white-space: nowrap;
            }
            .pw-db-item {
                display: flex;
                align-items: center;
                margin-right: 20px;
                flex-shrink: 0;
            }
            .pw-db-divider {
                width: 1px;
                height: 15px;
                background: #334155;
                margin-right: 20px;
            }
            .pw-db-label { color: #94a3b8; margin-right: 4px; }
            .pw-db-value { font-weight: 600; color: #818cf8; }
            .pw-db-icon { margin-right: 4px; opacity: 0.7; }
            .pw-db-highlight { color: #fbbf24; }
            .pw-query-list {
                display: none;
                position: absolute;
                bottom: 35px;
                left: 0;
                background: #0f172a;
                border: 1px solid #334155;
                padding: 10px;
                border-radius: 4px;
                width: 600px;
                max-height: 400px;
                overflow-y: auto;
                box-shadow: 0 -10px 15px -3px rgba(0, 0, 0, 0.5);
                z-index: 1000001;
            }
            .pw-query-list.pw-active { display: block !important; }
            .pw-db-item:hover .pw-query-list { display: block; }
            .pw-query-item {
                border-bottom: 1px solid #1e293b;
                padding: 5px 0;
                white-space: pre-wrap;
                font-family: 'Fira Code', monospace;
                color: #e2e8f0;
            }
            .pw-query-item:last-child { border-bottom: none; }
            .pw-query-time { color: #10b981; font-weight: bold; font-size: 10px; }
        </style>
        <script>
            function pwToggleQueries(event) {
                event.stopPropagation();
                const list = document.querySelector('.pw-query-list');
                list.classList.toggle('pw-active');
            }
            document.addEventListener('click', function() {
                const list = document.querySelector('.pw-query-list');
                if (list) list.classList.remove('pw-active');
            });
        </script>
        <div id='pw-debug-bar'>
            <div class='pw-db-item'>
                <span class='pw-db-icon'>⚡</span>
                <span class='pw-db-label'>Time:</span>
                <span class='pw-db-value'>" . number_format($totalTime, 2) . "ms</span>
            </div>
            <div class='pw-db-item'>
                <span class='pw-db-icon'>🧠</span>
                <span class='pw-db-label'>Memory:</span>
                <span class='pw-db-value'>" . number_format($memory, 2) . "MB</span>
            </div>
            <div class='pw-db-item' style='position: relative; cursor: pointer;' onclick='pwToggleQueries(event)'>
                <span class='pw-db-icon'>🗄️</span>
                <span class='pw-db-label'>Queries:</span>
                <span class='pw-db-value'>$queryCount (" . number_format($queryTime, 2) . "ms)</span>
                
                <div class='pw-query-list' onclick='event.stopPropagation()'>
                    <div style='font-weight: bold; border-bottom: 2px solid #334155; padding-bottom: 5px; margin-bottom: 5px; color: #f1f5f9;'>Executed Queries</div>
                    " . array_reduce($this->queries, function($carry, $q) {
                        return $carry . "<div class='pw-query-item'>
                            <div class='pw-query-time'>[" . number_format($q['time'] * 1000, 2) . "ms]</div>
                            <code>" . htmlspecialchars($q['sql']) . "</code>
                        </div>";
                    }, '') . "
                </div>
            </div>

            <div class='pw-db-divider'></div>

            <div class='pw-db-item'>
                <span class='pw-db-label'>Theme:</span>
                <span class='pw-db-value pw-db-highlight'>$themeName</span>
            </div>
            <div class='pw-db-item'>
                <span class='pw-db-label'>Engine:</span>
                <span class='pw-db-value'>$themeEngine / $templateEngine</span>
            </div>

            <div class='pw-db-divider'></div>

            <div class='pw-db-item'>
                <span class='pw-db-label'>Context:</span>
                <span class='pw-db-value' title='Current Queried Object'>$context</span>
            </div>
            " . ($wpScreen ? "
            <div class='pw-db-item'>
                <span class='pw-db-label'>Screen:</span>
                <span class='pw-db-value pw-db-highlight'>$wpScreen</span>
            </div>" : "") . "


            <div class='pw-db-divider'></div>

            <div class='pw-db-item'>
                <span class='pw-db-label'>PHP:</span>
                <span class='pw-db-value'>$phpVersion</span>
            </div>
            <div class='pw-db-item' title='$serverInfo'>
                <span class='pw-db-label'>Server:</span>
                <span class='pw-db-value'>" . (strlen($serverInfo) > 15 ? substr($serverInfo, 0, 15) . '...' : $serverInfo) . "</span>
            </div>

            <div class='pw-db-item' style='margin-left: auto;'>
                <span class='pw-db-label' style='font-weight: bold; color: #f1f5f9;'>PrestoWorld</span>
            </div>
        </div>
        ";

        return $html;
    }
}
