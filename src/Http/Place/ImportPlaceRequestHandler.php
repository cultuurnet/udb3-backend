<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecification;
use CultuurNet\UDB3\Http\Offer\CalendarValidationRequestBodyParser;
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
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\Place\Udb3ModelToLegacyPlaceAdapter;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Offer\Commands\Video\ImportVideos;
use CultuurNet\UDB3\Place\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\ImportImages;
use CultuurNet\UDB3\Place\Commands\Moderation\Publish;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdateDescription;
use CultuurNet\UDB3\Place\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Place\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Place\Commands\UpdateTitle;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Place\Place as PlaceAggregate;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

    private ConsumerSpecification $shouldApprove;

    private ApiKeyReader $apiKeyReader;

    private ConsumerReadRepository $consumerReadRepository;

    public function __construct(
        Repository $aggregateRepository,
        UuidGeneratorInterface $uuidGenerator,
        DenormalizerInterface $placeDenormalizer,
        RequestBodyParser $importPreProcessingRequestBodyParser,
        IriGeneratorInterface $iriGenerator,
        CommandBus $commandBus,
        ImageCollectionFactory $imageCollectionFactory,
        ConsumerSpecification $shouldApprove,
        ApiKeyReader $apiKeyReader,
        ConsumerReadRepository $consumerReadRepository
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->uuidGenerator = $uuidGenerator;
        $this->placeDenormalizer = $placeDenormalizer;
        $this->importPreProcessingRequestBodyParser = $importPreProcessingRequestBodyParser;
        $this->iriGenerator = $iriGenerator;
        $this->commandBus = $commandBus;
        $this->imageCollectionFactory = $imageCollectionFactory;
        $this->shouldApprove = $shouldApprove;
        $this->apiKeyReader = $apiKeyReader;
        $this->consumerReadRepository = $consumerReadRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);

        $placeId = $this->uuidGenerator->generate();
        $responseStatus = StatusCodeInterface::STATUS_CREATED;
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
            new LegacyPlaceRequestBodyParser(),
            new IdPropertyPolyfillRequestBodyParser($this->iriGenerator, $placeId),
            $this->importPreProcessingRequestBodyParser,
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::PLACE),
            new CalendarValidationRequestBodyParser(),
            new BookingInfoValidationRequestBodyParser(),
            MainLanguageValidatingRequestBodyParser::createForPlace(),
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
        if (str_contains($request->getUri()->getPath(), 'imports')) {
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

            // Places created by specific API partners should automatically be
            // approved.
            $consumer = $this->getConsumer($request);
            if ($consumer && $this->shouldApprove->satisfiedBy($consumer)) {
                if (!str_contains($request->getUri()->getPath(), 'imports')) {
                    $placeAggregate->publish($publishDate);
                }

                $placeAggregate->approve();
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

        $organizerId = $placeAdapter->getOrganizerId();
        if ($organizerId) {
            $commands[] = new UpdateOrganizer($placeId, $organizerId);
        } else {
            $commands[] = new DeleteCurrentOrganizer($placeId);
        }

        $ageRange = $placeAdapter->getAgeRange();
        if ($ageRange) {
            $commands[] = new UpdateTypicalAgeRange($placeId, $ageRange);
        } else {
            $commands[] = new DeleteTypicalAgeRange($placeId);
        }

        $priceInfo = $placeAdapter->getPriceInfo();
        if ($priceInfo) {
            $commands[] = new UpdatePriceInfo($placeId, $priceInfo);
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

        $lastCommandId = null;
        foreach ($commands as $command) {
            /** @var string|null $commandId */
            $commandId = $this->commandBus->dispatch($command);
            if ($commandId) {
                $lastCommandId = $commandId;
            }
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

    private function getConsumer(ServerRequestInterface $request): ?Consumer
    {
        $apiKey = $this->apiKeyReader->read($request);

        if ($apiKey === null) {
            return null;
        }

        return $this->consumerReadRepository->getConsumer($apiKey);
    }
}
