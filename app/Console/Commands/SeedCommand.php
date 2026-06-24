<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use Witals\Framework\Support\ConfigFileWriter;
use Cycle\Database\DatabaseInterface;
use PrestoWorld\Modules\Schema\PostRepository;

class SeedCommand extends Command
{
    protected string $name = 'seed';
    protected string $description = 'Seed demo content (pages, posts) with EN + VI translations';

    public function handle(array $args): int
    {
        $this->info('Seeding demo content...');

        $db = app(DatabaseInterface::class);
        $repo = new PostRepository($db);

        if (!$db->hasTable('pw_posts')) {
            $this->error('Database not initialized. Run migration first.');
            return 1;
        }

        $this->line('  Creating pages...');

        $pages = [
            'home' => [
                'en' => ['title' => 'Home', 'slug' => 'home', 'content' => '<p>Welcome to PrestoWorld. This is a demo site built with the PrestoWorld CMS.</p>'],
                'vi' => ['title' => 'Trang chủ', 'slug' => 'trang-chu', 'content' => '<p>Chào mừng đến với PrestoWorld. Đây là trang web demo được xây dựng với PrestoWorld CMS.</p>'],
            ],
            'about' => [
                'en' => ['title' => 'About Us', 'slug' => 'about', 'content' => '<p>PrestoWorld is a modern, WordPress-compatible content management system built for performance and multilingual support.</p>'],
                'vi' => ['title' => 'Về chúng tôi', 'slug' => 've-chung-toi', 'content' => '<p>PrestoWorld là hệ thống quản lý nội dung hiện đại, tương thích WordPress, được xây dựng cho hiệu suất cao và hỗ trợ đa ngôn ngữ.</p>'],
            ],
        ];

        foreach ($pages as $key => $locales) {
            $slug = $locales['en']['slug'];

            $existing = $db->select('id')
                ->from('pw_posts')
                ->where('slug', $slug)
                ->where('post_type', 'page')
                ->run()
                ->fetch();

            if ($existing) {
                $this->line("  - {$locales['en']['title']}: already exists, skipping.");
                continue;
            }

            $db->insert('pw_posts')->values([
                'post_type' => 'page',
                'title' => $locales['en']['title'],
                'slug' => $slug,
                'status' => 'publish',
                'author_id' => 1,
            ])->run();

            $post = $db->select('id')
                ->from('pw_posts')
                ->where('slug', $slug)
                ->run()
                ->fetch();

            $postId = (int) $post['id'];

            // Save EN "translation" (marks source language)
            $repo->saveTranslation($postId, 'en', [
                'title' => $locales['en']['title'],
                'slug' => $slug,
                'content' => $locales['en']['content'],
                'element_type' => 'post_page',
            ]);

            // Save VI translation
            $repo->saveTranslation($postId, 'vi', [
                'title' => $locales['vi']['title'],
                'slug' => $locales['vi']['slug'],
                'content' => $locales['vi']['content'],
                'element_type' => 'post_page',
            ]);

            $this->line("  - {$locales['en']['title']} / {$locales['vi']['title']}: created.");
        }

        // Map homepage via config
        $this->line('  Setting homepage mapping...');
        $templatesFile = $this->app->basePath('config/templates.php');
        if (file_exists($templatesFile)) {
            $config = include $templatesFile;
            if (!isset($config['mapping']['/'])) {
                $config['mapping']['/'] = 'page';
                $config['mapping']['/home'] = 'page';
                ConfigFileWriter::write($templatesFile, $config);
                $this->line('  - config/templates.php updated safely.');
            }
        }

        $this->info('Seed complete!');
        $this->line('Created:');
        $this->line('  - Home / Trang chủ');
        $this->line('  - About Us / Về chúng tôi');

        return 0;
    }
}
