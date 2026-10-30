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

        $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
        $postsTable = $prefix . 'posts';
        $termsTable = $prefix . 'terms';
        $relationsTable = $prefix . 'term_relationships';
        $usersTable = $prefix . 'users';

        if (!$db->hasTable($postsTable)) {
            $this->error("Database not initialized. Table {$postsTable} is missing.");
            return 1;
        }

        $this->line('  Creating admin user...');
        if (!$db->hasTable($usersTable)) {
            $this->error("  {$usersTable} table missing. Run migration first.");
        } else {
            $firstUser = $db->select('id')
                ->from($usersTable)
                ->orderBy('id', 'asc')
                ->limit(1)
                ->run()
                ->fetch();

            $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@example.com';
            $existing = $db->select('id')
                ->from($usersTable)
                ->where('email', $adminEmail)
                ->run()
                ->fetch();

            if ($existing) {
                $this->line("  - {$adminEmail}: already exists, skipping.");
            } else {
                $now = new \DateTimeImmutable();
                $db->insert($usersTable)->values([
                    'email' => $adminEmail,
                    'username' => getenv('ADMIN_USERNAME') ?: 'admin',
                    'password_hash' => password_hash(getenv('ADMIN_PASSWORD') ?: 'admin123', PASSWORD_DEFAULT),
                    'display_name' => 'Administrator',
                    'role' => 'administrator',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->run();
                $this->line("  - {$adminEmail}: created.");
            }

            if (!$firstUser && !$existing) {
                $firstUser = $db->select('id')
                    ->from($usersTable)
                    ->where('email', $adminEmail)
                    ->run()
                    ->fetch();
            }
            if (!$firstUser && $existing) {
                $firstUser = $existing;
            }
            if (is_array($firstUser) && isset($firstUser['id'])) {
                (new \PrestoWorld\AccessControl\FirstUserRootAssigner($db, $prefix))
                    ->assign((string) $firstUser['id']);
                $this->line("  - Root role assigned to first user (ID {$firstUser['id']}).");
            }
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
                ->from($postsTable)
                ->where('slug', $slug)
                ->where('type', 'page')
                ->run()
                ->fetch();

            if ($existing) {
                $this->line("  - {$locales['en']['title']}: already exists, skipping.");
                continue;
            }

            $db->insert($postsTable)->values([
                'type' => 'page',
                'title' => $locales['en']['title'],
                'slug' => $slug,
                'content' => $locales['en']['content'],
                'status' => 'publish',
                'author_id' => 1,
            ])->run();

            $post = $db->select('id')
                ->from($postsTable)
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

        // Create categories
        $this->line('  Creating categories...');
        $categories = [
            ['name' => 'Tin Game', 'slug' => 'tin-game'],
            ['name' => 'Công Nghệ', 'slug' => 'cong-nghe'],
            ['name' => 'Esports', 'slug' => 'esports'],
        ];

        foreach ($categories as $cat) {
            $existing = $db->select('id')
                ->from($termsTable)
                ->where('slug', $cat['slug'])
                ->where('taxonomy', 'category')
                ->run()
                ->fetch();

            if ($existing) {
                $this->line("  - {$cat['name']}: already exists, skipping.");
                continue;
            }

            $db->insert($termsTable)->values([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'taxonomy' => 'category',
            ])->run();

            $this->line("  - {$cat['name']}: created.");
        }

        // Create demo posts
        $this->line('  Creating demo posts...');
        $posts = [
            [
                'title' => 'Tin Game Mới Nhất 2026',
                'slug' => 'tin-game-moi-nhat-2026',
                'content' => '<p>Cập nhật tin tức game mới nhất trong năm 2026.</p>',
                'category' => 'tin-game',
            ],
            [
                'title' => 'Công Nghệ AI Trong Game',
                'slug' => 'cong-nghe-ai-trong-game',
                'content' => '<p>AI đang thay đổi ngành công nghiệp game như thế nào.</p>',
                'category' => 'cong-nghe',
            ],
            [
                'title' => 'Giải Đấu Esports Quốc Tế',
                'slug' => 'giai-dau-esports-quoc-te',
                'content' => '<p>Tổng hợp các giải đấu esports quốc tế lớn nhất.</p>',
                'category' => 'esports',
            ],
        ];

        foreach ($posts as $postData) {
            $existing = $db->select('id')
                ->from($postsTable)
                ->where('slug', $postData['slug'])
                ->where('type', 'post')
                ->run()
                ->fetch();

            if ($existing) {
                $this->line("  - {$postData['title']}: already exists, skipping.");
                continue;
            }

            $db->insert($postsTable)->values([
                'type' => 'post',
                'title' => $postData['title'],
                'slug' => $postData['slug'],
                'content' => $postData['content'],
                'status' => 'publish',
                'author_id' => 1,
            ])->run();

            $post = $db->select('id')
                ->from($postsTable)
                ->where('slug', $postData['slug'])
                ->run()
                ->fetch();

            $postId = (int) $post['id'];

            // Get category term id
            $term = $db->select('id')
                ->from($termsTable)
                ->where('slug', $postData['category'])
                ->where('taxonomy', 'category')
                ->run()
                ->fetch();

            if ($term) {
                $db->insert($relationsTable)->values([
                    'object_id' => $postId,
                    'term_id' => (int) $term['id'],
                ])->run();
            }

            $this->line("  - {$postData['title']}: created.");
        }

        $this->info('Seed complete!');
        $this->line('Created:');
        $this->line('  - Home / Trang chủ');
        $this->line('  - About Us / Về chúng tôi');
        $this->line('  - Categories: Tin Game, Công Nghệ, Esports');
        $this->line('  - Posts: 3 demo posts');

        return 0;
    }
}
