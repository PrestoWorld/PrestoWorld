<?php

declare(strict_types=1);

namespace Tests\Unit\Gutenberg\Blocks;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\PostTitleBlock;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\PostDateBlock;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\PostTemplateBlock;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\QueryBlock;

class PostBlocksTest extends TestCase
{
    public function test_post_title_block_renders_correctly(): void
    {
        $block = new PostTitleBlock([
            'attrs' => ['level' => 3, 'isLink' => true],
            'classes' => ['custom-title']
        ]);
        
        $context = [
            'post' => [
                'post_title' => 'Test Hello',
                'url' => '/test-hello'
            ]
        ];
        
        $html = $block->render($context);
        
        $this->assertStringContainsString('<h3 class="custom-title">', $html);
        $this->assertStringContainsString('href="/test-hello"', $html);
        $this->assertStringContainsString('Test Hello', $html);
    }

    public function test_post_date_block_renders_correctly(): void
    {
        $block = new PostDateBlock([
            'attrs' => ['isLink' => false],
            'classes' => ['post-date']
        ]);
        
        $context = [
            'post' => ['post_date' => '2026-01-01T00:00:00+00:00']
        ];
        
        $html = $block->render($context);
        
        $this->assertStringContainsString('class="post-date"', $html);
        $this->assertStringContainsString('January 1, 2026', $html);
        $this->assertStringNotContainsString('<a href', $html);
    }

    public function test_post_template_block_loops_correctly(): void
    {
        $template = new PostTemplateBlock(['classes' => ['my-template']]);
        
        $titleBlock = new PostTitleBlock(['attrs' => ['isLink' => false]]);
        $template->setInnerBlocks([$titleBlock]);
        
        $context = [
            'posts' => [
                ['id' => 10, 'post_title' => 'Post 1'],
                ['id' => 11, 'post_title' => 'Post 2'],
            ]
        ];
        
        $html = $template->render($context);
        
        // wp-block-post-template is always first in the class list
        $this->assertStringContainsString('class="wp-block-post-template', $html);
        $this->assertStringContainsString('my-template', $html);
        $this->assertStringContainsString('post-10', $html);
        $this->assertStringContainsString('post-11', $html);
        $this->assertStringContainsString('Post 1', $html);
        $this->assertStringContainsString('Post 2', $html);
    }

    public function test_query_block_integrates_repository(): void
    {
        $queryBlock = new QueryBlock(['attrs' => ['query' => ['postType' => 'page']]]);
        
        // Mock Repository
        $repo = $this->createMock(\PrestoWorld\Modules\Schema\PostRepository::class);
        $repo->expects($this->once())
             ->method('find')
             ->with($this->callback(fn($c) => $c['post_type'] === 'page'))
             ->willReturn([['id' => 99, 'post_title' => 'Page 99']]);
             
        $context = ['post_repository' => $repo];
        
        $template = new PostTemplateBlock([]);
        $template->setInnerBlocks([new PostTitleBlock([])]);
        $queryBlock->setInnerBlocks([$template]);
        
        $html = $queryBlock->render($context);
        
        $this->assertStringContainsString('Page 99', $html);
    }
}
