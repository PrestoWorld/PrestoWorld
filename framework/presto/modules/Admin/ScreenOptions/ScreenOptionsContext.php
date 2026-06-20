<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\ScreenOptions;

use PrestoWorld\Contracts\Admin\ScreenOptions\ScreenOption as ScreenOptionContract;
use PrestoWorld\Contracts\Admin\ScreenOptions\ScreenOptionsContext as ScreenOptionsContextContract;

class ScreenOptionsContext implements ScreenOptionsContextContract
{
    protected array $options = [];

    public function __construct(
        protected string $screenId,
        protected string $title = '',
    ) {}

    public function getScreenId(): string
    {
        return $this->screenId;
    }

    public function getTitle(): string
    {
        return $this->title ?: ucfirst($this->screenId) . ' Settings';
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function addOption(ScreenOptionContract $option): void
    {
        $this->options[] = $option;
    }

    public function toArray(): array
    {
        return [
            'screenId' => $this->screenId,
            'title' => $this->getTitle(),
            'options' => array_map(
                fn(ScreenOptionContract $opt) => $opt->toArray(),
                $this->options,
            ),
        ];
    }
}
