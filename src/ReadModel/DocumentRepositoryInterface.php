<?php

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;

interface DocumentRepositoryInterface
{
    /**
     * @param string $id
     * @return JsonDocument|null
     *  The document with matching if or null when no document was found.
     *
     * @throws DocumentGoneException
     */
    public function get($id);

    public function save(JsonDocument $readModel);

    public function remove($id);
}
