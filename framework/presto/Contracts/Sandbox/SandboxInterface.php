<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Sandbox;

interface SandboxInterface
{
    /**
     * Execute a file within the sandbox environment.
     *
     * @param string $file Absolute path to the file.
     * @param array $context Additional variables to extract into the scope.
     * @return mixed
     */
    public function execute(string $file, array $context = []): mixed;

    /**
     * Add a transformer to the sandbox.
     */
    public function addTransformer(TransformerInterface $transformer): void;
}
