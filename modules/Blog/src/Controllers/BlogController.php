<?php

declare(strict_types=1);

namespace Modules\Blog\Controllers;

use Witals\Framework\Http\Response;
use Witals\Framework\Http\Request;
use Cycle\Database\DatabaseProviderInterface;
use Witals\Framework\Database\Crud\CrudController;
use PrestoWorld\Theme\ThemeManager;

class BlogController extends CrudController
{
    protected ThemeManager $theme;
    protected string $table = 'optilarity_blog_posts';
    protected array $translatableFields = ['title', 'content', 'excerpt'];
    protected bool $isSeoable = true;

    public function __construct(DatabaseProviderInterface $dbal, ThemeManager $theme)
    {
        parent::__construct($dbal);
        $this->theme = $theme;
    }

    public function index(Request $request): Response
    {
        $query = $this->dbal->database()->select('p.*', 'c.name as category_name', 'c.slug as category_slug')
            ->from('optilarity_blog_posts as p')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('p.status', 'publish')
            ->orderBy('p.is_featured', 'DESC')
            ->orderBy('p.published_at', 'DESC');

        $posts = array_map([$this, 'processItem'], $query->fetchAll());

        $categories = $this->dbal->database()->select('c.*', 'COUNT(p.id) as post_count')
            ->from('optilarity_blog_categories as c')
            ->leftJoin('optilarity_blog_posts as p')->on('c.id', 'p.category_id')
            ->groupBy('c.id')
            ->run()
            ->fetchAll();

        $featuredPost = null;
        if (!empty($posts) && $posts[0]['is_featured']) {
            $featuredPost = array_shift($posts);
        }

        return $this->render('blog/index', [
            'featured_post' => $featuredPost,
            'posts' => $posts,
            'categories' => $categories,
            'title' => 'Blog & Kiến thức'
        ]);
    }

    public function show(Request $request, $slug): Response
    {
        $postRaw = $this->dbal->database()->select('p.*', 'c.name as category_name', 'c.slug as category_slug')
            ->from('optilarity_blog_posts as p')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('p.slug', $slug)
            ->run()
            ->fetch();

        if (!$postRaw) {
            return Response::json(['error' => 'Post not found'], 404);
        }

        $post = $this->processItem($postRaw);

        // Increment views
        $this->dbal->database()->update('optilarity_blog_posts', [
            'view_count' => (int)$post['view_count'] + 1
        ], ['id' => $post['id']])->run();

        $tags = $this->dbal->database()->select('t.*')
            ->from('optilarity_blog_tags as t')
            ->join('optilarity_blog_post_tags as pt')->on('t.id', 'pt.tag_id')
            ->where('pt.post_id', $post['id'])
            ->run()
            ->fetchAll();

        $comments = $this->dbal->database()->select('*')
            ->from('optilarity_blog_comments')
            ->where('post_id', $post['id'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'DESC')
            ->run()
            ->fetchAll();

        $relatedPosts = array_map([$this, 'processItem'], $this->dbal->database()->select('p.*', 'c.name as category_name')
            ->from('optilarity_blog_posts as p')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('p.category_id', $post['category_id'])
            ->where('p.id', '!=', $post['id'])
            ->limit(3)
            ->run()
            ->fetchAll());

        return $this->render('blog/show', [
            'post' => $post,
            'tags' => $tags,
            'comments' => $comments,
            'related_posts' => $relatedPosts,
            'title' => $post['title']
        ]);
    }

    public function category(string $slug): Response
    {
        $category = $this->dbal->database()->select('*')
            ->from('optilarity_blog_categories')
            ->where('slug', $slug)
            ->run()
            ->fetch();

        if (!$category) {
            return Response::json(['error' => 'Category not found'], 404);
        }

        $posts = array_map([$this, 'processItem'], $this->dbal->database()->select('p.*', 'c.name as category_name')
            ->from('optilarity_blog_posts as p')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('p.category_id', $category['id'])
            ->where('p.status', 'publish')
            ->orderBy('p.published_at', 'DESC')
            ->run()
            ->fetchAll());

        $categories = $this->dbal->database()->select('c.*', 'COUNT(p.id) as post_count')
            ->from('optilarity_blog_categories as c')
            ->leftJoin('optilarity_blog_posts as p')->on('c.id', 'p.category_id')
            ->groupBy('c.id')
            ->run()
            ->fetchAll();

        return $this->render('blog/index', [
            'posts' => $posts,
            'categories' => $categories,
            'current_category' => $category,
            'title' => 'Chuyên mục: ' . $category['name']
        ]);
    }

    public function tag(string $slug): Response
    {
        $tag = $this->dbal->database()->select('*')
            ->from('optilarity_blog_tags')
            ->where('slug', $slug)
            ->run()
            ->fetch();

        if (!$tag) {
            return Response::json(['error' => 'Tag not found'], 404);
        }

        $posts = array_map([$this, 'processItem'], $this->dbal->database()->select('p.*', 'c.name as category_name')
            ->from('optilarity_blog_posts as p')
            ->join('optilarity_blog_post_tags as pt')->on('p.id', 'pt.post_id')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('pt.tag_id', $tag['id'])
            ->where('p.status', 'publish')
            ->run()
            ->fetchAll());

        $categories = $this->dbal->database()->select('c.*', 'COUNT(p.id) as post_count')
            ->from('optilarity_blog_categories as c')
            ->leftJoin('optilarity_blog_posts as p')->on('c.id', 'p.category_id')
            ->groupBy('c.id')
            ->run()
            ->fetchAll();

        return $this->render('blog/index', [
            'posts' => $posts,
            'categories' => $categories,
            'current_tag' => $tag,
            'title' => 'Tag: #' . $tag['name']
        ]);
    }

    public function apiIndex(): Response
    {
        $posts = array_map([$this, 'processItem'], $this->dbal->database()->select('*')
            ->from('optilarity_blog_posts')
            ->where('status', 'publish')
            ->run()
            ->fetchAll());
        
        return Response::json(['success' => true, 'data' => $posts]);
    }

    public function apiShow(string $slug): Response
    {
        $post = $this->dbal->database()->select('*')
            ->from('optilarity_blog_posts')
            ->where('slug', $slug)
            ->run()
            ->fetch();
        
        if (!$post) return Response::json(['success' => false, 'message' => 'Not found'], 404);
        return Response::json(['success' => true, 'data' => $this->processItem($post)]);
    }

    protected function render(string $view, array $data = []): Response
    {
        $html = $this->theme->render($view, $data);
        return Response::html($html);
    }
}
