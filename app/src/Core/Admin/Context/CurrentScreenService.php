<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Context;

use PrestoWorld\Core\Admin\Contracts\CurrentScreenInterface;
use PrestoWorld\Core\Admin\Contracts\WpScreenInterface;
use PrestoWorld\Core\Admin\Contracts\WpScreenInterface as WpScreenContract;
use Witals\Framework\Contracts\ResettableInterface;

/**
 * CurrentScreenService — manages the WordPress-compatible current screen state.
 *
 * Request-scoped service. Implements ResettableInterface so the lifecycle
 * (RoadRunnerLifecycle::cleanupRequest) clears state between requests,
 * preventing cross-request leakage on long-running runtimes.
 */
class CurrentScreenService implements CurrentScreenInterface, ResettableInterface
{
    protected ?WpScreenInterface $screen = null;

    public function setScreen(?string $screenId, array $meta = []): void
    {
        if ($screenId === null || $screenId === '') {
            $this->screen = null;
            return;
        }

        $this->screen = new WpScreen(
            id: $screenId,
            base: $meta['base'] ?? $this->inferBase($screenId),
            action: $meta['action'] ?? null,
            postType: $meta['post_type'] ?? null,
            parentFile: $meta['parent_file'] ?? null,
            parentBase: $meta['parent_base'] ?? null,
            isNetwork: (bool) ($meta['is_network'] ?? false),
            isUserAdmin: (bool) ($meta['is_user_admin'] ?? false),
        );
    }

    public function getScreen(): ?WpScreenInterface
    {
        return $this->screen;
    }

    public function clearScreen(): void
    {
        $this->screen = null;
    }

    public function hasScreen(): bool
    {
        return $this->screen !== null;
    }

    public function reset(): void
    {
        $this->screen = null;
    }

    private function inferBase(string $screenId): string
    {
        // Map common screen ids to wp-admin bases.
        $map = [
            'dashboard' => 'index.php',
            'posts' => 'edit.php',
            'post' => 'post.php',
            'post-new' => 'post-new.php',
            'page' => 'edit.php',
            'pages' => 'edit.php',
            'media' => 'upload.php',
            'media-new' => 'media-new.php',
            'themes' => 'themes.php',
            'plugins' => 'plugins.php',
            'users' => 'users.php',
            'tools' => 'tools.php',
            'settings' => 'options-general.php',
        ];

        return $map[$screenId] ?? $screenId;
    }
}
