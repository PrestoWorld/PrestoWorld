<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl;

use PrestoWorld\AccessControl\AccessControlManager;
use Witals\Framework\Application;

if (!function_exists('access')) {
    /**
     * Trả về AccessControlManager instance (Context/Strategy).
     */
    function access(?Application $app = null): AccessControlManager
    {
        if ($app !== null) {
            return $app->make(AccessControlManager::class);
        }

        if (function_exists('app')) {
            $manager = \app(AccessControlManager::class);
            if ($manager instanceof AccessControlManager) {
                return $manager;
            }
        }

        throw new \RuntimeException('AccessControlManager chưa được đăng ký trong core container.');
    }
}
