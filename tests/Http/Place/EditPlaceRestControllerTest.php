<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Place\PlaceEditingServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class EditPlaceRestControllerTest extends TestCase
{
    /**
     * @var EditPlaceRestController
     */
    private $placeRestController;

    /**
     * @var PlaceEditingServiceInterface|MockObject
     */
    private $placeEditingService;

    /**
     * @var RepositoryInterface|MockObject
     */
    private $relationsRepository;

    /**
     * @var MediaManagerInterface|MockObject
     */
    private $mediaManager;

    /**
     * @var IriGeneratorInterface|MockObject
     */
    private $iriGenerator;

    /**
     * @var InMemoryConsumerRepository
     */
    private $consumerRepository;

    /**
     * @var ApiKey
     */
    private $apiKey;

    /**
     * @var ConsumerInterface|MockObject
     */
    private $consumer;

    /**
     * @var ConsumerSpecificationInterface|MockObject
     */
    private $shouldApprove;

    public function setUp()
    {
        $this->placeEditingService  = $this->createMock(PlaceEditingServiceInterface::class);
        $this->relationsRepository  = $this->createMock(RepositoryInterface::class);
        $this->mediaManager  = $this->createMock(MediaManagerInterface::class);
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->consumerRepository = new InMemoryConsumerRepository();
        $this->shouldApprove = $this->createMock(ConsumerSpecificationInterface::class);

        $this->apiKey = new ApiKey('f5278146-3133-48b8-ace4-7e3f0a49328a');
        $this->consumer = $this->createMock(ConsumerInterface::class);
        $this->consumerRepository->setConsumer($this->apiKey, $this->consumer);

        $this->shouldApprove->expects($this->any())
            ->method('satisfiedBy')
            ->with($this->consumer)
            ->willReturn(true);

        $this->placeRestController = new EditPlaceRestController(
            $this->placeEditingService,
            $this->relationsRepository,
            $this->mediaManager
        );

        $this->iriGenerator
            ->expects($this->any())
            ->method('iri')
            ->willReturnCallback(
                function ($placeId) {
                    return 'http://du.de/place/' . $placeId;
                }
            );
    }

    /**
     * @test
     */
    public function it_should_update_the_address_of_a_place_for_a_given_language()
    {
        $json = json_encode(
            [
                'streetAddress' => 'Eenmeilaan 35',
                'postalCode' => '3010',
                'addressLocality' => 'Kessel-Lo',
                'addressCountry' => 'BE',
            ]
        );

        $request = new Request([], [], [], [], [], [], $json);

        $placeId = '6645274f-d969-4d70-865e-3ec799db9624';
        $lang = 'nl';

        $this->placeEditingService->expects($this->once())
            ->method('updateAddress')
            ->with(
                $placeId,
                new Address(
                    new Street('Eenmeilaan 35'),
                    new PostalCode('3010'),
                    new Locality('Kessel-Lo'),
                    new CountryCode('BE')
                ),
                new Language($lang)
            );

        $response = $this->placeRestController->updateAddress($request, $placeId, $lang);

        $this->assertEquals(204, $response->getStatusCode());
    }
}
