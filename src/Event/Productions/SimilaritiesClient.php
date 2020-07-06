<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use GuzzleHttp\Client;

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

    public function excludeTemporarily(array $eventPairs): void
    {
        $data['pairs'] = [];
        /** @var SimilarEventPair $pair */
        foreach ($eventPairs as $pair) {
            $data['pairs'][] = [
                'event1' => $pair->getEventOne(),
                'event2' => $pair->getEventTwo(),
            ];
        }

        $response = $this->client->request(
            'PATCH',
            $this->uri . '/greylist?key=' . $this->key,
            ['json' => $data]
        );
    }

    public function excludePermanently(SimilarEventPair $pair)
    {
        $data['pairs'] = [
            'event1' => $pair->getEventOne(),
            'event2' => $pair->getEventTwo(),
        ];

        $response = $this->client->request(
            'PATCH',
            $this->uri . '/blacklist?key=' . $this->key,
            ['json' => $data]
        );
    }
}
