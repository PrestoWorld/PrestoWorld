<?php

declare(strict_types=1);

namespace App\Services;

use Witals\Framework\Http\Request;
use App\Http\TemplateResolver;
use App\Contracts\Http\PageRenderer;
use App\Contracts\Services\ContentRenderer;
use App\Exceptions\TemplateNotFoundException;

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
        $body = $this->contentRenderer->render($template);

        return $this->renderer->render($body);
    }
}
