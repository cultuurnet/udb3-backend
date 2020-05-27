<?php

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class UpdateAudience extends AbstractCommand
{
    /**
     * @var Audience
     */
    private $audience;

    /**
     * UpdateAudience constructor.
     * @param string $itemId
     * @param Audience $audience
     */
    public function __construct(
        $itemId,
        Audience $audience
    ) {
        parent::__construct($itemId);

        $this->audience = $audience;
    }

    /**
     * @return Audience
     */
    public function getAudience()
    {
        return $this->audience;
    }
}
