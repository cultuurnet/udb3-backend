<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Validator;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class PreventDeleteUitpasPlace implements OfferCommandValidator
{
    private DocumentRepository $placeRepository;
    private array $UiTPASLabels;

    public function __construct(DocumentRepository $placeRepository, array $UiTPASLabels)
    {
        $this->placeRepository = $placeRepository;
        $this->UiTPASLabels = $UiTPASLabels;
    }

    public function isValid(string $offerId): bool
    {
        try {
            $place = $this->placeRepository->fetch($offerId);

            return !$this->isUitPasPlace($place->getAssocBody());
        } catch (DocumentDoesNotExist $e) {
            // Just continue, the offer is an event
            return true;
        }
    }

    public function getApiProblem(): ApiProblem
    {
        return ApiProblem::cannotDeleteUitpasPlace();
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
