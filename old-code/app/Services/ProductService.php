<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\EntityManagerInterface;

class ProductService
{
    private ORMInterface $orm;
    private EntityManagerInterface $entityManager;

    public function __construct(ORMInterface $orm, EntityManagerInterface $entityManager)
    {
        $this->orm = $orm;
        $this->entityManager = $entityManager;
    }

    /**
     * Get all products with optional filters
     */
    public function getAll(array $filters = []): array
    {
        $select = $this->orm->getRepository(Product::class)->select();

        // Filter by status
        if (!empty($filters['status'])) {
            $select->where('status', $filters['status']);
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $select->where('category_id', $filters['category_id']);
        }

        // Search by name or SKU
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $select->where(function($select) use ($search) {
                $select->where('name', 'LIKE', "%{$search}%")
                       ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // Filter by featured
        if (isset($filters['is_featured'])) {
            $select->where('is_featured', (bool)$filters['is_featured']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $select->orderBy($orderBy, $orderDir);

        return $select->fetchAll();
    }

    /**
     * Get product by ID
     */
    public function getById(int $id): ?Product
    {
        return $this->orm->getRepository(Product::class)->findByPK($id);
    }

    /**
     * Get product by slug
     */
    public function getBySlug(string $slug): ?Product
    {
        return $this->orm->getRepository(Product::class)
            ->findOne(['slug' => $slug]);
    }

    /**
     * Get product by SKU
     */
    public function getBySku(string $sku): ?Product
    {
        return $this->orm->getRepository(Product::class)
            ->findOne(['sku' => $sku]);
    }

    /**
     * Create new product
     */
    public function create(array $data): Product
    {
        $product = new Product();

        $product->setName($data['name']);
        $product->setSlug($data['slug'] ?? Product::generateSlug($data['name']));
        $product->setSku($data['sku'] ?? '');
        $product->setDescription($data['description'] ?? null);
        $product->setShortDescription($data['short_description'] ?? null);
        $product->setPrice($data['price'] ?? null);
        $product->setSalePrice($data['sale_price'] ?? null);
        $product->setStock($data['stock'] ?? 0);
        $product->setCategoryId($data['category_id'] ?? null);
        $product->setType($data['type'] ?? 'simple');
        $product->setStatus($data['status'] ?? 'draft');
        $product->setImage($data['image'] ?? null);
        $product->setGallery($data['gallery'] ?? null);
        $product->setAttributes($data['attributes'] ?? null);
        $product->setIsFeatured($data['is_featured'] ?? false);

        $this->entityManager->persist($product);
        $this->entityManager->run();

        // Generate SKU if not provided
        if (empty($data['sku'])) {
            $product->setSku(Product::generateSku($product->getName(), $product->getId()));
            $this->entityManager->persist($product);
            $this->entityManager->run();
        }

        return $product;
    }

    /**
     * Update product
     */
    public function update(int $id, array $data): ?Product
    {
        $product = $this->getById($id);

        if (!$product) {
            return null;
        }

        $fields = [
            'name' => 'setName',
            'slug' => 'setSlug',
            'sku' => 'setSku',
            'description' => 'setDescription',
            'short_description' => 'setShortDescription',
            'price' => 'setPrice',
            'sale_price' => 'setSalePrice',
            'stock' => 'setStock',
            'category_id' => 'setCategoryId',
            'type' => 'setType',
            'status' => 'setStatus',
            'image' => 'setImage',
            'gallery' => 'setGallery',
            'attributes' => 'setAttributes',
            'is_featured' => 'setIsFeatured',
        ];

        foreach ($fields as $key => $setter) {
            if (isset($data[$key])) {
                $product->$setter($data[$key]);
            }
        }

        $this->entityManager->persist($product);
        $this->entityManager->run();

        return $product;
    }

    /**
     * Delete product
     */
    public function delete(int $id): bool
    {
        $product = $this->getById($id);

        if (!$product) {
            return false;
        }

        $this->entityManager->delete($product);
        $this->entityManager->run();

        return true;
    }

    /**
     * Check if SKU exists
     */
    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $select = $this->orm->getRepository(Product::class)
            ->select()
            ->where('sku', $sku);

        if ($excludeId) {
            $select->where('id', '!=', $excludeId);
        }

        return $select->fetchOne() !== null;
    }

    /**
     * Check if slug exists
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $select = $this->orm->getRepository(Product::class)
            ->select()
            ->where('slug', $slug);

        if ($excludeId) {
            $select->where('id', '!=', $excludeId);
        }

        return $select->fetchOne() !== null;
    }

    /**
     * Get products by category
     */
    public function getByCategory(int $categoryId, bool $activeOnly = true): array
    {
        $select = $this->orm->getRepository(Product::class)
            ->select()
            ->where('category_id', $categoryId);

        if ($activeOnly) {
            $select->where('status', 'active');
        }

        return $select->orderBy('created_at', 'DESC')->fetchAll();
    }

    /**
     * Get featured products
     */
    public function getFeatured(int $limit = 10): array
    {
        return $this->orm->getRepository(Product::class)
            ->select()
            ->where('is_featured', true)
            ->where('status', 'active')
            ->limit($limit)
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        $updated = 0;
        foreach ($ids as $id) {
            if ($this->update((int)$id, ['status' => $status])) {
                $updated++;
            }
        }
        return $updated;
    }

    /**
     * Bulk delete
     */
    public function bulkDelete(array $ids): int
    {
        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->delete((int)$id)) {
                $deleted++;
            }
        }
        return $deleted;
    }
}
