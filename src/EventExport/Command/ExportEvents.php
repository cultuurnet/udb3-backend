<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use ValueObjects\Web\EmailAddress;

/**
 * Base class for event export commands.
 */
abstract class ExportEvents implements ExportEventsInterface
{
    /**
     * @var EventExportQuery
     */
    private $query;

    /**
     * @var null|EmailAddress
     */
    private $address;

    /**
     * @var null|string[]
     */
    private $selection;

    /**
     * @var null|string[]
     */
    private $include;

    /**
     * @param string[]          $selection
     * @param string[]          $include
     */
    public function __construct(
        EventExportQuery $query,
        EmailAddress $address = null,
        $selection = null,
        $include = null
    ) {
        if ($query->isEmpty()) {
            throw new \RuntimeException('Query can not be empty');
        }

        $this->query = $query;
        $this->address = $address;
        $this->selection = $selection;

        $this->include = $include;
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
     * @return null|string[]
     */
    public function getInclude(): ?array
    {
        return $this->include;
    }
}
