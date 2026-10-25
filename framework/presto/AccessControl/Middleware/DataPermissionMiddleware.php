<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Middleware;

use PrestoWorld\AccessControl\AccessControlManager;
use PrestoWorld\AccessControl\ModulePermissionManager;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Middleware bảo vệ dữ liệu & tính năng của module cấp độ data/feature.
 *
 * Cách dùng:
 *   $router->middleware('access.module:orders.edit', DataPermissionMiddleware::class);
 *
 * Hoặc gán trực tiếp:
 *   new DataPermissionMiddleware($app, 'orders', 'edit', 'order:{id}');
 *
 * Kiểm tra qua ModulePermissionManager::canModule() — kết hợp RBAC role
 * và module permission. Nếu không đủ quyền, trả về 403.
 */
class DataPermissionMiddleware
{
    public function __construct(
        protected AccessControlManager $access,
        protected ModulePermissionManager $modulePerm,
        protected string $module,
        protected string $feature,
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

        $result = $this->modulePerm->canModule($subject, $this->module, $feature, $resource, [
            'request' => $request,
            'route' => $request->getAttributes(),
        ]);

        if (!$result['allowed']) {
            return Response::json([
                'error' => 'forbidden',
                'message' => $result['reason'] ?? 'Bạn không có quyền truy cập dữ liệu này.',
                'module' => $this->module,
                'feature' => $this->feature,
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
