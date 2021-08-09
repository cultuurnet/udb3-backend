<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ReadOrganizerRestControllerTest extends TestCase
{
    public const EXISTING_ID = 'existingId';
    public const NON_EXISTING_ID = 'nonExistingId';

    /**
     * @var EntityServiceInterface|MockObject
     */
    private $service;

    /**
     * @var ReadOrganizerRestController
     */
    private $organizerController;

    /**
     * @var JsonDocument
     */
    private $jsonDocument;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->jsonDocument = new JsonDocument('id', 'organizer');

        $this->service = $this->createMock(EntityServiceInterface::class);

        $this->service->method('getEntity')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return $this->jsonDocument->getRawBody();
                        default:
                            throw DocumentDoesNotExist::withId($id);
                    }
                }
            );

        $this->organizerController = new ReadOrganizerRestController(
            $this->service
        );
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_json_document_for_an_organizer()
    {
        $jsonResponse = $this->organizerController->get(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_OK, $jsonResponse->getStatusCode());
        $this->assertEquals($this->jsonDocument->getRawBody(), $jsonResponse->getContent());
    }


    public function it_throws_an_api_problem_exception_for_a_non_existing_organizer()
    {
        $this->expectException(ApiProblem::class);
        $this->organizerController->get(self::NON_EXISTING_ID);
    }
}
