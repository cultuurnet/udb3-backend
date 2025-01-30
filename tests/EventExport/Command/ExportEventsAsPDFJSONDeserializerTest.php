<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Footer;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Publisher;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Subtitle;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\SampleFiles;
use CultuurNet\UDB3\Search\Sorting;
use PHPUnit\Framework\TestCase;

class ExportEventsAsPDFJSONDeserializerTest extends TestCase
{
    private ExportEventsAsPDFJSONDeserializer $deserializer;

    public function setUp(): void
    {
        $this->deserializer = new ExportEventsAsPDFJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_returns_a_PDF_export_command(): void
    {
        $exportData = $this->getJSONStringFromFile('minimum_export_pdf_data.json');
        $command = $this->deserializer->deserialize($exportData);

        $this->assertInstanceOf(ExportEventsAsPDF::class, $command);

        $this->assertEquals(
            (new ExportEventsAsPDF(
                new EventExportQuery('city:doetown'),
                'vlieg',
                'http://foo.bar/logo.svg',
                new Title('a title'),
                WebArchiveTemplate::tips()
            ))->withSorting(new Sorting('availableTo', 'asc')),
            $command
        );
    }

    /**
     * @test
     */
    public function it_expects_a_query_property(): void
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data_without_query.json');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('query is missing');
        $this->deserializer->deserialize($exportData);
    }

    /**
     * @test
     */
    public function it_expects_a_brand_property(): void
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data_without_brand.json');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('brand is missing');
        $this->deserializer->deserialize($exportData);
    }

    /**
     * @test
     */
    public function it_expects_a_title_property(): void
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data_without_title.json');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('title is missing');
        $this->deserializer->deserialize($exportData);
    }

    /**
     * @test
     */
    public function it_expects_sorting_to_contain_property(): void
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data_without_sorting_property.json');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage("order is incomplete. You should provide a 'property' key.");
        $this->deserializer->deserialize($exportData);
    }

    /**
     * @test
     */
    public function it_includes_default_sorting_order(): void
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data_without_sorting_order.json');
        $command = $this->deserializer->deserialize($exportData);

        $this->assertEquals(new Sorting('availableTo', Sorting::DEFAULT_ORDER), $command->getSorting());
    }

    /**
     * @test
     */
    public function it_includes_optional_properties(): void
    {
        $exportData = $this->getJSONStringFromFile('export_pdf_data.json');
        $command = $this->deserializer->deserialize($exportData);

        $this->assertEquals(new Subtitle('a subtitle'), $command->getSubtitle());
        $this->assertEquals(new Publisher('a publisher'), $command->getPublisher());
        $this->assertEquals(new Footer('a footer'), $command->getFooter());
        $this->assertEquals(new EmailAddress('john@doe.com'), $command->getAddress());
        $this->assertEquals(WebArchiveTemplate::map(), $command->getTemplate());
        $this->assertEquals(new Sorting('availableTo', 'asc'), $command->getSorting());
    }

    /**
     * Test property provider
     * property, value, getter
     */
    public function exportPropertyDataProvider(): array
    {
        return [
            ['subtitle', new Subtitle('a subtitle'), 'getSubtitle'],
            ['publisher', new Publisher('a publisher'), 'getPublisher'],
            ['footer', new Footer('a footer'), 'getFooter'],
            ['email', new EmailAddress('john@doe.com'), 'getAddress'],
        ];
    }

    private function getJSONStringFromFile(string $fileName): string
    {
        return SampleFiles::read(__DIR__ . '/' . $fileName);
    }
}
