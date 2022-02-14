<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface ReadRepositoryInterface
{
    /**
     * @return Entity|null
     */
    public function getByUuid(UUID $uuid);

    /**
     * @return Entity|null
     */
    public function getByName(StringLiteral $name);

    /**
     * @return bool
     */
    public function canUseLabel(StringLiteral $userId, StringLiteral $name);

    /**
     * @return Entity[]|null
     */
    public function search(Query $query);

    public function searchTotalLabels(Query $query): int;
}
