<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Core\Admin\AdminDashboardManager;
use PrestoWorld\Core\Admin\Context\AdminContext;
use PrestoWorld\Core\Admin\Registry\AdminPageRegistry;
use PrestoWorld\Core\Admin\Renderers\SpaDashboardRenderer;
use PrestoWorld\Core\Admin\Contracts\AdminPageInterface;

class AdminDashboardManagerTest extends TestCase
{
    public function test_renders_with_spa_renderer_in_spa_context(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin/dashboard';
        $context = new AdminContext();
        $registry = new AdminPageRegistry();
        $renderer = new SpaDashboardRenderer();
        $manager = new AdminDashboardManager($context, $registry, [$renderer]);

        $page = new class implements AdminPageInterface {
            public function getId(): string { return 'demo'; }
            public function getSlug(): string { return 'demo'; }
            public function getTitle(): string { return 'Demo'; }
            public function getMenuTitle(): string { return 'Demo'; }
            public function getCapability(): string { return 'manage_options'; }
            public function getIcon(): ?string { return 'Circle'; }
            public function getParentSlug(): ?string { return null; }
            public function getPosition(): int { return 10; }
            public function getRenderCallback(): ?callable { return null; }
            public function getSpaComponent(): ?string { return 'demo-page'; }
            public function getAttributes(): array { return []; }
        };

        $manager->registerPage($page);
        ob_start();
        $result = $manager->render('demo');
        $output = ob_get_clean();
        $this->assertNull($result);
        $this->assertStringContainsString('presto-dashboard-app', $output);
        $this->assertStringContainsString('demo-page', $output);
    }

    public function test_get_menu_tree_from_registry(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin/settings';
        $context = new AdminContext();
        $registry = new AdminPageRegistry();
        $renderer = new SpaDashboardRenderer();
        $manager = new AdminDashboardManager($context, $registry, [$renderer]);

        $page = new class implements AdminPageInterface {
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

        $manager->registerPage($page);
        $tree = $manager->getMenuTree();
        $this->assertIsArray($tree);
        $this->assertNotEmpty($tree);
    }
}
