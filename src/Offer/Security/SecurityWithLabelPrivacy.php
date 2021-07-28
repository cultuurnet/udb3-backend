<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Security\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\AuthorizableLabelCommand;
use CultuurNet\UDB3\Security\Security;
use ValueObjects\StringLiteral\StringLiteral;

class SecurityWithLabelPrivacy implements Security
{
    /**
     * @var Security
     */
    private $decoratee;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var ReadRepositoryInterface
     */
    private $labelReadRepository;

    public function __construct(
        Security $decoratee,
        string $userId,
        ReadRepositoryInterface $labelReadRepository
    ) {
        $this->decoratee = $decoratee;
        $this->userId = $userId;
        $this->labelReadRepository = $labelReadRepository;
    }



    public function isAuthorized(AuthorizableCommandInterface $command)
    {
        if ($this->isLabelCommand($command)) {
            /** @var AuthorizableLabelCommand $command */
            return $this->canUseLabel($command);
        } else {
            return $this->decoratee->isAuthorized($command);
        }
    }

    /**
     * @return bool
     */
    private function isLabelCommand(AuthorizableCommandInterface $command)
    {
        return ($command instanceof AuthorizableLabelCommand);
    }

    /**
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function canUseLabel(AuthorizableLabelCommand $command)
    {
        foreach ($command->getLabelNames() as $labelName) {
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
