<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Cycle\Database\DatabaseInterface;
use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use Witals\Framework\Http\Response;

class DashboardController
{
    use AdminControllerHelperTrait;

    public function __construct(
        protected DatabaseInterface $db,
        protected PluginStoreInterface $plugins,
    ) {
        $this->initHelpers($db);
    }

    public function stats(): Response
    {
        try {
            $baseQuery = $this->db->select('COUNT(*) as count')
                ->from($this->prefix . 'posts')
                ->where('post_status', '!=', 'auto-draft');

            $pubQuery = $this->db->select('COUNT(*) as count')
                ->from($this->prefix . 'posts')
                ->where('post_status', 'publish');

            $draftQuery = $this->db->select('COUNT(*) as count')
                ->from($this->prefix . 'posts')
                ->where('post_status', 'draft');

            if ($this->isWordPress()) {
                $baseQuery = $baseQuery->where('post_type', 'post');
                $pubQuery = $pubQuery->where('post_type', 'post');
                $draftQuery = $draftQuery->where('post_type', 'post');
            }

            $totalPosts = (int) ($baseQuery->fetch()['count'] ?? 0);
            $publishedPosts = (int) ($pubQuery->fetch()['count'] ?? 0);
            $draftPosts = (int) ($draftQuery->fetch()['count'] ?? 0);
        } catch (\Throwable) {
            $totalPosts = $publishedPosts = $draftPosts = 0;
        }

        $byPostType = [];
        if ($this->isWordPress()) {
            try {
                $rows = $this->db->select(['post_type', 'COUNT(*) as count'])
                    ->from($this->prefix . 'posts')
                    ->where('post_status', '!=', 'auto-draft')
                    ->groupBy('post_type')
                    ->fetchAll();

                $labels = [
                    'post' => 'Posts',
                    'page' => 'Pages',
                    'attachment' => 'Media',
                    'revision' => 'Revisions',
                    'nav_menu_item' => 'Menu Items',
                    'customize_changeset' => 'Changesets',
                ];

                foreach ($rows as $row) {
                    $type = $row['post_type'] ?? 'unknown';
                    $byPostType[] = [
                        'type' => $type,
                        'count' => (int) ($row['count'] ?? 0),
                        'label' => $labels[$type] ?? ucfirst($type),
                    ];
                }
            } catch (\Throwable) {
                $byPostType = [];
            }
        }

        $installed = $this->plugins->getInstalledPlugins();
        $totalPlugins = count($installed);
        $activePlugins = count(array_filter($installed, fn($p) => $p['enabled']));

        return Response::json([
            'posts' => [
                'total' => $totalPosts,
                'published' => $publishedPosts,
                'draft' => $draftPosts,
            ],
            'plugins' => [
                'total' => $totalPlugins,
                'active' => $activePlugins,
                'inactive' => $totalPlugins - $activePlugins,
            ],
            'byPostType' => $byPostType,
        ]);
    }

    public function activities(): Response
    {
        try {
            $query = $this->db->select('*')
                ->from($this->prefix . 'posts')
                ->where('post_status', '!=', 'auto-draft');

            if ($this->isWordPress()) {
                $query = $query->where('post_type', 'post');
            }

            $rows = $query->orderBy('post_modified', 'DESC')
                ->limit(20)
                ->fetchAll();
        } catch (\Throwable) {
            $rows = [];
        }

        $activities = [];
        foreach ($rows as $row) {
            $title = $row['post_title'] ?? $row['title'] ?? 'Untitled';
            $status = $row['post_status'] ?? $row['status'] ?? '';
            $activities[] = [
                'id' => (int) ($row['ID'] ?? $row['id']),
                'text' => $status === 'publish'
                    ? "Published post: \"{$title}\""
                    : "Updated post: \"{$title}\"",
                'time' => $this->relativeTime($row['post_modified'] ?? $row['post_date'] ?? $row['updated_at'] ?? $row['created_at'] ?? ''),
                'type' => $status === 'publish' ? 'post' : 'update',
            ];
        }

        return Response::json($activities);
    }
}
