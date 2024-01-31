<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\CommandHandling\AsyncCommand;
use CultuurNet\UDB3\CommandHandling\AsyncCommandTrait;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\Sorting;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

/**
 * Base class for event export commands.
 */
abstract class ExportEvents implements ExportEventsInterface, AsyncCommand
{
    use AsyncCommandTrait;

    private EventExportQuery $query;

    private ?EmailAddress $address;

    /**
     * @var null|string[]
     */
    private ?array $selection;

    private ?Sorting $sorting;

    /**
     * @var string[]
     */
    private array $include;

    /**
     * @param string[] $include
     * @param string[]|null $selection
     */
    public function __construct(
        EventExportQuery $query,
        array $include,
        EmailAddress $address = null,
        ?array $selection = null,
        ?Sorting $sorting = null
    ) {
        $this->query = $query;
        $this->include = $include;
        $this->address = $address;
        $this->selection = $selection;
        $this->sorting = $sorting;
    }

    /**
     * @return EventExportQuery The query.
     */
    public function getQuery(): EventExportQuery
    {
        return $this->query;
    }

    public function getAddress(): ?EmailAddress
    {
        return $this->address;
    }

    /**
     * @return null|string[]
     */
    public function getSelection(): ?array
    {
        return $this->selection;
    }

    /**
     * @return string[]
     */
    public function getInclude(): array
    {
        return $this->include;
    }

    public function getSorting(): ?Sorting
    {
        return $this->sorting;
    }

    public function withSorting(Sorting $sorting): ExportEvents
    {
        $exportEvents = clone $this;
        $exportEvents->sorting = $sorting;

        return $exportEvents;
    }
}
