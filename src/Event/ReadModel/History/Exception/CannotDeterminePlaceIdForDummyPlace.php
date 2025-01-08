<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History\Exception;

class CannotDeterminePlaceIdForDummyPlace extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Could not determine the old place uuid because it was a dummy place.');
    }
}
