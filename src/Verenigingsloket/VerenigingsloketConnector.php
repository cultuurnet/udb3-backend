<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Verenigingsloket;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Verenigingsloket\Enum\VerenigingsloketConnectionStatus;
use CultuurNet\UDB3\Verenigingsloket\Result\VerenigingsloketConnectionResult;

interface VerenigingsloketConnector
{
    public function fetchVerenigingsloketConnectionForOrganizer(Uuid $organizerId, VerenigingsloketConnectionStatus $status): ?VerenigingsloketConnectionResult;
    public function breakConnectionFromVerenigingsloket(Uuid $organizerId, string $userId): bool;
}
