<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractReject extends AbstractModerationCommand
{
    /**
     * The reason why an offer is rejected, e.g.: Image and price info is missing.
     *
     * @var StringLiteral
     */
    private $reason;

    /**
     * @param string $itemId
     *  The id of the item that is targeted by the command.
     *
     * @param StringLiteral $reason
     *  The reason why an offer is rejected, e.g.: Image and price info is missing.
     */
    public function __construct($itemId, StringLiteral $reason)
    {
        parent::__construct($itemId);
        $this->reason = $reason;
    }

    /**
     * @return StringLiteral
     */
    public function getReason()
    {
        return $this->reason;
    }
}
