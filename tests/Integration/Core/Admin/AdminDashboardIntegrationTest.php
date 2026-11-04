<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Core\Admin\AdminDashboardManager;
use PrestoWorld\Core\Admin\AdminDashboardServiceProvider;
use PrestoWorld\Core\Admin\Context\AdminContext;
use PrestoWorld\Core\Admin\Contracts\AdminContextInterface;
use PrestoWorld\Core\Admin\Contracts\AdminRegistryInterface;
use PrestoWorld\Core\Admin\Contracts\AdminPageInterface;
use PrestoWorld\Core\Admin\Registry\AdminPageRegistry;

class AdminDashboardIntegrationTest extends TestCase
{
    public function test_service_provider_binds_manager_and_renders_via_spa(): void
    {
        if (!class_exists(\App\Foundation\Application::class)) {
            $this->markTestSkipped('App\Foundation\Application not available');
        }

        $app = new \App\Foundation\Application(dirname(__DIR__, 5));
        $app->setConfigPaths('config');
        $app->register(AdminDashboardServiceProvider::class);
        $provider = $app->make(AdminDashboardServiceProvider::class);
        $provider->boot();

        $context = $app->make(AdminContextInterface::class);
        $this->assertSame($context, $app->make(AdminContext::class));
        $registry = $app->make(AdminRegistryInterface::class);
        $this->assertSame($registry, $app->make(AdminPageRegistry::class));

        $_SERVER['REQUEST_URI'] = '/admin/integration';
        $manager = $app->make(AdminDashboardManager::class);

        $page = new class implements AdminPageInterface {
            public function getId(): string { return 'integration-test'; }
            public function getSlug(): string { return 'integration-test'; }
            public function getTitle(): string { return 'Integration Test'; }
            public function getMenuTitle(): string { return 'Integration Test'; }
            public function getCapability(): string { return 'manage_options'; }
            public function getIcon(): ?string { return 'Circle'; }
            public function getParentSlug(): ?string { return null; }
            public function getPosition(): int { return 50; }
            public function getRenderCallback(): ?callable { return null; }
            public function getSpaComponent(): ?string { return 'integration-test'; }
            public function getAttributes(): array { return []; }
        };

        $manager->registerPage($page);
        ob_start();
        $manager->render('integration-test');
        $output = ob_get_clean();

        $this->assertStringContainsString('presto-dashboard-app', $output);
        $tree = $manager->getMenuTree();
        $this->assertIsArray($tree);
    }
}
