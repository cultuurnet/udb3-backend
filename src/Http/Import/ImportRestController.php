<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Respect\Validation\Exceptions\ValidationException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImportRestController
{
    /**
     * @var ApiKeyReaderInterface
     */
    private $apiKeyReader;

    /**
     * @var ConsumerReadRepositoryInterface
     */
    private $consumerReadRepository;

    /**
     * @var DocumentImporterInterface
     */
    private $documentImporter;

    /**
     * @var UuidGeneratorInterface
     */
    private $uuidGenerator;

    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @var string
     */
    private $idProperty;

    /**
     * @param string $idProperty
     */
    public function __construct(
        ApiKeyReaderInterface $apiKeyReader,
        ConsumerReadRepositoryInterface $consumerReadRepository,
        DocumentImporterInterface $documentImporter,
        UuidGeneratorInterface $uuidGenerator,
        IriGeneratorInterface $iriGenerator,
        $idProperty = 'id'
    ) {
        $this->apiKeyReader = $apiKeyReader;
        $this->consumerReadRepository = $consumerReadRepository;
        $this->documentImporter = $documentImporter;
        $this->uuidGenerator = $uuidGenerator;
        $this->iriGenerator = $iriGenerator;
        $this->idProperty = $idProperty;
    }

    /**
     * @param string $cdbid
     * @return Response
     */
    public function importWithId(Request $request, $cdbid)
    {
        $apiKey = $this->apiKeyReader->read(
            (new DiactorosFactory())->createRequest($request)
        );

        if ($apiKey) {
            $consumer = $this->consumerReadRepository->getConsumer($apiKey);
        } else {
            $consumer = null;
        }

        $json = $this->getJson($request);
        $document = DecodedDocument::fromJson($cdbid, $json);

        $body = $document->getBody();
        $body['@id'] = $this->iriGenerator->iri($cdbid);
        $document = $document->withBody($body);

        try {
            $this->documentImporter->import($document, $consumer);
        } catch (DBALEventStoreException $exception) {
            if ($exception->getPrevious() instanceof UniqueConstraintViolationException) {
                throw ApiProblem::resourceIdAlreadyInUse($cdbid);
            }
        }

        return (new JsonResponse())
            ->setData([$this->idProperty => $cdbid])
            ->setPrivate();
    }

    /**
     * @return Response
     */
    public function importWithoutId(Request $request)
    {
        $cdbid = $this->uuidGenerator->generate();
        return $this->importWithId($request, $cdbid);
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getJson($request)
    {
        $json = $request->getContent();

        if (empty($json)) {
            throw new ValidationException('JSON-LD missing.');
        }

        return $json;
    }
}
