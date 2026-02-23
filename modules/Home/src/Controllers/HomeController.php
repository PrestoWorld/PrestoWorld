<?php

declare(strict_types=1);

namespace Modules\Home\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class HomeController
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Render the home page
     */
    public function index(Request $request): Response
    {
        $hooks = $this->app->make('hooks');
        
        // Pre-fetch posts for themes that expect 'posts' variable (like tucnguyen)
        $postsData = $this->fetchPosts();
        
        // 1. Get all sections defined by modules/themes
        $sections = $hooks->applyFilters('home_sections', [
            'hero' => [
                'priority' => 10,
                'callback' => [$this, 'renderHero'],
                'enabled' => true,
            ],
            'features' => [
                'priority' => 20,
                'callback' => [$this, 'renderFeatures'],
                'enabled' => true,
            ],
            'recent_posts' => [
                'priority' => 30,
                'callback' => [$this, 'renderRecentPosts'],
                'enabled' => true,
                'data' => $postsData
            ],
        ]);
        
        // 2. Sort sections by priority
        uasort($sections, function($a, $b) {
            return ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50);
        });
        
        // 3. Render sections
        $content = '';
        foreach ($sections as $name => $section) {
            if (!($section['enabled'] ?? true)) {
                continue;
            }

            $sectionOutput = (string) $this->app->call($section['callback'], [
                'request' => $request, 
                'section' => $section,
                'name' => $name,
                'posts' => $postsData
            ]);

            $content .= $hooks->applyFilters("home_section_{$name}_output", $sectionOutput, $section);
        }
        
        $pageTitle = $hooks->applyFilters('home_page_title', 'Experience the power of Native Theme Engine');
        
        // 4. Use theme engine if available
        if ($this->app->has(\PrestoWorld\Theme\ThemeManager::class)) {
            $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);
            $themeManager->loadActiveTheme();

            return Response::html($themeManager->render('index', [
                'title' => $pageTitle,
                'content' => $content,
                'posts' => $postsData,
                'is_module_home' => true
            ]));
        }
        
        return Response::html("<html><head><title>{$pageTitle}</title></head><body>{$content}</body></html>");
    }

    protected function fetchPosts(): array
    {
        $postsData = [];
        try {
            if ($this->app->has(\Cycle\ORM\ORMInterface::class)) {
                $orm = $this->app->make(\Cycle\ORM\ORMInterface::class);
                $repo = $orm->getRepository(\App\Models\Post::class);
                
                $posts = $repo->select()
                    ->where('status', 'publish')
                    ->where('type', 'in', ['post', 'page'])
                    ->orderBy('date', 'DESC')
                    ->limit(3)
                    ->fetchAll();

                if (empty($posts)) {
                    $rawPosts = $this->app->make(\Cycle\Database\DatabaseInterface::class)
                        ->select('ID', 'post_title', 'post_name', 'post_date', 'post_type')
                        ->from('wp_posts')
                        ->where('post_status', 'publish')
                        ->where('post_type', 'in', ['post', 'page'])
                        ->orderBy('post_date', 'DESC')
                        ->limit(3)
                        ->fetchAll();

                    foreach ($rawPosts as $rp) {
                        $postsData[] = [
                            'id' => (int)$rp['ID'],
                            'title' => (string)$rp['post_title'],
                            'slug' => (string)$rp['post_name'],
                            'type' => (string)$rp['post_type'],
                            'date' => isset($rp['post_date']) ? date('M d, Y', strtotime($rp['post_date'])) : 'Unknown',
                            'url' => '#'
                        ];
                    }
                } else {
                    foreach ($posts as $post) {
                        $postsData[] = [
                            'id' => $post->id,
                            'title' => $post->title,
                            'slug' => $post->slug,
                            'type' => $post->type,
                            'date' => $post->date instanceof \DateTimeInterface ? $post->date->format('M d, Y') : 'Unknown',
                            'url' => '#'
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("HomeModule Data Fetch Error: " . $e->getMessage());
        }
        return $postsData;
    }

    /**
     * Default Hero Section
     */
    public function renderHero(Request $request, array $section): string
    {
        $data = $this->app->make('hooks')->applyFilters('home_hero_data', [
            'title' => 'Experience the power of Native Theme Engine',
            'subtitle' => 'Integrated with CycleORM and RoadRunner for extreme performance.',
            'cta' => 'View Documentation',
            'link' => '#features',
            'bg_gradient' => 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%)'
        ]);
        
        return "
        <section class='hero-section' style='padding: 120px 20px; text-align: center; background: {$data['bg_gradient']}; color: white; position: relative; overflow: hidden;'>
            <div style='position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%); pointer-events: none;'></div>
            <div style='position: relative; z-index: 1;'>
                <h1 style='font-size: 4rem; font-weight: 800; margin-bottom: 24px; letter-spacing: -0.02em;'>{$data['title']}</h1>
                <p style='font-size: 1.5rem; max-width: 800px; margin: 0 auto 40px; color: #94a3b8; line-height: 1.6;'>{$data['subtitle']}</p>
                <div style='display: flex; gap: 15px; justify-content: center;'>
                    <a href='{$data['link']}' style='background: #6366f1; color: white; padding: 16px 36px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: all 0.3s; box-shadow: 0 10px 15px -3px rgba(99,102,241,0.4);'>{$data['cta']}</a>
                    <a href='#' style='background: rgba(255,255,255,0.05); color: white; padding: 16px 36px; border-radius: 12px; text-decoration: none; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s;'>Learn More</a>
                </div>
            </div>
        </section>";
    }

    /**
     * Default Features Section
     */
    public function renderFeatures(Request $request, array $section): string
    {
        $features = $this->app->make('hooks')->applyFilters('home_features_data', [
            [
                'icon' => '⚡',
                'title' => 'CycleORM Power',
                'desc' => 'Native integration with a powerful Data Mapper ORM for PHP.'
            ],
            [
                'icon' => '🎨',
                'title' => 'Theme Engine',
                'desc' => 'Swap themes on the fly with our powerful native theme manager.'
            ],
            [
                'icon' => '🧱',
                'title' => 'Zero-Change Customization',
                'desc' => 'Extend and customize everything without modification of core files.'
            ]
        ]);
        
        $html = "
        <section id='features' class='features-section' style='padding: 100px 20px; background: #ffffff;'>
            <div style='max-width: 1200px; margin: 0 auto;'>
                <div style='text-align: center; margin-bottom: 60px;'>
                    <h2 style='font-size: 2.5rem; font-weight: 700; color: #0f172a; margin-bottom: 16px;'>Built for Modern Development</h2>
                    <p style='color: #64748b; font-size: 1.125rem;'>High-quality infrastructure for your next enterprise application.</p>
                </div>
                <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px;'>";
        
        foreach ($features as $f) {
            $html .= "
            <div style='padding: 40px; border-radius: 24px; background: #f8fafc; border: 1px solid #f1f5f9; transition: transform 0.3s, box-shadow 0.3s;'>
                <div style='width: 64px; height: 64px; background: white; border-radius: 16px; display: flex; items-center: center; justify-content: center; font-size: 2rem; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'>{$f['icon']}</div>
                <h3 style='font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: 12px;'>{$f['title']}</h3>
                <p style='color: #475569; line-height: 1.6;'>{$f['desc']}</p>
            </div>";
        }
        
        $html .= "</div></div></section>";
        return $html;
    }

    /**
     * Recent Posts Section
     */
    public function renderRecentPosts(Request $request, array $section, array $posts = []): string
    {
        $postsData = $posts;
        $heading = $this->app->make('hooks')->applyFilters('home_posts_heading', 'Latest Posts (from WordPress Database):');
        
        $html = "<section class='recent-posts' style='padding: 80px 20px; background: #f1f5f9;'>
            <div style='max-width: 1200px; margin: 0 auto;'>
                <h3 style='font-size: 2rem; font-weight: 700; color: #0f172a; margin-bottom: 40px;'>{$heading}</h3>";

        if (empty($postsData)) {
            $html .= "<div style='padding: 40px; text-align: center; background: white; border-radius: 16px; color: #64748b;'>No posts found.</div>";
        } else {
            $html .= "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;'>";
            foreach ($postsData as $post) {
                $html .= "
                <a href='{$post['url']}' style='text-decoration: none; color: inherit; display: block;'>
                    <div style='padding: 24px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; transition: all 0.3s;'>
                        <div style='font-size: 0.875rem; color: #6366f1; font-weight: 600; margin-bottom: 8px;'>{$post['date']}</div>
                        <h4 style='font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 12px; line-height: 1.4;'>{$post['title']}</h4>
                        <div style='color: #64748b; font-size: 0.875rem; display: flex; items-center: center; gap: 4px;'>Read more <span style='font-size: 1.2rem;'>→</span></div>
                    </div>
                </a>";
            }
            $html .= "</div>";
        }

        $html .= "</div></section>";
        return $html;
    }
}
