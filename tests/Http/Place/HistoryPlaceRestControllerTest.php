<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Http\Management\User\UserIdentificationInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HistoryPlaceRestControllerTest extends TestCase
{
    public const EXISTING_ID = 'existingId';
    public const NON_EXISTING_ID = 'nonExistingId';
    public const REMOVED_ID = 'removedId';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UserIdentificationInterface
     */
    private $userIdentification;

    /**
     * @var HistoryPlaceRestController
     */
    private $controller;

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

        $documentRepositoryInterface = $this->createMock(DocumentRepository::class);
        $documentRepositoryInterface->method('get')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return new JsonDocument('id', $this->rawHistory);
                        case self::REMOVED_ID:
                            throw new DocumentGoneException();
                        default:
                            return null;
                    }
                }
            );

        $this->userIdentification = $this->createMock(UserIdentificationInterface::class);
        /** @var DocumentRepository $documentRepositoryInterface */
        $this->controller = new HistoryPlaceRestController(
            $documentRepositoryInterface,
            $this->userIdentification
        );
    }

    /**
     * @test
     */
    public function returns_a_http_response_with_json_history_for_an_event(): void
    {
        $this->givenGodUser();
        $jsonResponse = $this->controller->get(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_OK, $jsonResponse->getStatusCode());
        $this->assertEquals($this->expectedProcessedHistory, $jsonResponse->getContent());
    }

    /**
     * @test
     */
    public function returns_a_http_response_with_error_NOT_FOUND_for_a_non_existing_event(): void
    {
        $this->givenGodUser();
        $jsonResponse = $this->controller->get(self::NON_EXISTING_ID);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $jsonResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function returns_a_http_response_with_error_HTTP_GONE_for_a_removed_event(): void
    {
        $this->givenGodUser();
        $jsonResponse = $this->controller->get(self::REMOVED_ID);

        $this->assertEquals(Response::HTTP_GONE, $jsonResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function returns_a_http_response_with_error_FORBIDDEN_for_a_regular_user(): void
    {
        $this->givenRegularUser();
        $jsonResponse = $this->controller->get(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $jsonResponse->getStatusCode());
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
