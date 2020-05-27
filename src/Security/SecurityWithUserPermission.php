<?php

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Offer\Security\Permission\PermissionVoterInterface;
use ValueObjects\StringLiteral\StringLiteral;

class SecurityWithUserPermission extends SecurityDecoratorBase
{
    /**
     * @var UserIdentificationInterface
     */
    private $userIdentification;

    /**
     * @var PermissionVoterInterface
     */
    private $permissionVoter;

    /**
     * @var CommandFilterInterface
     */
    private $commandFilter;

    /**
     * @param SecurityInterface $decoratee
     * @param UserIdentificationInterface $userIdentification
     * @param PermissionVoterInterface $permissionVoter
     * @param CommandFilterInterface $commandFilter
     */
    public function __construct(
        SecurityInterface $decoratee,
        UserIdentificationInterface $userIdentification,
        PermissionVoterInterface $permissionVoter,
        CommandFilterInterface $commandFilter
    ) {
        parent::__construct($decoratee);

        $this->userIdentification = $userIdentification;
        $this->permissionVoter = $permissionVoter;
        $this->commandFilter = $commandFilter;
    }

    /**
     * @inheritdoc
     */
    public function isAuthorized(AuthorizableCommandInterface $command)
    {
        if ($this->commandFilter->matches($command)) {
            return $this->permissionVoter->isAllowed(
                $command->getPermission(),
                new StringLiteral(''),
                $this->userIdentification->getId()
            );
        }

        return parent::isAuthorized($command);
    }
}
