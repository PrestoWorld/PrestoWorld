<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use App\Http\PageRenderer;
use App\Contracts\Http\ThemeConfig;

class PageRendererTest extends TestCase
{
    private function makeRenderer(array $config = []): PageRenderer
    {
        return new PageRenderer(ThemeConfig::fromArray($config));
    }

    public function test_render_returns_full_html_document(): void
    {
        $renderer = $this->makeRenderer();
        $html = $renderer->render('<p>Hello</p>');

        $this->assertStringStartsWith('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('</html>', $html);
        $this->assertStringContainsString('<p>Hello</p>', $html);
    }

    public function test_render_uses_custom_title(): void
    {
        $renderer = $this->makeRenderer();
        $html = $renderer->render('', 'Custom Title');

        $this->assertStringContainsString('<title>Custom Title</title>', $html);
    }

    public function test_render_default_title(): void
    {
        $renderer = $this->makeRenderer();
        $html = $renderer->render('');

        $this->assertStringContainsString('<title>PrestoWorld</title>', $html);
    }

    public function test_render_uses_configured_default_title(): void
    {
        $renderer = $this->makeRenderer(['default_title' => 'MySite']);
        $html = $renderer->render('');

        $this->assertStringContainsString('<title>MySite</title>', $html);
        $this->assertStringNotContainsString('<title>PrestoWorld</title>', $html);
    }

    public function test_render_uses_configured_charset(): void
    {
        $renderer = $this->makeRenderer(['charset' => 'ISO-8859-1']);
        $html = $renderer->render('');

        $this->assertStringContainsString('ISO-8859-1', $html);
    }

    public function test_render_uses_configured_viewport(): void
    {
        $renderer = $this->makeRenderer(['viewport' => 'width=480']);
        $html = $renderer->render('');

        $this->assertStringContainsString('width=480', $html);
    }

    public function test_render_uses_configured_css_reset(): void
    {
        $renderer = $this->makeRenderer(['css_reset' => 'body { margin: 10px; }']);
        $html = $renderer->render('');

        $this->assertStringContainsString('body { margin: 10px; }', $html);
        $this->assertStringNotContainsString('box-sizing', $html);
    }

    public function test_add_style_appears_in_output(): void
    {
        $renderer = $this->makeRenderer();
        $renderer->addStyle('body { color: red; }');
        $html = $renderer->render('');

        $this->assertStringContainsString('body { color: red; }', $html);
    }

    public function test_multiple_styles_are_combined(): void
    {
        $renderer = $this->makeRenderer();
        $renderer->addStyle('a { color: blue; }');
        $renderer->addStyle('p { margin: 0; }');
        $html = $renderer->render('');

        $this->assertStringContainsString('a { color: blue; }', $html);
        $this->assertStringContainsString('p { margin: 0; }', $html);
    }

    public function test_add_style_after_render_does_not_affect_previous_output(): void
    {
        $renderer = $this->makeRenderer();
        $first = $renderer->render('');

        $renderer->addStyle('new { color: red; }');
        $second = $renderer->render('');

        $this->assertStringNotContainsString('new', $first);
        $this->assertStringContainsString('new', $second);
    }

    public function test_render_handles_empty_body(): void
    {
        $renderer = $this->makeRenderer();
        $html = $renderer->render('');

        $this->assertStringContainsString('<body>', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function test_render_includes_viewport_meta(): void
    {
        $renderer = $this->makeRenderer();
        $html = $renderer->render('');

        $this->assertStringContainsString('viewport', $html);
    }

    public function test_render_includes_charset(): void
    {
        $renderer = $this->makeRenderer();
        $html = $renderer->render('');

        $this->assertStringContainsString('charset', $html);
    }
}
