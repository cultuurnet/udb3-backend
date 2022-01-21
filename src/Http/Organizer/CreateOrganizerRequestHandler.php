<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Organizer as OrganizerAggregate;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CreateOrganizerRequestHandler implements RequestHandlerInterface
{
    private OrganizerRepository $organizerRepository;

    private CommandBus $commandBus;

    private UuidGeneratorInterface $uuidGenerator;

    private IriGeneratorInterface $iriGenerator;

    public function __construct(
        OrganizerRepository $organizerRepository,
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        IriGeneratorInterface $iriGenerator
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
        $this->iriGenerator = $iriGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new LegacyContactPointRequestBodyParser(),
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER_POST),
            new LegacyOrganizerRequestBodyParser($this->uuidGenerator, $this->iriGenerator),
            new DenormalizingRequestBodyParser(new OrganizerDenormalizer(), Organizer::class)
        );

        /** @var Organizer $organizer */
        $organizer = $requestBodyParser->parse($request)->getParsedBody();

        try {
            $this->organizerRepository->save(
                OrganizerAggregate::create(
                    $organizer->getId()->toString(),
                    $organizer->getMainLanguage(),
                    $organizer->getUrl(),
                    $organizer->getName()->getTranslation($organizer->getMainLanguage())
                )
            );
        } catch (UniqueConstraintException $e) {
            // Saving the organizer to the event store can trigger a UniqueConstraintException if the URL is already in
            // use by another organizer. This is intended but we need to return a prettier error for API integrators.
            // Note that in reality the organizer should always have a URL in this request handler, but in other cases
            // it can be null so we need to handle a theoretical null pointer exception here.
            $originalUrl = $organizer->getUrl() ? $organizer->getUrl()->toString() : '';
            throw ApiProblem::organizerUrlAlreadyInUse($originalUrl, $e->getDuplicateValue());
        }

        $commands = [];

        if (!$organizer->getContactPoint()->isEmpty()) {
            $commands[] = new UpdateContactPoint(
                $organizer->getId()->toString(),
                $organizer->getContactPoint()
            );
        }

        if ($organizer->getAddress()) {
            $commands[] = new UpdateAddress(
                $organizer->getId()->toString(),
                $organizer->getAddress()->getTranslation($organizer->getMainLanguage()),
                $organizer->getMainLanguage()
            );
        }

        foreach ($commands as $command) {
            $this->commandBus->dispatch($command);
        }

        return new JsonResponse([
            'organizerId' => $organizer->getId()->toString(),
            'url' => $this->iriGenerator->iri($organizer->getId()->toString()),
        ]);
    }
}
