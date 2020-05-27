<?php

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use ValueObjects\StringLiteral\StringLiteral;

class SecurityDecoratorBase implements SecurityInterface
{
    /**
     * @var SecurityInterface
     */
    private $decoratee;

    /**
     * SecurityDecoratorBase constructor.
     * @param SecurityInterface $decoratee
     */
    public function __construct(SecurityInterface $decoratee)
    {
        $this->decoratee = $decoratee;
    }

    /**
     * @inheritdoc
     */
    public function allowsUpdateWithCdbXml(StringLiteral $offerId)
    {
        return $this->decoratee->allowsUpdateWithCdbXml($offerId);
    }

    /**
     * @inheritdoc
     */
    public function isAuthorized(AuthorizableCommandInterface $command)
    {
        return $this->decoratee->isAuthorized($command);
    }
}
