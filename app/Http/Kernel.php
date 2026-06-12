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
    public function __construct(
        private PageService $pageService,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $html = $this->pageService->handle($request);

            return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (TemplateNotFoundException) {
            return new Response('Page not found', 404);
        } catch (RenderException) {
            return new Response('Internal server error', 500);
        }
    }
}
