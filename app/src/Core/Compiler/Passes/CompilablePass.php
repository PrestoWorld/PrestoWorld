<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler\Passes;

use PhpParser\NodeVisitor;

/**
 * Một pass trong pipeline compiler (spec §10.2.3).
 *
 * Passes nhận CompilationContext qua constructor (context per-compile),
 * sau đó được dùng cho traversal từng file.
 */
interface CompilablePass extends NodeVisitor
{
}