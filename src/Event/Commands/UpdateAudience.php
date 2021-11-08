<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class UpdateAudience extends AbstractCommand
{
    private AudienceType $audienceType;

    public function __construct(
        string $itemId,
        AudienceType $audience
    ) {
        parent::__construct($itemId);

        $this->audienceType = $audience;
    }

    public function getAudienceType(): AudienceType
    {
        return $this->audienceType;
    }
}
