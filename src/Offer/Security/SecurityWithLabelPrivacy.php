<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\LabelSecurityInterface;
use CultuurNet\UDB3\Security\SecurityDecoratorBase;
use CultuurNet\UDB3\Security\SecurityInterface;
use ValueObjects\StringLiteral\StringLiteral;

class SecurityWithLabelPrivacy extends SecurityDecoratorBase
{
    /**
     * @var string
     */
    private $userId;

    /**
     * @var ReadRepositoryInterface
     */
    private $labelReadRepository;

    public function __construct(
        SecurityInterface $decoratee,
        string $userId,
        ReadRepositoryInterface $labelReadRepository
    ) {
        parent::__construct($decoratee);

        $this->userId = $userId;
        $this->labelReadRepository = $labelReadRepository;
    }


    /**
     * @inheritdoc
     */
    public function isAuthorized(AuthorizableCommandInterface $command)
    {
        if ($this->isLabelCommand($command)) {
            /** @var LabelSecurityInterface $command */
            return $this->canUseLabel($command);
        } else {
            return parent::isAuthorized($command);
        }
    }

    /**
     * @return bool
     */
    private function isLabelCommand(AuthorizableCommandInterface $command)
    {
        return ($command instanceof LabelSecurityInterface);
    }

    /**
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function canUseLabel(LabelSecurityInterface $command)
    {
        foreach ($command->getNames() as $labelName) {
            if (!$this->labelReadRepository->canUseLabel(
                new StringLiteral($this->userId),
                $labelName
            )) {
                return false;
            }
        }
        return true;
    }
}
