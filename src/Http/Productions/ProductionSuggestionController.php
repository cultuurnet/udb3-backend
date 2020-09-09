<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Cake\Chronos\Date;
use CultuurNet\UDB3\Event\Productions\SimilarEventsRepository;
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
     * @var SimilarEventsRepository
     */
    private $similarEventsRepository;

    /**
     * @var DocumentRepositoryInterface
     */
    private $enrichedEventRepository;

    public function __construct(
        SimilarEventsRepository $similarEventsRepository,
        DocumentRepositoryInterface $enrichedEventRepository
    ) {
        $this->similarEventsRepository = $similarEventsRepository;
        $this->enrichedEventRepository = $enrichedEventRepository;
    }

    public function nextSuggestion(): Response
    {
        try {
            $suggestion = $this->similarEventsRepository->findNextSuggestion();
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
