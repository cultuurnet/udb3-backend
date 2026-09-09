<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData\OOXML;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;

final class OOXMLFileWriterTest extends TestCase
{
    private const FAQ = "Hoe geraak ik er? Met de bus.\nWat kost het? 10 euro.";

    private string $filePath;

    protected function setUp(): void
    {
        $this->filePath = tempnam(sys_get_temp_dir(), uniqid()) . '.xlsx';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    /**
     * @test
     */
    public function it_keeps_a_value_with_newlines_in_a_single_cell(): void
    {
        $sheet = $this->write(['id', 'titel', 'faq'], ['1', 'Concert', self::FAQ]);

        $this->assertSame(
            [
                ['id', 'titel', 'faq'],
                ['1', 'Concert', self::FAQ],
            ],
            $sheet->toArray()
        );
    }

    /**
     * @test
     */
    public function it_wraps_the_text_of_a_cell(): void
    {
        $sheet = $this->write(['id', 'faq'], ['1', self::FAQ]);

        $alignment = $sheet->getStyle('B2')->getAlignment();

        $this->assertTrue($alignment->getWrapText());
        $this->assertSame('top', $alignment->getVertical());
    }

    /**
     * @test
     */
    public function it_widens_every_column_of_the_header(): void
    {
        $sheet = $this->write(['id', 'titel', 'faq'], ['1', 'Concert', self::FAQ]);

        foreach (['A', 'B', 'C'] as $column) {
            $this->assertSame(40.0, $sheet->getColumnDimension($column)->getWidth());
        }
    }

    private function write(array ...$rows): Worksheet
    {
        $writer = new OOXMLFileWriter($this->filePath);

        foreach ($rows as $row) {
            $writer->writeRow($row);
        }

        $writer->close();

        return (new XlsxReader())->load($this->filePath)->getActiveSheet();
    }
}
