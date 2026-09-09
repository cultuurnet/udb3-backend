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
     * @var int[]
     */
    private array $wrappedColumns;

    /**
     * @param int[] $wrappedColumns
     *   The columns whose value holds more than one line, as column numbers. Excel only shows a
     *   newline inside a cell as a line break when the cell wraps its text, so those columns are
     *   wrapped and widened. Every other column is left exactly as it was.
     */
    public function __construct(string $filePath, array $wrappedColumns = [])
    {
        $this->filePath = $filePath;
        $this->wrappedColumns = $wrappedColumns;
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->setActiveSheetIndex(0);
        $this->i = 1;

        $this->widenWrappedColumns();
    }

    /**
     * @param string[] $row
     */
    public function writeRow(array $row): void
    {
        $this->spreadsheet->getActiveSheet()->fromArray(
            $row,
            '',
            'A' . $this->i
        );

        $this->wrapCells();

        $this->i++;
    }

    private function wrapCells(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        foreach ($this->wrappedColumns as $column) {
            $cell = Coordinate::stringFromColumnIndex($column) . $this->i;

            // The row keeps its automatic height, so it grows to fit whatever the cell wraps to.
            $alignment = $sheet->getStyle($cell)->getAlignment();
            $alignment->setWrapText(true);
            $alignment->setVertical(Alignment::VERTICAL_TOP);
        }
    }

    private function widenWrappedColumns(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        foreach ($this->wrappedColumns as $column) {
            $sheet->getColumnDimensionByColumn($column)->setWidth(self::COLUMN_WIDTH);
        }
    }

    public function close(): void
    {
        $objWriter = new Xlsx($this->spreadsheet);
        $objWriter->save($this->filePath);
    }
}
