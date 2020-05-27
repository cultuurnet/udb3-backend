<?php

namespace CultuurNet\UDB3\Event\ReadModel;

use CultuurNet\UDB3\ReadModel\JsonDocument;

interface DocumentRepositoryInterface
{
    /**
     * @param string $id
     * @return JsonDocument|null
     *  The document with matching if or null when no document was found.
     *
     * @throws DocumentGoneException
     * @TODO Move class to Offer namespace as it is also used in Place.
     */
    public function get($id);

    public function save(JsonDocument $readModel);

    public function remove($id);
}
