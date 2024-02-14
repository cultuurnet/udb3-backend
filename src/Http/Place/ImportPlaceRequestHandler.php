<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\GuardOrganizer;
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
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\Place\Udb3ModelToLegacyPlaceAdapter;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\UpdateTitle;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Offer\Commands\Video\ImportVideos;
use CultuurNet\UDB3\Offer\InvalidWorkflowStatusTransition;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\ImportImages;
use CultuurNet\UDB3\Place\Commands\Moderation\Publish;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdateDescription;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Place\Place as PlaceAggregate;
use CultuurNet\UDB3\Place\ReadModel\Duplicate\MultipleDuplicatePlacesFound;
use CultuurNet\UDB3\Place\ReadModel\Duplicate\LookupDuplicatePlace;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ImportPlaceRequestHandler implements RequestHandlerInterface
{
    use GuardOrganizer;

    private Repository $aggregateRepository;

    private UuidGeneratorInterface $uuidGenerator;

    private DenormalizerInterface $placeDenormalizer;

    private RequestBodyParser $importPreProcessingRequestBodyParser;

    private IriGeneratorInterface $iriGenerator;

    private CommandBus $commandBus;

    private ImageCollectionFactory $imageCollectionFactory;

    private bool $preventDuplicatePlaces;

    private LookupDuplicatePlace $lookupDuplicatePlace;

    private DocumentRepository $organizerDocumentRepository;

    public function __construct(
        Repository $aggregateRepository,
        UuidGeneratorInterface $uuidGenerator,
        DenormalizerInterface $placeDenormalizer,
        RequestBodyParser $importPreProcessingRequestBodyParser,
        IriGeneratorInterface $iriGenerator,
        CommandBus $commandBus,
        ImageCollectionFactory $imageCollectionFactory,
        bool $preventDuplicatePlaces,
        LookupDuplicatePlace $lookupDuplicatePlace,
        DocumentRepository $organizerDocumentRepository
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->uuidGenerator = $uuidGenerator;
        $this->placeDenormalizer = $placeDenormalizer;
        $this->importPreProcessingRequestBodyParser = $importPreProcessingRequestBodyParser;
        $this->iriGenerator = $iriGenerator;
        $this->commandBus = $commandBus;
        $this->imageCollectionFactory = $imageCollectionFactory;
        $this->preventDuplicatePlaces = $preventDuplicatePlaces;
        $this->lookupDuplicatePlace = $lookupDuplicatePlace;
        $this->organizerDocumentRepository = $organizerDocumentRepository;
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
        $title = $place->getTitle()->getTranslation($place->getTitle()->getOriginalLanguage());
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
            $this->guardDuplicatePlace($place);

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
                $place->getMainLanguage(),
                $place->getTitle()->getTranslation($place->getMainLanguage())
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
            $commands[] = new UpdateTitle(
                $placeId,
                new Language($language),
                new Title($title->toString())
            );
        }

        foreach ($placeAdapter->getDescriptionTranslations() as $language => $description) {
            $language = new LegacyLanguage($language);
            $commands[] = new UpdateDescription($placeId, $language, $description);
        }

        foreach ($placeAdapter->getAddressTranslations() as $language => $address) {
            $language = new LegacyLanguage($language);
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
            $this->guardOrganizer($organizerId, $this->organizerDocumentRepository);
            $commands[] = new UpdateOrganizer($placeId, $organizerId);
        } else {
            $commands[] = new DeleteCurrentOrganizer($placeId);
        }

        foreach ($commands as $command) {
            try {
                $this->commandBus->dispatch($command);
            } catch (InvalidWorkflowStatusTransition $notAllowedToPublish) {
            }
        }

        $responseBody = [
            'id' => $placeId,
            'placeId' => $placeId,
            'url' => $this->iriGenerator->iri($placeId),
            'commandId' => Uuid::NIL,
        ];
        return new JsonResponse($responseBody, $responseStatus);
    }

    public function guardDuplicatePlace(Place $place): void
    {
        if (! $this->preventDuplicatePlaces) {
            return;
        }

        try {
            $duplicatePlaceId = $this->lookupDuplicatePlace->getDuplicatePlaceUri($place);
            if ($duplicatePlaceId !== null) {
                throw ApiProblem::duplicatePlaceDetected(
                    'A place with this address / name combination already exists. Please use the existing place for your purposes.',
                    ['duplicatePlaceUri' => $duplicatePlaceId]
                );
            }
        } catch (MultipleDuplicatePlacesFound $e) {
            throw ApiProblem::duplicatePlaceDetected(
                $e->getMessage(),
                ['query' => $e->getQuery()]
            );
        }
    }
}
