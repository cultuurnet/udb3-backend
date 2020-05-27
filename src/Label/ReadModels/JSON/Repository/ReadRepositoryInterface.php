<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use ValueObjects\Identity\UUID;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

interface ReadRepositoryInterface
{
    /**
     * @param UUID $uuid
     * @return Entity|null
     */
    public function getByUuid(UUID $uuid);

    /**
     * @param StringLiteral $name
     * @return Entity|null
     */
    public function getByName(StringLiteral $name);

    /**
     * @param StringLiteral $userId
     * @param StringLiteral $name
     * @return bool
     */
    public function canUseLabel(StringLiteral $userId, StringLiteral $name);
    
    /**
     * @param Query $query
     * @return Entity[]|null
     */
    public function search(Query $query);

    /**
     * @param Query $query
     * @return Natural
     */
    public function searchTotalLabels(Query $query);
}
