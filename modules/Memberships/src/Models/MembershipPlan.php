<?php

declare(strict_types=1);

namespace Modules\Memberships\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity(repository: MembershipPlanRepository::class)]
#[Table(name: 'optilarity_membership_plans')]
class MembershipPlan
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $slug;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'decimal', precision: 10, scale: 2)]
    public float $price = 0.0;

    #[Column(type: 'string', default: 'monthly')]
    public string $billingCycle = 'monthly';  // monthly, yearly, lifetime

    #[Column(type: 'string', default: 'USD')]
    public string $currency = 'USD';

    #[Column(type: 'integer', nullable: true)]
    public ?int $maxLicenses = null;  // null = unlimited

    #[Column(type: 'integer', nullable: true)]
    public ?int $maxDomains = null;

    #[Column(type: 'text', nullable: true)]
    public ?string $features = null;  // JSON array

    #[Column(type: 'boolean', default: true)]
    public bool $isActive = true;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getFeatures(): array
    {
        return $this->features ? json_decode($this->features, true) : [];
    }

    public function setFeatures(array $features): void
    {
        $this->features = json_encode($features);
    }
}
