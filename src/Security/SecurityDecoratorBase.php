<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use ValueObjects\StringLiteral\StringLiteral;

class SecurityDecoratorBase implements SecurityInterface
{
    /**
     * @var SecurityInterface
     */
    private $decoratee;

    /**
     * SecurityDecoratorBase constructor.
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
