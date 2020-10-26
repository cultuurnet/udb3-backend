<?php

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\SearchV3\Serializer\SerializerInterface;
use CultuurNet\SearchV3\ValueObjects\Event;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\EventServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Http\Management\User\UserIdentificationInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use DateTimeZone;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadEventRestControllerTest extends TestCase
{
    const EXISTING_ID = 'existingId';
    const NON_EXISTING_ID = 'nonExistingId';
    const REMOVED_ID = 'removedId';

    /**
     * @var ReadEventRestController
     */
    private $eventRestController;

    /**
     * @var JsonDocument
     */
    private $historyJsonDocument;

    /**
     * @var string
     */
    private $historyReponseContent;

    /**
     * @var string
     */
    private $calSum;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var UserIdentificationInterface|MockObject
     */
    private $userIdentification;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->historyJsonDocument = new JsonDocument(
            'id',
            json_encode(
                [
                    'cfaed6fc-296a-427d-8931-c36428f25336_1_2019-04-23T16:00:00+0200' => [
                        'author' => 'author1',
                    ],
                    'cfaed6fc-296a-427d-8931-c36428f25336_2_2019-04-23T16:15:00+0200' => [
                        'author' => 'author2',
                    ],
                ]
            )
        );

        $this->historyReponseContent = json_encode(
            [
                [
                    'author' => 'author2',
                ],
                [
                    'author' => 'author1',
                ],
            ]
        );

        $this->calSum = 'zondag 7 oktober 2018 van 12:15 tot 18:00';

        $this->event = new Event();
        $tz = new DateTimeZone('Europe/Brussels');
        $this->event->setStartDate(new \DateTime('2018-10-07 12:15:00', $tz));
        $this->event->setEndDate(new \DateTime('2018-10-07 18:00:00', $tz));
        $this->event->setCalendarType('single');

        $jsonRepository = $this->createMock(DocumentRepository::class);
        $jsonRepository->method('get')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return $this->historyJsonDocument;
                        case self::REMOVED_ID:
                            throw new DocumentGoneException();
                        default:
                            return null;
                    }
                }
            );

        $documentRepositoryInterface = $this->createMock(DocumentRepository::class);
        $documentRepositoryInterface->method('get')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return $this->historyJsonDocument;
                        case self::REMOVED_ID:
                            throw new DocumentGoneException();
                        default:
                            return null;
                    }
                }
            );

        $serializerInterface = $this->createMock(SerializerInterface::class);
        $serializerInterface->method('deserialize')
            ->willReturnCallback(
                function () {
                    return $this->event;
                }
            );

        $this->userIdentification = $this->createMock(UserIdentificationInterface::class);

        /**
         * @var EventServiceInterface $eventServiceInterface
         * @var DocumentRepository $documentRepositoryInterface
         */
        $this->eventRestController = new ReadEventRestController(
            $jsonRepository,
            $documentRepositoryInterface,
            $serializerInterface,
            $this->userIdentification
        );
    }

    /**
     * @test
     */
    public function returns_a_http_response_with_json_history_for_an_event(): void
    {
        $this->givenGodUser();
        $jsonResponse = $this->eventRestController->history(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_OK, $jsonResponse->getStatusCode());
        $this->assertEquals($this->historyReponseContent, $jsonResponse->getContent());
    }

    /**
     * @test
     */
    public function returns_a_http_response_with_error_NOT_FOUND_for_a_non_existing_event(): void
    {
        $this->givenGodUser();
        $jsonResponse = $this->eventRestController->history(self::NON_EXISTING_ID);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $jsonResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function returns_a_http_response_with_error_HTTP_GONE_for_a_removed_event(): void
    {
        $this->givenGodUser();
        $jsonResponse = $this->eventRestController->history(self::REMOVED_ID);

        $this->assertEquals(Response::HTTP_GONE, $jsonResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function returns_a_http_response_with_error_FORBIDDEN_for_a_regular_user(): void
    {
        $this->givenRegularUser();
        $jsonResponse = $this->eventRestController->history(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $jsonResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_json_get_for_an_event(): void
    {
        $jsonResponse = $this->eventRestController->get(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_OK, $jsonResponse->getStatusCode());
        $this->assertEquals($this->historyJsonDocument->getRawBody(), $jsonResponse->getContent());
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_error_NOT_FOUND_for_getting_a_non_existing_event(): void
    {
        $jsonResponse = $this->eventRestController->get(self::NON_EXISTING_ID);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $jsonResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_a_calendar_summary_for_an_event(): void
    {
        $request = new Request(array('style' => 'text', 'format' => 'lg'));
        $calSumResponse = $this->eventRestController->getCalendarSummary(self::EXISTING_ID, $request);

        $this->assertEquals($this->calSum, $calSumResponse);
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_error_NOT_FOUND_for_calendar_summary_for_non_existing_event(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $request = new Request(array('style' => 'text', 'format' => 'lg'));
        $this->eventRestController->getCalendarSummary(self::NON_EXISTING_ID, $request);
    }

    private function givenGodUser(): void
    {
        $this->userIdentification
            ->method('isGodUser')
            ->willReturn(true);
    }

    private function givenRegularUser(): void
    {
        $this->userIdentification
            ->method('isGodUser')
            ->willReturn(false);
    }
}
