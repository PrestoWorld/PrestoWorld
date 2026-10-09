<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Dashboard;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\Admin\Dashboard\DashboardScreenRegistry;
use PrestoWorld\Modules\Admin\Screen\Screen;

class DashboardScreenRegistryTest extends TestCase
{
    public function test_register_and_retrieve_screen(): void
    {
        $registry = new DashboardScreenRegistry();

        $registry->registerScreen(new Screen(
            id: 'members',
            title: 'Members',
            icon: 'Users',
            position: 30,
            component: 'MemberList',
            source: 'plugin',
            settings: ['per_page' => 20],
        ));

        $screen = $registry->getScreen('members');

        $this->assertNotNull($screen);
        $this->assertSame('members', $screen->getId());
        $this->assertSame('MemberList', $screen->getComponent());
        $this->assertSame('plugin', $screen->getSource());
        $this->assertSame(['per_page' => 20], $screen->getSettings());

        $this->assertSame([
            'id' => 'members',
            'title' => 'Members',
            'parent' => null,
            'capability' => null,
            'icon' => 'Users',
            'position' => 30,
            'component' => 'MemberList',
            'source' => 'plugin',
            'settings' => ['per_page' => 20],
        ], $screen->toArray());
    }

    public function test_registry_returns_screens_sorted_by_position(): void
    {
        $registry = new DashboardScreenRegistry();

        $registry->registerScreen(new Screen(id: 'posts', title: 'Posts', position: 30));
        $registry->registerScreen(new Screen(id: 'dashboard', title: 'Dashboard', position: 10));

        $ids = array_map(fn(Screen $s) => $s->getId(), $registry->getScreens());

        $this->assertSame(['dashboard', 'posts'], $ids);
    }

    public function test_provider_registers_screens(): void
    {
        $registry = new DashboardScreenRegistry();

        $registry->addProvider(new class() implements \PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenProviderInterface {
            public function getIdentifier(): string
            {
                return 'test-module';
            }

            public function getScreens(): array
            {
                return [
                    new Screen(id: 'books', title: 'Books', icon: 'Book', source: 'module'),
                ];
            }

            public function getPriority(): int
            {
                return 10;
            }
        });

        $this->assertNotNull($registry->getScreen('books'));
        $this->assertSame('module', $registry->getScreen('books')->getSource());
    }

    public function test_registration_overrides_existing_screen(): void
    {
        $registry = new DashboardScreenRegistry();

        $registry->registerScreen(new Screen(id: 'posts', title: 'Posts', component: null, source: 'core'));
        $registry->registerScreen(new Screen(id: 'posts', title: 'All Posts', component: 'PostTable', source: 'plugin'));

        $this->assertSame('PostTable', $registry->getScreen('posts')->getComponent());
        $this->assertSame('plugin', $registry->getScreen('posts')->getSource());
    }

    public function test_remove_screen(): void
    {
        $registry = new DashboardScreenRegistry();

        $registry->registerScreen(new Screen(id: 'posts', title: 'Posts'));
        $registry->removeScreen('posts');

        $this->assertNull($registry->getScreen('posts'));
        $this->assertSame([], $registry->toArray());
    }
}