<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Offer\Security\Permission\PermissionVoterInterface;
use ValueObjects\StringLiteral\StringLiteral;

class SecurityWithUserPermission extends SecurityDecoratorBase
{
    /**
     * @var string
     */
    private $userId;

    /**
     * @var PermissionVoterInterface
     */
    private $permissionVoter;

    /**
     * @var CommandFilterInterface
     */
    private $commandFilter;


    public function __construct(
        SecurityInterface $decoratee,
        string $userId,
        PermissionVoterInterface $permissionVoter,
        CommandFilterInterface $commandFilter
    ) {
        parent::__construct($decoratee);

        $this->userId = $userId;
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
                new StringLiteral($this->userId)
            );
        }

        return parent::isAuthorized($command);
    }
}
