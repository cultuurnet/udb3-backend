<?php

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\LabelSecurityInterface;
use CultuurNet\UDB3\Security\SecurityDecoratorBase;
use CultuurNet\UDB3\Security\SecurityInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;

class SecurityWithLabelPrivacy extends SecurityDecoratorBase
{
    /**
     * @var UserIdentificationInterface
     */
    private $userIdentification;

    /**
     * @var ReadRepositoryInterface
     */
    private $labelReadRepository;

    /**
     * SecurityWithLabelPrivacy constructor.
     *
     * @param SecurityInterface $decoratee
     * @param UserIdentificationInterface $userIdentification
     * @param ReadRepositoryInterface $labelReadRepository
     */
    public function __construct(
        SecurityInterface $decoratee,
        UserIdentificationInterface $userIdentification,
        ReadRepositoryInterface $labelReadRepository
    ) {
        parent::__construct($decoratee);

        $this->userIdentification = $userIdentification;
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
     * @param AuthorizableCommandInterface $command
     * @return bool
     */
    private function isLabelCommand(AuthorizableCommandInterface $command)
    {
        return ($command instanceof LabelSecurityInterface);
    }

    /**
     * @param LabelSecurityInterface $command
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function canUseLabel(LabelSecurityInterface $command)
    {
        foreach ($command->getNames() as $labelName) {
            if (!$this->labelReadRepository->canUseLabel(
                $this->userIdentification->getId(),
                $labelName
            )) {
                return false;
            }
        }
        return true;
    }
}
