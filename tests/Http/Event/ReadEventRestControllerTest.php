<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadEventRestControllerTest extends TestCase
{
    public const EXISTING_ID = 'existingId';
    public const NON_EXISTING_ID = 'nonExistingId';
    public const REMOVED_ID = 'removedId';

    /**
     * @var JsonDocument
     */
    private $jsonDocument;

    /**
     * @var JsonDocument
     */
    private $jsonDocumentWithMetadata;

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
     * @inheritdoc
     */
    public function setUp()
    {
        $this->jsonDocument = new JsonDocument(
            'id',
            json_encode(
                [
                    '@context' => '/contexts/event',
                    'status' => [
                        'type' => 'Available',
                    ],
                    'bookingAvailability' => [
                        'type' => 'Unavailable',
                    ],
                    'startDate' => '2018-10-07 12:15:00+0200',
                    'endDate' => '2018-10-07 18:00:00+0200',
                    'calendarType' => 'single',
                ]
            )
        );

        $this->jsonDocumentWithMetadata = new JsonDocument(
            'id',
            json_encode(
                [
                    '@type' => 'Event',
                    'popularity' => 123456,
                ]
            )
        );

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

        $this->calSum = 'Zondag 7 oktober 2018 van 12:15 tot 18:00 (Volzet of uitverkocht)';
    }

    private function createController(bool $godUser): ReadEventRestController
    {
        $jsonRepository = $this->createMock(DocumentRepository::class);
        $jsonRepository->method('fetch')
            ->willReturnCallback(
                function (string $id, bool $includeMetadata = false) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return $includeMetadata ? $this->jsonDocumentWithMetadata : $this->jsonDocument;
                        case self::REMOVED_ID:
                            throw DocumentDoesNotExist::withId($id);
                        default:
                            throw DocumentDoesNotExist::withId($id);
                    }
                }
            );

        $documentRepositoryInterface = $this->createMock(DocumentRepository::class);
        $documentRepositoryInterface->method('fetch')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return $this->historyJsonDocument;
                        case self::REMOVED_ID:
                            throw DocumentDoesNotExist::withId(self::REMOVED_ID);
                        default:
                            throw DocumentDoesNotExist::withId($id);
                    }
                }
            );

        return new ReadEventRestController(
            $jsonRepository,
            $documentRepositoryInterface,
            $godUser
        );
    }

    /**
     * @test
     */
    public function returns_a_http_response_with_json_history_for_an_event(): void
    {
        $controller = $this->createController(true);
        $jsonResponse = $controller->history(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_OK, $jsonResponse->getStatusCode());
        $this->assertEquals($this->historyReponseContent, $jsonResponse->getContent());
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_exception_for_a_non_existing_event(): void
    {
        $this->expectException(ApiProblem::class);
        $controller = $this->createController(true);
        $controller->history(self::NON_EXISTING_ID);
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_exception_for_a_regular_user(): void
    {
        $this->expectException(ApiProblem::class);
        $controller = $this->createController(false);
        $controller->history(self::EXISTING_ID);
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_a_calendar_summary_for_an_event(): void
    {
        $request = new Request(['style' => 'text', 'format' => 'lg']);

        $controller = $this->createController(true);
        $calSumResponse = $controller->getCalendarSummary(self::EXISTING_ID, $request);

        $this->assertEquals($this->calSum, $calSumResponse);
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_exception_for_calendar_summary_for_non_existing_event(): void
    {
        $this->expectException(ApiProblem::class);

        $request = new Request(['style' => 'text', 'format' => 'lg']);

        $controller = $this->createController(true);
        $controller->getCalendarSummary(self::NON_EXISTING_ID, $request);
    }
}
