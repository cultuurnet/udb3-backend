<?php

namespace CultuurNet\UDB3\Model\Import\PreProcessing;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class LocationPreProcessingDocumentImporterTest extends TestCase
{
    /**
     * @var DocumentImporterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $importer;

    /**
     * @var PlaceIDParser
     */
    private $placeIdParser;

    /**
     * @var DocumentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $placeDocumentRepository;

    /**
     * @var TermPreProcessingDocumentImporter
     */
    private $preProcessor;

    public function setUp()
    {
        $this->importer = $this->createMock(DocumentImporterInterface::class);
        $this->placeIdParser = new PlaceIDParser();
        $this->placeDocumentRepository = $this->createMock(DocumentRepositoryInterface::class);

        $this->preProcessor = new LocationPreProcessingDocumentImporter(
            $this->importer,
            $this->placeIdParser,
            $this->placeDocumentRepository
        );
    }

    /**
     * @test
     */
    public function it_should_supplement_location_data()
    {
        $placeData = $this->getRequiredPlaceJsonData();
        $placeDocument = $this->getDecodedDocument($this->getPlaceId(), $placeData);
        $this->expectPlace($placeDocument->toJsonDocument());

        $eventData = $this->getRequiredEventJsonData();
        $eventDocument = $this->getDecodedDocument($this->getEventId(), $eventData);

        $expectedData = $eventData;
        $expectedData['location'] = $placeData;

        $expectedDocument = $this->getDecodedDocument($this->getEventId(), $expectedData);

        $this->expectImportDocument($expectedDocument);

        $this->preProcessor->import($eventDocument);
    }

    /**
     * @test
     */
    public function it_should_ignore_missing_location_property()
    {
        $eventData = $this->getRequiredEventJsonData();
        unset($eventData['location']);

        $document = $this->getDecodedDocument($this->getEventId(), $eventData);

        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_invalid_location_property()
    {
        $eventData = $this->getRequiredEventJsonData();
        $eventData['location'] = $this->getPlaceId();

        $document = $this->getDecodedDocument($this->getEventId(), $eventData);

        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_location_without_id()
    {
        $eventData = $this->getRequiredEventJsonData();
        $eventData['location'] = ['name' => ['nl' => 'Foo bar']];

        $document = $this->getDecodedDocument($this->getEventId(), $eventData);

        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_location_with_non_string_id()
    {
        $eventData = $this->getRequiredEventJsonData();
        $eventData['location'] = ['@id' => 123456];

        $document = $this->getDecodedDocument($this->getEventId(), $eventData);

        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_location_with_non_url_id()
    {
        $eventData = $this->getRequiredEventJsonData();
        $eventData['location'] = ['@id' => $this->getPlaceId()];

        $document = $this->getDecodedDocument($this->getEventId(), $eventData);

        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_location_with_incorrect_url_id()
    {
        $eventData = $this->getRequiredEventJsonData();
        $eventData['location'] = ['@id' => 'http://io.uitdatabank.be/events/' . $this->getPlaceId()];

        $document = $this->getDecodedDocument($this->getEventId(), $eventData);

        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_location_with_unknown_id()
    {
        $eventData = $this->getRequiredEventJsonData();
        $document = $this->getDecodedDocument($this->getEventId(), $eventData);

        $this->expectNoPlaceFound();
        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_location_with_invalid_stored_json_document()
    {
        $eventData = $this->getRequiredEventJsonData();
        $document = $this->getDecodedDocument($this->getEventId(), $eventData);

        $this->expectPlaceDeleted();
        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @return array
     */
    private function getRequiredEventJsonData()
    {
        return [
            '@id' => 'https://io.uitdatabank.be/events/c33b4498-0932-4fbe-816f-c6641f30ba3b',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Voorbeeld naam',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-01-01T12:00:00+01:00',
            'endDate' => '2018-01-01T17:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.7.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/places/f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0',
            ],
        ];
    }

    private function getEventId()
    {
        return 'c33b4498-0932-4fbe-816f-c6641f30ba3b';
    }

    /**
     * @return array
     */
    private function getRequiredPlaceJsonData()
    {
        return [
            '@id' => 'https://io.uitdatabank.be/places/f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Voorbeeld naam',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.14.0.0.0',
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];
    }

    private function getPlaceId()
    {
        return 'c33b4498-0932-4fbe-816f-c6641f30ba3b';
    }

    private function expectPlace(JsonDocument $jsonDocument)
    {
        $this->placeDocumentRepository->expects($this->any())
            ->method('get')
            ->with('f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0')
            ->willReturn($jsonDocument);
    }

    private function expectNoPlaceFound()
    {
        $this->placeDocumentRepository->expects($this->any())
            ->method('get')
            ->with('f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0')
            ->willReturn(null);
    }

    private function expectPlaceDeleted()
    {
        $this->placeDocumentRepository->expects($this->any())
            ->method('get')
            ->with('f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0')
            ->willThrowException(new DocumentGoneException());
    }

    private function expectImportDocument(DecodedDocument $decodedDocument)
    {
        $this->importer->expects($this->once())
            ->method('import')
            ->with($decodedDocument);
    }

    private function getDecodedDocument($id, array $data)
    {
        return new DecodedDocument($id, $data);
    }
}
