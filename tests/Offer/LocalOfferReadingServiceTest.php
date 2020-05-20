<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url;

class LocalOfferReadingServiceTest extends TestCase
{
    /**
     * @var IriOfferIdentifierFactoryInterface|MockObject
     */
    private $iriOfferIdentifierFactory;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $eventDocumentRepository;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $placeDocumentRepository;

    /**
     * @var LocalOfferReadingService
     */
    private $service;

    public function setUp()
    {
        $this->iriOfferIdentifierFactory = $this->createMock(IriOfferIdentifierFactoryInterface::class);
        $this->eventDocumentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->placeDocumentRepository = $this->createMock(DocumentRepositoryInterface::class);

        $this->service = (new LocalOfferReadingService($this->iriOfferIdentifierFactory))
            ->withDocumentRepository(OfferType::EVENT(), $this->eventDocumentRepository)
            ->withDocumentRepository(OfferType::PLACE(), $this->placeDocumentRepository);
    }

    /**
     * @test
     * @dataProvider offerRepositoryDataProvider
     *
     * @param Url $iri
     * @param string $id
     * @param OfferType $type
     */
    public function it_loads_an_offer_from_its_correct_repository_based_on_its_type(
        Url $iri,
        $id,
        OfferType $type
    ) {
        $expectedDocument = new JsonDocument($id, '{}');

        // Map the given iri to our expected id and type.
        $this->iriOfferIdentifierFactory->expects($this->once())
            ->method('fromIri')
            ->with($iri)
            ->willReturn(
                new IriOfferIdentifier(
                    $iri,
                    $id,
                    $type
                )
            );

        // Determine which repository should be used, and which are irrelevant.
        switch ($type->toNative()) {
            case 'Event':
                $correctRepository = $this->eventDocumentRepository;
                $incorrectRepositories = [$this->placeDocumentRepository];
                break;

            case 'Place':
                $correctRepository = $this->placeDocumentRepository;
                $incorrectRepositories = [$this->eventDocumentRepository];
                break;

            default:
                throw new \LogicException('Unknown type ' . $type->toNative());
        }

        // Make sure the get() method will be called on the correct repository.
        $correctRepository->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn($expectedDocument);

        // Make sure the get() method will not be called on the other repositories.
        /* @var DocumentRepositoryInterface|MockObject $incorrectRepository */
        foreach ($incorrectRepositories as $incorrectRepository) {
            $incorrectRepository->expects($this->never())
                ->method('get');
        }

        // Load the offer json document based on its iri.
        $actualDocument = $this->service->load((string) $iri);

        // Make sure the document is passed through.
        $this->assertEquals($expectedDocument, $actualDocument);
    }

    /**
     * @return array
     */
    public function offerRepositoryDataProvider()
    {
        return [
            [
                Url::fromNative('local://event/1'),
                '1',
                OfferType::EVENT(),
            ],
            [
                Url::fromNative('local://place/7'),
                '7',
                OfferType::PLACE(),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_no_repository_is_provided_for_a_specific_offer_type()
    {
        // Set up a LocalOfferReadingService with no DocumentRepository for Place.
        $service = (new LocalOfferReadingService($this->iriOfferIdentifierFactory))
            ->withDocumentRepository(OfferType::EVENT(), $this->eventDocumentRepository);

        // Map the given iri to a place.
        $iri = Url::fromNative('local://place/2');
        $id = 2;
        $type = OfferType::PLACE();

        $this->iriOfferIdentifierFactory->expects($this->once())
            ->method('fromIri')
            ->with($iri)
            ->willReturn(
                new IriOfferIdentifier(
                    $iri,
                    $id,
                    $type
                )
            );

        // Make sure an exception is thrown when trying to load the iri.
        $this->expectException(
            \LogicException::class,
            'No document repository found for offer type Place.'
        );

        $service->load((string) $iri);
    }
}
