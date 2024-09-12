<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class EmbeddingRelatedResourcesOfferRepository extends DocumentRepositoryDecorator
{
    private array $repositoryMapping = [];

    public static function createForEventRepository(
        DocumentRepository $sourceRepository,
        DocumentRepository $placeRepository,
        DocumentRepository $organizerRepository
    ): self {
        $repository = new self($sourceRepository);
        $repository->repositoryMapping['location'] = $placeRepository;
        $repository->repositoryMapping['organizer'] = $organizerRepository;
        return $repository;
    }

    public static function createForPlaceRepository(
        DocumentRepository $sourceRepository,
        DocumentRepository $organizerRepository
    ): self {
        $repository = new self($sourceRepository);
        $repository->repositoryMapping['organizer'] = $organizerRepository;
        return $repository;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $document = parent::fetch($id, $includeMetadata);

        foreach ($this->repositoryMapping as $propertyName => $repository) {
            $document = $this->embedRelatedResource($document, $propertyName, $repository);
        }

        return $document;
    }

    private function embedRelatedResource(
        JsonDocument $document,
        string $property,
        DocumentRepository $documentRepository
    ): JsonDocument {
        return $document->applyAssoc(
            function (array $json) use ($property, $documentRepository): array {
                $url = $json[$property]['@id'] ?? null;
                if (!is_string($url)) {
                    return $json;
                }

                $id = $this->getUuidFromUrl($url);

                try {
                    $embedDocument = $documentRepository->fetch($id);
                } catch (DocumentDoesNotExist $e) {
                    return $json;
                }

                $embedJson = $embedDocument->getAssocBody();

                // While the embedded document _should_ have the (same) @id as well, let's make 100% sure we don't
                // accidentally remove or alter the original one. We have it here anyway.
                $embedJson['@id'] = $url;
                $json[$property] = $embedJson;
                return $json;
            }
        );
    }

    private function getUuidFromUrl(string $url): string
    {
        $parts = explode('/', $url);
        return end($parts);
    }
}
