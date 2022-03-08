<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Offer\CalendarValidationRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\AssociativeArrayRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\IdPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\MainLanguageValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ImportEventRequestHandler implements RequestHandlerInterface
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

        $eventId = $routeParameters->hasEventId() ? $routeParameters->getEventId() : $this->uuidGenerator->generate();
        $responseStatus = $routeParameters->hasEventId() ? StatusCodeInterface::STATUS_OK : StatusCodeInterface::STATUS_CREATED;

        /** @var array $data */
        $data = RequestBodyParserFactory::createBaseParser(
            new ImportTermRequestBodyParser(new EventCategoryResolver()),
            new IdPropertyPolyfillRequestBodyParser($this->iriGenerator, $eventId),
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT),
            new CalendarValidationRequestBodyParser(),
            MainLanguageValidatingRequestBodyParser::createForPlace(),
            new AssociativeArrayRequestBodyParser()
        )->parse($request)->getParsedBody();

        $document = new DecodedDocument($eventId, $data);

        try {
            $commandId = $this->documentImporter->import($document);
        } catch (DBALEventStoreException $exception) {
            if ($exception->getPrevious() instanceof UniqueConstraintViolationException) {
                throw ApiProblem::resourceIdAlreadyInUse($eventId);
            }
            throw $exception;
        }

        $responseBody = ['id' => $eventId];
        if ($commandId) {
            $responseBody['commandId'] = $commandId;
        }
        return new JsonResponse($responseBody, $responseStatus);
    }
}
