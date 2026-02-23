<?php

declare(strict_types=1);

namespace Modules\LicenseManager\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity(repository: LicenseRepository::class)]
#[Table(name: 'optilarity_licenses')]
class License
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $licenseKey;

    #[Column(type: 'integer', nullable: true)]
    public ?int $customerId = null;

    #[Column(type: 'integer', nullable: true)]
    public ?int $orderId = null;

    #[Column(type: 'integer', nullable: true)]
    public ?int $productId = null;  // ref to SoftwareCatalog product

    #[Column(type: 'string', nullable: true)]
    public ?string $productType = null;  // software, plugin, theme

    #[Column(type: 'string', default: 'active')]
    public string $status = 'active';  // active, expired, suspended, revoked

    #[Column(type: 'integer', default: 1)]
    public int $maxActivations = 1;

    #[Column(type: 'integer', default: 0)]
    public int $activationsUsed = 0;

    #[Column(type: 'text', nullable: true)]
    public ?string $activatedDomains = null;  // JSON array

    #[Column(type: 'date', nullable: true)]
    public ?\DateTimeImmutable $expiresAt = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->licenseKey = $this->generateKey();
    }

    private function generateKey(): string
    {
        $parts = [];
        for ($i = 0; $i < 4; $i++) {
            $parts[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return implode('-', $parts);
    }

    public function getActivatedDomains(): array
    {
        return $this->activatedDomains ? json_decode($this->activatedDomains, true) : [];
    }

    public function activate(string $domain): bool
    {
        $domains = $this->getActivatedDomains();
        if (in_array($domain, $domains)) {
            return true; // already activated
        }
        if ($this->activationsUsed >= $this->maxActivations) {
            return false;
        }
        $domains[] = $domain;
        $this->activatedDomains = json_encode($domains);
        $this->activationsUsed++;
        return true;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
    }
}
