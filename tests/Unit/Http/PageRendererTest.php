<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use App\Http\PageRenderer;

class PageRendererTest extends TestCase
{
    public function test_render_returns_full_html_document(): void
    {
        $renderer = new PageRenderer();
        $html = $renderer->render('<p>Hello</p>');

        $this->assertStringStartsWith('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('</html>', $html);
        $this->assertStringContainsString('<p>Hello</p>', $html);
    }

    public function test_render_uses_custom_title(): void
    {
        $renderer = new PageRenderer();
        $html = $renderer->render('', 'Custom Title');

        $this->assertStringContainsString('<title>Custom Title</title>', $html);
    }

    public function test_render_default_title(): void
    {
        $renderer = new PageRenderer();
        $html = $renderer->render('');

        $this->assertStringContainsString('<title>PrestoWorld</title>', $html);
    }

    public function test_add_style_appears_in_output(): void
    {
        $renderer = new PageRenderer();
        $renderer->addStyle('body { color: red; }');
        $html = $renderer->render('');

        $this->assertStringContainsString('body { color: red; }', $html);
    }

    public function test_multiple_styles_are_combined(): void
    {
        $renderer = new PageRenderer();
        $renderer->addStyle('a { color: blue; }');
        $renderer->addStyle('p { margin: 0; }');
        $html = $renderer->render('');

        $this->assertStringContainsString('a { color: blue; }', $html);
        $this->assertStringContainsString('p { margin: 0; }', $html);
    }

    public function test_add_style_after_render_does_not_affect_previous_output(): void
    {
        $renderer = new PageRenderer();
        $first = $renderer->render('');

        $renderer->addStyle('new { color: red; }');
        $second = $renderer->render('');

        $this->assertStringNotContainsString('new', $first);
        $this->assertStringContainsString('new', $second);
    }

    public function test_render_handles_empty_body(): void
    {
        $renderer = new PageRenderer();
        $html = $renderer->render('');

        $this->assertStringContainsString('<body>', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function test_render_includes_viewport_meta(): void
    {
        $renderer = new PageRenderer();
        $html = $renderer->render('');

        $this->assertStringContainsString('viewport', $html);
    }

    public function test_render_includes_charset(): void
    {
        $renderer = new PageRenderer();
        $html = $renderer->render('');

        $this->assertStringContainsString('UTF-8', $html);
    }

    public function test_render_escapes_title(): void
    {
        $renderer = new PageRenderer();
        $html = $renderer->render('', 'Test & "Title"');

        $this->assertStringContainsString('Test & "Title"', $html);
    }
}
