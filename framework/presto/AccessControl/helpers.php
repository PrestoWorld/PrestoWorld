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
        static $manager = null;

        if ($app !== null) {
            $manager = new AccessControlManager($app);
        }

        if ($manager === null) {
            throw new \RuntimeException('AccessControlManager chưa được khởi tạo. Hãy bật module access-control.');
        }

        return $manager;
    }
}
