<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DomainMessage;

interface PropertiesFactoryInterface
{
    /**
     * @return array
     */
    public function createProperties(DomainMessage $domainMessage);
}
