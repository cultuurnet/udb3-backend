<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Cake\Chronos\Date;
use CultuurNet\UDB3\Event\Productions\SimilaritiesClient;
use CultuurNet\UDB3\Event\Productions\SuggestionsNotFound;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Http\HttpFoundation\NoContent;
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
        $date = $this->minDate ?: Date::now();

        try {
            $suggestion = $this->similaritiesClient->nextSuggestion($date);
        } catch (SuggestionsNotFound $e) {
            return new NoContent();
        }

        return new JsonResponse(
            [
                'events' => [
                    $this->getEventBody($suggestion->getEventOne()),
                    $this->getEventBody($suggestion->getEventTwo()),
                ],
                'similarity' => $suggestion->getSimilarity(),
            ]
        );
    }

    private function getEventBody(string $eventId): array
    {
        try {
            $event = $this->enrichedEventRepository->get($eventId);
        } catch (DocumentGoneException $e) {
            throw new SuggestedEventRemovedException($eventId);
        }

        if ($event === null) {
            throw new SuggestedEventNotFoundException($eventId);
        }

        return json_decode($event->getRawBody(), true);
    }
}
