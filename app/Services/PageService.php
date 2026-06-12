<?php

declare(strict_types=1);

namespace App\Services;

use Witals\Framework\Http\Request;
use App\Http\TemplateResolver;
use App\Contracts\Http\PageRenderer;
use App\Exceptions\TemplateNotFoundException;
use App\Exceptions\RenderException;

class PageService
{
    public function __construct(
        private TemplateResolver $resolver,
        private ContentRenderer $contentRenderer,
        private PageRenderer $renderer,
    ) {}

    public function handle(Request $request): string
    {
        $template = $this->resolver->resolve($request);

        if ($template === null || $template === '') {
            throw new TemplateNotFoundException('No template could be resolved for this request');
        }

        $this->renderer->addStyle($this->contentRenderer->getStyles());

        try {
            $body = $this->contentRenderer->render($template);
        } catch (RenderException $e) {
            throw new RenderException(
                "Failed to render template [{$template}]: " . $e->getMessage(),
                0,
                $e
            );
        }

        return $this->renderer->render($body);
    }
}
