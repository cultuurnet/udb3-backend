<?php

namespace CultuurNet\UDB3\Offer\Commands;

abstract class AbstractUpdateTypicalAgeRange extends AbstractCommand
{
    /**
     * @var string
     */
    protected $typicalAgeRange;

    /**
     * UpdateTypicalAgeRange constructor.
     * @param string $itemId
     * @param string $typicalAgeRange
     */
    public function __construct($itemId, $typicalAgeRange)
    {
        parent::__construct($itemId);
        $this->typicalAgeRange = $typicalAgeRange;
    }

    /**
     * @return string
     */
    public function getTypicalAgeRange()
    {
        return $this->typicalAgeRange;
    }
}
