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
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Organizer as OrganizerAggregate;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ImportOrganizerRequestHandler implements RequestHandlerInterface
{
    private Repository $aggregateRepository;
    private CommandBus $commandBus;
    private LockedLabelRepository $lockedLabelRepository;
    private UuidGeneratorInterface $uuidGenerator;
    private IriGeneratorInterface $iriGenerator;
    private RequestBodyParser $importPreProcessingRequestBodyParser;

    public function __construct(
        Repository $aggregateRepository,
        CommandBus $commandBus,
        LockedLabelRepository $lockedLabelRepository,
        UuidGeneratorInterface $uuidGenerator,
        IriGeneratorInterface $iriGenerator,
        RequestBodyParser $importPreProcessingRequestBodyParser
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->commandBus = $commandBus;
        $this->lockedLabelRepository = $lockedLabelRepository;
        $this->uuidGenerator = $uuidGenerator;
        $this->iriGenerator = $iriGenerator;
        $this->importPreProcessingRequestBodyParser = $importPreProcessingRequestBodyParser;
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

        try {
            $this->aggregateRepository->load($organizerId);
            $exists = true;
        } catch (AggregateNotFoundException $e) {
            $exists = false;
        }

        /** @var Organizer $data */
        $data = RequestBodyParserFactory::createBaseParser(
            new IdPropertyPolyfillRequestBodyParser($this->iriGenerator, $organizerId),
            $this->importPreProcessingRequestBodyParser,
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER),
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

        $lockedLabels = $this->lockedLabelRepository->getLockedLabelsForItem($organizerId);
        $commands[] = (new ImportLabels($organizerId, $data->getLabels()))
            ->withLabelsToKeepIfAlreadyOnOrganizer($lockedLabels);

        $lastCommandId = null;
        foreach ($commands as $command) {
            // It's not possible to catch the UniqueConstraintException that UpdateWebsite can cause here, since the
            // commands are handled async.
            /** @var string|null $commandId */
            $commandId = $this->commandBus->dispatch($command);
            if ($commandId) {
                $lastCommandId = $commandId;
            }
        }

        $responseBody = ['id' => $organizerId];
        if ($lastCommandId) {
            $responseBody['commandId'] = $lastCommandId;
        }
        return new JsonResponse($responseBody, $responseStatus);
    }
}
