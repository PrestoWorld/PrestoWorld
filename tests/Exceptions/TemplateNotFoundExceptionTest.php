<?php

declare(strict_types=1);

namespace Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use App\Exceptions\TemplateNotFoundException;

class TemplateNotFoundExceptionTest extends TestCase
{
    public function testExtendsRenderException(): void
    {
        $exception = new TemplateNotFoundException('Test message');

        $this->assertInstanceOf(\App\Exceptions\RenderException::class, $exception);
    }

    public function testConstructorAcceptsMessage(): void
    {
        $exception = new TemplateNotFoundException('Template not found');

        $this->assertSame('Template not found', $exception->getMessage());
    }

    public function testConstructorAcceptsCode(): void
    {
        $exception = new TemplateNotFoundException('Test message', 404);

        $this->assertSame(404, $exception->getCode());
    }

    public function testConstructorAcceptsPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new TemplateNotFoundException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
