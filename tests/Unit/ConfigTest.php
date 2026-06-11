<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Foundation\Application;
use PrestoWorld\Foundation\Config\ConfigRepository;

class ConfigTest extends TestCase
{
    protected string $tempConfigPath;

    protected function setUp(): void
    {
        $this->tempConfigPath = __DIR__ . '/../temp_config';
        if (!is_dir($this->tempConfigPath)) {
            mkdir($this->tempConfigPath, 0777, true);
        }

        // Create a dummy config file
        file_put_contents($this->tempConfigPath . '/app.php', "<?php return ['name' => 'TestApp', 'debug' => true];");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempConfigPath . '/app.php')) {
            unlink($this->tempConfigPath . '/app.php');
        }
        if (is_dir($this->tempConfigPath)) {
            rmdir($this->tempConfigPath);
        }
    }

    public function test_can_load_config()
    {
        $app = new Application(dirname(__DIR__, 2));
        $app->setConfigPaths($this->tempConfigPath);

        $this->assertEquals('TestApp', $app->config('app.name'));
        $this->assertTrue($app->config('app.debug'));
    }

    public function test_returns_default_when_config_not_found()
    {
        $app = new Application(dirname(__DIR__, 2));
        $app->setConfigPaths($this->tempConfigPath);

        $this->assertEquals('Default', $app->config('invalid.key', 'Default'));
    }
}
