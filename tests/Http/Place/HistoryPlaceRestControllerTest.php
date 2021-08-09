<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblemException;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HistoryPlaceRestControllerTest extends TestCase
{
    public const EXISTING_ID = 'existingId';
    public const NON_EXISTING_ID = 'nonExistingId';

    /**
     * @var string
     */
    private $rawHistory;

    /**
     * @var string
     */
    private $expectedProcessedHistory;

    public function setUp()
    {
        $this->rawHistory = json_encode(
            [
                'cfaed6fc-296a-427d-8931-c36428f25336_1_2019-04-23T16:00:00+0200' => [
                    'author' => 'author1',
                ],
                'cfaed6fc-296a-427d-8931-c36428f25336_2_2019-04-23T16:15:00+0200' => [
                    'author' => 'author2',
                ],
            ]
        );

        $this->expectedProcessedHistory = json_encode(
            [
                [
                    'author' => 'author2',
                ],
                [
                    'author' => 'author1',
                ],
            ]
        );
    }

    private function createController(bool $godUser): HistoryPlaceRestController
    {
        $documentRepositoryInterface = $this->createMock(DocumentRepository::class);
        $documentRepositoryInterface->method('fetch')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return new JsonDocument('id', $this->rawHistory);
                        default:
                            throw DocumentDoesNotExist::withId($id);
                    }
                }
            );

        /** @var DocumentRepository $documentRepositoryInterface */
        return new HistoryPlaceRestController(
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
        $jsonResponse = $controller->get(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_OK, $jsonResponse->getStatusCode());
        $this->assertEquals($this->expectedProcessedHistory, $jsonResponse->getContent());
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_exception_for_a_non_existing_event(): void
    {
        $this->expectException(ApiProblemException::class);
        $controller = $this->createController(true);
        $controller->get(self::NON_EXISTING_ID);
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_exception_for_a_regular_user(): void
    {
        $this->expectException(ApiProblemException::class);
        $controller = $this->createController(false);
        $controller->get(self::EXISTING_ID);
    }
}
