<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData\OOXML;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;

final class OOXMLFileWriterTest extends TestCase
{
    private const FAQ = "Hoe geraak ik er? Met de bus.\nWat kost het? 10 euro.";

    /**
     * A description keeps the line breaks that StripHtmlStringFilter writes for its markup.
     */
    private const DESCRIPTION = "Eerste alinea.\n\nTweede alinea.\n\nDerde alinea.";

    private string $filePath;

    protected function setUp(): void
    {
        $this->filePath = sys_get_temp_dir() . '/' . uniqid('ooxml', true) . '.xlsx';
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
        $sheet = $this->write([3], ['id', 'titel', 'faq'], ['1', 'Concert', self::FAQ]);

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
    public function it_wraps_and_widens_a_wrapping_column(): void
    {
        $sheet = $this->write([2], ['id', 'faq'], ['1', self::FAQ]);

        $alignment = $sheet->getStyle('B2')->getAlignment();

        $this->assertTrue($alignment->getWrapText());
        $this->assertSame('top', $alignment->getVertical());
        $this->assertSame(40.0, $sheet->getColumnDimension('B')->getWidth());
    }

    /**
     * @test
     */
    public function it_leaves_every_other_column_alone(): void
    {
        $sheet = $this->write([2], ['id', 'faq'], ['1', self::FAQ]);

        $this->assertFalse($sheet->getStyle('A2')->getAlignment()->getWrapText());
        $this->assertNotSame(40.0, $sheet->getColumnDimension('A')->getWidth());
    }

    /**
     * A description holds newlines of its own, and has always been shown as a single run of text.
     * Only a column that is named as wrapping is wrapped, so that stays true.
     *
     * @test
     */
    public function it_leaves_a_column_that_holds_newlines_of_its_own_alone(): void
    {
        $sheet = $this->write(
            [3],
            ['id', 'omschrijving', 'faq'],
            ['1', self::DESCRIPTION, self::FAQ]
        );

        $this->assertFalse($sheet->getStyle('B2')->getAlignment()->getWrapText());
        $this->assertNotSame(40.0, $sheet->getColumnDimension('B')->getWidth());
        $this->assertSame(self::DESCRIPTION, $sheet->getCell('B2')->getValue());
    }

    /**
     * @test
     */
    public function it_wraps_nothing_when_no_column_wraps(): void
    {
        $sheet = $this->write([], ['id', 'omschrijving'], ['1', self::DESCRIPTION]);

        $this->assertFalse($sheet->getStyle('B2')->getAlignment()->getWrapText());
        $this->assertNotSame(40.0, $sheet->getColumnDimension('B')->getWidth());
    }

    /**
     * @param int[] $wrappedColumns
     */
    private function write(array $wrappedColumns, array ...$rows): Worksheet
    {
        $writer = new OOXMLFileWriter($this->filePath, $wrappedColumns);

        foreach ($rows as $row) {
            $writer->writeRow($row);
        }

        $writer->close();

        return (new XlsxReader())->load($this->filePath)->getActiveSheet();
    }
}
