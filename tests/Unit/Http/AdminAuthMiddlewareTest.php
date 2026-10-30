<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Middleware\AdminAuthMiddleware;
use PHPUnit\Framework\TestCase;
use PrestoWorld\AccessControl\AccessControlManager;
use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Contracts\Auth\TokenInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class AdminAuthMiddlewareTest extends TestCase
{
    public function test_acl_root_role_allows_dashboard_access_for_token_user_id(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getPayload')->willReturn([
            'user_id' => 1,
            'role' => 'subscriber',
        ]);

        $auth = $this->createMock(AuthContextInterface::class);
        $auth->method('getToken')->willReturn($token);
        $auth->method('getActor')->willReturn(null);

        $accessControl = $this->getMockBuilder(AccessControlManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasRole'])
            ->getMock();
        $accessControl->expects($this->once())
            ->method('hasRole')
            ->with('1', 'root')
            ->willReturn(true);

        $middleware = new AdminAuthMiddleware($auth, $accessControl);
        $response = $middleware->handle(
            new Request('GET', '/dashboard'),
            static fn (): Response => Response::html('dashboard'),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('dashboard', $response->getContent());
    }

    public function test_user_without_acl_root_role_still_gets_forbidden(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getPayload')->willReturn([
            'user_id' => 1,
            'role' => 'subscriber',
        ]);

        $auth = $this->createMock(AuthContextInterface::class);
        $auth->method('getToken')->willReturn($token);
        $auth->method('getActor')->willReturn(null);

        $accessControl = $this->getMockBuilder(AccessControlManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasRole'])
            ->getMock();
        $accessControl->expects($this->once())
            ->method('hasRole')
            ->with('1', 'root')
            ->willReturn(false);

        $middleware = new AdminAuthMiddleware($auth, $accessControl);
        $response = $middleware->handle(
            new Request('GET', '/dashboard'),
            static fn (): Response => Response::html('dashboard'),
        );

        $this->assertSame(403, $response->getStatusCode());
    }
}
