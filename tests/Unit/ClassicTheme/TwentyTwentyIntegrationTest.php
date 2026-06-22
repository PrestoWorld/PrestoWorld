<?php

declare(strict_types=1);

namespace Tests\Unit\ClassicTheme;

use PHPUnit\Framework\TestCase;
use App\Foundation\Application;
use PrestoWorld\Modules\ClassicTheme\ThemeDetector;
use PrestoWorld\Contracts\Theme\ThemeType;

class TwentyTwentyIntegrationTest extends TestCase
{
    private static string $themeDir;

    private static Application $app;

    private static bool $initialized = false;

    public static function setUpBeforeClass(): void
    {
        self::$themeDir = realpath(__DIR__ . '/../../../public/wp-content/themes/twentytwenty');

        if (self::$themeDir === false) {
            self::markTestSkipped('Twenty Twenty theme not found');
        }

        self::$app = new Application(dirname(__DIR__, 3));
        self::$app->setConfigPaths('config');
        self::$app->boot();

        // BridgeServiceProvider may auto-detect TwentyTwentyFive from
        // wp-config.php and override PW_THEME_DIR during boot; restore
        // our intended value afterward.
        putenv('PW_THEME_DIR=' . self::$themeDir);

        self::$initialized = true;
    }

    public function test_theme_is_detected_as_classic(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $type = ThemeDetector::detect(self::$themeDir);

        $this->assertSame(ThemeType::CLASSIC, $type);
    }

    public function test_functions_php_loads_twentytwenty_classes(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $engine = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class)->create();
        $loader = $engine->getFunctionsLoader();
        $loader->load();

        $this->assertTrue(
            function_exists('twentytwenty_the_theme_svg'),
            'twentytwenty_the_theme_svg should be defined by functions.php',
        );
        $this->assertTrue(
            class_exists('TwentyTwenty_Walker_Page'),
            'TwentyTwenty_Walker_Page should be available',
        );
        $this->assertTrue(
            class_exists('TwentyTwenty_SVG_Icons'),
            'TwentyTwenty_SVG_Icons should be available',
        );
        $this->assertTrue(
            class_exists('TwentyTwenty_Walker_Comment'),
            'TwentyTwenty_Walker_Comment should be available',
        );
    }

    public function test_engine_renders_full_html_document(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $result = $factory->create()->render('index');

        $this->assertInstanceOf(\App\Contracts\Services\RenderedContent::class, $result);
        $this->assertTrue($result->complete);

        $body = $result->body;
        $this->assertStringStartsWith('<!DOCTYPE html>', trim($body));
        $this->assertStringEndsWith('</html>', trim($body));
    }

    public function test_rendered_page_contains_expected_sections(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $result = $factory->create()->render('index');
        $body = $result->body;

        $this->assertStringContainsString('<header id="site-header"', $body, 'Page should have a header');
        $this->assertStringContainsString('<main id="site-content">', $body, 'Page should have main content');
        $this->assertStringContainsString('<footer id="site-footer"', $body, 'Page should have a footer');
    }

    public function test_rendered_page_contains_navigation(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $result = $factory->create()->render('index');
        $body = $result->body;

        $this->assertStringContainsString('primary-menu', $body, 'Page should contain primary menu');
        $this->assertStringContainsString('search-toggle', $body, 'Page should contain search toggle');
        $this->assertStringContainsString('nav-toggle', $body, 'Page should contain nav toggle');
    }

    public function test_rendered_page_is_valid_html(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $result = $factory->create()->render('index');
        $body = $result->body;

        $openHtml = substr_count($body, '<html');
        $closeHtml = substr_count($body, '</html>');
        $this->assertSame(1, $openHtml, 'There should be exactly one <html> tag');
        $this->assertSame(1, $closeHtml, 'There should be exactly one </html> tag');

        $openBody = substr_count($body, '<body');
        $closeBody = substr_count($body, '</body>');
        $this->assertSame(1, $openBody, 'There should be exactly one <body> tag');
        $this->assertSame(1, $closeBody, 'There should be exactly one </body> tag');
    }

    public function test_rendered_page_contains_site_title(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $result = $factory->create()->render('index');
        $body = $result->body;

        $this->assertStringContainsString(
            'PrestoWorld',
            $body,
            'Page should contain the site title from get_bloginfo',
        );
    }

    public function test_rendered_page_contains_footer_links(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $result = $factory->create()->render('index');
        $body = $result->body;

        $this->assertStringContainsString(
            'Privacy Policy',
            $body,
            'Page should contain privacy policy link in footer',
        );
        $this->assertStringContainsString(
            'Powered by WordPress',
            $body,
            'Page should contain powered-by-WordPress text',
        );
    }

    public function test_rendered_page_contains_search_form_in_modal(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $result = $factory->create()->render('index');
        $body = $result->body;

        $this->assertStringContainsString(
            'search-modal',
            $body,
            'Page should contain search modal',
        );
        $this->assertStringContainsString(
            'class="search-form"',
            $body,
            'Page should contain search form',
        );
    }

    public function test_style_parser_reads_twentytwenty_headers(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $engine = $factory->create();
        $parser = $engine->getStyleParser();

        $this->assertSame('Twenty Twenty', $parser->name());
        $this->assertNotEmpty($parser->version());
        $this->assertStringContainsString('block editor', $parser->description() ?? '');
    }

    public function test_render_single_template(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $result = $factory->create()->render('single', [
            'post_title' => 'Test Post',
            'post_content' => '<p>Hello World</p>',
            'post_type' => 'post',
        ]);

        $this->assertStringContainsString('<main id="site-content">', $result->body);
    }

    public function test_render_page_template(): void
    {
        if (!self::$initialized) {
            $this->markTestSkipped();
        }

        $factory = self::$app->make(\PrestoWorld\Theme\ThemeEngineFactory::class);
        $result = $factory->create()->render('page', [
            'post_title' => 'About Us',
            'post_content' => '<p>About page content</p>',
            'post_type' => 'page',
        ]);

        $this->assertStringContainsString('<main id="site-content">', $result->body);
    }
}
