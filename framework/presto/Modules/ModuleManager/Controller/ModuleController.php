<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ModuleManager\Controller;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\AbstractController;
use Witals\Framework\Module\ModuleManager as WitalsModuleManager;
use Witals\Framework\Module\Contracts\ModuleInterface;

class ModuleController extends AbstractController
{
    protected WitalsModuleManager $moduleManager;

    public function __construct(WitalsModuleManager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    public function index(Request $request): Response
    {
        $modules = $this->moduleManager->all();

        $moduleData = [];
        foreach ($modules as $name => $metadata) {
            $moduleData[] = [
                'name' => $name,
                'version' => $metadata['version'] ?? 'unknown',
                'description' => $metadata['description'] ?? '',
                'type' => $metadata['type'] ?? 'support',
                'priority' => $metadata['priority'] ?? 50,
                'isLoaded' => $this->moduleManager->isLoaded($name),
                'path' => $metadata['_path'] ?? '',
                'dependencies' => array_keys($metadata['dependencies'] ?? []),
                'provides' => $metadata['provides'] ?? [],
            ];
        }

        return Response::json([
            'success' => true,
            'data' => $moduleData,
        ]);
    }

    public function show(Request $request, string $name): Response
    {
        $modules = $this->moduleManager->all();
        $metadata = $modules[$name] ?? null;

        if (!$metadata) {
            return Response::json([
                'success' => false,
                'error' => "Module '{$name}' not found.",
            ], 404);
        }

        return Response::json([
            'success' => true,
            'data' => [
                'name' => $name,
                'version' => $metadata['version'] ?? 'unknown',
                'description' => $metadata['description'] ?? '',
                'type' => $metadata['type'] ?? 'support',
                'priority' => $metadata['priority'] ?? 50,
                'isLoaded' => $this->moduleManager->isLoaded($name),
                'path' => $metadata['_path'] ?? '',
                'dependencies' => array_keys($metadata['dependencies'] ?? []),
                'provides' => $metadata['provides'] ?? [],
            ],
        ]);
    }

    public function load(Request $request, string $name): Response
    {
        $module = $this->moduleManager->load($name);

        if (!$module) {
            return Response::json([
                'success' => false,
                'error' => "Failed to load module '{$name}'.",
            ], 400);
        }

        return Response::json([
            'success' => true,
            'data' => [
                'name' => $module->getName(),
                'version' => $module->getVersion(),
            ],
            'message' => "Module '{$name}' loaded successfully.",
        ]);
    }

    public function unload(Request $request, string $name): Response
    {
        $this->moduleManager->resetLifecycle();

        return Response::json([
            'success' => true,
            'message' => "Module lifecycle reset. Individual module unloading is not currently supported.",
        ]);
    }

    public function reset(Request $request): Response
    {
        $this->moduleManager->resetLifecycle();

        return Response::json([
            'success' => true,
            'message' => "Module lifecycle reset. All modules unloaded.",
        ]);
    }

    public function routes(Request $request, string $name): Response
    {
        if (!$this->moduleManager->isLoaded($name)) {
            $module = $this->moduleManager->load($name);
            if (!$module) {
                return Response::json([
                    'success' => false,
                    'error' => "Module '{$name}' not found or failed to load.",
                ], 404);
            }
        }

        $routeIndex = $this->moduleManager->buildRouteIndex();

        $matchedRoutes = [];
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'] as $method) {
            foreach ($routeIndex[$method] ?? [] as $entry) {
                if ($entry['module'] === $name) {
                    $matchedRoutes[] = $entry;
                }
            }
        }

        return Response::json([
            'success' => true,
            'data' => [
                'module' => $name,
                'routes' => $matchedRoutes,
            ],
        ]);
    }
}
