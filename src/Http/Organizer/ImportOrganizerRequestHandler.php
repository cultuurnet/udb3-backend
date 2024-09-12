<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Label\DuplicateLabelValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\IdPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\MainLanguageValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\DeleteDescription;
use CultuurNet\UDB3\Organizer\Commands\DeleteEducationalDescription;
use CultuurNet\UDB3\Organizer\Commands\ImportImages;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateDescription;
use CultuurNet\UDB3\Organizer\Commands\UpdateEducationalDescription;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Organizer as OrganizerAggregate;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

final class ImportOrganizerRequestHandler implements RequestHandlerInterface
{
    private Repository $aggregateRepository;
    private CommandBus $commandBus;
    private UuidGeneratorInterface $uuidGenerator;
    private IriGeneratorInterface $iriGenerator;
    private RequestBodyParser $importPreProcessingRequestBodyParser;

    public function __construct(
        Repository $aggregateRepository,
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        IriGeneratorInterface $iriGenerator,
        RequestBodyParser $importPreProcessingRequestBodyParser
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
        $this->iriGenerator = $iriGenerator;
        $this->importPreProcessingRequestBodyParser = $importPreProcessingRequestBodyParser;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $usingOldImportsPath = str_contains($request->getUri()->getPath(), 'imports');

        $routeParameters = new RouteParameters($request);

        $organizerId = $this->uuidGenerator->generate();
        $responseStatus = $usingOldImportsPath ? StatusCodeInterface::STATUS_OK : StatusCodeInterface::STATUS_CREATED;
        if ($routeParameters->hasOrganizerId()) {
            $organizerId = $routeParameters->getOrganizerId();
            $responseStatus = StatusCodeInterface::STATUS_OK;
        }

        try {
            $this->aggregateRepository->load($organizerId);
            $exists = true;
        } catch (AggregateNotFoundException $e) {
            $exists = false;
        }

        /** @var Organizer $data */
        $data = RequestBodyParserFactory::createBaseParser(
            $this->importPreProcessingRequestBodyParser,
            new IdPropertyPolyfillRequestBodyParser($this->iriGenerator, $organizerId),
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER),
            new DuplicateLabelValidatingRequestBodyParser(),
            MainLanguageValidatingRequestBodyParser::createForOrganizer(),
            new DenormalizingRequestBodyParser(new OrganizerDenormalizer(), Organizer::class)
        )->parse($request)->getParsedBody();

        $mainLanguage = $data->getMainLanguage();
        $title = $data->getName()->getTranslation($data->getMainLanguage());
        $url = $data->getUrl();

        $commands = [];
        if (!$exists) {
            $organizer = OrganizerAggregate::create(
                $organizerId,
                $mainLanguage,
                $url,
                $title
            );

            try {
                $this->aggregateRepository->save($organizer);
            } catch (UniqueConstraintException $e) {
                throw ApiProblem::duplicateUrl($url->toString(), $e->getDuplicateValue());
            } catch (DBALEventStoreException $exception) {
                if ($exception->getPrevious() instanceof UniqueConstraintViolationException) {
                    throw ApiProblem::resourceIdAlreadyInUse($organizerId);
                }
                throw $exception;
            }
        } else {
            $commands[] = new UpdateTitle(
                $organizerId,
                $title,
                $mainLanguage
            );
            $commands[] = new UpdateWebsite($organizerId, $data->getUrl());
        }

        $commands[] = new UpdateContactPoint($organizerId, $data->getContactPoint());

        $description = $data->getDescription();
        $descriptionCommands = [
            'nl' => new DeleteDescription($organizerId, new Language('nl')),
            'fr' => new DeleteDescription($organizerId, new Language('fr')),
            'de' => new DeleteDescription($organizerId, new Language('de')),
            'en' => new DeleteDescription($organizerId, new Language('en')),
        ];
        if ($description) {
            foreach ($description->getLanguages() as $language) {
                $descriptionCommands[$language->getCode()] = new UpdateDescription(
                    $organizerId,
                    $description->getTranslation($language),
                    $language
                );
            }
        }
        foreach ($descriptionCommands as $descriptionCommand) {
            $commands[] = $descriptionCommand;
        }

        $educationalDescription = $data->getEducationalDescription();
        $educationalDescriptionCommands = [
            'nl' => new DeleteEducationalDescription($organizerId, new Language('nl')),
            'fr' => new DeleteEducationalDescription($organizerId, new Language('fr')),
            'de' => new DeleteEducationalDescription($organizerId, new Language('de')),
            'en' => new DeleteEducationalDescription($organizerId, new Language('en')),
        ];
        if ($educationalDescription) {
            foreach ($educationalDescription->getLanguages() as $language) {
                $educationalDescriptionCommands[$language->getCode()] = new UpdateEducationalDescription(
                    $organizerId,
                    $educationalDescription->getTranslation($language),
                    $language
                );
            }
        }
        foreach ($educationalDescriptionCommands as $educationalDescriptionCommand) {
            $commands[] = $educationalDescriptionCommand;
        }

        $address = $data->getAddress();
        if ($address) {
            foreach ($address->getLanguages() as $language) {
                $commands[] = new UpdateAddress(
                    $organizerId,
                    $address->getTranslation($language),
                    $language
                );
            }
        } else {
            $commands[] = new RemoveAddress($organizerId);
        }

        $translatedTitle = $data->getName();
        foreach ($translatedTitle->getLanguagesWithoutOriginal() as $language) {
            $title = $translatedTitle->getTranslation($language);
            $commands[] = new UpdateTitle($organizerId, $title, $language);
        }

        $commands[] = new ImportLabels($organizerId, $data->getLabels());
        $commands[] = new ImportImages($organizerId, $data->getImages());

        foreach ($commands as $command) {
            try {
                $this->commandBus->dispatch($command);
            } catch (UniqueConstraintException $e) {
                throw ApiProblem::duplicateUrl($url->toString(), $e->getDuplicateValue());
            }
        }

        $responseBody = [
            'id' => $organizerId,
            'organizerId' => $organizerId,
            'url' => $this->iriGenerator->iri($organizerId),
            'commandId' => Uuid::NIL,
        ];
        return new JsonResponse($responseBody, $responseStatus);
    }
}
