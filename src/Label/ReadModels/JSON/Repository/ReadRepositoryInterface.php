<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use ValueObjects\Identity\UUID;
use ValueObjects\Number\Natural;
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

    /**
     * @return Natural
     */
    public function searchTotalLabels(Query $query);
}
