<?php

declare(strict_types=1);

namespace Tests\Unit\Search;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\Search\AsyncSearchAdapterDecorator;
use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;
use Witals\Framework\Concurrent\FiberManager;

class AsyncSearchAdapterDecoratorTest extends TestCase
{
    public function test_disabled_mode_passes_through_immediately(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);

        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('search')
            ->with('posts', 'hello', ['limit' => 5])
            ->willReturn(['results' => [], 'found' => 0]);

        $result = $decorator->search('posts', 'hello', ['limit' => 5]);

        $this->assertSame(['results' => [], 'found' => 0], $result);
    }

    public function test_enabled_mode_runs_through_event_loop(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(true);

        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('search')
            ->willReturn(['results' => [['id' => 1]], 'found' => 1]);

        $result = $decorator->search('posts', 'test');

        $this->assertSame(['results' => [['id' => 1]], 'found' => 1], $result);
    }

    public function test_search_returns_raw_array(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);

        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $expected = [
            'results' => [['id' => '1', 'score' => 0.95]],
            'found' => 1,
            'page' => 1,
            'per_page' => 10,
        ];

        $inner->method('search')->willReturn($expected);

        $this->assertSame($expected, $decorator->search('index', 'q'));
    }

    public function test_index_delegates_to_inner(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('index')
            ->with('posts', [['id' => 1, 'title' => 'Hello']]);

        $decorator->index('posts', [['id' => 1, 'title' => 'Hello']]);
    }

    public function test_add_document_delegates(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('addDocument')
            ->with('posts', ['id' => 2, 'title' => 'World']);

        $decorator->addDocument('posts', ['id' => 2, 'title' => 'World']);
    }

    public function test_update_document_delegates(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('updateDocument')
            ->with('posts', '2', ['title' => 'Updated']);

        $decorator->updateDocument('posts', '2', ['title' => 'Updated']);
    }

    public function test_delete_document_delegates(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('deleteDocument')
            ->with('posts', '3');

        $decorator->deleteDocument('posts', '3');
    }

    public function test_get_document_delegates(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('getDocument')
            ->with('posts', '1')
            ->willReturn(['id' => '1', 'title' => 'Doc']);

        $result = $decorator->getDocument('posts', '1');

        $this->assertSame(['id' => '1', 'title' => 'Doc'], $result);
    }

    public function test_delete_index_delegates(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())->method('deleteIndex')->with('old_index');

        $decorator->deleteIndex('old_index');
    }

    public function test_index_exists_delegates(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('indexExists')
            ->with('posts')
            ->willReturn(true);

        $this->assertTrue($decorator->indexExists('posts'));
    }

    public function test_get_name_delegates(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('getName')
            ->willReturn('mock-adapter');

        $this->assertSame('mock-adapter', $decorator->getName());
    }

    public function test_configure_delegates(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(false);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->expects($this->once())
            ->method('configure')
            ->with(['api_key' => 'secret']);

        $decorator->configure(['api_key' => 'secret']);
    }

    public function test_all_methods_when_enabled_with_mock_inner(): void
    {
        $inner = $this->createMock(SearchEngineInterface::class);
        $concurrent = new FiberManager(true);
        $decorator = new AsyncSearchAdapterDecorator($inner, $concurrent);

        $inner->method('getName')->willReturn('mock');
        $inner->method('indexExists')->willReturn(true);
        $inner->method('search')->willReturn(['results' => []]);
        $inner->method('getDocument')->willReturn(['id' => '1']);

        $this->assertSame('mock', $decorator->getName());
        $this->assertTrue($decorator->indexExists('posts'));
        $this->assertSame(['results' => []], $decorator->search('posts', 'q'));
        $this->assertSame(['id' => '1'], $decorator->getDocument('posts', '1'));

        $decorator->index('posts', []);
        $decorator->addDocument('posts', []);
        $decorator->updateDocument('posts', '1', []);
        $decorator->deleteDocument('posts', '1');
        $decorator->deleteIndex('posts');
        $decorator->configure([]);

        $this->assertTrue(true, 'All methods executed without error in enabled mode');
    }
}
