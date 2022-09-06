<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use Psr\Http\Message\ResponseInterface;

trait AssertJsonResponseTrait
{
    private function assertJsonResponse(ResponseInterface $expectedResponse, ResponseInterface $actualResponse): void
    {
        $this->assertEquals($expectedResponse->getStatusCode(), $actualResponse->getStatusCode());
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
        $this->assertEquals($expectedResponse->getBody()->getContents(), $actualResponse->getBody()->getContents());
    }
}
