<?php

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Body;

use Broadway\Domain\DomainMessage;

interface BodyFactoryInterface
{
    /**
     * @return string
     */
    public function createBody(DomainMessage $domainMessage);
}
