<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Serializer\Place\NilLocationNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class NilLocationEnrichedPlaceRepository extends DocumentRepositoryDecorator
{
    private NilLocationNormalizer $nilLocationNormalizer;

    public function __construct(NilLocationNormalizer $nilLocationNormalizer, DocumentRepository $documentRepository)
    {
        parent::__construct($documentRepository);
        $this->nilLocationNormalizer = $nilLocationNormalizer;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        if ($id === Uuid::NIL) {
            return (new JsonDocument($id))->withAssocBody(
                $this->nilLocationNormalizer->normalize(ImmutablePlace::createNilLocation())
            );
        }

        return parent::fetch($id, $includeMetadata);
    }
}
