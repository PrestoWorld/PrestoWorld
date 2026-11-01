<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Context;

use PrestoWorld\Core\Admin\Contracts\AdminContextInterface;
use Witals\Framework\Http\Request;

class AdminContext implements AdminContextInterface
{
    public function __construct(protected ?Request $request = null)
    {
        if ($this->request === null && function_exists('app')) {
            try {
                $this->request = app(Request::class);
            } catch (\Throwable $e) {
                $this->request = null;
            }
        }
    }

    public function isWpAdmin(): bool
    {
        $path = $this->getPath();
        if (str_starts_with($path, '/wp-admin')) {
            return true;
        }

        if (isset($_GET['page']) && defined('ABSPATH')) {
            // Likely WordPress admin style request in bridge context
        }

        // Check if running through wp-bridge controller context
        if ($this->request !== null) {
            $uri = method_exists($this->request, 'uri') ? $this->request->uri() : $path;
            if (str_contains((string)$uri, 'wp-admin')) {
                return true;
            }
        }

        return false;
    }

    public function isSpaDashboard(): bool
    {
        if ($this->isWpAdmin()) {
            return false;
        }
        $path = $this->getPath();
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/dashboard')) {
            return true;
        }
        return true;
    }

    public function getMode(): string
    {
        return $this->isWpAdmin() ? 'wp-admin' : 'spa';
    }

    protected function getPath(): string
    {
        if ($this->request !== null && method_exists($this->request, 'path')) {
            return (string)$this->request->path();
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
            return $uri;
        }
        return '/';
    }
}
