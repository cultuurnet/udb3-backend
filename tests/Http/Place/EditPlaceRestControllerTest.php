<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Place\PlaceEditingServiceInterface;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;

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
     * @var QueryParameterApiKeyReader
     */
    private $apiKeyReader;

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
        $this->apiKeyReader = new QueryParameterApiKeyReader('apiKey');
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
            $this->mediaManager,
            $this->iriGenerator,
            $this->apiKeyReader,
            $this->consumerRepository,
            $this->shouldApprove
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
    public function it_should_respond_with_the_location_of_the_new_offer_when_creating_a_place()
    {
        $request = Request::create('www.uitdatabank.dev', 'GET', [], [], [], [], $this->getMajorInfoJson());

        $this->placeEditingService
            ->expects($this->once())
            ->method('createPlace')
            ->with(
                new Language('en'),
                new Title('foo'),
                new EventType('1.8.2', 'PARTY!'),
                new Address(
                    new Street('acmelane 12'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                )
            )
            ->willReturn('A14DD1C8-0F9C-4633-B56A-A908F009AD94');

        $response = $this->placeRestController->createPlace($request);

        $expectedResponseContent = json_encode(
            [
                'placeId' => 'A14DD1C8-0F9C-4633-B56A-A908F009AD94',
                'url' => 'http://du.de/place/A14DD1C8-0F9C-4633-B56A-A908F009AD94',
            ]
        );

        $this->assertEquals($expectedResponseContent, $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_create_an_approved_place_for_privileged_consumers()
    {
        $request = Request::create(
            'www.uitdatabank.dev',
            'GET',
            ['apiKey' => $this->apiKey->toString()],
            [],
            [],
            [],
            $this->getMajorInfoJson()
        );

        $this->placeEditingService
            ->expects($this->once())
            ->method('createApprovedPlace')
            ->with(
                new Language('en'),
                new Title('foo'),
                new EventType('1.8.2', 'PARTY!'),
                new Address(
                    new Street('acmelane 12'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                )
            )
            ->willReturn('A14DD1C8-0F9C-4633-B56A-A908F009AD94');

        $response = $this->placeRestController->createPlace($request);

        $expectedResponseContent = json_encode(
            [
                'placeId' => 'A14DD1C8-0F9C-4633-B56A-A908F009AD94',
                'url' => 'http://du.de/place/A14DD1C8-0F9C-4633-B56A-A908F009AD94',
            ]
        );

        $this->assertEquals($expectedResponseContent, $response->getContent());
    }

    /**
     * @test
     */
    public function it_updates_major_info()
    {
        $placeId = new UUID('A14DD1C8-0F9C-4633-B56A-A908F009AD94');
        $request = new Request([], [], [], [], [], [], $this->getMajorInfoJson());

        $this->placeEditingService
            ->expects($this->once())
            ->method('updateMajorInfo')
            ->with(
                $placeId,
                new Title('foo'),
                new EventType('1.8.2', 'PARTY!'),
                new Address(
                    new Street('acmelane 12'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                )
            );

        $response = $this->placeRestController->updateMajorInfo($request, $placeId->toNative());

        $this->assertEquals(204, $response->getStatusCode());
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
                    Country::fromNative('BE')
                ),
                new Language($lang)
            );

        $response = $this->placeRestController->updateAddress($request, $placeId, $lang);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @return string
     */
    private function getMajorInfoJson()
    {
        return json_encode(
            [
                'mainLanguage' => 'en',
                'name' => 'foo',
                'type' => [
                    'id' => '1.8.2',
                    'label' => 'PARTY!',
                ],
                'theme' => [
                    'id' => '6.6.6',
                    'label' => 'Pentagrams',
                ],
                'address' => [
                    'streetAddress' => 'acmelane 12',
                    'postalCode' => '3000',
                    'addressLocality' => 'Leuven',
                    'addressCountry' => 'BE',
                ],
                'calendar' => [
                    'type' => 'permanent',
                ],
            ]
        );
    }
}
