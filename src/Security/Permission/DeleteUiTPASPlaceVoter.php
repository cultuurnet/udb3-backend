<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class DeleteUiTPASPlaceVoter implements PermissionVoter
{
    private DocumentRepository $placeRepository;
    private array $UiTPASLabels;

    public function __construct(DocumentRepository $placeRepository, array $UiTPASLabels)
    {
        $this->placeRepository = $placeRepository;
        $this->UiTPASLabels = $UiTPASLabels;
    }

    public function isAllowed(
        Permission $permission,
        string $itemId,
        string $userId
    ): bool {
        if (!Permission::aanbodVerwijderen()->sameAs($permission)) {
            return true;
        }

        try {
            $place = $this->placeRepository->fetch($itemId);

            return !$this->isUitPasPlace($place->getAssocBody());
        } catch (DocumentDoesNotExist $e) {
            // Just continue, the offer is an event
            return true;
        }
    }

    private function isUitPasPlace(array $body): bool
    {
        if (!isset($body['hiddenLabels'])) {
            return false;
        }

        foreach ($body['hiddenLabels'] as $label) {
            if (!in_array($label, $this->UiTPASLabels, true)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
