<?php

namespace CultuurNet\UDB3\Symfony\Organizer;

use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Symfony\Component\HttpFoundation\Response;

class ReadOrganizerRestControllerTest extends \PHPUnit_Framework_TestCase
{
    const EXISTING_ID = 'existingId';
    const NON_EXISTING_ID = 'nonExistingId';
    const REMOVED_ID = 'removedId';

    /**
     * @var EntityServiceInterface|\PHPUnit_Framework_MockObject_MockObject
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
                        case self::REMOVED_ID:
                            throw new DocumentGoneException();
                        default:
                            return null;
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

    /**
     *
     */
    public function it_returns_a_http_response_with_error_NOT_FOUND_for_a_non_existing_organizer()
    {
        $jsonResponse = $this->organizerController->get(self::NON_EXISTING_ID);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $jsonResponse->getStatusCode());
    }
}
