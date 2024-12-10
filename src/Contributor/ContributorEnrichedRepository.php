<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;

final class ContributorEnrichedRepository extends DocumentRepositoryDecorator
{
    private ContributorRepository $contributorRepository;

    private PermissionVoter $permissionVoter;

    private ?string $currentUserId;

    public function __construct(
        ContributorRepository $contributorRepository,
        DocumentRepository $documentRepository,
        PermissionVoter $permissionVoter,
        ?string $currentUserId
    ) {
        parent::__construct($documentRepository);
        $this->contributorRepository = $contributorRepository;
        $this->permissionVoter = $permissionVoter;
        $this->currentUserId = $currentUserId;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $jsonDocument = parent::fetch($id, $includeMetadata);

        if ($this->hasPermission($jsonDocument->getId())) {
            $jsonDocument = $this->enrich($jsonDocument);
        }

        return $jsonDocument;
    }

    public function save(JsonDocument $document): void
    {
        parent::save(
            $document->applyAssoc(
                function (array $json) {
                    unset($json['contributors']);
                    return $json;
                }
            )
        );
    }

    private function enrich(JsonDocument $jsonDocument): JsonDocument
    {
        $contributors = $this->contributorRepository->getContributors(new Uuid($jsonDocument->getId()))->toStringArray();

        return $jsonDocument->applyAssoc(
            function (array $body) use ($contributors) {
                if (count($contributors) > 0) {
                    $body['contributors'] = $contributors;
                }
                return $body;
            }
        );
    }

    private function hasPermission(string $id): bool
    {
        return $this->currentUserId !== null &&
            $this->permissionVoter->isAllowed(
                Permission::aanbodBewerken(),
                $id,
                $this->currentUserId
            );
    }
}
