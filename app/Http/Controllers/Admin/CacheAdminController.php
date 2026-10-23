<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use PrestoWorld\Core\Cache\CacheManager;
use PrestoWorld\Core\Cache\CacheSettings;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Dashboard API cho màn hình Cache — stats/purge/settings của mọi loại cache.
 *
 * GET  /api/admin/cache/stats     — cards + timing + framework caches
 * POST /api/admin/cache/purge     — {type: page|fragment|parse|hooks|all}
 * POST /api/admin/cache/settings  — {type, enabled, ttl?}
 */
class CacheAdminController
{
    public function stats(): Response
    {
        return Response::json(CacheManager::stats());
    }

    public function purge(Request $request): Response
    {
        $type = $this->stringParam($request, 'type', '');

        if ($type === '') {
            return Response::json(['error' => 'Missing "type" param'], 400);
        }

        try {
            $result = CacheManager::purge($type);
        } catch (\InvalidArgumentException $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }

        return Response::json($result + ['ok' => true]);
    }

    public function settings(Request $request): Response
    {
        $type = $this->stringParam($request, 'type', '');

        if ($type === '' || !in_array($type, CacheSettings::TYPES, true)) {
            return Response::json(['error' => 'Unknown "type"'], 400);
        }

        $body = $request->getJson();
        $enabled = $this->boolParam($request, 'enabled');
        if ($enabled === null) {
            return Response::json(['error' => 'Missing "enabled" param'], 400);
        }

        $ttl = null;
        if (array_key_exists('ttl', is_array($body) ? $body : [])) {
            $ttl = max(0, (int) $body['ttl']);
        }

        try {
            CacheManager::setSettings($type, $enabled, $ttl);
        } catch (\InvalidArgumentException $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }

        return Response::json([
            'ok' => true,
            'settings' => CacheSettings::get($type),
        ]);
    }

    private function stringParam(Request $request, string $key, string $default = ''): string
    {
        $value = $request->query($key, null);
        if ($value === null) {
            $json = $request->getJson();
            $value = is_array($json) ? ($json[$key] ?? null) : null;
        }

        return is_scalar($value) ? (string) $value : $default;
    }

    private function boolParam(Request $request, string $key): ?bool
    {
        $value = null;
        $json = $request->getJson();
        if (is_array($json) && array_key_exists($key, $json)) {
            $value = $json[$key];
        }
        if ($value === null) {
            $value = $request->query($key, null);
        }

        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) (int) $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }
}
