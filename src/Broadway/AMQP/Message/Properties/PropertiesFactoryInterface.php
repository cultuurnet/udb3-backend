<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DomainMessage;

interface PropertiesFactoryInterface
{
    public function createProperties(DomainMessage $domainMessage): array;
}
