<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

abstract class AbstractReject extends AbstractModerationCommand
{
    /**
     * The reason why an offer is rejected, e.g.: Image and price info is missing.
     */
    private string $reason;

    /**
     * @param string $itemId
     *  The id of the item that is targeted by the command.
     *
     * @param string $reason
     *  The reason why an offer is rejected, e.g.: Image and price info is missing.
     */
    public function __construct(string $itemId, string $reason)
    {
        parent::__construct($itemId);
        $this->reason = $reason;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
