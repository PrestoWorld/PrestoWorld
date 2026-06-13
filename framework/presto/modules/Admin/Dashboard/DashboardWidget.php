<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\Dashboard;

use PrestoWorld\Contracts\Admin\Dashboard\DashboardWidget as DashboardWidgetContract;

class DashboardWidget implements DashboardWidgetContract
{
    public function __construct(
        protected string $id,
        protected string $title,
        protected string $content = '',
        protected int $priority = 10,
        protected int $column = 1,
        protected string $context = 'dashboard',
        protected bool $visible = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            title: $data['title'] ?? '',
            content: $data['content'] ?? '',
            priority: $data['priority'] ?? 10,
            column: $data['column'] ?? 1,
            context: $data['context'] ?? 'dashboard',
            visible: $data['visible'] ?? true,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getColumn(): int
    {
        return $this->column;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'priority' => $this->priority,
            'column' => $this->column,
            'context' => $this->context,
            'visible' => $this->visible,
        ];
    }
}
