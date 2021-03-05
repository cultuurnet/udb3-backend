<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventEditingServiceInterface;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Event\Location\LocationNotFound;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ValueObjects\Identity\UUID;

class EditEventRestControllerTest extends TestCase
{
    /**
     * @var EditEventRestController
     */
    private $controller;

    /**
     * @var EventEditingServiceInterface|MockObject
     */
    private $eventEditor;

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
        $this->eventEditor = $this->createMock(EventEditingServiceInterface::class);
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

        $this->controller = new EditEventRestController(
            $this->eventEditor,
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
                function ($eventId) {
                    return 'http://du.de/event/' . $eventId;
                }
            );
    }

    /**
     * @test
     */
    public function it_creates_an_event()
    {
        $request = Request::create('www.uitdatabank.dev', 'GET', [], [], [], [], $this->getMajorInfoJson());

        $this->eventEditor
            ->expects($this->once())
            ->method('createEvent')
            ->with(
                new Language('en'),
                new Title('foo'),
                new EventType('1.8.2', 'PARTY!'),
                new LocationId('fe282e4f-35f5-480d-a90b-2720ab883b0a')
            )
            ->willReturn('A14DD1C8-0F9C-4633-B56A-A908F009AD94');

        $response = $this->controller->createEvent($request);

        $expectedResponseContent = json_encode(
            [
                'eventId' => 'A14DD1C8-0F9C-4633-B56A-A908F009AD94',
                'url' => 'http://du.de/event/A14DD1C8-0F9C-4633-B56A-A908F009AD94',
            ]
        );

        $this->assertEquals($expectedResponseContent, $response->getContent());
    }

    /**
     * @test
     */
    public function it_will_not_create_an_event_with_invalid_location_id(): void
    {
        $request = Request::create('www.uitdatabank.dev', 'GET', [], [], [], [], $this->getMajorInfoJson());
        $invalidLocationId = new LocationId('fe282e4f-35f5-480d-a90b-2720ab883b0a');

        $this->eventEditor
            ->expects($this->once())
            ->method('createEvent')
            ->with(
                new Language('en'),
                new Title('foo'),
                new EventType('1.8.2', 'PARTY!'),
                $invalidLocationId
            )
            ->willThrowException(LocationNotFound::withLocationId($invalidLocationId));

        $this->expectException(BadRequestHttpException::class);
        $this->controller->createEvent($request);
    }

    /**
     * @test
     */
    public function it_should_create_an_approved_event_for_privileged_consumers()
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

        $this->eventEditor
            ->expects($this->once())
            ->method('createApprovedEvent')
            ->with(
                new Language('en'),
                new Title('foo'),
                new EventType('1.8.2', 'PARTY!'),
                new LocationId('fe282e4f-35f5-480d-a90b-2720ab883b0a')
            )
            ->willReturn('A14DD1C8-0F9C-4633-B56A-A908F009AD94');

        $response = $this->controller->createEvent($request);

        $expectedResponseContent = json_encode(
            [
                'eventId' => 'A14DD1C8-0F9C-4633-B56A-A908F009AD94',
                'url' => 'http://du.de/event/A14DD1C8-0F9C-4633-B56A-A908F009AD94',
            ]
        );

        $this->assertEquals($expectedResponseContent, $response->getContent());
    }

    /**
     * @test
     */
    public function it_copies_an_event()
    {
        $calendarData = json_encode([
            'calenderType' => 'permanent',
        ]);

        $request = new Request([], [], [], [], [], [], $calendarData);

        $this->eventEditor
            ->expects($this->once())
            ->method('copyEvent')
            ->with(
                '1539b109-5eec-43ef-8dc9-830cbe0cff8e',
                new Calendar(CalendarType::PERMANENT())
            )
            ->willReturn('A14DD1C8-0F9C-4633-B56A-A908F009AD94');

        $response = $this->controller->copyEvent($request, '1539b109-5eec-43ef-8dc9-830cbe0cff8e');

        $expectedResponseContent = json_encode(
            [
                'eventId' => 'A14DD1C8-0F9C-4633-B56A-A908F009AD94',
                'url' => 'http://du.de/event/A14DD1C8-0F9C-4633-B56A-A908F009AD94',
            ]
        );

        $this->assertEquals($expectedResponseContent, $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_update_major_info_with_all_the_provided_json_data()
    {
        $eventId = new UUID('7f71ebbd-b22b-4b94-96df-947ad0c1534f');
        $request = new Request([], [], [], [], [], [], $this->getMajorInfoJson());

        $this->eventEditor
            ->expects($this->once())
            ->method('updateMajorInfo')
            ->with(
                $eventId,
                new Title('foo'),
                new EventType('1.8.2', 'PARTY!'),
                new LocationId('fe282e4f-35f5-480d-a90b-2720ab883b0a')
            );

        $response = $this->controller->updateMajorInfo($request, $eventId->toNative());

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_location()
    {
        $eventId = '7f71ebbd-b22b-4b94-96df-947ad0c1534f';
        $locationId = '9a1fe7fc-4129-4563-aafd-414ef25b2814';

        $this->eventEditor->expects($this->once())
            ->method('updateLocation')
            ->with(
                $eventId,
                new LocationId('9a1fe7fc-4129-4563-aafd-414ef25b2814')
            );

        $response = $this->controller->updateLocation($eventId, $locationId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_audience()
    {
        $eventId = new UUID('7f71ebbd-b22b-4b94-96df-947ad0c1534f');
        $content = json_encode(['audienceType' => 'education']);
        $request = new Request([], [], [], [], [], [], $content);

        $this->eventEditor->expects($this->once())
            ->method('updateAudience')
            ->with(
                $eventId,
                new Audience(AudienceType::EDUCATION())
            );

        $response = $this->controller->updateAudience($request, $eventId->toNative());

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_error_when_updating_audience_but_missing_cdbid()
    {
        $eventId = '';
        $content = json_encode(['audienceType' => 'education']);
        $request = new Request([], [], [], [], [], [], $content);

        $response = $this->controller->updateAudience($request, $eventId);

        $expectedResponse = json_encode(['error' => 'cdbid is required.']);
        $this->assertEquals($expectedResponse, $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_error_when_updating_audience_but_missing_audience_type()
    {
        $eventId = new UUID('7f71ebbd-b22b-4b94-96df-947ad0c1534f');
        $request = new Request();

        $response = $this->controller->updateAudience($request, $eventId->toNative());

        $expectedResponse = json_encode(['error' => 'audience type is required.']);
        $this->assertEquals($expectedResponse, $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
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
                'location' => [
                    'id' => 'fe282e4f-35f5-480d-a90b-2720ab883b0a',
                    'name' => 'P-P-Partyzone',
                    'address' => [
                        'streetAddress' => 'acmelane 12',
                        'postalCode' => '3000',
                        'addressLocality' => 'Leuven',
                        'addressCountry' => 'BE',
                    ],
                ],
                'calendar' => [
                    'type' => 'permanent',
                ],
            ]
        );
    }
}
