<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Client;

use CultuurNet\UDB3\Kinepolis\Exception\ImageNotFound;
use Http\Adapter\Guzzle7\Client;
use PHPUnit\Framework\TestCase;

final class AuthenticatedKinepolisClientTest extends TestCase
{
    private KinepolisClient $kinepolisClient;

    public function setUp(): void
    {
        $this->kinepolisClient = new AuthenticatedKinepolisClient(
            'https://testurl.be/nl/',
            new Client(),
            'key',
            'secret',
        );
    }

    /**
     * @test
     */
    public function it_will_throw_on_empty_image_urls(): void
    {
        $this->expectException(ImageNotFound::class);
        $this->expectExceptionMessage('Cannot process path: ""');
        $this->kinepolisClient->getImage('token', '');
    }
}
