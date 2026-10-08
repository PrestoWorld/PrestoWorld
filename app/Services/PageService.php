<?php

declare(strict_types=1);

namespace App\Services;

use Witals\Framework\Http\Request;
use App\Http\TemplateResolver;
use App\Contracts\Http\PageRenderer;
use App\Contracts\Services\ContentRenderer;
use App\Exceptions\NotFoundException;
use App\Exceptions\TemplateNotFoundException;
use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\Parameter;

class PageService
{
    private string $tablePrefix;

    /**
     * Set when the content tables are unavailable on the default connection
     * (multi-database setups where content lives on another connection).
     * While set, a missing row is treated as "unknown" instead of a 404.
     */
    private bool $lookupUnavailable = false;

    public function __construct(
        private TemplateResolver $resolver,
        private ContentRenderer $contentRenderer,
        private PageRenderer $renderer,
        private PostRepository $posts,
        private DatabaseInterface $db,
    ) {
        $this->tablePrefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
    }

    public function handle(Request $request): string
    {
        $template = $this->resolver->resolve($request);

        if ($template === null || $template === '') {
            throw new TemplateNotFoundException('No template could be resolved for this request');
        }

        $path = rtrim($request->path(), '/');
        $segments = $this->segments($path);
        $explicit = $path === '' || $this->resolver->matchesExplicitly($request);

        $post = [];
        foreach ($this->lookupVariants($segments) as $variant) {
            $post = $this->findPostBySlug($variant[0] ?? '');
            if ($post !== []) {
                break;
            }
        }

        $term = null;
        if (!$explicit && $post === []) {
            foreach ($this->lookupVariants($segments) as $variant) {
                $term = $this->findTerm($variant);
                if ($term !== null) {
                    break;
                }
            }
        }

        if (!$explicit) {
            if ($post !== []) {
                $hierarchy = ($post['post_type'] ?? 'page') === 'post' ? 'single' : 'page';
                if ($this->supportsTemplate($hierarchy)) {
                    $template = $hierarchy;
                }
            } elseif ($term !== null) {
                $template = $this->archiveTemplate($segments[0] ?? '', $template);
            } elseif (!$this->lookupUnavailable) {
                throw new NotFoundException("No content matched path [{$path}]");
            }
        }

        $data = $post !== [] ? $post : ($term !== null ? ['term' => $term] : []);
        $content = $this->contentRenderer->render($template, $data);

        return $this->renderer->render($content);
    }

    /**
     * Render the 404 response body: the theme's "404" template when available,
     * otherwise a built-in WordPress-style "page not found" page.
     */
    public function renderNotFound(Request $request): string
    {
        try {
            if ($this->supportsTemplate('404')) {
                $content = $this->contentRenderer->render('404', []);
                if (trim($content->body) !== '') {
                    return $this->renderer->render($content, 'Page not found');
                }
            }
        } catch (\Throwable) {
            // Theme template unavailable or broken — fall back to the built-in page.
        }

        return self::fallbackNotFoundPage();
    }

    /**
     * Look up a published post/page by its first path segment.
     *
     * @return array<string, mixed>
     */
    protected function findPostBySlug(string $slug): array
    {
        if ($slug === '') {
            return [];
        }

        try {
            $posts = $this->tablePrefix . 'posts';
            if (!$this->db->hasTable($posts)) {
                $this->lookupUnavailable = true;

                return [];
            }

            $translations = $this->tablePrefix . 'post_translations';
            $withTranslations = $this->db->hasTable($translations);

            $columns = ['p.*'];
            if ($withTranslations) {
                $columns[] = 't.title AS translation_title';
                $columns[] = 't.content AS translation_content';
            }

            $query = $this->db->select(...$columns)->from("{$posts} AS p");
            if ($withTranslations) {
                $query->leftJoin("{$translations} AS t")
                    ->on('p.id', 't.post_id')
                    ->andOn('t.locale', '=', new Parameter('en'));
            }

            $row = $query
                ->where('p.slug', $slug)
                ->where('p.post_type', 'IN', ['page', 'post'])
                ->where('p.status', 'publish')
                ->run()
                ->fetch();
        } catch (\Throwable) {
            $this->lookupUnavailable = true;

            return [];
        }

        if (!is_array($row) || $row === []) {
            return [];
        }

        if (isset($row['translation_content'])) {
            $row['content'] = $row['translation_content'];
        }
        if (isset($row['translation_title'])) {
            $row['title'] = $row['translation_title'];
        }
        $row['post_title'] = $row['title'] ?? '';
        $row['post_content'] = $row['content'] ?? '';

        return $row;
    }

    /**
     * Look up a taxonomy term for a "/taxonomy/slug" style path.
     *
     * @param list<string> $segments
     * @return array<mixed, mixed>|null
     */
    protected function findTerm(array $segments): ?array
    {
        if (count($segments) < 2) {
            return null;
        }

        try {
            $terms = $this->tablePrefix . 'terms';
            if (!$this->db->hasTable($terms)) {
                $this->lookupUnavailable = true;

                return null;
            }

            $row = $this->db->select('*')
                ->from($terms)
                ->where('taxonomy', $segments[0])
                ->where('slug', $segments[count($segments) - 1])
                ->run()
                ->fetch();
        } catch (\Throwable) {
            $this->lookupUnavailable = true;

            return null;
        }

        if (!is_array($row) || $row === []) {
            return null;
        }

        return $row;
    }

    /**
     * @param list<string> $segments
     * @return list<list<string>>
     */
    private function lookupVariants(array $segments): array
    {
        $variants = [$segments];

        // Locale-prefixed paths ("/vi/ve-chung-toi") also match the un-prefixed segments.
        if ($segments !== [] && preg_match('/^[a-z]{2}$/', $segments[0]) === 1) {
            $stripped = array_slice($segments, 1);
            if ($stripped !== []) {
                $variants[] = $stripped;
            }
        }

        return $variants;
    }

    /**
     * @param string $path
     * @return list<string>
     */
    private function segments(string $path): array
    {
        return array_values(
            array_filter(explode('/', trim($path, '/')), static fn (string $s): bool => $s !== '')
        );
    }

    private function archiveTemplate(string $taxonomy, string $fallback): string
    {
        foreach ([$taxonomy, 'archive'] as $candidate) {
            if ($candidate !== '' && $this->supportsTemplate($candidate)) {
                return $candidate;
            }
        }

        return $fallback;
    }

    private function supportsTemplate(string $template): bool
    {
        try {
            return $this->contentRenderer->supports($template);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function fallbackNotFoundPage(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Page not found</title>
            <style>
                *, *::before, *::after { box-sizing: border-box; }
                body { margin: 0; font-family: system-ui, sans-serif; background: #09090b; color: #e4e4e7; display: flex; min-height: 100vh; align-items: center; justify-content: center; text-align: center; }
                main { padding: 32px 20px; max-width: 600px; }
                .code { font-size: clamp(72px, 15vw, 140px); font-weight: 900; line-height: 1; color: #e11d48; margin: 0; }
                h1 { font-size: 24px; margin: 16px 0 8px; }
                p { color: #a1a1aa; line-height: 1.6; margin: 0 0 24px; }
                a { color: #e11d48; text-decoration: none; font-weight: 600; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <main>
                <p class="code">404</p>
                <h1>Page not found</h1>
                <p>Oops! That page can't be found. The page you are looking for doesn't exist or has been moved.</p>
                <p><a href="/">&larr; Back to home</a></p>
            </main>
        </body>
        </html>
        HTML;
    }
}
