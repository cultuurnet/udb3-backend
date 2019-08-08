<?php

namespace CultuurNet\UDB3\Model\Import\PreProcessing;

use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Import\Event\EventLegacyBridgeCategoryResolver;
use PHPUnit\Framework\TestCase;

class TermPreProcessingDocumentImporterTest extends TestCase
{
    /**
     * @var DocumentImporterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $importer;

    /**
     * @var EventLegacyBridgeCategoryResolver
     */
    private $categoryResolver;

    /**
     * @var TermPreProcessingDocumentImporter
     */
    private $preProcessor;

    public function setUp()
    {
        $this->importer = $this->createMock(DocumentImporterInterface::class);
        $this->categoryResolver = new EventLegacyBridgeCategoryResolver();

        $this->preProcessor = new TermPreProcessingDocumentImporter(
            $this->importer,
            $this->categoryResolver
        );
    }

    /**
     * @test
     */
    public function it_should_supplement_missing_term_fields()
    {
        $data = $this->getRequiredEventJsonData();
        $document = $this->getDecodedDocument($this->getEventId(), $data);

        $expected = $data;
        $expected['terms'][0] = [
            'id' => '0.7.0.0.0',
            'label' => 'Begeleide rondleiding',
            'domain' => 'eventtype',
        ];
        $expectedDocument = $this->getDecodedDocument($this->getEventId(), $expected);
        $this->expectImportDocument($expectedDocument);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_missing_terms_property()
    {
        $data = $this->getRequiredEventJsonData();
        unset($data['terms']);
        $document = $this->getDecodedDocument($this->getEventId(), $data);

        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_incorrect_terms_property()
    {
        $data = $this->getRequiredEventJsonData();
        $data['terms'] = 'foo,bar';

        $document = $this->getDecodedDocument($this->getEventId(), $data);

        $this->expectImportDocument($document);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_terms_without_id()
    {
        $data = $this->getRequiredEventJsonData();
        $data['terms'][1] = ['label' => 'concert', 'domain' => 'eventtype'];

        $document = $this->getDecodedDocument($this->getEventId(), $data);

        $expected = $data;
        $expected['terms'][0] = [
            'id' => '0.7.0.0.0',
            'label' => 'Begeleide rondleiding',
            'domain' => 'eventtype',
        ];

        $expectedDocument = $this->getDecodedDocument($this->getEventId(), $expected);
        $this->expectImportDocument($expectedDocument);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_terms_with_an_invalid_id()
    {
        $data = $this->getRequiredEventJsonData();
        $data['terms'][1] = ['id' => 12345];

        $document = $this->getDecodedDocument($this->getEventId(), $data);

        $expected = $data;
        $expected['terms'][0] = [
            'id' => '0.7.0.0.0',
            'label' => 'Begeleide rondleiding',
            'domain' => 'eventtype',
        ];

        $expectedDocument = $this->getDecodedDocument($this->getEventId(), $expected);
        $this->expectImportDocument($expectedDocument);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_unknown_terms()
    {
        $data = $this->getRequiredEventJsonData();
        $data['terms'][1] = ['id' => '100.100.100.100.100'];

        $document = $this->getDecodedDocument($this->getEventId(), $data);

        $expected = $data;
        $expected['terms'][0] = [
            'id' => '0.7.0.0.0',
            'label' => 'Begeleide rondleiding',
            'domain' => 'eventtype',
        ];

        $expectedDocument = $this->getDecodedDocument($this->getEventId(), $expected);
        $this->expectImportDocument($expectedDocument);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_ignore_complete_terms()
    {
        $data = $this->getRequiredEventJsonData();
        $data['terms'][1] = [
            'id' => '0.6.0.0.0',
            'label' => 'Beurs',
            'domain' => 'eventtype',
        ];

        $document = $this->getDecodedDocument($this->getEventId(), $data);

        $expected = $data;
        $expected['terms'][0] = [
            'id' => '0.7.0.0.0',
            'label' => 'Begeleide rondleiding',
            'domain' => 'eventtype',
        ];

        $expectedDocument = $this->getDecodedDocument($this->getEventId(), $expected);
        $this->expectImportDocument($expectedDocument);

        $this->preProcessor->import($document);
    }

    /**
     * @test
     */
    public function it_should_correct_incorrect_terms()
    {
        $data = $this->getRequiredEventJsonData();
        $data['terms'][1] = [
            'id' => '0.6.0.0.0',
            'label' => 'Beurs INCORRECT',
            'domain' => 'eventtype INCORRECT',
        ];

        $document = $this->getDecodedDocument($this->getEventId(), $data);

        $expected = $data;
        $expected['terms'][0] = [
            'id' => '0.7.0.0.0',
            'label' => 'Begeleide rondleiding',
            'domain' => 'eventtype',
        ];
        $expected['terms'][1] = [
            'id' => '0.6.0.0.0',
            'label' => 'Beurs',
            'domain' => 'eventtype',
        ];

        $expectedDocument = $this->getDecodedDocument($this->getEventId(), $expected);
        $this->expectImportDocument($expectedDocument);

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

    private function getDecodedDocument($id, array $data)
    {
        return new DecodedDocument($id, $data);
    }

    private function expectImportDocument(DecodedDocument $decodedDocument)
    {
        $this->importer->expects($this->once())
            ->method('import')
            ->with($decodedDocument);
    }
}
