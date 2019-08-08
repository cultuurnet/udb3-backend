<?php

namespace CultuurNet\UDB3\Model\Import\PreProcessing;

use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class LocationPreProcessingDocumentImporter implements DocumentImporterInterface
{
    /**
     * @var DocumentImporterInterface
     */
    private $jsonImporter;

    /**
     * @var UUIDParser
     */
    private $placeIdParser;

    /**
     * @var DocumentRepositoryInterface
     */
    private $placeDocumentRepository;

    /**
     * @param DocumentImporterInterface $jsonImporter
     * @param UUIDParser $placeIdParser
     * @param DocumentRepositoryInterface $placeDocumentRepository
     */
    public function __construct(
        DocumentImporterInterface $jsonImporter,
        UUIDParser $placeIdParser,
        DocumentRepositoryInterface $placeDocumentRepository
    ) {
        $this->jsonImporter = $jsonImporter;
        $this->placeIdParser = $placeIdParser;
        $this->placeDocumentRepository = $placeDocumentRepository;
    }

    /**
     * Pre-processes the JSON to embed location properties based on the location id.
     *
     * @param DecodedDocument $decodedDocument
     * @param ConsumerInterface|null $consumer
     */
    public function import(DecodedDocument $decodedDocument, ConsumerInterface $consumer = null)
    {
        $data = $decodedDocument->getBody();

        // Attempt to add or correct the embedded place data.
        if (isset($data['location']['@id']) && is_string($data['location']['@id'])) {
            try {
                $url = new Url((string) $data['location']['@id']);
                $placeId = $this->placeIdParser->fromUrl($url);
                $placeJsonDocument = $this->placeDocumentRepository->get($placeId->toString());

                $placeJson = [];
                if ($placeJsonDocument) {
                    $placeJson = json_decode($placeJsonDocument->getRawBody(), true);
                    $placeJson = $placeJson ? $placeJson : [];
                }
                $placeJson['@id'] = $url->toString();

                $data['location'] = $placeJson;
            } catch (\Exception $e) {
                // Do nothing. Validators will report the invalid data that
                // caused the exception later on.
            }
        }

        $decodedDocument = $decodedDocument->withBody($data);

        $this->jsonImporter->import($decodedDocument, $consumer);
    }
}
