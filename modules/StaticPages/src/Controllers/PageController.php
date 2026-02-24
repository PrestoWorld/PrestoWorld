<?php

declare(strict_types=1);

namespace Modules\StaticPages\Controllers;

use Witals\Framework\Http\Response;
use Witals\Framework\Http\Request;
use Cycle\Database\DatabaseProviderInterface;
use Witals\Framework\Container\Container;

class PageController
{
    protected DatabaseProviderInterface $dbal;
    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    public function show(Request $request): Response
    {
        $slug = trim($request->uri(), '/');
        
        $page = $this->dbal->database()->select('*')
            ->from('optilarity_static_pages')
            ->where('slug', $slug)
            ->where('status', 'publish')
            ->run()
            ->fetch();

        if (!$page) {
            // Fallback for demo if DB is empty
            $page = $this->getFallbackPage($slug);
            if (!$page) {
                return Response::json(['error' => 'Page not found'], 404);
            }
        }

        return $this->render('page', [
            'page' => $page,
            'title' => $page['title']
        ]);
    }

    protected function getFallbackPage(string $slug): ?array
    {
        $fallbacks = [
            'about-us' => [
                'title' => 'Về chúng tôi',
                'content' => 'Nội dung giới thiệu về DigitalCore đang được cập nhật...'
            ],
            'contact' => [
                'title' => 'Liên hệ',
                'content' => 'Thông tin liên hệ: email@optilarity.top'
            ],
            'privacy-policy' => [
                'title' => 'Chính sách bảo mật',
                'content' => 'Chúng tôi cam kết bảo vệ thông tin cá nhân của bạn...'
            ]
        ];

        return isset($fallbacks[$slug]) ? $fallbacks[$slug] : null;
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
        $db->delete('optilarity_static_pages')->run();

        $pages = [
            ['title' => 'Về chúng tôi', 'slug' => 'about-us', 'content' => 'DigitalCore là nền tảng chia sẻ tài nguyên số...'],
            ['title' => 'Liên hệ', 'slug' => 'contact', 'content' => 'Hãy gửi tin nhắn cho chúng tôi...'],
            ['title' => 'Chính sách bảo mật', 'slug' => 'privacy-policy', 'content' => 'Chính sách bảo mật của chúng tôi...'],
        ];

        foreach ($pages as $p) {
            $db->insert('optilarity_static_pages')->values($p + ['created_at' => now()])->run();
        }

        return Response::json(['message' => 'Pages seeded successfully']);
    }
}
