<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use Cake\Chronos\Date;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class SimilaritiesClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $key;

    public function __construct(Client $client, string $uri, string $key)
    {
        $this->client = $client;
        $this->uri = $uri;
        $this->key = $key;
    }

    /**
     * @param SimilarEventPair[] $eventPairs
     * @throws GuzzleException
     */
    public function excludeTemporarily(array $eventPairs): void
    {
        $data['pairs'] = [];
        foreach ($eventPairs as $pair) {
            $data['pairs'][] = [
                'event1' => $pair->getEventOne(),
                'event2' => $pair->getEventTwo(),
            ];
        }

        $this->client->request(
            'PATCH',
            $this->uri . '/temporarilyexcluded?key=' . $this->key,
            ['json' => $data]
        );
    }

    /**
     * @param SimilarEventPair[] $eventPairs
     */
    public function excludePermanently(array $eventPairs): void
    {
        $data['pairs'] = [];
        foreach ($eventPairs as $pair) {
            $data['pairs'][] = [
                'event1' => $pair->getEventOne(),
                'event2' => $pair->getEventTwo(),
            ];
        }

        $this->client->request(
            'PATCH',
            $this->uri . '/permanentlyexcluded?key=' . $this->key,
            ['json' => $data]
        );
    }

    public function nextSuggestion(Date $dateFrom, int $size = 1, int $offset = 0): Suggestion
    {
        try {
            $response = $this->client->request(
                'GET',
                $this->uri . '?size=' . $size . '&minDate=' .
                $dateFrom->format('Y-m-d') . '&offset' . $offset . '&key=' . $this->key
            );
        } catch (ClientException $throwable) {
            throw new SuggestionsNotFound();
        }

        $contents = json_decode($response->getBody()->getContents(), true);

        return new Suggestion(
            $contents[0]['event1'],
            $contents[0]['event2'],
            (float) $contents[0]['similarity']
        );
    }
}
