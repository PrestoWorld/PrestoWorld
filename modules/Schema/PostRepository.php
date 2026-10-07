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
    protected string $defaultLocale = 'en';
    protected ?string $currentLocale = null;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function setLocale(string $locale): void
    {
        $this->currentLocale = $locale;
    }

    public function getLocale(): string
    {
        return $this->currentLocale ?? $this->defaultLocale;
    }

    /**
     * Find posts with automatic hydration of custom data + translations
     */
    public function find(array $criteria = []): array
    {
        $query = $this->db->select('*')->from($this->tablePrefix . 'posts');

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

    protected function hydrateCustomData(array $posts): array
    {
        $byType = [];
        $indexedPosts = [];

        foreach ($posts as $post) {
            $byType[$post['post_type']][] = $post['id'];
            $indexedPosts[$post['id']] = $post;
            $indexedPosts[$post['id']]['terms'] = [];
            $indexedPosts[$post['id']]['link']  = $this->generateLink($post);
        }

        $this->hydrateSpecializedData($byType, $indexedPosts);
        $this->hydrateTerms(array_keys($indexedPosts), $indexedPosts);

        $locale = $this->getLocale();
        if ($locale !== $this->defaultLocale) {
            $this->hydrateTranslations(array_keys($indexedPosts), $locale, $indexedPosts);
        }

        return array_values($indexedPosts);
    }

    protected function generateLink(array $post): string
    {
        $slug = $post['slug'] ?? '';
        if (empty($slug)) {
             return '#';
        }
        $type = $post['post_type'] ?? 'post';
        
        if ($type === 'page') {
            return '/' . ltrim($slug, '/');
        }
        
        return '/' . $type . '/' . ltrim($slug, '/');
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

    /**
     * Load translations for the current locale and merge them into posts.
     * Only runs when locale differs from default.
     */
    protected function hydrateTranslations(array $postIds, string $locale, array &$indexedPosts): void
    {
        $tableName = $this->tablePrefix . 'post_translations';
        if (!$this->db->hasTable($tableName)) {
            return;
        }

        $rows = $this->db->select('*')
            ->from($tableName)
            ->where('post_id', 'IN', $postIds)
            ->where('locale', $locale)
            ->fetchAll();

        foreach ($rows as $row) {
            $postId = $row['post_id'];
            foreach (['title', 'slug', 'content', 'excerpt'] as $field) {
                if (isset($row[$field]) && $row[$field] !== '' && $row[$field] !== null) {
                    $indexedPosts[$postId][$field] = $row[$field];
                }
            }
            $indexedPosts[$postId]['translation_id'] = $row['id'];
        }
    }

    /**
     * Save or update a translation for a post in a given locale.
     */
    public function saveTranslation(int $postId, string $locale, array $data): bool
    {
        $dataTable = $this->tablePrefix . 'post_translations';
        if (!$this->db->hasTable($dataTable)) {
            return false;
        }

        $existing = $this->db->select('id')
            ->from($dataTable)
            ->where('post_id', $postId)
            ->where('locale', $locale)
            ->run()
            ->fetch();

        $fields = array_intersect_key($data, array_flip(['title', 'slug', 'content', 'excerpt']));

        if ($existing) {
            $this->db->update($dataTable, $fields, ['id' => $existing['id']])->run();
        } else {
            $fields['post_id'] = $postId;
            $fields['locale'] = $locale;
            $this->db->insert($dataTable)->values($fields)->run();
        }

        // Ensure icl_translations entry exists
        $this->ensureIclEntry($postId, $locale, $data['element_type'] ?? 'post_post');

        return true;
    }

    /**
     * Register a translation group (trid) for a post in icl_translations.
     * WPML-compatible: each locale variant gets an entry sharing the same trid.
     */
    public function ensureIclEntry(int $elementId, string $locale, string $elementType = 'post_post', ?int $trid = null): int
    {
        $iclTable = $this->tablePrefix . 'icl_translations';
        if (!$this->db->hasTable($iclTable)) {
            return 0;
        }

        // Allow one entry per (element_type, element_id, language_code)
        $existing = $this->db->select('translation_id', 'trid')
            ->from($iclTable)
            ->where('element_type', $elementType)
            ->where('element_id', $elementId)
            ->where('language_code', $locale)
            ->run()
            ->fetch();

        if ($existing) {
            return (int) $existing['trid'];
        }

        // Find or create a trid — share with existing entries for this element
        if ($trid === null) {
            $any = $this->db->select('trid')
                ->from($iclTable)
                ->where('element_type', $elementType)
                ->where('element_id', $elementId)
                ->run()
                ->fetch();
            $trid = $any ? (int) $any['trid'] : $this->nextTrid($iclTable);
        }

        $sourceLang = $this->findSourceLang($iclTable, $trid);

        $this->db->insert($iclTable)->values([
            'element_type' => $elementType,
            'element_id' => $elementId,
            'trid' => $trid,
            'language_code' => $locale,
            'source_language_code' => $sourceLang,
        ])->run();

        // Store trid on the post row
        $this->syncPostTrid($elementId, $trid);

        return $trid;
    }

    protected function nextTrid(string $iclTable): int
    {
        $max = $this->db->select('trid')
            ->from($iclTable)
            ->orderBy('trid', 'DESC')
            ->limit(1)
            ->run()
            ->fetch();
        return ($max ? (int) $max['trid'] : 0) + 1;
    }

    protected function findSourceLang(string $iclTable, int $trid): ?string
    {
        $first = $this->db->select('language_code')
            ->from($iclTable)
            ->where('trid', $trid)
            ->orderBy('translation_id', 'ASC')
            ->limit(1)
            ->run()
            ->fetch();
        return $first ? $first['language_code'] : null;
    }

    protected function syncPostTrid(int $postId, int $trid): void
    {
        $postsTable = $this->tablePrefix . 'posts';
        if ($this->db->hasTable($postsTable)) {
            $this->db->update($postsTable, ['trid' => $trid], ['id' => $postId])->run();
        }
    }

    /**
     * Get all translations for a post (WPML-style).
     * Returns array of [translation_id, element_id, language_code, source_language_code]
     */
    public function getTranslations(int $postId, ?string $elementType = null): array
    {
        $iclTable = $this->tablePrefix . 'icl_translations';
        if (!$this->db->hasTable($iclTable)) {
            return [];
        }

        $elementType ??= 'post_post';

        $entry = $this->db->select('trid')
            ->from($iclTable)
            ->where('element_type', $elementType)
            ->where('element_id', $postId)
            ->run()
            ->fetch();

        if (!$entry) {
            return [];
        }

        return $this->db->select('*')
            ->from($iclTable)
            ->where('trid', (int) $entry['trid'])
            ->fetchAll();
    }
}
