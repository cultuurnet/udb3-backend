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

    public function markAsLinked(array $tuples): void
    {
        $data['pairs'] = [];
        /** @var Tuple $tuple */
        foreach ($tuples as $tuple) {
            $data['pairs'][] = $tuple->asArray();
        }

        $response = $this->client->request(
            'PATCH',
            $this->uri . '/greylist?key=' . $this->key,
            ['json' => $data]
        );
    }

    public function skipped(Tuple $tuple)
    {
        $data['pairs'] = [
            $tuple->asArray()
        ];

        $response = $this->client->request(
            'PATCH',
            $this->uri . '/blacklist?key=' . $this->key,
            ['json' => $data]
        );
    }
}
