<?php

namespace CultuurNet\UDB3;

use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class LocalEntityService implements EntityServiceInterface
{
    /**
     * @var DocumentRepository
     */
    protected $documentRepository;

    /**
     * @var RepositoryInterface
     */
    protected $entityRepository;

    /**
     * @var IriGeneratorInterface
     */
    protected $iriGenerator;

    public function __construct(
        DocumentRepository $documentRepository,
        RepositoryInterface $entityRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        $this->documentRepository = $documentRepository;
        $this->entityRepository = $entityRepository;
        $this->iriGenerator = $iriGenerator;
    }

    public function getEntity(string $id): string
    {
        /** @var JsonDocument $document */
        $document = $this->documentRepository->get($id);

        if (!$document) {
            // If the read model is not initialized yet, try to load
            // the entity, which will initialize the read model.
            try {
                $this->entityRepository->load($id);
            } catch (AggregateNotFoundException $e) {
                throw new EntityNotFoundException(
                    sprintf('Entity with id: %s not found.', $id)
                );
            }

            /** @var JsonDocument $document */
            $document = $this->documentRepository->get($id);

            if (!$document) {
                throw new EntityNotFoundException(
                    sprintf('Entity with id: %s not found.', $id)
                );
            }
        }

        return $document->getRawBody();
    }

    public function iri($id)
    {
        return $this->iriGenerator->iri($id);
    }
}
