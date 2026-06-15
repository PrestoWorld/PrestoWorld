<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Foundation\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class ApplicationTest extends TestCase
{
    public function test_application_can_handle_request()
    {
        $app = new Application(dirname(__DIR__, 2));

        $request = new Request('GET', '/');

        $response = $app->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
