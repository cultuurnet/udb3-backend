<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\SapiVersion;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class ExportEventsJSONDeserializerTest extends TestCase
{
    /**
     * @var ExportEventsJSONDeserializer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deserializer;

    /**
     * @var SapiVersion
     */
    private $sapiVersion;

    public function setUp()
    {
        $this->sapiVersion = new SapiVersion(SapiVersion::V2);

        $this->deserializer = $this->getMockForAbstractClass(
            ExportEventsJSONDeserializer::class,
            [
                $this->sapiVersion,
            ]
        );
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
                $this->sapiVersion,
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
                $this->sapiVersion,
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
            $this->sapiVersion,
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
                new ExportEventsAsCSVJSONDeserializer(
                    new SapiVersion(SapiVersion::V2)
                ),
                ExportEventsAsCSV::class,
            ],
            [
                new ExportEventsAsJsonLDJSONDeserializer(
                    new SapiVersion(SapiVersion::V2)
                ),
                ExportEventsAsJsonLD::class,
            ],
            [
                new ExportEventsAsOOXMLJSONDeserializer(
                    new SapiVersion(SapiVersion::V2)
                ),
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
        return file_get_contents(__DIR__ . '/' . $fileName);
    }
}
