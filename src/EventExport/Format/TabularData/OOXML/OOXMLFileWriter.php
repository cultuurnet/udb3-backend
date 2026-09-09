<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData\OOXML;

use CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriterInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OOXMLFileWriter implements TabularDataFileWriterInterface
{
    /**
     * Text only wraps inside the width of its column, and the default of about 8 characters would
     * turn a wrapped cell into a column of single words.
     */
    private const COLUMN_WIDTH = 40;

    private string $filePath;

    private int $i;

    private Spreadsheet $spreadsheet;

    /**
     * The columns that hold a wrapped cell, as column numbers.
     *
     * @var array<int, true>
     */
    private array $wrappedColumns = [];

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->setActiveSheetIndex(0);
        $this->i = 1;
    }

    /**
     * @param string[] $row
     */
    public function writeRow($row): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $sheet->fromArray(
            $row,
            '',
            'A' . $this->i
        );

        $this->wrapCellsWithNewlines($row);

        $this->i++;
    }

    /**
     * The faq column holds a line per question, and the description column keeps the line breaks of
     * its markup. Excel only shows those as line breaks when the cell wraps its text. Only such a
     * cell is wrapped, so that every value that renders on one line today keeps doing so.
     *
     * @param string[] $row
     */
    private function wrapCellsWithNewlines(array $row): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        foreach (array_values($row) as $index => $value) {
            if (!is_string($value) || !str_contains($value, "\n")) {
                continue;
            }

            $column = $index + 1;
            $this->wrappedColumns[$column] = true;

            // The row keeps its automatic height, so it grows to fit whatever the cell wraps to.
            $cell = Coordinate::stringFromColumnIndex($column) . $this->i;
            $alignment = $sheet->getStyle($cell)->getAlignment();
            $alignment->setWrapText(true);
            $alignment->setVertical(Alignment::VERTICAL_TOP);
        }
    }

    public function close(): void
    {
        $this->widenWrappedColumns();

        $objWriter = new Xlsx($this->spreadsheet);
        $objWriter->save($this->filePath);
    }

    /**
     * Widening happens once every row is known, because a column is only widened when a cell in it
     * turned out to need wrapping.
     */
    private function widenWrappedColumns(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        foreach (array_keys($this->wrappedColumns) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setWidth(self::COLUMN_WIDTH);
        }
    }
}
