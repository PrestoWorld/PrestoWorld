<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Legacy;

/**
 * Trả về khi mã WP gọi wp_die/exit (spec 10 §10.6.7 — termination rewrite).
 *
 * Compiled output throw exception này thay vì die() để lỗi không giết worker;
 * middleware/layer trên quyết định cách phản hồi (status, message, code).
 */
class LegacyTerminationException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $status = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}