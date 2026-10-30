<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Foundation\Application;
use App\Config\ConfigManager;
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

    public function test_table_prefix_reader_uses_environment_and_refreshes_singleton_cache(): void
    {
        $previousGetenv = getenv('PW_TABLE_PREFIX');
        $hadEnvValue = array_key_exists('PW_TABLE_PREFIX', $_ENV);
        $previousEnv = $_ENV['PW_TABLE_PREFIX'] ?? null;
        $hadServerValue = array_key_exists('PW_TABLE_PREFIX', $_SERVER);
        $previousServer = $_SERVER['PW_TABLE_PREFIX'] ?? null;

        try {
            putenv('PW_TABLE_PREFIX=first_');
            $_ENV['PW_TABLE_PREFIX'] = 'first_';
            $_SERVER['PW_TABLE_PREFIX'] = 'first_';

            $config = new ConfigManager(dirname(__DIR__, 2));
            $this->assertSame('first_', $config->getTablePrefix());

            putenv('PW_TABLE_PREFIX=second_');
            $_ENV['PW_TABLE_PREFIX'] = 'second_';
            $_SERVER['PW_TABLE_PREFIX'] = 'second_';
            $config->clearCache();

            $this->assertSame('second_', $config->getTablePrefix());
        } finally {
            if ($previousGetenv === false) {
                putenv('PW_TABLE_PREFIX');
            } else {
                putenv('PW_TABLE_PREFIX=' . $previousGetenv);
            }

            if ($hadEnvValue) {
                $_ENV['PW_TABLE_PREFIX'] = $previousEnv;
            } else {
                unset($_ENV['PW_TABLE_PREFIX']);
            }

            if ($hadServerValue) {
                $_SERVER['PW_TABLE_PREFIX'] = $previousServer;
            } else {
                unset($_SERVER['PW_TABLE_PREFIX']);
            }
        }
    }
}
