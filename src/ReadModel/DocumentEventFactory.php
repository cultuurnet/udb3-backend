<?php

namespace CultuurNet\UDB3\ReadModel;

interface DocumentEventFactory
{
    public function createEvent(string $id);
}
