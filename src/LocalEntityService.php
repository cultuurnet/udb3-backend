<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class LocalEntityService implements EntityServiceInterface
{
    protected DocumentRepository $documentRepository;

    protected Repository $entityRepository;

    protected IriGeneratorInterface $iriGenerator;

    public function __construct(
        DocumentRepository $documentRepository,
        Repository $entityRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        $this->documentRepository = $documentRepository;
        $this->entityRepository = $entityRepository;
        $this->iriGenerator = $iriGenerator;
    }

    public function getEntity(string $id): string
    {
        try {
            $document = $this->documentRepository->fetch($id);
        } catch (DocumentDoesNotExist $e) {
            // If the read model is not initialized yet, try to load
            // the entity, which will initialize the read model.
            try {
                $this->entityRepository->load($id);
            } catch (AggregateNotFoundException $e) {
                throw new EntityNotFoundException(
                    sprintf('Entity with id: %s not found.', $id)
                );
            }

            try {
                $document = $this->documentRepository->fetch($id);
            } catch (DocumentDoesNotExist $e) {
                throw new EntityNotFoundException(
                    sprintf('Entity with id: %s not found.', $id)
                );
            }
        }

        return $document->getRawBody();
    }

    public function iri($id): string
    {
        return $this->iriGenerator->iri($id);
    }
}
