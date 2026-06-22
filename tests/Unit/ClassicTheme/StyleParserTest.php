<?php

declare(strict_types=1);

namespace Tests\Unit\ClassicTheme;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\ClassicTheme\StyleParser;

class StyleParserTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = sys_get_temp_dir() . '/test_style_' . uniqid() . '.css';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function test_parse_returns_empty_when_file_missing(): void
    {
        $parser = new StyleParser('/nonexistent/style.css');
        $this->assertSame([], $parser->parse());
    }

    public function test_parse_extracts_all_standard_headers(): void
    {
        $css = <<<CSS
/*
Theme Name: Twenty Twenty
Theme URI: https://wordpress.org/themes/twentytwenty/
Author: the WordPress team
Author URI: https://wordpress.org/
Description: Our default theme for 2020
Version: 3.1
Requires at least: 4.7
Tested up to: 7.0
Requires PHP: 5.2.4
Text Domain: twentytwenty
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: blog, one-column, custom-background
*/

body { color: black; }
CSS;

        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);
        $data = $parser->parse();

        $this->assertSame('Twenty Twenty', $data['Theme Name']);
        $this->assertSame('https://wordpress.org/themes/twentytwenty/', $data['Theme URI']);
        $this->assertSame('the WordPress team', $data['Author']);
        $this->assertSame('3.1', $data['Version']);
        $this->assertSame('4.7', $data['Requires at least']);
        $this->assertSame('7.0', $data['Tested up to']);
        $this->assertSame('5.2.4', $data['Requires PHP']);
        $this->assertSame('twentytwenty', $data['Text Domain']);
        $this->assertSame('GPLv2 or later', $data['License']);
        $this->assertSame('blog, one-column, custom-background', $data['Tags']);
    }

    public function test_get_returns_specific_header(): void
    {
        $css = '/* Theme Name: My Theme */ body { }';

        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);

        $this->assertSame('My Theme', $parser->get('Theme Name'));
        $this->assertNull($parser->get('Nonexistent'));
        $this->assertSame('default', $parser->get('Nonexistent', 'default'));
    }

    public function test_name_version_description_helpers(): void
    {
        $css = "/*\nTheme Name: Test Theme\nVersion: 2.0\nDescription: A test\n*/\nbody { }";

        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);

        $this->assertSame('Test Theme', $parser->name());
        $this->assertSame('2.0', $parser->version());
        $this->assertSame('A test', $parser->description());
    }

    public function test_text_domain_helper(): void
    {
        $css = '/* Text Domain: my-domain */ body { }';

        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);

        $this->assertSame('my-domain', $parser->textDomain());
    }

    public function test_tags_helper(): void
    {
        $css = '/* Tags: blog, one-column, custom-background, accessibility-ready */ body { }';

        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);

        $this->assertSame(
            ['blog', 'one-column', 'custom-background', 'accessibility-ready'],
            $parser->tags(),
        );
    }

    public function test_css_returns_content_after_header(): void
    {
        $css = <<<CSS
/* Theme Name: Test */
body { color: black; }
h1 { color: red; }
CSS;

        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);

        $this->assertSame("body { color: black; }\nh1 { color: red; }", $parser->css());
    }

    public function test_css_returns_full_content_when_no_header(): void
    {
        $css = 'body { margin: 0; }';

        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);

        $this->assertSame('body { margin: 0; }', $parser->css());
    }

    public function test_css_returns_empty_when_no_file(): void
    {
        $parser = new StyleParser('/nonexistent/style.css');
        $this->assertSame('', $parser->css());
    }

    public function test_parse_caches_result_by_mtime(): void
    {
        $css = '/* Theme Name: First */';
        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);

        $this->assertSame('First', $parser->name());

        sleep(1);
        file_put_contents($this->tmpFile, '/* Theme Name: Second */');

        $this->assertSame('Second', $parser->name());
    }

    public function test_helper_return_defaults_when_missing(): void
    {
        $css = '/* Some Random Comment */ body { }';

        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);

        $this->assertSame('Unknown', $parser->name());
        $this->assertSame('1.0', $parser->version());
        $this->assertSame('', $parser->description());
        $this->assertSame('', $parser->textDomain());
        $this->assertSame([], $parser->tags());
    }

    public function test_parse_extracts_template_header(): void
    {
        $css = '/* Template: twentytwentyfour */ body { }';

        file_put_contents($this->tmpFile, $css);
        $parser = new StyleParser($this->tmpFile);

        $this->assertSame('twentytwentyfour', $parser->get('Template'));
    }
}
