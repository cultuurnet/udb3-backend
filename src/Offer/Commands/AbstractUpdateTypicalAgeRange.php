<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;

abstract class AbstractUpdateTypicalAgeRange extends AbstractCommand
{
    protected AgeRange $typicalAgeRange;

    public function __construct(string $itemId, AgeRange $typicalAgeRange)
    {
        parent::__construct($itemId);
        $this->typicalAgeRange = $typicalAgeRange;
    }

    public function getTypicalAgeRange(): AgeRange
    {
        return $this->typicalAgeRange;
    }
}
