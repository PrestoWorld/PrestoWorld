<?php

declare(strict_types=1);

namespace App\Services;

use Witals\Framework\Http\Request;
use App\Http\TemplateResolver;
use App\Contracts\Http\PageRenderer;
use App\Contracts\Services\ContentRenderer;
use App\Exceptions\TemplateNotFoundException;
use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;

class PageService
{
    public function __construct(
        private TemplateResolver $resolver,
        private ContentRenderer $contentRenderer,
        private PageRenderer $renderer,
        private PostRepository $posts,
        private DatabaseInterface $db,
    ) {}

    public function handle(Request $request): string
    {
        $template = $this->resolver->resolve($request);

        if ($template === null || $template === '') {
            throw new TemplateNotFoundException('No template could be resolved for this request');
        }

        $path = rtrim($request->path(), '/');
        $segments = explode('/', trim($path, '/'));

        $slug = $segments[0] ?? '';

        $post = [];
        if ($slug !== '') {
            $row = $this->db->select('p.*', 't.title AS translation_title', 't.content AS translation_content')
                ->from('pw_posts AS p')
                ->leftJoin('pw_post_translations AS t')
                ->on('p.id', 't.post_id')
                ->andOn('t.locale', 'en')
                ->where('p.slug', $slug)
                ->where('p.post_type', 'page')
                ->where('p.status', 'publish')
                ->run()
                ->fetch();

            if ($row) {
                $post = $row;
                if (isset($row['translation_content'])) {
                    $post['content'] = $row['translation_content'];
                }
                if (isset($row['translation_title'])) {
                    $post['title'] = $row['translation_title'];
                }
                $post['post_title'] = $post['title'];
                $post['post_content'] = $post['content'];
            }
        }

        $content = $this->contentRenderer->render($template, $post);

        return $this->renderer->render($content);
    }
}
