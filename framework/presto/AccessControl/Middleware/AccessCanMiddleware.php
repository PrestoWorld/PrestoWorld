<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Middleware;

use PrestoWorld\AccessControl\AccessControlManager;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Middleware bảo vệ route/controller bằng Access Control.
 *
 * Cách dùng:
 *   $router->middleware('access.can:posts.edit', AccessCanMiddleware::class);
 *
 * Hoặc gán trực tiếp:
 *   new AccessCanMiddleware($app, 'posts.edit', 'post:{id}');
 */
class AccessCanMiddleware
{
    public function __construct(
        protected AccessControlManager $access,
        protected string $action,
        protected ?string $resourceExpr = null,
        protected string $subjectResolver = 'auth.user.id',
    ) {}

    public function handle(Request $request, \Closure $next): Response
    {
        $subject = $this->resolveSubject($request);

        $resource = null;
        if ($this->resourceExpr !== null) {
            $resource = $this->resolveResource($request);
        }

        $result = $this->access->can($subject, $this->action, $resource, [
            'request' => $request,
            'route' => $request->getAttributes(),
        ]);

        if (!$result['allowed']) {
            return Response::json([
                'error' => 'forbidden',
                'message' => $result['reason'] ?? 'Bạn không có quyền thực hiện thao tác này.',
                'model' => $result['model'] ?? 'rbac',
            ], 403);
        }

        return $next($request);
    }

    protected function resolveSubject(Request $request): string
    {
        if ($this->subjectResolver === 'auth.user.id') {
            $auth = $request->getAttribute('auth.user');
            if (is_array($auth) && isset($auth['id']) && is_string($auth['id'])) {
                return $auth['id'];
            }

            $id = $request->header('X-User-Id');
            if (is_string($id)) {
                return $id;
            }

            return 'anonymous';
        }

        return 'anonymous';
    }

    protected function resolveResource(Request $request): ?string
    {
        if ($this->resourceExpr === null) {
            return null;
        }

        $resource = $this->resourceExpr;
        foreach ($request->getAttributes() as $key => $value) {
            $valueStr = is_scalar($value) ? (string) $value : '';
            $resource = str_replace('{' . $key . '}', $valueStr, $resource);
        }

        return $resource;
    }
}
