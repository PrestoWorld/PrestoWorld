<?php

declare(strict_types=1);

namespace Tests\Unit\Runtime;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Core\CacheRepository;
use PrestoWorld\Core\Database\PrestoWpdb;
use PrestoWorld\Core\Database\QueryTransformer;
use PrestoWorld\Core\Escape;
use PrestoWorld\Core\Legacy\LegacyRegistry;
use PrestoWorld\Core\Legacy\LegacyState;
use PrestoWorld\Core\Legacy\LegacyTerminationException;
use PrestoWorld\Core\OptionRepository;
use PrestoWorld\Core\Post\PostEntity;
use PrestoWorld\Core\Post\PostQuery;
use PrestoWorld\Core\PostRepository;
use PrestoWorld\Core\Sanitize;
use PrestoWorld\Core\Translator;
use Witals\Framework\Container\Container;
use Witals\Framework\Module\Contracts\HookInterface;
use PrestoWorld\Core\Legacy\LegacyHook;

/**
 * WS-B Runtime Shim Layer tests (spec 10 §10.7 + 06 Legacy Support).
 */
final class RuntimeShimTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 3) . '/app/src/Core/Legacy/wp-compatibility.php';
        require_once dirname(__DIR__, 3) . '/app/src/Core/Legacy/wp-shims.php';

        $container = Container::getInstance();
        if ($container === null) {
            $container = new Container();
            Container::setInstance($container);
        }
        $container->singleton(HookInterface::class, LegacyHook::class);
        $container->instance(HookInterface::class, new LegacyHook());
        LegacyState::setRegistry(new LegacyRegistry());
        OptionRepository::reset();
        PostRepository::reset();
        Translator::reset();
        CacheRepository::flush();
    }

    public function testEscapeHtmlAttrUrl(): void
    {
        $this->assertSame('&lt;b&gt;&quot;x&quot;&lt;/b&gt;', Escape::html('<b>"x"</b>'));
        $this->assertSame('&lt;script&gt;', Escape::attr('<script>'));
        $this->assertSame('', Escape::url('javascript:alert(1)'));
        $this->assertSame('https://example.com/a?b=1', Escape::url('https://example.com/a?b=1'));
        $this->assertStringContainsString('\\x3C', Escape::js('<script>'));
    }

    public function testSanitize(): void
    {
        $this->assertSame('hello world', Sanitize::text("hello\n world"));
        $this->assertSame('my-post', Sanitize::title('My Post!'));
        $this->assertSame(5, Sanitize::absInt(-5));
        $this->assertSame('a@b.com', Sanitize::email('a@b.com '));
        $this->assertSame('', Sanitize::email('not-an-email'));
    }

    public function testTranslatorFallbackAndEntries(): void
    {
        $this->assertSame('Hello', Translator::__('Hello'));

        Translator::setEntries('default', ['Hello' => 'Xin chào']);

        $this->assertSame('Xin chào', Translator::__('Hello'));
        $this->assertSame('1 item', Translator::_n('1 item', '%d items', 1));
        $this->assertSame('%d items', Translator::_n('1 item', '%d items', 3));
    }

    public function testOptionRepositoryFallsBackToMemory(): void
    {
        $this->assertNull(OptionRepository::get('missing', null));
        $this->assertSame('Demo', OptionRepository::get('dw_widget_title', 'Demo'));

        OptionRepository::update('dw_widget_title', 'New');
        $this->assertSame('New', OptionRepository::get('dw_widget_title'));
        $this->assertTrue(OptionRepository::delete('dw_widget_title'));
    }

    public function testCacheRepositoryTtlAndFlush(): void
    {
        CacheRepository::set('k', 'v', 60);
        $this->assertSame('v', CacheRepository::get('k'));

        $this->assertSame(2, CacheRepository::increment('counter', 2));
        $this->assertSame(3, CacheRepository::increment('counter'));

        CacheRepository::flush();
        $this->assertNull(CacheRepository::get('k'));
    }

    public function testQueryTransformerAppliesRuntimeRulesForPostgres(): void
    {
        $transformer = new QueryTransformer('postgresql');

        $this->assertSame($transformer->dialect(), 'postgresql');

        $file = dirname(__DIR__, 3) . '/resources/mappings/master.json';
        if (!is_file($file)) {
            $this->markTestSkipped('mapping file missing');
        }

        $transformer = QueryTransformer::fromMappingFile($file, 'postgresql');
        $sql = 'SELECT * FROM wp_posts WHERE id IN (SELECT id FROM wp_postmeta)';
        $out = $transformer->transform($sql);

        $this->assertStringContainsString('pw_posts.meta', $out);
    }

    public function testPrestoWpdbPrepareAndEscLikeWithoutDb(): void
    {
        $wpdb = new PrestoWpdb();

        $this->assertSame('pw_', $wpdb->prefix);
        $this->assertSame("SELECT 'O\\'Brien'", $wpdb->prepare("SELECT %s", "O'Brien"));
        $this->assertSame('100', $wpdb->prepare('%d', 100));
        $this->assertSame('a\\%b\\_c', $wpdb->esc_like('a%b_c'));

        $this->assertSame([], $wpdb->get_results('SELECT 1'));
        $this->assertFalse($wpdb->query('DELETE FROM x'));
        $this->assertNull($wpdb->get_var('SELECT 1'));
    }

    public function testLegacyHooksShimActionsAndFilters(): void
    {
        $ran = [];
        add_action('my_action', static function (string $a) use (&$ran): void {
            $ran[] = $a;
        }, 10, 1);

        $this->assertNotFalse(has_action('my_action'));

        do_action('my_action', 'x');
        do_action('my_action', 'y');

        $this->assertSame(['x', 'y'], $ran);
        $this->assertSame(2, did_action('my_action'));
        $this->assertFalse(has_action('other'));

        add_filter('the_filter', static fn (string $v): string => strtoupper($v));

        $this->assertSame('HI', apply_filters('the_filter', 'hi'));

        $this->assertTrue(remove_all_actions('my_action'));
        $this->assertFalse(has_action('my_action'));
    }

    public function testLegacyInvokerStateReset(): void
    {
        do_action('state_probe');
        $this->assertSame(1, did_action('state_probe'));

        LegacyState::reset();

        $this->assertSame(0, did_action('state_probe'));
        $this->assertNull(current_action());
    }

    public function testTerminationExceptionCarriesStatus(): void
    {
        try {
            throw new LegacyTerminationException('Preview not supported', 404);
        } catch (LegacyTerminationException $e) {
            $this->assertSame('Preview not supported', $e->getMessage());
            $this->assertSame(404, $e->getCode());
        }

        $e = new LegacyTerminationException();
        $this->assertSame('', $e->getMessage());
    }

    public function testPostQueryHavePostsLoop(): void
    {
        PostRepository::seed(new PostEntity(['ID' => 1, 'post_title' => 'A', 'post_type' => 'product']));
        PostRepository::seed(new PostEntity(['ID' => 2, 'post_title' => 'B', 'post_type' => 'product']));

        $query = new PostQuery(['post_type' => 'product', 'posts_per_page' => 5]);

        $this->assertTrue($query->have_posts());
        $seen = [];
        while ($query->have_posts()) {
            $query->the_post();
            $seen[] = $GLOBALS['post']->post_title;
        }

        $this->assertSame(['A', 'B'], $seen);
        $this->assertFalse($query->have_posts());
        $this->assertSame(2, $query->found_posts);
    }
}