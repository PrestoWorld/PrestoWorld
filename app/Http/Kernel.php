<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;
use App\Contracts\Http\PageRenderer;

class Kernel implements KernelContract
{
    public function __construct(
        private GutenbergModule $gutenberg,
        private PageRenderer $renderer,
        private TemplateResolver $resolver,
    ) {}

    public function handle(Request $request): Response
    {
        $template = $this->resolver->resolve($request);
        $this->renderer->addStyle($this->gutenberg->getStyles());
        $body = $this->gutenberg->renderTemplate($template);
        $html = $this->renderer->render($body);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
