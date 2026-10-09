<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Http\Request;
use App\Contracts\Http\TemplateMappingPolicy;
use App\Contracts\Http\PageRenderer;
use App\Contracts\Services\ContentRenderer;
use App\Exceptions\NotFoundException;
use App\Exceptions\TemplateNotFoundException;
use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\Parameter;

/**
 * Smart Router — intelligent URL routing with fast content type detection
 *
 * Integrates UrlParser, RewriteRuleRegistry, and ContentTypeDetector
 * to provide WordPress-compatible routing with O(1) fast path detection.
 *
 * Routing flow:
 * 1. Parse URL → detect content type (fast path, cached)
 * 2. Match rewrite rules → determine template
 * 3. Load content → render with correct template
 * 4. Fallback → 404 if no content found
 */
class SmartRouter
{
    private string $tablePrefix;

    /**
     * Set when the content tables are unavailable on the default connection
     * (multi-database setups where content lives on another connection).
     * While set, a missing row is treated as "unknown" instead of a 404.
     */
    private bool $lookupUnavailable = false;

    public function __construct(
        private UrlParser $urlParser,
        private RewriteRuleRegistry $rewriteRegistry,
        private ContentTypeDetector $contentTypeDetector,
        private TemplateResolver $templateResolver,
        private ContentRenderer $contentRenderer,
        private PageRenderer $renderer,
        private PostRepository $posts,
        private DatabaseInterface $db,
    ) {
        $this->tablePrefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
    }

    /**
     * Handle incoming request
     */
    public function handle(Request $request): string
    {
        $path = rtrim($request->path(), '/');
        $segments = $this->segments($path);
        $explicit = $path === '' || $this->templateResolver->matchesExplicitly($request);

        // 1. Detect content type (fast path)
        $detected = $this->contentTypeDetector->detect($path);

        // 2. Match rewrite rules
        $rule = $this->rewriteRegistry->match($path);

        // 3. Determine template
        $template = $this->resolveTemplate($detected, $rule, $segments, $explicit);

        if ($template === null || $template === '') {
            throw new TemplateNotFoundException('No template could be resolved for this request');
        }

        // 4. Load content
        $data = $this->loadContent($detected, $segments, $explicit);

        // 5. Render
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

        return $this->fallbackNotFoundPage();
    }

    /**
     * Resolve template from detected content type and rewrite rules
     *
     * @param array{type: string, template: string, confidence: float} $detected
     * @param array{type: string, template: string, priority: int, matches: array<string, string>}|null $rule
     * @param list<string> $segments
     */
    private function resolveTemplate(array $detected, ?array $rule, array $segments, bool $explicit): string
    {
        // If rewrite rule matched, use its template
        if ($rule !== null && $rule['priority'] >= 20) {
            return $rule['template'];
        }

        // If content type detected with high confidence, use its template
        if ($detected['confidence'] >= 0.8 && isset($detected['template'])) {
            $template = $detected['template'];
            if ($this->supportsTemplate($template)) {
                return $template;
            }
        }

        // Fallback to template resolver
        $request = Request::createFromGlobals();
        $template = $this->templateResolver->resolve($request);

        if ($template === null || $template === '') {
            throw new TemplateNotFoundException('No template could be resolved for this request');
        }

        // If not explicit, try to refine template based on content
        if (!$explicit) {
            $template = $this->refineTemplate($template, $detected, $segments);
        }

        return $template;
    }

    /**
     * Refine template based on detected content type
     *
     * @param array{type: string, template: string, confidence: float} $detected
     * @param list<string> $segments
     */
    private function refineTemplate(string $template, array $detected, array $segments): string
    {
        $type = $detected['type'] ?? '';

        // Single post/page
        if ($type === 'single' || $type === 'page') {
            $hierarchy = ($detected['post_type'] ?? 'post') === 'post' ? 'single' : 'page';
            if ($this->supportsTemplate($hierarchy)) {
                return $hierarchy;
            }
        }

        // Term archive
        if ($type === 'term_archive') {
            $taxonomy = $detected['taxonomy'] ?? ($segments[0] ?? '');
            if ($taxonomy !== '' && $this->supportsTemplate($taxonomy)) {
                return $taxonomy;
            }
            if ($this->supportsTemplate('archive')) {
                return 'archive';
            }
        }

        // Post type archive
        if ($type === 'post_type_archive') {
            $postType = $detected['post_type'] ?? '';
            if ($postType !== '' && $this->supportsTemplate('archive-' . $postType)) {
                return 'archive-' . $postType;
            }
            if ($this->supportsTemplate('archive')) {
                return 'archive';
            }
        }

        // Date archive
        if ($type === 'date_archive') {
            if ($this->supportsTemplate('date')) {
                return 'date';
            }
            if ($this->supportsTemplate('archive')) {
                return 'archive';
            }
        }

        // Author archive
        if ($type === 'author_archive') {
            if ($this->supportsTemplate('author')) {
                return 'author';
            }
            if ($this->supportsTemplate('archive')) {
                return 'archive';
            }
        }

        // Search
        if ($type === 'search') {
            if ($this->supportsTemplate('search')) {
                return 'search';
            }
        }

        return $template;
    }

