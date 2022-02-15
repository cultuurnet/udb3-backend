<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
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
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Offer\Commands\Video\ImportVideos;
use CultuurNet\UDB3\Place\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\ImportImages;
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

    private ConsumerSpecificationInterface $shouldApprove;

    private LockedLabelRepository $lockedLabelRepository;

    private ApiKeyReaderInterface $apiKeyReader;

    private ConsumerReadRepositoryInterface $consumerReadRepository;

    public function __construct(
        Repository $aggregateRepository,
        UuidGeneratorInterface $uuidGenerator,
        DenormalizerInterface $placeDenormalizer,
        RequestBodyParser $importPreProcessingRequestBodyParser,
        IriGeneratorInterface $iriGenerator,
        CommandBus $commandBus,
        ImageCollectionFactory $imageCollectionFactory,
        LockedLabelRepository $lockedLabelRepository,
        ConsumerSpecificationInterface $shouldApprove,
        ApiKeyReaderInterface $apiKeyReader,
        ConsumerReadRepositoryInterface $consumerReadRepository
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->uuidGenerator = $uuidGenerator;
        $this->placeDenormalizer = $placeDenormalizer;
        $this->importPreProcessingRequestBodyParser = $importPreProcessingRequestBodyParser;
        $this->iriGenerator = $iriGenerator;
        $this->commandBus = $commandBus;
        $this->imageCollectionFactory = $imageCollectionFactory;
        $this->lockedLabelRepository = $lockedLabelRepository;
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
            new IdPropertyPolyfillRequestBodyParser($this->iriGenerator, $placeId),
            $this->importPreProcessingRequestBodyParser,
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::PLACE),
            new CalendarValidationRequestBodyParser(),
            new BookingInfoValidationRequestBodyParser(),
            new PriceInfoValidationRequestBodyParser(),
            new DenormalizingRequestBodyParser($this->placeDenormalizer, Place::class)
        )->parse($request)->getParsedBody();

        $placeAdapter = new Udb3ModelToLegacyPlaceAdapter($place);

        $mainLanguage = $placeAdapter->getMainLanguage();
        $title = $placeAdapter->getTitle();
        $type = $placeAdapter->getType();
        $address = $placeAdapter->getAddress();
        $calendar = $placeAdapter->getCalendar();
        $publishDate = $placeAdapter->getAvailableFrom(new DateTimeImmutable());

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

            // New places created via the import API should always be set to
            // ready_for_validation.
            // The publish date in PLaceCreated does not seem to trigger a
            // wfStatus "ready_for_validation" on the json-ld so we manually
            // publish the place after creating it.
            // Existing places should always keep their original status, so
            // only do this publish command for new places.
            $placeAggregate->publish($publishDate);

            // Places created by specific API partners should automatically be
            // approved.
            $consumer = $this->getConsumer($request);
            if ($consumer && $this->shouldApprove->satisfiedBy($consumer)) {
                $placeAggregate->approve();
            }

            $this->aggregateRepository->save($placeAggregate);
        } else {
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

        $lockedLabels = $this->lockedLabelRepository->getLockedLabelsForItem($placeId);
        $commands[] = (new ImportLabels($placeId, $place->getLabels()))
            ->withLabelsToKeepIfAlreadyOnOffer($lockedLabels);

        $images = $this->imageCollectionFactory->fromMediaObjectReferences($place->getMediaObjectReferences());
        $commands[] = new ImportImages($placeId, $images);

        $commands[] = new ImportVideos($placeId, $place->getVideos());

        $lastCommandId = null;
        foreach ($commands as $command) {
            /** @var string|null $commandId */
            $commandId = $this->commandBus->dispatch($command);
            if ($commandId) {
                $lastCommandId = $commandId;
            }
        }

        $responseBody = ['id' => $placeId];
        if ($lastCommandId) {
            $responseBody['commandId'] = $lastCommandId;
        }
        return new JsonResponse($responseBody, $responseStatus);
    }

    private function getConsumer(ServerRequestInterface $request): ?ConsumerInterface
    {
        $apiKey = $this->apiKeyReader->read($request);

        if ($apiKey === null) {
            return null;
        }

        return $this->consumerReadRepository->getConsumer($apiKey);
    }
}
