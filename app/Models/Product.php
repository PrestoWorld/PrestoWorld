<?php

declare(strict_types=1);

namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\BelongsTo;

#[Entity]
#[Table(name: 'products')]
class Product
{
    #[Column(type: 'primary')]
    private int $id;

    #[Column(type: 'string', length: 255)]
    private string $name;

    #[Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[Column(type: 'string', length: 100, unique: true)]
    private string $sku;

    #[Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $shortDescription = null;

    #[Column(type: 'float', nullable: true)]
    private ?float $price = null;

    #[Column(type: 'float', nullable: true)]
    private ?float $salePrice = null;

    #[Column(type: 'integer', default: 0)]
    private int $stock = 0;

    #[Column(type: 'integer', nullable: true)]
    private ?int $categoryId = null;

    #[Column(type: 'string', length: 50, default: 'simple')]
    private string $type = 'simple'; // simple, variable, digital

    #[Column(type: 'string', length: 50, default: 'draft')]
    private string $status = 'draft'; // active, draft, inactive

    #[Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[Column(type: 'json', nullable: true)]
    private ?array $gallery = null;

    #[Column(type: 'json', nullable: true)]
    private ?array $attributes = null;

    #[Column(type: 'integer', default: 0)]
    private int $viewCount = 0;

    #[Column(type: 'boolean', default: false)]
    private bool $isFeatured = false;

    #[Column(type: 'datetime')]
    private \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[BelongsTo(target: Category::class, innerKey: 'categoryId', load: 'lazy')]
    private ?Category $category = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getSalePrice(): ?float
    {
        return $this->salePrice;
    }

    public function getCurrentPrice(): ?float
    {
        return $this->salePrice ?? $this->price;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getGallery(): ?array
    {
        return $this->gallery;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    // Setters
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setShortDescription(?string $shortDescription): void
    {
        $this->shortDescription = $shortDescription;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setPrice(?float $price): void
    {
        $this->price = $price;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setSalePrice(?float $salePrice): void
    {
        $this->salePrice = $salePrice;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setCategoryId(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setType(string $type): void
    {
        $this->type = $type;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setGallery(?array $gallery): void
    {
        $this->gallery = $gallery;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setAttributes(?array $attributes): void
    {
        $this->attributes = $attributes;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setIsFeatured(bool $isFeatured): void
    {
        $this->isFeatured = $isFeatured;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function incrementViewCount(): void
    {
        $this->viewCount++;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Generate slug from name
     */
    public static function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Generate SKU from name and id
     */
    public static function generateSku(string $name, int $id): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
        return $prefix . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
    }
}
