<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Security\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\AuthorizableLabelCommand;
use CultuurNet\UDB3\Security\CommandBusSecurity;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Checks commands that add/remove labels from entities to see if the user is allowed to use those specific labels.
 */
class LabelCommandBusSecurity implements CommandBusSecurity
{
    /**
     * @var CommandBusSecurity
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
        CommandBusSecurity $decoratee,
        string $userId,
        ReadRepositoryInterface $labelReadRepository
    ) {
        $this->decoratee = $decoratee;
        $this->userId = $userId;
        $this->labelReadRepository = $labelReadRepository;
    }

    public function isAuthorized(AuthorizableCommandInterface $command): bool
    {
        if (!($command instanceof AuthorizableLabelCommand)) {
            return $this->decoratee->isAuthorized($command);
        }

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
