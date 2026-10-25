<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Http;

use PrestoWorld\Core\SyncCore\Domain\Contracts\ConnectorRegistryInterface;
use PrestoWorld\Core\SyncCore\Domain\Dto\SyncSettings;
use PrestoWorld\Core\SyncCore\Domain\Enums\SyncDirection;
use PrestoWorld\Core\SyncCore\Domain\Enums\SyncTrigger;
use PrestoWorld\Core\SyncCore\Engine\SyncEngine;
use PrestoWorld\Core\SyncCore\Storage\SyncJobRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncMappingRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncRecordRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncSettingsRepository;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Generic admin API shared by every sync connector.
 *
 *   GET    /api/admin/sync/connectors
 *   GET    /api/admin/sync/{platform}                 settings + overview
 *   PUT    /api/admin/sync/{platform}/settings
 *   POST   /api/admin/sync/{platform}/test
 *   GET    /api/admin/sync/{platform}/resources
 *   GET    /api/admin/sync/{platform}/jobs
 *   POST   /api/admin/sync/{platform}/jobs
 *   GET    /api/admin/sync/{platform}/jobs/{job}
 *   POST   /api/admin/sync/{platform}/jobs/{job}/cancel
 *   GET    /api/admin/sync/{platform}/jobs/{job}/records
 *   GET    /api/admin/sync/{platform}/mappings
 *   DELETE /api/admin/sync/{platform}/mappings/{mapping}
 */
final class SyncAdminController
{
    private const PREFIX = '/api/admin/sync';

    public function __construct(
        private ConnectorRegistryInterface $connectors,
        private SyncSettingsRepository $settings,
        private SyncJobRepository $jobs,
        private SyncRecordRepository $records,
        private SyncMappingRepository $mappings,
        private SyncEngine $engine,
    ) {
    }

