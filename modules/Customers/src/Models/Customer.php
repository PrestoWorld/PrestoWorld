<?php

declare(strict_types=1);

namespace Modules\Customers\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity(repository: CustomerRepository::class)]
#[Table(name: 'optilarity_customers')]
class Customer
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string', nullable: true)]
    public ?string $userId = null;

    #[Column(type: 'string')]
    public string $firstName;

    #[Column(type: 'string')]
    public string $lastName;

    #[Column(type: 'string')]
    public string $email;

    #[Column(type: 'string', nullable: true)]
    public ?string $phone = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $company = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $country = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $address = null;

    #[Column(type: 'string', default: 'active')]
    public string $status = 'active';  // active, suspended, banned

    #[Column(type: 'text', nullable: true)]
    public ?string $notes = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getFullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }
}
