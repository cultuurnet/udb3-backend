<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Services;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface ReadServiceInterface
{
    /**
     * @return Entity|null
     */
    public function getByUuid(UUID $uuid);

    /**
     * @return Entity|null
     */
    public function getByName(StringLiteral $identifier);

    /**
     * @return Entity[]|null
     */
    public function search(Query $query);

    public function searchTotalLabels(Query $query): int;
}
