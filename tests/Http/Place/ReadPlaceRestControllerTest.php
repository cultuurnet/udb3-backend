<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ReadPlaceRestControllerTest extends TestCase
{
    private const EXISTING_ID = 'existingId';
    private const NON_EXISTING_ID = 'nonExistingId';

    /**
     * @var ReadPlaceRestController
     */
    private $placeRestController;

    /**
     * @var JsonDocument
     */
    private $jsonDocument;

    /**
     * @var JsonDocument
     */
    private $jsonDocumentWithMetadata;

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
            'existingId',
            json_encode(
                [
                    '@context' => '/contexts/place',
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
            'existingId',
            json_encode(
                [
                    '@type' => 'Place',
                    'metadata' => [
                        'popularity' => 123456,
                    ],
                ]
            )
        );

        $this->calSum = 'Zondag 7 oktober 2018 van 12:15 tot 18:00 (Volzet of uitverkocht)';

        /** @var DocumentRepository|MockObject $jsonRepository */
        $jsonRepository = $this->createMock(DocumentRepository::class);
        $jsonRepository->method('fetch')
            ->willReturnCallback(
                function (string $id, bool $includeMetadata = false) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return $includeMetadata ? $this->jsonDocumentWithMetadata : $this->jsonDocument;
                        default:
                            throw DocumentDoesNotExist::withId($id);
                    }
                }
            );

        $this->placeRestController = new ReadPlaceRestController($jsonRepository);
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_a_calendar_summary_for_a_place(): void
    {
        $request = new Request(['style' => 'text', 'format' => 'lg']);
        $calSumResponse = $this->placeRestController->getCalendarSummary(self::EXISTING_ID, $request);

        $this->assertEquals($this->calSum, $calSumResponse->getContent());
    }
}
