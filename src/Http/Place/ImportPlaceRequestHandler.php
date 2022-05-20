<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Http\Offer\OfferValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\IdPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\Place\Udb3ModelToLegacyPlaceAdapter;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Offer\Commands\Video\ImportVideos;
use CultuurNet\UDB3\Offer\InvalidWorkflowStatusTransition;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\ImportImages;
use CultuurNet\UDB3\Place\Commands\Moderation\Publish;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdateDescription;
use CultuurNet\UDB3\Place\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Place\Commands\UpdateTitle;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Place\Place as PlaceAggregate;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ImportPlaceRequestHandler implements RequestHandlerInterface
{
    private Repository $aggregateRepository;

    private UuidGeneratorInterface $uuidGenerator;

    private DenormalizerInterface $placeDenormalizer;

    private RequestBodyParser $importPreProcessingRequestBodyParser;

    private IriGeneratorInterface $iriGenerator;

    private CommandBus $commandBus;

    private ImageCollectionFactory $imageCollectionFactory;

    public function __construct(
        Repository $aggregateRepository,
        UuidGeneratorInterface $uuidGenerator,
        DenormalizerInterface $placeDenormalizer,
        RequestBodyParser $importPreProcessingRequestBodyParser,
        IriGeneratorInterface $iriGenerator,
        CommandBus $commandBus,
        ImageCollectionFactory $imageCollectionFactory
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->uuidGenerator = $uuidGenerator;
        $this->placeDenormalizer = $placeDenormalizer;
        $this->importPreProcessingRequestBodyParser = $importPreProcessingRequestBodyParser;
        $this->iriGenerator = $iriGenerator;
        $this->commandBus = $commandBus;
        $this->imageCollectionFactory = $imageCollectionFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $usingOldImportsPath = str_contains($request->getUri()->getPath(), 'imports');

        $routeParameters = new RouteParameters($request);

        $placeId = $this->uuidGenerator->generate();
        $responseStatus = $usingOldImportsPath ? StatusCodeInterface::STATUS_OK : StatusCodeInterface::STATUS_CREATED;
        $placeExists = false;

        if ($routeParameters->hasPlaceId()) {
            $placeId = $routeParameters->getPlaceId();
            $responseStatus = StatusCodeInterface::STATUS_OK;

            try {
                $this->aggregateRepository->load($placeId);
                $placeExists = true;
            } catch (AggregateNotFoundException $e) {
            }
        }

        /** @var Place $place */
        $place = RequestBodyParserFactory::createBaseParser(
            $this->importPreProcessingRequestBodyParser,
            new IdPropertyPolyfillRequestBodyParser($this->iriGenerator, $placeId),
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::PLACE),
            new OfferValidatingRequestBodyParser(OfferType::place()),
            new DenormalizingRequestBodyParser($this->placeDenormalizer, Place::class)
        )->parse($request)->getParsedBody();

        $placeAdapter = new Udb3ModelToLegacyPlaceAdapter($place);

        $mainLanguage = $placeAdapter->getMainLanguage();
        $title = $placeAdapter->getTitle();
        $type = $placeAdapter->getType();
        $address = $placeAdapter->getAddress();
        $calendar = $placeAdapter->getCalendar();
        $publishDate = $placeAdapter->getAvailableFrom(new DateTimeImmutable());

        // Get the workflowStatus from the JSON. If the JSON has no workflowStatus, it will be DRAFT by default.
        // If the request URL contains "imports", overwrite the workflowStatus to READY_FOR_VALIDATION to ensure
        // backward compatibility with existing integrations that use those deprecated imports paths without a
        // workflowStatus, and who expect the workflowStatus to automatically be READY_FOR_VALIDATION or APPROVED.
        $workflowStatus = $place->getWorkflowStatus();
        if ($usingOldImportsPath) {
            $workflowStatus = WorkflowStatus::READY_FOR_VALIDATION();
        }

        $commands = [];
        if (!$placeExists) {
            $placeAggregate = PlaceAggregate::create(
                $placeId,
                $mainLanguage,
                $title,
                $type,
                $address,
                $calendar,
                $publishDate
            );

            if ($workflowStatus->sameAs(WorkflowStatus::READY_FOR_VALIDATION())) {
                $placeAggregate->publish($publishDate);
            }

            $this->aggregateRepository->save($placeAggregate);
        } else {
            if ($workflowStatus->sameAs(WorkflowStatus::READY_FOR_VALIDATION())) {
                $commands[] = new Publish($placeId, $publishDate);
            }

            $commands[] = new UpdateTitle(
                $placeId,
                $mainLanguage,
                $title
            );

            $commands[] = new UpdateType($placeId, $type->getId());
            $commands[] = new UpdateAddress($placeId, $address, $mainLanguage);
            $commands[] = new UpdateCalendar($placeId, $calendar);
        }

        $bookingInfo = $placeAdapter->getBookingInfo();
        $commands[] = new UpdateBookingInfo($placeId, $bookingInfo);

        $contactPoint = $placeAdapter->getContactPoint();
        $commands[] = new UpdateContactPoint($placeId, $contactPoint);

        $description = $placeAdapter->getDescription();
        if ($description) {
            $commands[] = new UpdateDescription($placeId, $mainLanguage, $description);
        }

        $ageRange = $placeAdapter->getAgeRange();
        if ($ageRange) {
            $commands[] = new UpdateTypicalAgeRange($placeId, $ageRange);
        } else {
            $commands[] = new DeleteTypicalAgeRange($placeId);
        }

        if ($place->getPriceInfo()) {
            $commands[] = new UpdatePriceInfo($placeId, $place->getPriceInfo());
        }

        foreach ($placeAdapter->getTitleTranslations() as $language => $title) {
            $language = new Language($language);
            $commands[] = new UpdateTitle($placeId, $language, $title);
        }

        foreach ($placeAdapter->getDescriptionTranslations() as $language => $description) {
            $language = new Language($language);
            $commands[] = new UpdateDescription($placeId, $language, $description);
        }

        foreach ($placeAdapter->getAddressTranslations() as $language => $address) {
            $language = new Language($language);
            $commands[] = new UpdateAddress($placeId, $address, $language);
        }

        $commands[] = new ImportLabels($placeId, $place->getLabels());

        $images = $this->imageCollectionFactory->fromMediaObjectReferences($place->getMediaObjectReferences());
        $commands[] = new ImportImages($placeId, $images);

        $commands[] = new ImportVideos($placeId, $place->getVideos());

        if ($workflowStatus->sameAs(WorkflowStatus::DELETED())) {
            $commands[] = new DeleteOffer($placeId);
        }

        $organizerId = $placeAdapter->getOrganizerId();
        if ($organizerId) {
            $commands[] = new UpdateOrganizer($placeId, $organizerId);
        } else {
            $commands[] = new DeleteCurrentOrganizer($placeId);
        }

        foreach ($commands as $command) {
            try {
                $commandId = $this->commandBus->dispatch($command);
            } catch (InvalidWorkflowStatusTransition $notAllowedToPublish) {
            }
            $lastCommandId = $commandId ?? null;
        }

        if ($lastCommandId === null) {
            $lastCommandId = Uuid::NIL;
        }

        $responseBody = [
            'id' => $placeId,
            'placeId' => $placeId,
            'url' => $this->iriGenerator->iri($placeId),
        ];
        if ($lastCommandId) {
            $responseBody['commandId'] = $lastCommandId;
        }
        return new JsonResponse($responseBody, $responseStatus);
    }
}
