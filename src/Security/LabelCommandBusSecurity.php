<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;

/**
 * Checks commands that add/remove labels from entities to see if the user is allowed to use those specific labels.
 */
class LabelCommandBusSecurity implements CommandBusSecurity
{
    private CommandBusSecurity $decoratee;

    private string $userId;

    private ReadRepositoryInterface $labelReadRepository;

    public function __construct(
        CommandBusSecurity $decoratee,
        string $userId,
        ReadRepositoryInterface $labelReadRepository
    ) {
        $this->decoratee = $decoratee;
        $this->userId = $userId;
        $this->labelReadRepository = $labelReadRepository;
    }

    public function isAuthorized(AuthorizableCommand $command): bool
    {
        if (!($command instanceof AuthorizableLabelCommand)) {
            return $this->decoratee->isAuthorized($command);
        }

        foreach ($command->getLabelNames() as $labelName) {
            if (!$this->labelReadRepository->canUseLabel(
                $this->userId,
                $labelName->toNative()
            )) {
                throw ApiProblem::labelNotAllowed($labelName->toNative());
            }
        }
        return true;
    }
}
