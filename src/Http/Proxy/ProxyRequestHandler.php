<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ProxyRequestHandler implements RequestHandlerInterface
{
    private string $newDomain;
    private ClientInterface $httpClient;

    public function __construct(string $newDomain, ClientInterface $httpClient)
    {
        $this->newDomain = $newDomain;
        $this->httpClient = $httpClient;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $rewrittenUri = $request->getUri()
            ->withHost($this->newDomain);

        $rewrittenRequest = $request->withUri($rewrittenUri);

        // Disable conversion of HTTP error responses to exceptions so we can return the 4xx and 5xx responses as-is.
        return $this->httpClient->send($rewrittenRequest, ['http_errors' => false]);
    }
}
