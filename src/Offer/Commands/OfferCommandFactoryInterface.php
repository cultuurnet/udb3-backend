<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractApprove;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsDuplicate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsInappropriate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractReject;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\Language;

interface OfferCommandFactoryInterface
{
    public function createUpdateDescriptionCommand(string $id, Language $language, Description $description): AbstractUpdateDescription;

    public function createApproveCommand(string $id): AbstractApprove;

    public function createRejectCommand(string $id, StringLiteral $reason): AbstractReject;

    public function createFlagAsInappropriate(string $id): AbstractFlagAsInappropriate;

    public function createFlagAsDuplicate(string $id): AbstractFlagAsDuplicate;
}
