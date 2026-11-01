<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Core\Admin\Context\AdminContext;

class AdminContextTest extends TestCase
{
    public function test_defaults_to_spa_when_no_wp_admin_path(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin/dashboard';
        $context = new AdminContext();
        $this->assertFalse($context->isWpAdmin());
        $this->assertTrue($context->isSpaDashboard());
        $this->assertSame('spa', $context->getMode());
    }

    public function test_detects_wp_admin_from_path(): void
    {
        $_SERVER['REQUEST_URI'] = '/wp-admin/index.php';
        $context = new AdminContext();
        $this->assertTrue($context->isWpAdmin());
        $this->assertFalse($context->isSpaDashboard());
        $this->assertSame('wp-admin', $context->getMode());
    }
}
