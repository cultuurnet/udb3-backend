<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

final class KinepolisService
{
    private KinepolisClient $client;

    private KinepolisParser $parser;

    public function __construct(
        KinepolisClient $client,
        KinepolisParser $parser
    ) {
        $this->client = $client;
        $this->parser = $parser;
    }

    public function getClient(): KinepolisClient
    {
        return $this->client;
    }

    public function getParser(): KinepolisParser
    {
        return $this->parser;
    }
}