    /**
     * Load content based on detected type
     *
     * @param array{type: string, template: string, confidence: float, id?: int, slug?: string, taxonomy?: string, term_id?: int, post_type?: string, author?: string, year?: int, month?: int, day?: int, page?: int, query?: string} $detected
     * @param list<string> $segments
     */
    private function loadContent(array $detected, array $segments, bool $explicit): array
    {
        $type = $detected['type'] ?? '';

        // Single post/page
        if ($type === 'single' || $type === 'page') {
            $post = $this->loadPost($detected, $segments);
            if ($post !== []) {
                return $post;
            }
        }

        // Term archive
        if ($type === 'term_archive') {
            $term = $this->loadTerm($detected, $segments);
            if ($term !== null) {
                return ['term' => $term];
            }
        }

        // Post type archive
        if ($type === 'post_type_archive') {
            return ['post_type' => $detected['post_type'] ?? ''];
        }

        // Date archive
        if ($type === 'date_archive') {
            return [
                'year' => $detected['year'] ?? null,
                'month' => $detected['month'] ?? null,
                'day' => $detected['day'] ?? null,
            ];
        }

        // Author archive
        if ($type === 'author_archive') {
            return ['author' => $detected['author'] ?? ''];
        }

        // Search
        if ($type === 'search') {
            return ['query' => $detected['query'] ?? ''];
        }

        // Pagination
        if ($type === 'paged') {
            return ['page' => $detected['page'] ?? 1];
        }

        // Fallback: try to find post or term by segments
        if (!$explicit) {
            $post = $this->findPostBySegments($segments);
            if ($post !== []) {
                return $post;
            }

            $term = $this->findTermBySegments($segments);
            if ($term !== null) {
                return ['term' => $term];
            }
        }

        return [];
    }

    /**
     * @param array{type: string, id?: int, slug?: string, post_type?: string} $detected
     * @param list<string> $segments
     * @return array<string, mixed>
     */
    private function loadPost(array $detected, array $segments): array
    {
        // If we have the ID from detection, load directly
        if (isset($detected['id'])) {
            return $this->findPostById($detected['id']);
        }

        // If we have the slug, load by slug
        if (isset($detected['slug']) && $detected['slug'] !== '') {
            $postType = $detected['post_type'] ?? 'post';
            return $this->findPostByTypeAndSlug($postType, $detected['slug']);
        }

        // Fallback: try segments
        return $this->findPostBySegments($segments);
    }

    /**
     * @param array{type: string, taxonomy?: string, term_id?: int, slug?: string} $detected
     * @param list<string> $segments
     * @return array<mixed, mixed>|null
     */
    private function loadTerm(array $detected, array $segments): ?array
    {
        // If we have the term ID from detection, load directly
        if (isset($detected['term_id'])) {
            return $this->findTermById($detected['term_id']);
        }

        // If we have taxonomy and slug, load by slug
        if (isset($detected['taxonomy']) && isset($detected['slug']) && $detected['slug'] !== '') {
            return $this->findTermBySlug($detected['taxonomy'], $detected['slug']);
        }

        // Fallback: try segments
        return $this->findTermBySegments($segments);
    }

    /**
     * @param list<string> $segments
     * @return array<string, mixed>
     */
    private function findPostBySegments(array $segments): array
    {
        foreach ($this->lookupVariants($segments) as $variant) {
            $post = $this->findPostBySlug($variant[0] ?? '');
            if ($post !== []) {
                return $post;
            }
        }

        return [];
    }

    /**
     * @param list<string> $segments
     * @return array<mixed, mixed>|null
     */
    private function findTermBySegments(array $segments): ?array
    {
        if (count($segments) < 2) {
            return null;
        }

        foreach ($this->lookupVariants($segments) as $variant) {
            $term = $this->findTerm($variant);
            if ($term !== null) {
                return $term;
            }
        }

        return null;
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
     * Look up a post by ID
     *
     * @return array<string, mixed>
     */
    protected function findPostById(int $id): array
    {
        if ($id <= 0) {
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
                ->where('p.id', $id)
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
     * Look up a post by type and slug
     *
     * @return array<string, mixed>
     */
    protected function findPostByTypeAndSlug(string $postType, string $slug): array
    {
        if ($slug === '' || $postType === '') {
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
                ->where('p.post_type', $postType)
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
     * Look up a term by taxonomy and slug
     *
     * @return array<mixed, mixed>|null
     */
    protected function findTermBySlug(string $taxonomy, string $slug): ?array
    {
        if ($taxonomy === '' || $slug === '') {
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
                ->where('taxonomy', $taxonomy)
                ->where('slug', $slug)
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
     * Look up a term by ID
     *
     * @return array<mixed, mixed>|null
     */
    protected function findTermById(int $id): ?array
    {
        if ($id <= 0) {
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
                ->where('id', $id)
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

    private function supportsTemplate(string $template): bool
    {
        try {
            return $this->contentRenderer->supports($template);
        } catch (\Throwable) {
            return false;
        }
    }

    private function fallbackNotFoundPage(): string
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
