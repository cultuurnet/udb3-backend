<?php

namespace CultuurNet\UDB3\ReadModel;

abstract class DocumentRepositoryDecorator implements DocumentRepositoryInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    protected $decoratedRepository;

    public function __construct(DocumentRepositoryInterface $repository)
    {
        $this->decoratedRepository = $repository;
    }

    public function get(string $id): ?JsonDocument
    {
        return $this->decoratedRepository->get($id);
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
