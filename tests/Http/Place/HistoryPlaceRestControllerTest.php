<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Http\Management\User\UserIdentificationInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HistoryPlaceRestControllerTest extends TestCase
{
    const EXISTING_ID = 'existingId';
    const NON_EXISTING_ID = 'nonExistingId';
    const REMOVED_ID = 'removedId';

    private const RAW_HISTORY = 'history';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UserIdentificationInterface
     */
    private $userIdentification;

    /**
     * @var HistoryPlaceRestController
     */
    private $controller;

    public function setUp()
    {
        $documentRepositoryInterface = $this->createMock(DocumentRepositoryInterface::class);
        $documentRepositoryInterface->method('get')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return new JsonDocument('id', self::RAW_HISTORY);
                        case self::REMOVED_ID:
                            throw new DocumentGoneException();
                        default:
                            return null;
                    }
                }
            );

        $this->userIdentification = $this->createMock(UserIdentificationInterface::class);
        /** @var DocumentRepositoryInterface $documentRepositoryInterface */
        $this->controller = new HistoryPlaceRestController(
            $documentRepositoryInterface,
            $this->userIdentification
        );

    }

    /**
     * @test
     */
    public function returns_a_http_response_with_json_history_for_an_event_2(): void
    {
        $this->givenGodUser();
        $jsonResponse = $this->controller->get(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_OK, $jsonResponse->getStatusCode());
        $this->assertEquals(self::RAW_HISTORY, $jsonResponse->getContent());
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
