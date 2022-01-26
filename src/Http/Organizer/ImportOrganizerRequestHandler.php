<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\AssociativeArrayRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\IdPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ImportOrganizerRequestHandler implements RequestHandlerInterface
{
    private DocumentImporterInterface $documentImporter;
    private UuidGeneratorInterface $uuidGenerator;
    private IriGeneratorInterface $iriGenerator;

    public function __construct(
        DocumentImporterInterface $documentImporter,
        UuidGeneratorInterface $uuidGenerator,
        IriGeneratorInterface $iriGenerator
    ) {
        $this->documentImporter = $documentImporter;
        $this->uuidGenerator = $uuidGenerator;
        $this->iriGenerator = $iriGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);

        $organizerId = $this->uuidGenerator->generate();
        $responseStatus = StatusCodeInterface::STATUS_CREATED;
        if ($routeParameters->hasOrganizerId()) {
            $organizerId = $routeParameters->getOrganizerId();
            $responseStatus = StatusCodeInterface::STATUS_OK;
        }

        /** @var array $data */
        $data = RequestBodyParserFactory::createBaseParser(
            new IdPropertyPolyfillRequestBodyParser($this->iriGenerator, $organizerId),
            new AssociativeArrayRequestBodyParser()
        )->parse($request)->getParsedBody();

        $document = new DecodedDocument($organizerId, $data);

        try {
            $commandId = $this->documentImporter->import($document);
        } catch (DBALEventStoreException $exception) {
            if ($exception->getPrevious() instanceof UniqueConstraintViolationException) {
                throw ApiProblem::resourceIdAlreadyInUse($organizerId);
            }
            throw $exception;
        }

        $responseBody = ['id' => $organizerId];
        if ($commandId) {
            $responseBody['commandId'] = $commandId;
        }
        return new JsonResponse($responseBody, $responseStatus);
    }
}
