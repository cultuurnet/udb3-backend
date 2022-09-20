<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use PHPUnit\Framework\TestCase;

final class CalendarSummaryWithFormatterRepositoryTest extends TestCase
{
    private DocumentRepository $documentRepository;

    private CalendarSummaryRepositoryInterface $repository;

    public function setUp(): void
    {
        $this->documentRepository = new InMemoryDocumentRepository();
        $this->documentRepository->
        $this->repository = new CalendarSummaryWithFormatterRepository($this->documentRepository);
    }
}
