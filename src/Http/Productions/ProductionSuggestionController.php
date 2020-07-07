<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Event\Productions\SimilaritiesClient;
use CultuurNet\UDB3\Event\Productions\SuggestionsNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProductionSuggestionController
{

    /**
     * @var SimilaritiesClient
     */
    private $similaritiesClient;

    public function __construct(SimilaritiesClient $similaritiesClient)
    {
        $this->similaritiesClient = $similaritiesClient;
    }

    public function nextSuggestion(): Response
    {
        try {
            $date = new \DateTime();
            $date->setTime(0, 0, 1);
            $suggestion = $this->similaritiesClient->nextSuggestion($date);
            return new JsonResponse(['eventIds' => [$suggestion->getEventTwo(), $suggestion->getEventOne()]]);
        } catch (SuggestionsNotFound $exception) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }
    }
}
