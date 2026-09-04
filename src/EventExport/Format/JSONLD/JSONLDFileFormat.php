<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\FileFormatInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

final class JSONLDFileFormat implements FileFormatInterface
{
    /**
     * @var string[]
     */
    private ?array $include;

    private CalendarSummaryRepositoryInterface $calendarSummaryRepository;

    private ?DocumentRepository $placeRepository;

    /**
     * @param null|string[] $include
     */
    public function __construct(
        ?array $include = null,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository = null,
        ?DocumentRepository $placeRepository = null
    ) {
        $this->include = $include;
        $this->calendarSummaryRepository = $calendarSummaryRepository;
        $this->placeRepository = $placeRepository;
    }

    public function getFileNameExtension(): string
    {
        return 'json';
    }

    public function getWriter(): JSONLDFileWriter
    {
        return new JSONLDFileWriter($this->include, $this->calendarSummaryRepository, $this->placeRepository);
    }
}
