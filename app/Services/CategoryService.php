<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Cycle\ORM\EntityManagerInterface;

class CategoryService
{
    private ORMInterface $orm;
    private EntityManagerInterface $entityManager;

    public function __construct(ORMInterface $orm, EntityManagerInterface $entityManager)
    {
        $this->orm = $orm;
        $this->entityManager = $entityManager;
    }

    /**
     * Get all categories
     */
    public function getAll(bool $activeOnly = false): array
    {
        $select = $this->orm->getRepository(Category::class)->select();

        if ($activeOnly) {
            $select->where('is_active', true);
        }

        return $select->orderBy('sort_order')->orderBy('name')->fetchAll();
    }

    /**
     * Get category by ID
     */
    public function getById(int $id): ?Category
    {
        return $this->orm->getRepository(Category::class)->findByPK($id);
    }

    /**
     * Get category by slug
     */
    public function getBySlug(string $slug): ?Category
    {
        return $this->orm->getRepository(Category::class)
            ->findOne(['slug' => $slug]);
    }

    /**
     * Create new category
     */
    public function create(array $data): Category
    {
        $category = new Category();
        $category->setName($data['name']);
        $category->setSlug($data['slug'] ?? Category::generateSlug($data['name']));
        $category->setDescription($data['description'] ?? null);
        $category->setParentId($data['parent_id'] ?? null);
        $category->setSortOrder($data['sort_order'] ?? 0);
        $category->setIsActive($data['is_active'] ?? true);

        $this->entityManager->persist($category);
        $this->entityManager->run();

        return $category;
    }

    /**
     * Update category
     */
    public function update(int $id, array $data): ?Category
    {
        $category = $this->getById($id);

        if (!$category) {
            return null;
        }

        if (isset($data['name'])) {
            $category->setName($data['name']);
        }
        if (isset($data['slug'])) {
            $category->setSlug($data['slug']);
        }
        if (isset($data['description'])) {
            $category->setDescription($data['description']);
        }
        if (isset($data['parent_id'])) {
            $category->setParentId($data['parent_id']);
        }
        if (isset($data['sort_order'])) {
            $category->setSortOrder($data['sort_order']);
        }
        if (isset($data['is_active'])) {
            $category->setIsActive($data['is_active']);
        }

        $this->entityManager->persist($category);
        $this->entityManager->run();

        return $category;
    }

    /**
     * Delete category
     */
    public function delete(int $id): bool
    {
        $category = $this->getById($id);

        if (!$category) {
            return false;
        }

        $this->entityManager->delete($category);
        $this->entityManager->run();

        return true;
    }

    /**
     * Get categories as tree structure
     */
    public function getTree(): array
    {
        $categories = $this->getAll();
        return $this->buildTree($categories);
    }

    /**
     * Build tree from flat array
     */
    private function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->getParentId() === $parentId) {
                $node = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                    'description' => $category->getDescription(),
                    'sort_order' => $category->getSortOrder(),
                    'is_active' => $category->isActive(),
                    'children' => $this->buildTree($categories, $category->getId())
                ];
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * Check if slug exists (for validation)
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $select = $this->orm->getRepository(Category::class)
            ->select()
            ->where('slug', $slug);

        if ($excludeId) {
            $select->where('id', '!=', $excludeId);
        }

        return $select->fetchOne() !== null;
    }
}
