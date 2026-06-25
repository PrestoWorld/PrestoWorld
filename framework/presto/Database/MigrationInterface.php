<?php

declare(strict_types=1);

namespace PrestoWorld\Database;

use Cycle\Database\DatabaseInterface;

interface MigrationInterface
{
    public function up(DatabaseInterface $db, string $prefix): void;

    public function down(DatabaseInterface $db, string $prefix): void;
}
