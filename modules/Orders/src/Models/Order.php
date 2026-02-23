<?php

declare(strict_types=1);

namespace Modules\Orders\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity(repository: OrderRepository::class)]
#[Table(name: 'optilarity_orders')]
class Order
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $orderNumber;

    #[Column(type: 'integer', nullable: true)]
    public ?int $customerId = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $customerEmail = null;

    #[Column(type: 'string', default: 'pending')]
    public string $status = 'pending';  // pending, processing, completed, cancelled, refunded

    #[Column(type: 'string', default: 'pending')]
    public string $paymentStatus = 'pending';  // pending, paid, failed, refunded

    #[Column(type: 'string', nullable: true)]
    public ?string $paymentMethod = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $transactionId = null;

    #[Column(type: 'decimal', precision: 10, scale: 2)]
    public float $subtotal = 0.0;

    #[Column(type: 'decimal', precision: 10, scale: 2)]
    public float $tax = 0.0;

    #[Column(type: 'decimal', precision: 10, scale: 2)]
    public float $total = 0.0;

    #[Column(type: 'string', default: 'USD')]
    public string $currency = 'USD';

    #[Column(type: 'text', nullable: true)]
    public ?string $items = null;  // JSON array of line items

    #[Column(type: 'text', nullable: true)]
    public ?string $notes = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $paidAt = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->orderNumber = 'ORD-' . strtoupper(uniqid());
    }

    public function getItems(): array
    {
        return $this->items ? json_decode($this->items, true) : [];
    }

    public function setItems(array $items): void
    {
        $this->items = json_encode($items);
    }
}
