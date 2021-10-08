<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ReadEventRestControllerTest extends TestCase
{
    public const EXISTING_ID = 'existingId';
    public const NON_EXISTING_ID = 'nonExistingId';
    public const REMOVED_ID = 'removedId';

    /**
     * @var JsonDocument
     */
    private $historyJsonDocument;

    /**
     * @var string
     */
    private $historyReponseContent;

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
    }

    private function createController(bool $godUser): ReadEventRestController
    {
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
}
