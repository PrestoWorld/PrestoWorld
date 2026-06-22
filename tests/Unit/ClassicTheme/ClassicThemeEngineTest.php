<?php

declare(strict_types=1);

namespace Tests\Unit\ClassicTheme;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\ClassicTheme\ClassicThemeEngine;
use App\Contracts\Services\RenderedContent;
use Witals\Framework\Contracts\Container;

class ClassicThemeEngineTest extends TestCase
{
    private string $tmpDir;

    private Container $container;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/presto_engine_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
        $this->container = $this->createMock(Container::class);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->tmpDir);
    }

    public function test_render_returns_complete_content(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');
        file_put_contents($this->tmpDir . '/index.php', '<?php echo "<main>Home</main>";');
        file_put_contents($this->tmpDir . '/header.php', '<!DOCTYPE html><html><head><title>Test</title></head><body>');
        file_put_contents($this->tmpDir . '/footer.php', '</body></html>');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $result = $engine->render('index');

        $this->assertInstanceOf(RenderedContent::class, $result);
        $this->assertTrue($result->complete);
        $this->assertStringContainsString('<!DOCTYPE html>', $result->body);
        $this->assertStringContainsString('<main>Home</main>', $result->body);
        $this->assertStringContainsString('</body></html>', $result->body);
    }

    public function test_render_without_header_footer(): void
    {
        file_put_contents($this->tmpDir . '/index.php', '<?php echo "<main>Home</main>";');
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $result = $engine->render('home');

        $this->assertStringContainsString('<main>Home</main>', $result->body);
    }

    public function test_render_includes_styles_from_css(): void
    {
        $css = '/* Theme Name: Test */ body { color: black; } h1 { color: red; }';
        file_put_contents($this->tmpDir . '/style.css', $css);
        file_put_contents($this->tmpDir . '/index.php', '<?php echo "content"; ?>');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $result = $engine->render('index');

        $this->assertStringContainsString('body { color: black; }', $result->styles);
        $this->assertStringContainsString('h1 { color: red; }', $result->styles);
    }

    public function test_render_passes_post_data_to_template(): void
    {
        file_put_contents(
            $this->tmpDir . '/single.php',
            '<?php echo "<h1>" . ($post["post_title"] ?? "") . "</h1>"; ?>',
        );
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $result = $engine->render('single', ['post_title' => 'Hello World']);

        $this->assertStringContainsString('<h1>Hello World</h1>', $result->body);
    }

    public function test_supports_returns_true_when_template_exists(): void
    {
        file_put_contents($this->tmpDir . '/page.php', '<?php');
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);

        $this->assertTrue($engine->supports('page'));
    }

    public function test_supports_returns_true_for_index_fallback(): void
    {
        file_put_contents($this->tmpDir . '/index.php', '<?php');
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);

        $this->assertTrue($engine->supports('nonexistent'));
    }

    public function test_supports_returns_false_when_no_templates(): void
    {
        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);

        $this->assertFalse($engine->supports('anything'));
    }

    public function test_get_styles_returns_css(): void
    {
        $css = '/* Theme Name: Test */ body { background: white; }';
        file_put_contents($this->tmpDir . '/style.css', $css);

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $styles = $engine->getStyles();

        $this->assertStringContainsString('body { background: white; }', $styles);
    }

    public function test_get_styles_returns_empty_when_no_css(): void
    {
        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);

        $this->assertSame('', $engine->getStyles());
    }

    public function test_get_theme_path(): void
    {
        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);

        $this->assertSame($this->tmpDir, $engine->getThemePath());
    }

    public function test_get_style_parser(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: ParserTest */');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $parser = $engine->getStyleParser();

        $this->assertSame('ParserTest', $parser->name());
    }

    public function test_render_partial(): void
    {
        mkdir($this->tmpDir . '/template-parts', 0777, true);
        file_put_contents(
            $this->tmpDir . '/template-parts/content.php',
            '<?php echo "<article>" . ($post["post_title"] ?? "") . "</article>"; ?>',
        );

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $html = $engine->renderPart('content', ['post_title' => 'Partial']);

        $this->assertStringContainsString('<article>Partial</article>', $html);
    }

    public function test_render_partial_returns_empty_when_not_found(): void
    {
        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $html = $engine->renderPart('nonexistent');

        $this->assertSame('', $html);
    }

    public function test_functions_loader_invokes_functions_php(): void
    {
        file_put_contents(
            $this->tmpDir . '/functions.php',
            '<?php function my_test_function() { return "from_functions"; }',
        );
        file_put_contents(
            $this->tmpDir . '/index.php',
            '<?php echo my_test_function(); ?>',
        );
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $result = $engine->render('index');

        $this->assertStringContainsString('from_functions', $result->body);
    }

    public function test_render_handles_404_template(): void
    {
        file_put_contents($this->tmpDir . '/404.php', '<?php echo "<h1>Not Found</h1>"; ?>');
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);
        $result = $engine->render('404');

        $this->assertStringContainsString('<h1>Not Found</h1>', $result->body);
    }

    public function test_engine_is_singleton_across_calls(): void
    {
        file_put_contents($this->tmpDir . '/index.php', '<?php echo "hello"; ?>');
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');

        $engine = new ClassicThemeEngine($this->tmpDir, $this->container);

        $loader1 = $engine->getTemplateLoader();
        $loader2 = $engine->getTemplateLoader();

        $this->assertSame($loader1, $loader2);
    }

    private function rmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
