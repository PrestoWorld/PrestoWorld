<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Core\Admin\Registry\AdminPageRegistry;

class AdminPageRegistryTest extends TestCase
{
    public function test_register_and_get_page(): void
    {
        $registry = new AdminPageRegistry();
        $page = new class implements \PrestoWorld\Core\Admin\Contracts\AdminPageInterface {
            public function getId(): string { return 'test-page'; }
            public function getSlug(): string { return 'test-page'; }
            public function getTitle(): string { return 'Test Page'; }
            public function getMenuTitle(): string { return 'Test Page'; }
            public function getCapability(): string { return 'manage_options'; }
            public function getIcon(): ?string { return 'Settings'; }
            public function getParentSlug(): ?string { return null; }
            public function getPosition(): int { return 10; }
            public function getRenderCallback(): ?callable { return null; }
            public function getSpaComponent(): ?string { return 'test-page'; }
            public function getAttributes(): array { return []; }
        };

        $registry->registerPage($page);
        $this->assertSame($page, $registry->getPage('test-page'));
        $this->assertCount(1, $registry->getPages());
    }

    public function test_get_menu_tree_structure(): void
    {
        $registry = new AdminPageRegistry();
        $page = new class implements \PrestoWorld\Core\Admin\Contracts\AdminPageInterface {
            public function getId(): string { return 'settings'; }
            public function getSlug(): string { return 'settings'; }
            public function getTitle(): string { return 'Settings'; }
            public function getMenuTitle(): string { return 'Settings'; }
            public function getCapability(): string { return 'manage_options'; }
            public function getIcon(): ?string { return 'Settings'; }
            public function getParentSlug(): ?string { return null; }
            public function getPosition(): int { return 80; }
            public function getRenderCallback(): ?callable { return null; }
            public function getSpaComponent(): ?string { return 'settings'; }
            public function getAttributes(): array { return []; }
        };

        $registry->registerPage($page);
        $tree = $registry->getMenuTree();
        $this->assertIsArray($tree);
        $this->assertArrayHasKey(0, $tree);
        $this->assertSame('settings', $tree[0]['slug']);
        $this->assertArrayHasKey('children', $tree[0]);
    }
}
