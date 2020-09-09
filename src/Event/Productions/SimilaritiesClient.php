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
}
