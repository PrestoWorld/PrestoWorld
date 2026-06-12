<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use App\Services\PageService;
use App\Exceptions\TemplateNotFoundException;
use App\Exceptions\RenderException;

class Kernel implements KernelContract
{
    private const SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    public function __construct(
        private PageService $pageService,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $html = $this->pageService->handle($request);

            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                ...self::SECURITY_HEADERS,
            ]);
        } catch (TemplateNotFoundException) {
            return new Response('Page not found', 404, self::SECURITY_HEADERS);
        } catch (RenderException) {
            return new Response('Internal server error', 500, self::SECURITY_HEADERS);
        }
    }
}
