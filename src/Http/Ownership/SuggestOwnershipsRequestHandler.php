<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SuggestOwnershipsRequestHandler implements RequestHandlerInterface
{
    private ResultsGeneratorInterface $resultsGenerator;
    private DocumentRepository $offerRepository;
    private CurrentUser $currentUser;
    private UserIdentityResolver $userIdentityResolver;

    public function __construct(ResultsGeneratorInterface $resultsGenerator, DocumentRepository $offerRepository, CurrentUser $currentUser, UserIdentityResolver $userIdentityResolver)
    {
        $this->resultsGenerator = $resultsGenerator;
        $this->offerRepository = $offerRepository;
        $this->currentUser = $currentUser;
        $this->userIdentityResolver = $userIdentityResolver;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $itemType = $request->getQueryParams()['itemType'] ?? '';

        if (empty($itemType)) {
            throw ApiProblem::queryParameterMissing('itemType');
        }

        $statuses = implode(
            ',',
            array_map(fn (WorkflowStatus $status) => $status->toString(), [WorkflowStatus::DRAFT(), WorkflowStatus::READY_FOR_VALIDATION(), WorkflowStatus::APPROVED()])
        );

        $id = $this->currentUser->getId();
        $user = $this->userIdentityResolver->getUserById($id);
        $ids = ["auth0|{$id}", $id];
        $email = $user->getEmailAddress();
        $creatorQuery = 'creator:(' . implode(' OR ', [...$ids, $email]) . ')';

        $queryParts = [
            '_exists_organizer.id',
            'addressCountry=*',
            "workflowStatus={$statuses}",
            $creatorQuery,
        ];

        $query = implode('&', $queryParts);

        $results = [];

        /**
         * @var ItemIdentifier $result
         */
        foreach ($this->resultsGenerator->search($query) as $result) {
            $results[] = $this->offerRepository->fetch($result->getId())->getAssocBody();
        }

        return new JsonLdResponse($results);
    }
}
