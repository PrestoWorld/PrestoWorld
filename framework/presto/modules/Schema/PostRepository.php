<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Schema;

use Cycle\Database\DatabaseInterface;

/**
 * High-Performance Post Repository
 * 
 * Handles unified queries across multiple post types and 
 * automatically hydrates custom data from specialized tables.
 */
class PostRepository
{
    protected DatabaseInterface $db;
    protected string $tablePrefix = 'pw_';

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Find posts with automatic hydration of custom data
     */
    public function find(array $criteria = []): array
    {
        $query = $this->db->select('*')->from($this->tablePrefix . 'posts');

        // Apply basic filters
        if (isset($criteria['post_type'])) {
            $types = (array) $criteria['post_type'];
            $query->where('post_type', 'IN', $types);
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        $posts = $query->fetchAll();
        
        if (empty($posts)) {
            return [];
        }

        return $this->hydrateCustomData($posts);
    }

    /**
     * Group posts by type and batch load their custom data + taxonomies
     */
    protected function hydrateCustomData(array $posts): array
    {
        $byType = [];
        $indexedPosts = [];

        foreach ($posts as $post) {
            $byType[$post['post_type']][] = $post['id'];
            $indexedPosts[$post['id']] = $post;
            $indexedPosts[$post['id']]['terms'] = []; // Initialize terms array
        }

        $this->hydrateSpecializedData($byType, $indexedPosts);
        $this->hydrateTerms(array_keys($indexedPosts), $indexedPosts);

        return array_values($indexedPosts);
    }

    protected function hydrateSpecializedData(array $byType, array &$indexedPosts): void
    {
        foreach ($byType as $type => $ids) {
            $customTableName = $this->tablePrefix . "post_" . $type;
            if (!$this->db->hasTable($customTableName)) continue;

            $customData = $this->db->select('*')
                ->from($customTableName)
                ->where('post_id', 'IN', $ids)
                ->fetchAll();

            foreach ($customData as $row) {
                $postId = $row['post_id'];
                unset($row['post_id'], $row['id']);
                $indexedPosts[$postId] = array_merge($indexedPosts[$postId], $row);
            }
        }
    }

    protected function hydrateTerms(array $postIds, array &$indexedPosts): void
    {
        $termData = $this->db->select('tr.object_id', 't.*')
            ->from($this->tablePrefix . 'term_relationships as tr')
            ->innerJoin($this->tablePrefix . 'terms', 't')->on('t.id', 'tr.term_id')
            ->where('tr.object_id', 'IN', $postIds)
            ->fetchAll();

        foreach ($termData as $row) {
            $postId = $row['object_id'];
            unset($row['object_id']);
            $indexedPosts[$postId]['terms'][] = $row;
        }
    }
}
