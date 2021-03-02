<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

interface DocumentEventFactory
{
    public function createEvent(string $id);
}
