<?php

declare(strict_types=1);

namespace Tests\Foundation;

use PHPUnit\Framework\TestCase;
use App\Foundation\Application;

class ApplicationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application('/tmp');
    }

    public function testConstructorCreatesApplication(): void
    {
        $this->assertInstanceOf(Application::class, $this->app);
    }

    public function testBootIsIdempotent(): void
    {
        $this->app->boot();
        $this->app->boot();

        $this->expectNotToPerformAssertions();
    }

    public function testSetConfigPaths(): void
    {
        $this->app->setConfigPaths('/config');

        $this->expectNotToPerformAssertions();
    }

    public function testSetConfigPathsWithArray(): void
    {
        $this->app->setConfigPaths(['/config1', '/config2']);

        $this->expectNotToPerformAssertions();
    }

    public function testAddConfigPath(): void
    {
        $this->app->addConfigPath('/config');

        $this->expectNotToPerformAssertions();
    }

    public function testConfigReturnsDefaultWhenNotFound(): void
    {
        $result = $this->app->config('nonexistent.key', 'default');

        $this->assertSame('default', $result);
    }

    public function testConfigReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $result = $this->app->config('nonexistent.key');

        $this->assertNull($result);
    }

    public function testResolveConfigRepositoryReturnsRepository(): void
    {
        $repo = $this->app->resolveConfigRepository();

        $this->assertInstanceOf(\PrestoWorld\Foundation\Config\ConfigRepository::class, $repo);
    }

    public function testResolveConfigRepositoryCachesInstance(): void
    {
        $repo1 = $this->app->resolveConfigRepository();
        $repo2 = $this->app->resolveConfigRepository();

        $this->assertSame($repo1, $repo2);
    }

    public function testInitializeTranslator(): void
    {
        $this->app->initializeTranslator();

        $this->expectNotToPerformAssertions();
    }

    public function testRegisterConfiguredProviders(): void
    {
        $this->expectNotToPerformAssertions();

        $this->app->registerConfiguredProviders();
    }

    public function testExtendsBaseApplication(): void
    {
        $this->assertInstanceOf(\Witals\Framework\Application::class, $this->app);
    }
}
