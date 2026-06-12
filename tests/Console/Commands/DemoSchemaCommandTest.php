<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use App\Console\Commands\DemoSchemaCommand;

class DemoSchemaCommandTest extends TestCase
{
    private DemoSchemaCommand $command;

    protected function setUp(): void
    {
        $this->command = new DemoSchemaCommand();
    }

    public function testGetName(): void
    {
        $this->assertSame('demo:schema', $this->command->name);
    }

    public function testGetDescription(): void
    {
        $this->assertSame('Demonstrate PrestoWorld Live Migration for Taxonomies and Post Types', $this->command->description);
    }

    public function testHandleReturnsZero(): void
    {
        $this->expectNotToPerformAssertions();

        $result = $this->command->handle([]);

        $this->assertSame(0, $result);
    }

    public function testExtendsCommand(): void
    {
        $this->assertInstanceOf(\Witals\Framework\Console\Command::class, $this->command);
    }
}
