<?php

namespace CultuurNet\UDB3\ReadModel;

abstract class DocumentRepositoryDecorator implements DocumentRepository
{
    /**
     * @var DocumentRepository
     */
    protected $decoratedRepository;

    public function __construct(DocumentRepository $repository)
    {
        $this->decoratedRepository = $repository;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        return $this->decoratedRepository->fetch($id, $includeMetadata);
    }

    public function get(string $id, bool $includeMetadata = false): ?JsonDocument
    {
        return $this->decoratedRepository->get($id, $includeMetadata);
    }

    public function save(JsonDocument $readModel): void
    {
        $this->decoratedRepository->save($readModel);
    }

    public function remove($id): void
    {
        $this->decoratedRepository->remove($id);
    }
}
