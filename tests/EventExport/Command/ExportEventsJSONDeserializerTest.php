<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\SapiVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class ExportEventsJSONDeserializerTest extends TestCase
{
    /**
     * @var ExportEventsJSONDeserializer|MockObject
     */
    private $deserializer;

    public function setUp()
    {
        $this->deserializer = $this->getMockForAbstractClass(ExportEventsJSONDeserializer::class);
    }

    /**
     * @test
     */
    public function it_deserializes_a_minimal_export_command()
    {
        $data = new StringLiteral(
            $this->getJsonData('export_data_query.json')
        );

        $this->deserializer->expects($this->once())
            ->method('createCommand')
            ->with(
                new EventExportQuery('city:leuven'),
                null,
                null,
                null
            )
            ->willReturn(new \stdClass());

        $this->deserializer->deserialize($data);
    }

    /**
     * @test
     */
    public function it_deserializes_a_complete_export_command()
    {
        $data = new StringLiteral(
            $this->getJsonData('export_data.json')
        );

        $this->deserializer->expects($this->once())
            ->method('createCommand')
            ->with(
                new EventExportQuery('city:leuven'),
                new EmailAddress('foo@bar.com'),
                [
                    "8102d369-47c5-4ded-ad03-e12ef7b246c3",
                    "b5f6f86b-6e81-439b-b93d-68a95a356756",
                    "eb59c69c-2e29-4cbc-901f-d9076b38ca59",
                ],
                [
                    "name",
                    "image",
                    "address",
                ]
            )
            ->willReturn(new \stdClass());

        $this->deserializer->deserialize($data);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_query_is_missing()
    {
        $data = new StringLiteral('{"email":"foo@bar.com"}');
        $this->expectException(MissingValueException::class);
        $this->deserializer->deserialize($data);
    }

    /**
     * @test
     * @dataProvider commandDataProvider
     *
     * @param DeserializerInterface $deserializer
     * @param string                $expectedCommandType
     */
    public function it_can_create_different_command_types(
        DeserializerInterface $deserializer,
        $expectedCommandType
    ) {
        $data = new StringLiteral(
            $this->getJsonData('export_data.json')
        );

        $command = $deserializer->deserialize($data);

        $expectedCommand = new $expectedCommandType(
            new EventExportQuery('city:leuven'),
            new EmailAddress('foo@bar.com'),
            [
                "8102d369-47c5-4ded-ad03-e12ef7b246c3",
                "b5f6f86b-6e81-439b-b93d-68a95a356756",
                "eb59c69c-2e29-4cbc-901f-d9076b38ca59",
            ],
            [
                "name",
                "image",
                "address",
            ]
        );

        $this->assertEquals($expectedCommand, $command);
    }

    /**
     * @return array
     */
    public function commandDataProvider()
    {
        return [
            [
                new ExportEventsAsCSVJSONDeserializer(),
                ExportEventsAsCSV::class,
            ],
            [
                new ExportEventsAsJsonLDJSONDeserializer(),
                ExportEventsAsJsonLD::class,
            ],
            [
                new ExportEventsAsOOXMLJSONDeserializer(),
                ExportEventsAsOOXML::class,
            ],
        ];
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function getJsonData($fileName)
    {
        return file_get_contents(__DIR__.'/'.$fileName);
    }
}
