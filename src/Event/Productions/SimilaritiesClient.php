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

}
