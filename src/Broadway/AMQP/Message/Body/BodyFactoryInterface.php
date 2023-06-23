<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Body;

use Broadway\Domain\DomainMessage;

interface BodyFactoryInterface
{
    public function createBody(DomainMessage $domainMessage): string;
}
