<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;

class Kernel implements KernelContract
{
    public function handle(Request $request): Response
    {
        $gutenberg = app(GutenbergModule::class);
        $renderer = new PageRenderer();
        $resolver = new TemplateResolver();

        $template = $resolver->resolve($request);
        $renderer->addStyle($gutenberg->getStyles());
        $body  = $gutenberg->renderTemplate($template);
        $html  = $renderer->render($body);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
