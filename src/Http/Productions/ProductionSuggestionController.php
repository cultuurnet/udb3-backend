<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Event\Productions\ProductionEnrichedEventRepository;
use CultuurNet\UDB3\Event\Productions\SimilaritiesClient;
use CultuurNet\UDB3\Event\Productions\SuggestionsNotFound;
use CultuurNet\UDB3\HttpFoundation\Response\JsonLdResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProductionSuggestionController
{

    /**
     * @var SimilaritiesClient
     */
    private $similaritiesClient;
    /**
     * @var ProductionEnrichedEventRepository
     */
    private $enrichedEventRepository;

    public function __construct(
        SimilaritiesClient $similaritiesClient,
        ProductionEnrichedEventRepository $enrichedEventRepository
    ) {
        $this->similaritiesClient = $similaritiesClient;
        $this->enrichedEventRepository = $enrichedEventRepository;
    }

    public function nextSuggestion(): Response
    {
        try {
            $date = new \DateTime();
            $date->setTime(0, 0, 1);
            $suggestion = $this->similaritiesClient->nextSuggestion($date);
            $eventTwo = $this->enrichedEventRepository->get($suggestion->getEventOne());
            $eventOne = $this->enrichedEventRepository->get($suggestion->getEventTwo());
            return new JsonResponse(
                [
                    'events' => [
                        $eventTwo->getBody(),
                        $eventOne->getBody(),
                    ],
                ]
            );
        } catch (SuggestionsNotFound $exception) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }
    }
}
