<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ContentRenderer;
use App\Exceptions\RenderException;

class ContentRendererTest extends TestCase
{
    public function test_render_throws_when_gutenberg_null(): void
    {
        $renderer = new ContentRenderer(null);

        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('Gutenberg module is not available');

        $renderer->render('index');
    }

    public function test_get_styles_returns_empty_when_null(): void
    {
        $renderer = new ContentRenderer(null);

        $this->assertSame('', $renderer->getStyles());
    }
}
