<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\TrimmedString;

class EventExportQuery extends TrimmedString
{
    /**
     * @inheritdoc
     */
    public function __construct($value)
    {
        parent::__construct($value);

        if ($this->isEmpty()) {
            throw new \InvalidArgumentException('Query can not be empty');
        }
    }
}
