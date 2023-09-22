<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\CommandHandling\AsyncCommand;
use CultuurNet\UDB3\CommandHandling\AsyncCommandTrait;
use CultuurNet\UDB3\EventExport\EventExportQuery;
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
        ?array $selection = null
    ) {
        $this->query = $query;
        $this->include = $include;
        $this->address = $address;
        $this->selection = $selection;
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
}
