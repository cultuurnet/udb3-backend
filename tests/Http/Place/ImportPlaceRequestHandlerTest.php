<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Commands\Video\ImportVideos;
use CultuurNet\UDB3\Place\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\ImportImages;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Place;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ImportPlaceRequestHandlerTest extends TestCase
{
    private MockObject $aggregateRepository;

    private MockObject $uuidGenerator;

    private TraceableCommandBus $commandBus;

    private MockObject $imageCollectionFactory;

    private MockObject $lockedLabelRepository;

    private MockObject $consumerSpecification;

    private MockObject $apiReader;

    private MockObject $consumerRepository;

    private ImportPlaceRequestHandler $importPlaceRequestHandler;

    protected function setUp(): void
    {
        $this->aggregateRepository = $this->createMock(Repository::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->commandBus = new TraceableCommandBus();
        $this->imageCollectionFactory = $this->createMock(ImageCollectionFactory::class);
        $this->lockedLabelRepository = $this->createMock(LockedLabelRepository::class);
        $this->consumerSpecification = $this->createMock(ConsumerSpecificationInterface::class);
        $this->apiReader = $this->createMock(ApiKeyReaderInterface::class);
        $this->consumerRepository = $this->createMock(ConsumerReadRepositoryInterface::class);

        $this->importPlaceRequestHandler = new ImportPlaceRequestHandler(
            $this->aggregateRepository,
            $this->uuidGenerator,
            new PlaceDenormalizer(),
            new CombinedRequestBodyParser(),
            new CallableIriGenerator(fn ($placeId) => 'https://io.uitdatabank.dev/places/' . $placeId),
            $this->commandBus,
            $this->imageCollectionFactory,
            $this->lockedLabelRepository,
            $this->consumerSpecification,
            $this->apiReader,
            $this->consumerRepository
        );

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_imports_a_new_place_with_only_required_fields(): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $givenPlace = [
            'name' => [
                'nl' => 'Cafe Den Hemel',
            ],
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [
                    'addressCountry' => 'BE',
                    'addressLocality' => 'Scherpenheuvel-Zichem',
                    'postalCode' => '3271',
                    'streetAddress' => 'Hoornblaas 107',
                ],
            ],
            'calendarType' => 'permanent',
            'mainLanguage' => 'nl',
        ];

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($placeId);

        $this->aggregateRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(
                    fn (Place $place) => $place->getAggregateRootId() === $placeId
                )
            );

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->lockedLabelRepository->expects($this->once())
            ->method('getLockedLabelsForItem')
            ->with($placeId)
            ->willReturn(new Labels());

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($givenPlace)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(['id' => $placeId]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteCurrentOrganizer($placeId),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
