<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Search;

use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;
use Witals\Framework\Contracts\ConcurrentManager;

class AsyncSearchAdapterDecorator implements SearchEngineInterface
{
    public function __construct(
        private SearchEngineInterface $inner,
        private ConcurrentManager $concurrent,
    ) {}

    public function index(string $index, array $documents): void
    {
        $this->concurrent->run(fn() => $this->inner->index($index, $documents));
    }

    public function addDocument(string $index, array $document): void
    {
        $this->concurrent->run(fn() => $this->inner->addDocument($index, $document));
    }

    public function updateDocument(string $index, string $id, array $document): void
    {
        $this->concurrent->run(fn() => $this->inner->updateDocument($index, $id, $document));
    }

    public function deleteDocument(string $index, string $id): void
    {
        $this->concurrent->run(fn() => $this->inner->deleteDocument($index, $id));
    }

    public function search(string $index, string $query, array $options = []): array
    {
        return $this->concurrent->run(
            fn(): array => $this->inner->search($index, $query, $options),
        );
    }

    public function getDocument(string $index, string $id): ?array
    {
        return $this->concurrent->run(fn(): ?array => $this->inner->getDocument($index, $id));
    }

    public function deleteIndex(string $index): void
    {
        $this->concurrent->run(fn() => $this->inner->deleteIndex($index));
    }

    public function indexExists(string $index): bool
    {
        return $this->concurrent->run(fn(): bool => $this->inner->indexExists($index));
    }

    public function getName(): string
    {
        return $this->concurrent->run(fn(): string => $this->inner->getName());
    }

    public function configure(array $config): void
    {
        $this->concurrent->run(fn() => $this->inner->configure($config));
    }
}
