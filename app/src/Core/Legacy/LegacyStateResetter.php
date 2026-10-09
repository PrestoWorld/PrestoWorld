<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Legacy;

use Witals\Framework\Contracts\ResettableInterface;

/**
 * Bridge LegacyState vào lifecycle long-running (spec 10 §10.7.2).
 *
 * Lifecycle quét mọi container instance implements ResettableInterface và gọi
 * reset() sau mỗi request (RoadRunnerLifecycle::cleanupRequest). Adapter này
 * chuyển sang LegacyState::reset() — chỗ dựa duy nhất cho việc dọn state
 * global/static legacy giữa các request, thay vì để nó nghẽn 1 lần duy nhất.
 */
final class LegacyStateResetter implements ResettableInterface
{
    public function reset(): void
    {
        // resetRequest (chứ KHÔNG phải reset()): lifecycle long-running boot 1 lần,
        // registry hook giữ qua worker; chỉ dọn transient state mỗi request.
        LegacyState::resetRequest();
    }
}