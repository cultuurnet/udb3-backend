<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Cake\Chronos\Date;
use CultuurNet\UDB3\Event\Productions\SimilaritiesClient;
use CultuurNet\UDB3\Event\Productions\SuggestionsNotFound;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProductionSuggestionController
{
    /**
     * @var SimilaritiesClient
     */
    private $similaritiesClient;

    /**
     * @var DocumentRepositoryInterface
     */
    private $enrichedEventRepository;

    /**
     * @var Date|null
     */
    private $minDate;

    public function __construct(
        SimilaritiesClient $similaritiesClient,
        DocumentRepositoryInterface $enrichedEventRepository,
        ?Date $minDate = null
    ) {
        $this->similaritiesClient = $similaritiesClient;
        $this->enrichedEventRepository = $enrichedEventRepository;
        $this->minDate = $minDate;
    }

    public function nextSuggestion(): Response
    {
        try {
            $date = $this->minDate ?: Date::now();
            $suggestion = $this->similaritiesClient->nextSuggestion($date);
            $eventOne = $this->enrichedEventRepository->get($suggestion->getEventOne());
            $eventTwo = $this->enrichedEventRepository->get($suggestion->getEventTwo());
            return new JsonResponse(
                [
                    'events' => [
                        $eventOne->getBody(),
                        $eventTwo->getBody(),
                    ],
                    'similarity' => $suggestion->getSimilarity(),
                ]
            );
        } catch (SuggestionsNotFound $exception) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }
    }
}
