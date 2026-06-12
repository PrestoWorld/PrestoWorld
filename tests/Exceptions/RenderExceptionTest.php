<?php

declare(strict_types=1);

namespace Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use App\Exceptions\RenderException;

class RenderExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = new RenderException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testConstructorAcceptsMessage(): void
    {
        $exception = new RenderException('Test message');

        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testConstructorAcceptsCode(): void
    {
        $exception = new RenderException('Test message', 500);

        $this->assertSame(500, $exception->getCode());
    }

    public function testConstructorAcceptsPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new RenderException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
