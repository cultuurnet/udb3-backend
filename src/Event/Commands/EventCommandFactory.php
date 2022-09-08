<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Commands\Moderation\Approve;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Event\Commands\Moderation\Reject;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateDescription;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractApprove;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsDuplicate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsInappropriate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractReject;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\StringLiteral;

class EventCommandFactory implements OfferCommandFactoryInterface
{
    public function createUpdateDescriptionCommand(string $id, Language $language, Description $description): AbstractUpdateDescription
    {
        return new UpdateDescription($id, $language, $description);
    }

    public function createApproveCommand(string $id): AbstractApprove
    {
        return new Approve($id);
    }

    public function createRejectCommand(string $id, StringLiteral $reason): AbstractReject
    {
        return new Reject($id, $reason);
    }

    public function createFlagAsInappropriate(string $id): AbstractFlagAsInappropriate
    {
        return new FlagAsInappropriate($id);
    }

    public function createFlagAsDuplicate(string $id): AbstractFlagAsDuplicate
    {
        return new FlagAsDuplicate($id);
    }
}
