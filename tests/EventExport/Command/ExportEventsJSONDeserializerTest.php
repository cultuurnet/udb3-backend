<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportEventsJSONDeserializerTest extends TestCase
{
    /**
     * @var ExportEventsJSONDeserializer&MockObject
     */
    private $deserializer;

    public function setUp(): void
    {
        $this->deserializer = $this->getMockForAbstractClass(ExportEventsJSONDeserializer::class);
    }

    /**
     * @test
     */
    public function it_deserializes_a_minimal_export_command(): void
    {
        $data = $this->getJsonData('export_data_query.json');

        $this->deserializer->expects($this->once())
            ->method('createCommand')
            ->with(
                new EventExportQuery('city:leuven'),
                null,
                null,
                null
            )
            ->willReturn(new ExportEventsAsOOXML(new EventExportQuery('city:leuven'), []));

        $this->deserializer->deserialize($data);
    }

    /**
     * @test
     */
    public function it_deserializes_a_complete_export_command(): void
    {
        $data = $this->getJsonData('export_data.json');

        $this->deserializer->expects($this->once())
            ->method('createCommand')
            ->with(
                new EventExportQuery('city:leuven'),
                [
                    'name',
                    'image',
                    'address',
                ],
                new EmailAddress('foo@bar.com'),
                [
                    '8102d369-47c5-4ded-ad03-e12ef7b246c3',
                    'b5f6f86b-6e81-439b-b93d-68a95a356756',
                    'eb59c69c-2e29-4cbc-901f-d9076b38ca59',
                ]
            )
            ->willReturn(new ExportEventsAsOOXML(new EventExportQuery('city:leuven'), []));

        $this->deserializer->deserialize($data);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_query_is_missing(): void
    {
        $data = '{"email":"foo@bar.com"}';
        $this->expectException(MissingValueException::class);
        $this->deserializer->deserialize($data);
    }

    /**
     * @test
     * @dataProvider commandDataProvider
     */
    public function it_can_create_different_command_types(
        DeserializerInterface $deserializer,
        string $expectedCommandType
    ): void {
        $data = $this->getJsonData('export_data.json');

        $command = $deserializer->deserialize($data);

        $expectedCommand = new $expectedCommandType(
            new EventExportQuery('city:leuven'),
            [
                'name',
                'image',
                'address',
            ],
            new EmailAddress('foo@bar.com'),
            [
                '8102d369-47c5-4ded-ad03-e12ef7b246c3',
                'b5f6f86b-6e81-439b-b93d-68a95a356756',
                'eb59c69c-2e29-4cbc-901f-d9076b38ca59',
            ]
        );

        $this->assertEquals($expectedCommand, $command);
    }

    public function commandDataProvider(): array
    {
        return [
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

    private function getJsonData(string $fileName): string
    {
        return SampleFiles::read(__DIR__ . '/' . $fileName);
    }
}
