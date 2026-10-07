<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\ScreenOptions;

use PrestoWorld\Contracts\Admin\ScreenOptions\ScreenOption as ScreenOptionContract;

class ScreenOption implements ScreenOptionContract
{
    public function __construct(
        protected string $id,
        protected string $label,
        protected string $type = 'checkbox',
        protected mixed $default = null,
        protected array $options = [],
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'default' => $this->default,
            'options' => $this->options,
        ];
    }
}
