<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

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
    public function isAuthorized(AuthorizableCommandInterface $command)
    {
        return $this->decoratee->isAuthorized($command);
    }
}
