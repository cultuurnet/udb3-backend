<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Event\Commands\DeleteOnlineUrl;
use CultuurNet\UDB3\Event\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Event\Commands\ImportImages;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Commands\UpdateAttendanceMode;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Commands\UpdateOnlineUrl;
use CultuurNet\UDB3\Event\Commands\UpdateTheme;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Event\Event as EventAggregate;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
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
use CultuurNet\UDB3\Model\Event\Event;
use CultuurNet\UDB3\Model\Import\Event\Udb3ModelToLegacyEventAdapter;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
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
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ImportEventRequestHandler implements RequestHandlerInterface
{
    use GuardOrganizer;
    private Repository $aggregateRepository;
    private UuidGeneratorInterface $uuidGenerator;
    private IriGeneratorInterface $eventIriGenerator;
    private DenormalizerInterface $eventDenormalizer;
    private RequestBodyParser $combinedRequestBodyParser;
    private CommandBus $commandBus;
    private ImageCollectionFactory $imageCollectionFactory;
    private DocumentRepository $locationDocumentRepository;
    private DocumentRepository $organizerDocumentRepository;

    public function __construct(
        Repository $aggregateRepository,
        UuidGeneratorInterface $uuidGenerator,
        IriGeneratorInterface $eventIriGenerator,
        DenormalizerInterface $eventDenormalizer,
        RequestBodyParser $combinedRequestBodyParser,
        CommandBus $commandBus,
        ImageCollectionFactory $imageCollectionFactory,
        DocumentRepository $locationDocumentRepository,
        DocumentRepository $organizerDocumentRepository
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->uuidGenerator = $uuidGenerator;
        $this->eventIriGenerator = $eventIriGenerator;
        $this->eventDenormalizer = $eventDenormalizer;
        $this->combinedRequestBodyParser = $combinedRequestBodyParser;
        $this->commandBus = $commandBus;
        $this->imageCollectionFactory = $imageCollectionFactory;
        $this->locationDocumentRepository = $locationDocumentRepository;
        $this->organizerDocumentRepository = $organizerDocumentRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $usingOldImportsPath = str_contains($request->getUri()->getPath(), 'imports');

        $routeParameters = new RouteParameters($request);

        $eventId = $routeParameters->hasEventId() ? $routeParameters->getEventId() : $this->uuidGenerator->generate();
        $responseStatus = $routeParameters->hasEventId() || $usingOldImportsPath ? StatusCodeInterface::STATUS_OK : StatusCodeInterface::STATUS_CREATED;
        $eventExists = false;

        if ($routeParameters->hasEventId()) {
            try {
                $this->aggregateRepository->load($eventId);
                $eventExists = true;
            } catch (AggregateNotFoundException $e) {
            }
        }

        /** @var Event $event */
        $event = RequestBodyParserFactory::createBaseParser(
            $this->combinedRequestBodyParser,
            new IdPropertyPolyfillRequestBodyParser($this->eventIriGenerator, $eventId),
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT),
            new AttendanceModeValidatingRequestBodyParser(),
            new AgeRangeValidatingRequestBodyParser(),
            new OfferValidatingRequestBodyParser(OfferType::event()),
            new DenormalizingRequestBodyParser($this->eventDenormalizer, Event::class)
        )->parse($request)->getParsedBody();

        $eventAdapter = new Udb3ModelToLegacyEventAdapter($event);

        $mainLanguage = $eventAdapter->getMainLanguage();
        $title = $event->getTitle()->getOriginalValue();
        $type = $eventAdapter->getType();
        $location = $eventAdapter->getLocation();
        $calendar = $eventAdapter->getCalendar();
        $theme = $eventAdapter->getTheme();
        $publishDate = $eventAdapter->getAvailableFrom(new DateTimeImmutable());

        if (!$location->isNilLocation()) {
            try {
                $this->locationDocumentRepository->fetch($location->toString());
            } catch (DocumentDoesNotExist $e) {
                throw ApiProblem::bodyInvalidData(
                    new SchemaError(
                        '/location',
                        'The location with id "' . $location->toString() . '" was not found.'
                    )
                );
            }
        }

        if ($event->getOnlineUrl() && $event->getAttendanceMode()->sameAs(AttendanceMode::offline())) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/onlineUrl',
                    'An onlineUrl can not be used in combination with an offline attendanceMode.'
                )
            );
        }

        // Get the workflowStatus from the JSON. If the JSON has no workflowStatus, it will be DRAFT by default.
        // If the request URL contains "imports", overwrite the workflowStatus to READY_FOR_VALIDATION to ensure
        // backward compatibility with existing integrations that use those deprecated imports paths without a
        // workflowStatus, and who expect the workflowStatus to automatically be READY_FOR_VALIDATION or APPROVED.
        $workflowStatus = $event->getWorkflowStatus();
        if ($usingOldImportsPath) {
            $workflowStatus = WorkflowStatus::READY_FOR_VALIDATION();
        }

        $commands = [];
        if (!$eventExists) {
            $eventAggregate = EventAggregate::create(
                $eventId,
                $mainLanguage,
                $title,
                $type,
                $location,
                $calendar,
                $theme,
                $publishDate
            );

            if ($workflowStatus->sameAs(WorkflowStatus::READY_FOR_VALIDATION())) {
                $eventAggregate->publish($publishDate);
            }

            $this->aggregateRepository->save($eventAggregate);

            $commands[] = new UpdateAttendanceMode($eventId, $event->getAttendanceMode());
        } else {
            if ($workflowStatus->sameAs(WorkflowStatus::READY_FOR_VALIDATION())) {
                $commands[] = new Publish($eventId, $publishDate);
            }

            $commands[] = new UpdateTitle(
                $eventId,
                $event->getMainLanguage(),
                $event->getTitle()->getTranslation($event->getMainLanguage())
            );
            $commands[] = new UpdateType($eventId, $type->getId());
            // The attendance mode needs to be updated before the location can be changed.
            // For example passing a real location to an online event is not allowed.
            $commands[] = new UpdateAttendanceMode($eventId, $event->getAttendanceMode());
            $commands[] = new UpdateLocation($eventId, $location);
            $commands[] = new UpdateCalendar($eventId, $calendar);

            if ($theme) {
                $commands[] = new UpdateTheme($eventId, $theme->getId());
            }
        }

        if ($event->getOnlineUrl()) {
            $commands[] = new UpdateOnlineUrl($eventId, $event->getOnlineUrl());
        } else {
            $commands[] = new DeleteOnlineUrl($eventId);
        }

        if ($location->isDummyPlaceForEducation()) {
            $audienceType = AudienceType::education();
        } else {
            $audienceType = $event->getAudienceType();
        }
        $commands[] = new UpdateAudience($eventId, $audienceType);

        $bookingInfo = $eventAdapter->getBookingInfo();
        $commands[] = new UpdateBookingInfo($eventId, $bookingInfo);

        $contactPoint = $eventAdapter->getContactPoint();
        $commands[] = new UpdateContactPoint($eventId, $contactPoint);

        $description = $eventAdapter->getDescription();
        if ($description) {
            $commands[] = new UpdateDescription($eventId, $mainLanguage, $description);
        }

        $ageRange = $eventAdapter->getAgeRange();
        if ($ageRange) {
            $commands[] = new UpdateTypicalAgeRange($eventId, $ageRange);
        } else {
            $commands[] = new DeleteTypicalAgeRange($eventId);
        }

        if ($event->getPriceInfo()) {
            $commands[] = new UpdatePriceInfo($eventId, $event->getPriceInfo());
        }

        foreach ($eventAdapter->getTitleTranslations() as $language => $title) {
            $commands[] = new UpdateTitle(
                $eventId,
                new Language($language),
                new Title($title->toString())
            );
        }

        foreach ($eventAdapter->getDescriptionTranslations() as $language => $description) {
            $language = new LegacyLanguage($language);
            $commands[] = new UpdateDescription($eventId, $language, $description);
        }

        $commands[] = new ImportLabels($eventId, $event->getLabels());

        $images = $this->imageCollectionFactory->fromMediaObjectReferences($event->getMediaObjectReferences());
        $commands[] = new ImportImages($eventId, $images);

        $commands[] = new ImportVideos($eventId, $event->getVideos());

        if ($workflowStatus->sameAs(WorkflowStatus::DELETED())) {
            $commands[] = new DeleteOffer($eventId);
        }

        // Update the organizer only at the end, because it can trigger UiTPAS to send messages to another worker
        // which might cause race conditions if we're still dispatching other commands here as well.
        $organizerId = $eventAdapter->getOrganizerId();
        if ($organizerId) {
            try {
                $this->guardOrganizer($organizerId, $this->organizerDocumentRepository);
                $commands[] = new UpdateOrganizer($eventId, $organizerId);
            } catch (DocumentDoesNotExist $e) {
                throw ApiProblem::bodyInvalidData(
                    new SchemaError(
                        '/organizer',
                        'The organizer with id "' . $organizerId . '" was not found.'
                    )
                );
            }
        } else {
            $commands[] = new DeleteCurrentOrganizer($eventId);
        }

        foreach ($commands as $command) {
            try {
                $this->commandBus->dispatch($command);
            } catch (InvalidWorkflowStatusTransition $notAllowedToPublish) {
            }
        }

        $responseBody = [
            'id' => $eventId,
            'eventId' => $eventId,
            'url' => $this->eventIriGenerator->iri($eventId),
            'commandId' => Uuid::NIL,
        ];
        return new JsonResponse($responseBody, $responseStatus);
    }
}
