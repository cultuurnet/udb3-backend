<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Services;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\StringLiteral;

interface ReadServiceInterface
{
    public function getByUuid(UUID $uuid): ?Entity;

    public function getByName(StringLiteral $identifier): ?Entity;

    /**
     * @return Entity[]|null
     */
    public function search(Query $query): ?array;

    public function searchTotalLabels(Query $query): int;
}
