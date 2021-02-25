<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Footer;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Publisher;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Subtitle;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class ExportEventsAsPDFJSONDeserializerTest extends TestCase
{
    /**
     * @var ExportEventsAsPDFJSONDeserializer
     */
    private $deserializer;

    public function setUp()
    {
        $this->deserializer = new ExportEventsAsPDFJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_returns_a_PDF_export_command()
    {
        $exportData = $this->getJSONStringFromFile('minimum_export_pdf_data.json');
        $command = $this->deserializer->deserialize($exportData);

        $this->assertInstanceOf(ExportEventsAsPDF::class, $command);

        $this->assertEquals(
            new ExportEventsAsPDF(
                new EventExportQuery('city:doetown'),
                'vlieg',
                'http://foo.bar/logo.svg',
                new Title('a title'),
                WebArchiveTemplate::TIPS()
            ),
            $command
        );
    }

    /**
     * @test
     */
    public function it_expects_a_query_property()
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data_without_query.json');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('query is missing');
        $this->deserializer->deserialize($exportData);
    }

    /**
     * @test
     */
    public function it_expects_a_brand_property()
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data_without_brand.json');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('brand is missing');
        $this->deserializer->deserialize($exportData);
    }

    /**
     * @test
     */
    public function it_expects_a_title_property()
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data_without_title.json');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('title is missing');
        $this->deserializer->deserialize($exportData);
    }

    /**
     * @test
     */
    public function it_includes_optional_properties()
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data.json');
        $command = $this->deserializer->deserialize($exportData);

        $this->assertEquals(new Subtitle('a subtitle'), $command->getSubtitle());
        $this->assertEquals(new Publisher('a publisher'), $command->getPublisher());
        $this->assertEquals(new Footer('a footer'), $command->getFooter());
        $this->assertEquals(new EmailAddress('john@doe.com'), $command->getAddress());
        $this->assertEquals(WebArchiveTemplate::MAP(), $command->getTemplate());
    }

    /**
     * Test property provider
     * property, value, getter
     */
    public function exportPropertyDataProvider()
    {
        return [
            ['subtitle', new Subtitle('a subtitle'), 'getSubtitle'],
            ['publisher', new Publisher('a publisher'), 'getPublisher'],
            ['footer', new Footer('a footer'), 'getFooter'],
            ['email', new EmailAddress('john@doe.com'), 'getAddress'],
        ];
    }

    private function getJSONStringFromFile($fileName)
    {
        $json = file_get_contents(
            __DIR__.'/'.$fileName
        );

        return new StringLiteral($json);
    }
}
