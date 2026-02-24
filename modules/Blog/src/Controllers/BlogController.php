<?php

declare(strict_types=1);

namespace Modules\Blog\Controllers;

use Witals\Framework\Http\Response;
use Witals\Framework\Http\Request;
use Cycle\Database\DatabaseProviderInterface;
use Cake\Chronos\Chronos;
use Witals\Framework\Container\Container;

class BlogController
{
    protected DatabaseProviderInterface $dbal;
    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    public function index(Request $request): Response
    {
        $posts = $this->dbal->database()->select('p.*', 'c.name as category_name', 'c.slug as category_slug')
            ->from('optilarity_blog_posts as p')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('p.status', 'publish')
            ->orderBy('p.is_featured', 'DESC')
            ->orderBy('p.published_at', 'DESC')
            ->run()
            ->fetchAll();

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

    public function show(string $slug): Response
    {
        $post = $this->dbal->database()->select('p.*', 'c.name as category_name', 'c.slug as category_slug')
            ->from('optilarity_blog_posts as p')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('p.slug', $slug)
            ->run()
            ->fetch();

        if (!$post) {
            return Response::json(['error' => 'Post not found'], 404);
        }

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

        $relatedPosts = $this->dbal->database()->select('p.*', 'c.name as category_name')
            ->from('optilarity_blog_posts as p')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('p.category_id', $post['category_id'])
            ->where('p.id', '!=', $post['id'])
            ->limit(3)
            ->run()
            ->fetchAll();

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

        $posts = $this->dbal->database()->select('p.*', 'c.name as category_name')
            ->from('optilarity_blog_posts as p')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('p.category_id', $category['id'])
            ->where('p.status', 'publish')
            ->orderBy('p.published_at', 'DESC')
            ->run()
            ->fetchAll();

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

        $posts = $this->dbal->database()->select('p.*', 'c.name as category_name')
            ->from('optilarity_blog_posts as p')
            ->join('optilarity_blog_post_tags as pt')->on('p.id', 'pt.post_id')
            ->leftJoin('optilarity_blog_categories as c')->on('p.category_id', 'c.id')
            ->where('pt.tag_id', $tag['id'])
            ->where('p.status', 'publish')
            ->run()
            ->fetchAll();

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
        $posts = $this->dbal->database()->select('*')
            ->from('optilarity_blog_posts')
            ->where('status', 'publish')
            ->run()
            ->fetchAll();
        
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
        return Response::json(['success' => true, 'data' => $post]);
    }

    protected function render(string $view, array $data = []): Response
    {
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);
        $html = $themeManager->render($view, $data);
        return Response::html($html);
    }

    public function seed(): Response
    {
        $db = $this->dbal->database();

        // Clear existing
        $db->delete('optilarity_blog_comments')->run();
        $db->delete('optilarity_blog_post_tags')->run();
        $db->delete('optilarity_blog_posts')->run();
        $db->delete('optilarity_blog_tags')->run();
        $db->delete('optilarity_blog_categories')->run();

        // Categories
        $cats = [
            ['name' => 'WordPress', 'slug' => 'wordpress'],
            ['name' => 'Hosting & VPS', 'slug' => 'hosting-vps'],
            ['name' => 'Tutorials', 'slug' => 'tutorials'],
            ['name' => 'Reviews', 'slug' => 'reviews'],
            ['name' => 'Technology', 'slug' => 'technology'],
            ['name' => 'Marketing', 'slug' => 'marketing'],
        ];
        foreach ($cats as $cat) {
            $db->insert('optilarity_blog_categories')->values($cat + ['created_at' => now()])->run();
        }

        $wpCatId = $db->select('id')->from('optilarity_blog_categories')->where('slug', 'wordpress')->run()->fetchColumn();
        $hostingCatId = $db->select('id')->from('optilarity_blog_categories')->where('slug', 'hosting-vps')->run()->fetchColumn();
        $techCatId = $db->select('id')->from('optilarity_blog_categories')->where('slug', 'technology')->run()->fetchColumn();

        // Tags
        $tags = [
            ['name' => 'SEO', 'slug' => 'seo'],
            ['name' => 'Web Vitals', 'slug' => 'web-vitals'],
            ['name' => 'Optimization', 'slug' => 'optimization'],
            ['name' => 'Google', 'slug' => 'google'],
        ];
        foreach ($tags as $tag) {
            $db->insert('optilarity_blog_tags')->values($tag)->run();
        }

        // Posts
        $posts = [
            [
                'category_id' => $techCatId,
                'title' => 'Khám phá hệ sinh thái DigitalCore: Giải pháp toàn diện cho Developers',
                'slug' => 'kham-pha-he-sinh-thai-digitalcore',
                'excerpt' => 'DigitalCore không chỉ là nơi cung cấp tài nguyên, mà là người bạn đồng hành tin cậy trên con đường phát triển sự nghiệp của bạn.',
                'content' => 'Full content here...',
                'is_featured' => true,
                'reading_time' => 10,
                'status' => 'publish',
                'published_at' => now(),
                'created_at' => now(),
            ],
            [
                'category_id' => $wpCatId,
                'title' => 'Top 10 WordPress Themes tốt nhất cho Website bán hàng',
                'slug' => 'top-10-wordpress-themes-tot-nhat',
                'excerpt' => 'Tổng hợp danh sách các theme tối ưu SEO, tốc độ tải trang nhanh và giao diện đẹp.',
                'content' => 'Full content here...',
                'is_featured' => false,
                'reading_time' => 8,
                'status' => 'publish',
                'published_at' => now()->subHours(5),
                'created_at' => now(),
            ],
            [
                'category_id' => $hostingCatId,
                'title' => 'Hướng dẫn cài đặt VPS Ubuntu 22.04 từ A đến Z',
                'slug' => 'huong-dan-cai-dat-vps-ubuntu',
                'excerpt' => 'Chi tiết các bước thiết lập server, cài đặt LEMP Stack và bảo mật cơ bản.',
                'content' => 'Full content here...',
                'is_featured' => false,
                'reading_time' => 12,
                'status' => 'publish',
                'published_at' => now()->subDays(1),
                'created_at' => now(),
            ],
        ];

        foreach ($posts as $post) {
            $db->insert('optilarity_blog_posts')->values($post)->run();
        }

        return Response::json(['message' => 'Blog seeded successfully']);
    }
}
