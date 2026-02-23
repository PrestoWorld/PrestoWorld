<?php

declare(strict_types=1);

namespace Modules\Memberships\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity(repository: MembershipRepository::class)]
#[Table(name: 'optilarity_memberships')]
class Membership
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'integer')]
    public int $customerId;

    #[Column(type: 'integer')]
    public int $planId;

    #[Column(type: 'string', default: 'active')]
    public string $status = 'active';  // active, cancelled, expired, trialing

    #[Column(type: 'date', nullable: true)]
    public ?\DateTimeImmutable $startDate = null;

    #[Column(type: 'date', nullable: true)]
    public ?\DateTimeImmutable $endDate = null;

    #[Column(type: 'date', nullable: true)]
    public ?\DateTimeImmutable $trialEndDate = null;

    #[Column(type: 'boolean', default: false)]
    public bool $autoRenew = false;

    #[Column(type: 'string', nullable: true)]
    public ?string $notes = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $cancelledAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->startDate = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->endDate && $this->endDate < new \DateTimeImmutable()) {
            return false;
        }
        return true;
    }
}