    public function handle(Request $request, ?string $platform = null, ?string $job = null, ?string $mapping = null): Response
    {
        $method = strtoupper($request->method());
        $path = rtrim($request->path(), '/');

        try {
            if ($path === self::PREFIX . '/connectors') {
                return $this->connectorCatalog();
            }

            if ($platform === null || $platform === '') {
                return $this->error('Unknown platform', 404);
            }

            $connector = $this->connectors->get($platform);
            if ($connector === null) {
                return $this->error('Unknown platform: ' . $platform, 404);
            }

            $base = self::PREFIX . '/' . $platform;

            if ($path === $base || $path === $base . '/overview') {
                return $method === 'GET' ? $this->overview($platform) : $this->error('Method not allowed', 405);
            }

            if ($path === $base . '/settings') {
                return match ($method) {
                    'GET' => $this->getSettings($platform),
                    'PUT', 'POST' => $this->saveSettings($request, $platform),
                    default => $this->error('Method not allowed', 405),
                };
            }

            if ($path === $base . '/test') {
                return $method === 'POST' ? $this->test($platform) : $this->error('Method not allowed', 405);
            }

            if ($path === $base . '/resources') {
                return $method === 'GET' ? $this->resources($platform) : $this->error('Method not allowed', 405);
            }

            if ($path === $base . '/jobs') {
                return match ($method) {
                    'GET' => $this->listJobs($request, $platform),
                    'POST' => $this->createJob($request, $platform),
                    default => $this->error('Method not allowed', 405),
                };
            }

            if ($job !== null && $job !== '') {
                if ($path === $base . '/jobs/' . $job) {
                    return $method === 'GET' ? $this->showJob((int) $job) : $this->error('Method not allowed', 405);
                }
                if ($path === $base . '/jobs/' . $job . '/cancel') {
                    return $method === 'POST' ? $this->cancelJob((int) $job) : $this->error('Method not allowed', 405);
                }
                if ($path === $base . '/jobs/' . $job . '/records') {
                    return $method === 'GET' ? $this->listRecords($request, (int) $job) : $this->error('Method not allowed', 405);
                }
            }

            if ($path === $base . '/mappings') {
                return $method === 'GET' ? $this->listMappings($request, $platform) : $this->error('Method not allowed', 405);
            }

            if ($mapping !== null && $mapping !== '' && $path === $base . '/mappings/' . $mapping) {
                return $method === 'DELETE' ? $this->deleteMapping((int) $mapping) : $this->error('Method not allowed', 405);
            }

            return $this->error('Not found', 404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->error('Internal error: ' . $e->getMessage(), 500);
        }
    }

    private function connectorCatalog(): Response
    {
        $items = [];
        foreach ($this->connectors->all() as $connector) {
            $items[] = [
                'key' => $connector->key(),
                'label' => $connector->label(),
                'icon' => $connector->icon(),
                'resources' => array_map(fn ($r) => $r->toArray(), array_values($connector->resources())),
            ];
        }

        return $this->ok(['connectors' => $items]);
    }

    private function overview(string $platform): Response
    {
        $connector = $this->connectors->get($platform);
        $settings = $this->currentSettings($platform);
        $resources = array_map(fn ($r) => $r->toArray(), array_values($connector?->resources() ?? []));
        $mappings = $this->mappings->paginate(['platform' => $platform], 1, 1);
        $records = $this->records->countByPlatform($platform);

        return $this->ok([
            'connector' => [
                'key' => $connector?->key(),
                'label' => $connector?->label(),
                'icon' => $connector?->icon(),
            ],
            'settings' => $settings->toArray(),
            'settings_schema' => $connector?->settingsSchema() ?? [],
            'resources' => $resources,
            'stats' => $this->jobs->stats($platform) + [
                'mappings' => $mappings['total'],
                'records' => $records,
            ],
        ]);
    }

    private function getSettings(string $platform): Response
    {
        $connector = $this->connectors->get($platform);
        $settings = $this->currentSettings($platform);

        return $this->ok([
            'settings' => $settings->toArray(),
            'schema' => $connector?->settingsSchema() ?? [],
            'defaults' => $connector?->defaultSettings() ?? [],
        ]);
    }

    private function saveSettings(Request $request, string $platform): Response
    {
        $connector = $this->connectors->get($platform);
        $body = $request->getJson() ?? $request->post();
        $body = is_array($body) ? $body : [];

        $inbound = (bool) ($body['inbound_enabled'] ?? true);
        $outbound = (bool) ($body['outbound_enabled'] ?? false);
        $raw = $body['settings'] ?? [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        $raw = is_array($raw) ? $raw : [];

        if ($connector !== null) {
            $raw = $this->preserveSecrets($connector->settingsSchema(), $this->currentSettings($platform)->data, $raw);
        }
        $normalized = $connector?->validateSettings($raw) ?? $raw;
        $saved = $this->settings->save(new SyncSettings(
            platform: $platform,
            inboundEnabled: $inbound,
            outboundEnabled: $outbound,
            data: $normalized,
        ));

        return $this->ok(['settings' => $saved->toArray()]);
    }

    /**
     * Password inputs render empty; keep the previously stored value instead
     * of overwriting it with an empty string.
     *
     * @param list<array<string, mixed>> $schema
     * @param array<string, mixed> $current
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function preserveSecrets(array $schema, array $current, array $raw): array
    {
        foreach ($schema as $group) {
            $fields = is_array($group['fields'] ?? null) ? $group['fields'] : [];
            $groupKey = (string) ($group['group'] ?? '');
            $resourceKey = (string) ($group['resource'] ?? '');

            foreach ($fields as $field) {
                if (!is_array($field) || ($field['type'] ?? '') !== 'password') {
                    continue;
                }
                $key = (string) ($field['key'] ?? '');
                if ($key === '') {
                    continue;
                }

                if ($resourceKey !== '') {
                    $raw[$groupKey][$resourceKey][$key] = (string) ($raw[$groupKey][$resourceKey][$key] ?? '');
                    continue;
                }

                $incoming = (string) ($raw[$groupKey][$key] ?? '');
                if ($incoming === '') {
                    $raw[$groupKey][$key] = (string) ($current[$groupKey][$key] ?? '');
                }
            }
        }

        return $raw;
    }

    private function test(string $platform): Response
    {
        $connector = $this->connectors->get($platform);
        if ($connector === null) {
            return $this->error('Unknown platform', 404);
        }

        return $this->ok(['result' => $connector->testConnection($this->currentSettings($platform))]);
    }

    private function resources(string $platform): Response
    {
        $connector = $this->connectors->get($platform);
        $settings = $this->currentSettings($platform);

        return $this->ok([
            'resources' => array_map(fn ($r) => $r->toArray(), array_values($connector?->resources() ?? [])),
            'resource_settings' => $settings->data['resources'] ?? [],
        ]);
    }

    private function listJobs(Request $request, string $platform): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));

        $result = $this->jobs->paginate([
            'platform' => $platform,
            'direction' => (string) $request->query('direction', ''),
            'resource' => (string) $request->query('resource', ''),
            'status' => (string) $request->query('status', ''),
        ], $page, $perPage);

        return $this->ok([
            'items' => array_map(fn ($job) => $job->toArray(), $result['items']),
            'total' => $result['total'],
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    private function createJob(Request $request, string $platform): Response
    {
        $body = $request->getJson() ?? $request->post();
        $body = is_array($body) ? $body : [];

        $direction = SyncDirection::tryFrom((string) ($body['direction'] ?? 'inbound'));
        if ($direction === null) {
            throw new \InvalidArgumentException('Invalid direction.');
        }

        $resource = (string) ($body['resource'] ?? '');
        if ($resource === '') {
            throw new \InvalidArgumentException('Missing resource.');
        }

        $options = is_array($body['options'] ?? null) ? $body['options'] : [];
        $job = $this->engine->enqueue(
            $platform,
            $direction,
            $resource,
            SyncTrigger::MANUAL,
            $options,
        );
        $this->engine->dispatch($job->id);

        return $this->ok(['job' => $this->jobs->find($job->id)?->toArray() ?? $job->toArray()], 201);
    }

    private function showJob(int $job): Response
    {
        $found = $this->jobs->find($job);
        if ($found === null) {
            return $this->error('Job not found', 404);
        }

        return $this->ok(['job' => $found->toArray()]);
    }

    private function cancelJob(int $job): Response
    {
        $found = $this->jobs->find($job);
        if ($found === null) {
            return $this->error('Job not found', 404);
        }

        $cancelled = $this->jobs->cancel($job);

        return $this->ok([
            'cancelled' => $cancelled,
            'job' => $this->jobs->find($job)?->toArray() ?? $found->toArray(),
        ]);
    }

    private function listRecords(Request $request, int $job): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(200, (int) $request->query('per_page', 50)));

        $result = $this->records->paginate($job, [
            'status' => (string) $request->query('status', ''),
            'q' => (string) $request->query('q', ''),
        ], $page, $perPage);

        return $this->ok([
            'items' => $result['items'],
            'total' => $result['total'],
            'summary' => $this->records->summary($job),
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    private function listMappings(Request $request, string $platform): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(200, (int) $request->query('per_page', 50)));
        $resource = (string) $request->query('resource', 'products');

        $result = $this->mappings->paginate([
            'platform' => $platform,
            'resource' => $resource,
            'q' => (string) $request->query('q', ''),
        ], $page, $perPage);

        return $this->ok([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    private function deleteMapping(int $mapping): Response
    {
        return $this->ok(['deleted' => $this->mappings->delete($mapping)]);
    }

    private function currentSettings(string $platform): SyncSettings
    {
        $connector = $this->connectors->get($platform);

        return $this->settings->get($platform, [
            'settings' => $connector?->defaultSettings() ?? [],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function ok(array $data, int $status = 200): Response
    {
        return Response::json(['success' => true, 'data' => $data], $status);
    }

    private function error(string $message, int $status = 400): Response
    {
        return Response::json(['success' => false, 'error' => $message], $status);
    }
}
