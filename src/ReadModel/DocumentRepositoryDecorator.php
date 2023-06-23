<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

abstract class DocumentRepositoryDecorator implements DocumentRepository
{
    protected DocumentRepository $decoratedRepository;

    public function __construct(DocumentRepository $repository)
    {
        $this->decoratedRepository = $repository;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        return $this->decoratedRepository->fetch($id, $includeMetadata);
    }

    public function save(JsonDocument $document): void
    {
        $this->decoratedRepository->save($document);
    }

    public function remove(string $id): void
    {
        $this->decoratedRepository->remove($id);
    }
}
