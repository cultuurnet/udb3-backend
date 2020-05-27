<?php

namespace CultuurNet\UDB3\ReadModel;

interface DocumentEventFactory
{
    /**
     * @param string $id
     * @return object
     */
    public function createEvent($id);
}
