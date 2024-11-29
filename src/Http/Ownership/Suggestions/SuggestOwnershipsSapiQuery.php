<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership\Suggestions;

use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\User\UserIdentityDetails;

final class SuggestOwnershipsSapiQuery
{
    use IsString;

    public function __construct(UserIdentityDetails $user)
    {
        $workflowStatusQuery = $this->createWorkflowStatusQuery();
        $creatorQuery = $this->createCreatorQuery($user);

        $this->value = "_exists_:organizer.id AND address.\*.addressCountry:* AND {$workflowStatusQuery} AND {$creatorQuery}";
    }

    private function createWorkflowStatusQuery(): string
    {
        $statuses = implode(
            ' OR ',
            array_map(
                fn (WorkflowStatus $status) => $status->toString(),
                [WorkflowStatus::DRAFT(), WorkflowStatus::READY_FOR_VALIDATION(), WorkflowStatus::APPROVED()]
            )
        );

        return "workflowStatus:({$statuses})";
    }

    private function createCreatorQuery(UserIdentityDetails $user): string
    {
        $id = $user->getUserId();
        $ids = ["auth0|{$id}", $id];
        $email = $user->getEmailAddress();

        return 'creator:(' . implode(' OR ', [...$ids, $email]) . ')';
    }
}
