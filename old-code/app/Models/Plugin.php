<?php

declare(strict_types=1);

namespace App\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'plugins')]
class Plugin
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string', unique: true)]
    public string $path;

    #[Column(type: 'boolean')]
    public bool $is_wordpress = false;

    #[Column(type: 'json', nullable: true)]
    public ?array $metadata = null;
}
