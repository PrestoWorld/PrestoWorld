<?php

declare(strict_types=1);

namespace Modules\Invoices\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity(repository: InvoiceRepository::class)]
#[Table(name: 'optilarity_invoices')]
class Invoice
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $invoiceNumber;

    #[Column(type: 'integer', nullable: true)]
    public ?int $orderId = null;

    #[Column(type: 'integer', nullable: true)]
    public ?int $customerId = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $customerEmail = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $customerName = null;

    #[Column(type: 'string', default: 'draft')]
    public string $status = 'draft';  // draft, sent, paid, overdue, cancelled

    #[Column(type: 'decimal', precision: 10, scale: 2)]
    public float $subtotal = 0.0;

    #[Column(type: 'decimal', precision: 10, scale: 2)]
    public float $tax = 0.0;

    #[Column(type: 'decimal', precision: 10, scale: 2)]
    public float $total = 0.0;

    #[Column(type: 'string', default: 'USD')]
    public string $currency = 'USD';

    #[Column(type: 'text', nullable: true)]
    public ?string $lineItems = null;  // JSON

    #[Column(type: 'date', nullable: true)]
    public ?\DateTimeImmutable $dueDate = null;

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
        $this->invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string)rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public function getLineItems(): array
    {
        return $this->lineItems ? json_decode($this->lineItems, true) : [];
    }

    public function setLineItems(array $items): void
    {
        $this->lineItems = json_encode($items);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->dueDate && $this->dueDate < new \DateTimeImmutable();
    }
}
